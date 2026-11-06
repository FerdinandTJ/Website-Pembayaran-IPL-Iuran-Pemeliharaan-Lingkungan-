<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\ArrearsFollowUp;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $report = $reports->arrears($request->user()->housing_id, $filters);
        $rows = $report['query']->paginate(15)->withQueryString();
        $latestIds = ArrearsFollowUp::where('housing_id', $request->user()->housing_id)->whereIn('resident_id', $rows->pluck('id'))
            ->selectRaw('MAX(id)')->groupBy('resident_id');
        $latest = ArrearsFollowUp::with('assignee')->whereIn('id', $latestIds)->get()->keyBy('resident_id');

        return view('pengurus.follow-ups.index', ['rows' => $rows, 'latest' => $latest, 'summary' => $report['summary'], 'recent' => ArrearsFollowUp::with('resident', 'assignee')->where('housing_id', $request->user()->housing_id)->latest('id')->paginate(10, ['*'], 'history_page')->withQueryString()]);
    }

    public function show(Request $request, User $resident, ReportService $reports)
    {
        $this->scope($request, $resident);

        return view('pengurus.follow-ups.show', ['resident' => $resident,
            'invoices' => $reports->overdue($request->user()->housing_id)->where('user_id', $resident->id)->orderBy('due_date')->get(),
            'entries' => ArrearsFollowUp::with('author', 'assignee')->where('housing_id', $request->user()->housing_id)->where('resident_id', $resident->id)->latest('id')->paginate(15),
            'managers' => User::where('housing_id', $request->user()->housing_id)->where('role', 'pengurus')->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request, User $resident)
    {
        $this->scope($request, $resident);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ArrearsFollowUp::STATUSES))],
            'note' => ['required', 'string', 'min:5', 'max:2000'],
            'next_action_date' => ['required_if:status,promised,scheduled', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where('housing_id', $request->user()->housing_id)->where('role', 'pengurus')->where('is_active', true)],
        ]);
        DB::transaction(fn () => ArrearsFollowUp::create($data + ['housing_id' => $request->user()->housing_id, 'resident_id' => $resident->id, 'created_by' => $request->user()->id]));

        return back()->with('success', 'Tindak lanjut dicatat. Status pembayaran tagihan tidak diubah.');
    }

    private function scope(Request $request, User $resident): void
    {
        abort_unless($resident->housing_id === $request->user()->housing_id && $resident->isResident(), 404);
    }
}

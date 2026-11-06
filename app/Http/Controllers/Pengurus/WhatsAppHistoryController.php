<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Models\WhatsAppDelivery;
use App\Services\FonnteClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WhatsAppHistoryController extends Controller
{
    public function index(Request $request, FonnteClient $client)
    {
        $filters = $this->filters($request);
        $housingId = $request->user()->housing_id;
        $query = WhatsAppDelivery::where('housing_id', $housingId)
            ->when($filters['date_from'], fn ($q, $value) => $q->where('created_at', '>=', Carbon::parse($value, 'Asia/Jakarta')->startOfDay()))
            ->when($filters['date_to'], fn ($q, $value) => $q->where('created_at', '<', Carbon::parse($value, 'Asia/Jakarta')->addDay()->startOfDay()))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['event'] ?? null, fn ($q, $value) => $q->where('event', $value));
        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q->where('recipient_name', 'like', $search)->orWhere('recipient_house', 'like', $search)
                ->orWhereHas('user', fn ($q) => $q->where('housing_id', $housingId)->where('name', 'like', $search))
                ->orWhereHas('invoice', fn ($q) => $q->where('housing_id', $housingId)->where('house_label', 'like', $search)));
        }
        $counts = (clone $query)->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = collect(WhatsAppDelivery::STATUSES)->map(fn ($label, $status) => (int) ($counts[$status] ?? 0));
        $deliveries = $query->with($this->relations($housingId))->latest('created_at')->latest('id')->paginate(20)->appends($filters);

        return view('pengurus.whatsapp-history.index', ['deliveries' => $deliveries, 'filters' => $filters,
            'summary' => $summary, 'configured' => $client->configured()]);
    }

    public function show(Request $request, WhatsAppDelivery $whatsAppDelivery)
    {
        abort_unless($whatsAppDelivery->housing_id === $request->user()->housing_id, 404);
        $filters = $this->filters($request);
        $delivery = $whatsAppDelivery->load($this->relations($request->user()->housing_id));
        $invoice = $delivery->invoice;
        $matchingInvoice = $invoice && $invoice->user_id === $delivery->user_id;
        $matchingReceipt = $matchingInvoice && $delivery->receipt
            && ($delivery->receipt->invoice_id === $invoice->id
                || ($invoice->payment_batch_id && $delivery->receipt->payment_batch_id === $invoice->payment_batch_id));

        return view('pengurus.whatsapp-history.show', ['delivery' => $delivery, 'filters' => $filters,
            'page' => $request->integer('page', 1), 'matchingInvoice' => $matchingInvoice, 'matchingReceipt' => $matchingReceipt]);
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(array_keys(WhatsAppDelivery::STATUSES))],
            'event' => ['nullable', Rule::in(array_keys(WhatsAppDelivery::EVENTS))],
            'search' => ['nullable', 'string', 'max:255'], 'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $filters['date_from'] = $request->has('date_from') ? ($filters['date_from'] ?? '') : today('Asia/Jakarta')->startOfMonth()->toDateString();
        $filters['date_to'] = $request->has('date_to') ? ($filters['date_to'] ?? '') : today('Asia/Jakarta')->toDateString();
        if ($filters['date_from'] && $filters['date_to'] && $filters['date_to'] < $filters['date_from']) {
            throw ValidationException::withMessages(['date_to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);
        }
        unset($filters['page']);

        return $filters;
    }

    private function relations(int $housingId): array
    {
        return [
            'user' => fn ($q) => $q->where('housing_id', $housingId),
            'invoice' => fn ($q) => $q->where('housing_id', $housingId),
            'receipt' => fn ($q) => $q->where(fn ($q) => $q->whereIn('invoice_id', Invoice::forHousing($housingId)->select('id'))
                ->orWhereIn('payment_batch_id', PaymentBatch::where('housing_id', $housingId)->select('id'))),
        ];
    }
}

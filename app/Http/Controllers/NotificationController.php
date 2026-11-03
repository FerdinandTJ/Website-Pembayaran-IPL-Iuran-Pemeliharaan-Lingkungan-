<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Announcement;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    private function inbox(Request $request)
    {
        return $request->user()->notifications()->where('housing_id', $request->user()->housing_id);
    }

    public function index(Request $request)
    {
        $request->validate(['filter' => ['nullable', Rule::in(['all', 'unread'])]]);
        $query = $this->inbox($request)
            ->when($request->input('filter') === 'unread', fn ($q) => $q->whereNull('read_at'));
        if ($request->expectsJson()) {
            return response()->json([
                'items' => $query->limit(10)->get()->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->data['title'],
                    'message' => $item->data['message'],
                    'read' => $item->read_at !== null,
                    'time' => $item->created_at->diffForHumans(),
                    'read_url' => route('notifications.read', $item->id),
                ]),
                'unread_count' => $this->inbox($request)->whereNull('read_at')->count(),
            ]);
        }

        return view('notifications.index', [
            'notifications' => $query->paginate(15)->withQueryString(),
            'unreadCount' => $this->inbox($request)->whereNull('read_at')->count(),
        ]);
    }

    public function count(Request $request)
    {
        return response()->json(['unread_count' => $this->inbox($request)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, string $notification)
    {
        $item = $this->inbox($request)->findOrFail($notification);
        $url = $this->destination($request, $item);
        $item->markAsRead();

        return $request->expectsJson() ? response()->json(['url' => $url]) : redirect($url);
    }

    public function readAll(Request $request)
    {
        $this->inbox($request)->whereNull('read_at')->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->json(['unread_count' => 0]) : back();
    }

    private function destination(Request $request, DatabaseNotification $item): string
    {
        $event = $item->data['event'] ?? '';
        if ($event === 'push_test') {
            return route('notifications.index');
        }
        if ($event === 'announcement') {
            $announcement = Announcement::where('housing_id', $request->user()->housing_id)->whereIn('status', ['published', 'archived'])->findOrFail($item->data['announcement_id'] ?? null);

            return route('announcements.show', $announcement);
        }
        abort_unless(in_array($event, ['issued', 'submitted', 'verified', 'rejected', 'due_soon', 'overdue', 'cash_paid'], true), 404);
        $invoice = Invoice::forHousing($request->user()->housing_id)->findOrFail($item->data['invoice_id'] ?? null);
        $this->authorize('view', $invoice);

        if ($invoice->status === InvoiceStatus::Verified) {
            return route('receipts.show', $invoice);
        }

        return $request->user()->isManager()
            ? route('pengurus.verifications.index', ['invoice_id' => $invoice->id])
            : route('warga.invoices.show', $invoice);
    }
}

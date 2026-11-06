<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public const ENTITIES = ['Invoice' => 'Tagihan', 'Cashflow' => 'Transaksi Kas', 'PaymentBatch' => 'Pembayaran Gabungan',
        'InvoiceTemplate' => 'Template Tagihan', 'InvoiceTemplateRun' => 'Penerbitan Template', 'ArrearsFollowUp' => 'Tindak Lanjut', 'AccountingPeriod' => 'Tutup Buku'];

    public function index(Request $request)
    {
        $filters = $request->validate(['entity' => ['nullable', Rule::in(array_keys(self::ENTITIES))], 'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])]]);
        $query = ActivityLog::where('housing_id', $request->user()->housing_id)
            ->when($filters['entity'] ?? null, fn ($q, $value) => $q->where('entity_type', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value));

        return view('pengurus.activity-logs.index', ['logs' => $query->latest('id')->paginate(20)->withQueryString()]);
    }
}

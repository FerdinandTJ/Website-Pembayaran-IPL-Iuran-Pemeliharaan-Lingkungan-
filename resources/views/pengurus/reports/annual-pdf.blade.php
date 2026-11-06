<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Rekap Tahunan Rumah {{ $year }}</title>
<style>
@page { margin: 28px 32px 40px; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #21392f; line-height: 1.4; }
.header { border-bottom: 3px solid #16784a; padding-bottom: 10px; margin-bottom: 10px; }
.brand { color: #16784a; font-weight: bold; letter-spacing: 2px; font-size: 9px; }
h1 { font-size: 20px; margin: 3px 0; }
.muted { color: #61756b; }
.summary { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 10px 0; }
.summary td { background: #f1f6f3; border: 3px solid white; padding: 8px; }
.value { color: #125c3a; font-size: 12px; font-weight: bold; }
.note { color: #61756b; margin: 7px 0 10px; }
.data { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7px; }
.data th { background: #176b45; color: white; padding: 6px 3px; text-align: center; }
.data td { border-bottom: 1px solid #dce5df; padding: 6px 3px; vertical-align: middle; word-wrap: break-word; }
.data tr { page-break-inside: avoid; }
.data thead { display: table-header-group; }
.data tbody tr:nth-child(even) { background: #f6f8f6; }
.center { text-align: center; }
.amount { text-align: right; white-space: nowrap; }
.paid { color: #14643e; font-weight: bold; }
.pending { color: #155b82; font-weight: bold; }
.unpaid { color: #8a5100; font-weight: bold; }
</style></head><body>
@php($money = fn($value) => 'Rp '.preg_replace('/,00$/', '', number_format((float)$value, 2, ',', '.')))
<div class="header"><div class="brand">ENVIROPAY / LAPORAN PENGURUS</div><h1>Rekap Tahunan per Rumah {{ $year }}</h1><strong>{{ $housing->name }}</strong><div class="muted">{{ $housing->address }}</div></div>
<div>Dibuat {{ $generatedAt->format('d/m/Y H:i') }} WIB oleh {{ $preparedBy }} | {{ $summary['groups'] }} baris | {{ $summary['invoice_count'] }} tagihan</div>
<div>Status: {{ \App\Services\AnnualReportService::STATUSES[$filters['payment_status'] ?? ''] ?? 'Semua status' }}@if(isset($filters['search']) && $filters['search'] !== '') | Pencarian: {{ $filters['search'] }}@endif</div>
<table class="summary"><tr>@foreach(['billed' => 'Total tagihan', 'paid' => 'Lunas', 'pending' => 'Menunggu verifikasi', 'unpaid' => 'Belum lunas'] as $key => $label)<td>{{ $label }}<div class="value">{{ $money($summary[$key]) }}</div></td>@endforeach</tr></table>
<div class="note"><strong>L</strong> = seluruh tagihan bulan itu lunas; <strong>V</strong> = sisa tagihan menunggu verifikasi; <strong>B</strong> = ada tagihan belum dibayar/bukti ditolak; <strong>B/V</strong> = ada yang belum dibayar dan menunggu verifikasi; <strong>-</strong> = belum ada tagihan, bukan berarti lunas. Seluruh nilai mengikuti periode tagihan {{ $year }} dan status saat diunduh, bukan tanggal pembayaran. Nominal belum lunas tidak mencakup menunggu verifikasi.</div>
@if($summary['unlinked'])<div class="note">{{ $summary['unlinked'] }} akun memiliki tagihan tanpa rumah tercatat; ditampilkan terpisah dan tidak digabung ke rumah saat ini.</div>@endif
<table class="data"><thead><tr><th style="width:16%;text-align:left">Rumah / Penanggung Jawab Saat Ini</th>@foreach(range(1,12) as $m)<th style="width:3.5%">{{ \Carbon\Carbon::create($year,$m,1)->translatedFormat('M') }}</th>@endforeach<th style="width:14%">Lunas</th><th style="width:14%">Verifikasi</th><th style="width:14%">Belum Lunas</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><strong>{{ $row->group_type === 'house' ? $row->code : 'Tanpa Rumah Tercatat' }}</strong><br>{{ $row->resident ?: 'Belum ada penanggung jawab' }}@if($row->address)<br><span class="muted">{{ $row->address }}</span>@endif</td>
@foreach($row->months as $cell)<td class="center {{ $cell['status'] }}">{{ ['paid' => 'L', 'pending' => 'V', 'unpaid' => 'B', 'not_issued' => '-'][$cell['status']] }}{{ $cell['status'] === 'unpaid' && $cell['pending_count'] > 0 ? '/V' : '' }}</td>@endforeach
<td class="amount">{{ $money($row->totals['paid']) }}</td><td class="amount">{{ $money($row->totals['pending']) }}</td><td class="amount">{{ $money($row->totals['unpaid']) }}</td></tr>
@empty<tr><td colspan="16">Tidak ada rumah atau tagihan yang sesuai filter.</td></tr>@endforelse
</tbody></table>
<p class="note">Rumah mengikuti data pada saat tagihan diterbitkan. Pergantian penghuni tidak memindahkan tagihan lama. Beberapa tagihan dalam bulan yang sama dirangkum; status lunas hanya bila semuanya terverifikasi. Rumah tanpa tagihan tetap dapat ditampilkan. Ringkasan mencakup seluruh hasil filter.</p>
</body></html>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $title }} - {{ $housing->name }}</title>
<style>
    @page { margin: 28px 32px 40px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #21392f; font-size: 9px; line-height: 1.45; }
    .header { border-bottom: 3px solid #16784a; padding-bottom: 12px; margin-bottom: 12px; }
    .brand { color: #16784a; font-weight: bold; font-size: 10px; letter-spacing: 2px; }
    h1 { font-size: 21px; margin: 3px 0 5px; line-height: 1.25; }
    .housing { font-size: 12px; font-weight: bold; }
    .muted { color: #61756b; }
    .metadata { margin: 8px 0 12px; }
    .filter { background: #eff5f1; padding: 8px 10px; margin-bottom: 12px; word-wrap: break-word; }
    .filter span { margin-right: 14px; }
    .summary { width: 100%; border-collapse: collapse; margin: 8px 0 12px; table-layout: fixed; }
    .summary td { background: #f2f6f3; border: 4px solid white; padding: 9px; vertical-align: top; }
    .summary .value { font-size: 13px; font-weight: bold; color: #125c3a; margin-top: 3px; }
    .context { border: 1px solid #dce5df; padding: 9px; margin: 10px 0; page-break-inside: avoid; }
    .context table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 6px; }
    .context td { padding: 3px; vertical-align: top; }
    h2 { font-size: 12px; margin: 15px 0 7px; page-break-after: avoid; }
    .data { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8px; margin-bottom: 10px; }
    .data th { background: #176b45; color: white; text-align: left; padding: 7px 6px; font-weight: bold; }
    .data td { border-bottom: 1px solid #dce5df; padding: 6px; vertical-align: top; word-wrap: break-word; }
    .data tbody tr:nth-child(even) { background: #f6f8f6; }
    .data thead { display: table-header-group; }
    .data tr { page-break-inside: avoid; }
    .data .amount { text-align: right; white-space: nowrap; }
    .data .center { text-align: center; }
    .new-page { page-break-before: always; }
    .note { color: #61756b; font-size: 8px; margin-top: 7px; }
    .status { font-size: 7px; color: #61756b; }
</style>
</head>
<body>
@php($money = fn($value) => 'Rp '.preg_replace('/,00$/', '', number_format((float)$value, 2, ',', '.')))
<div class="header">
    <div class="brand">ENVIROPAY / LAPORAN PENGURUS</div>
    <h1>{{ $title }}</h1>
    <div class="housing">{{ $housing->name }}</div>
    <div class="muted">{{ $housing->address }}</div>
</div>
<div class="metadata">
    <strong>{{ $report === 'arrears' ? 'Kondisi per '.$generatedAt->translatedFormat('d F Y') : 'Periode '.$month->translatedFormat('F Y') }}</strong><br>
    Dibuat {{ $generatedAt->format('d/m/Y H:i') }} WIB oleh {{ $preparedBy }} | {{ $rows->count() }} {{ $report === 'arrears' ? 'warga' : ($report === 'billing' ? 'tagihan' : 'transaksi') }}
</div>
@if(count($filterLabels))<div class="filter">@foreach($filterLabels as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach</div>@endif
@if($report === 'cashflow')
@if(($bookPeriod ?? null)?->status === 'closed')<div class="note">Buku kas periode ini sudah ditutup pada {{ $bookPeriod->closed_at->format('d/m/Y H:i') }} WIB. Transaksi dikunci.</div>@endif
<table class="summary"><tr>
    @foreach(['income' => 'Pemasukan sesuai filter', 'expense' => 'Pengeluaran sesuai filter', 'net' => 'Selisih sesuai filter'] as $key => $label)
    <td>{{ $label }}<div class="value">{{ $money($summary[$key]) }}</div></td>
    @endforeach
</tr></table>
<div class="context"><strong>Saldo kas seluruh perumahan</strong><div class="muted">Mencakup semua transaksi bulan ini. Saldo berikut tidak mengikuti filter detail.</div>
    <table><tr>@foreach(['opening' => 'Saldo awal', 'period_income' => 'Pemasukan bulan ini', 'period_expense' => 'Pengeluaran bulan ini', 'closing' => 'Saldo akhir'] as $key => $label)<td><span class="muted">{{ $label }}</span><br><strong>{{ $money($summary[$key]) }}</strong></td>@endforeach</tr></table>
</div>
<h2>Rekap per Kategori</h2>
<table class="data"><thead><tr><th style="width:32%">Kategori</th><th style="width:10%" class="center">Transaksi</th><th class="amount">Pemasukan</th><th class="amount">Pengeluaran</th><th class="amount">Selisih</th></tr></thead><tbody>
    @forelse($categorySummary as $category)<tr><td>{{ $category->category->label() }}</td><td class="center">{{ $category->count }}</td><td class="amount">{{ $money($category->income) }}</td><td class="amount">{{ $money($category->expense) }}</td><td class="amount">{{ $money((float)$category->income - (float)$category->expense) }}</td></tr>
    @empty<tr><td colspan="5">Tidak ada transaksi yang sesuai filter.</td></tr>@endforelse
</tbody></table>
<h2 class="{{ $rows->count() > 10 || $categorySummary->count() > 3 ? 'new-page' : '' }}">Rincian Transaksi</h2>
<table class="data"><thead><tr><th style="width:12%">Tanggal</th><th style="width:29%">Transaksi</th><th style="width:10%">Jenis</th><th style="width:18%">Kategori</th><th style="width:14%">Sumber / Metode</th><th class="amount" style="width:17%">Nominal</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td>{{ $row->accounting_date?->format('d/m/Y') }}</td><td>{{ $row->name }}</td><td>{{ $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td><td>{{ $row->category->label() }}</td><td>{{ $row->isManual() ? 'Manual' : 'Tagihan' }}<br><span class="muted">{{ $row->method_label }}</span></td><td class="amount">{{ $money($row->total_cashflow) }}</td></tr>
    @empty<tr><td colspan="6">Tidak ada transaksi yang sesuai filter.</td></tr>@endforelse
</tbody></table>
<p class="note">Berdasarkan tanggal transaksi. Pembayaran tagihan mencakup seluruh komponen, termasuk pembayaran gabungan. Rekap dan rincian mencakup seluruh hasil filter, bukan hanya satu halaman aplikasi. Laporan mengikuti data saat diunduh dan dapat berubah bila transaksi manual diubah atau dihapus.</p>
@elseif($report === 'billing')
<table class="summary"><tr><td>Total tagihan<div class="value">{{ $money($summary['total']) }}</div></td><td>Nominal terverifikasi<div class="value">{{ $summary['percentage'] }}%</div></td></tr></table>
<table class="summary"><tr>@foreach(\App\Enums\InvoiceStatus::cases() as $status)<td>{{ $status->label() }} ({{ $summary['statuses'][$status->value]->count }})<div class="value">{{ $money($summary['statuses'][$status->value]->amount) }}</div></td>@endforeach</tr></table>
<h2>Rincian Tagihan</h2>
<table class="data"><thead><tr><th style="width:23%">Warga / Rumah</th><th style="width:27%">Tagihan</th><th style="width:13%">Periode</th><th style="width:12%">Jatuh Tempo</th><th style="width:10%">Status</th><th class="amount" style="width:15%">Nominal</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td><strong>{{ $row->resident->name }}</strong><br><span class="muted">{{ $row->house_label ?: $row->resident->house_address }}</span></td><td>{{ $row->title }}</td><td>{{ $row->billing_period->translatedFormat('F Y') }}</td><td>{{ $row->due_date->format('d/m/Y') }}</td><td>{{ $row->status->label() }}<br><span class="status">{{ $row->payment_label }}</span></td><td class="amount">{{ $money($row->total_amount) }}</td></tr>
    @empty<tr><td colspan="6">Tidak ada tagihan pada periode ini.</td></tr>@endforelse
</tbody></table>
<p class="note">Berdasarkan periode tagihan. Status pembayaran adalah kondisi saat laporan diunduh, bukan rekonstruksi status pada akhir bulan. Seluruh tagihan dalam periode ditampilkan.</p>
@else
<table class="summary"><tr><td>Total tunggakan<div class="value">{{ $money($summary['total']) }}</div></td><td>Warga menunggak<div class="value">{{ $summary['residents'] }}</div></td><td>Tagihan menunggak<div class="value">{{ $summary['invoices'] }}</div></td></tr><tr><td>Umur 1-30 hari<div class="value">{{ $money($summary['age_30']) }}</div></td><td>Umur 31-60 hari<div class="value">{{ $money($summary['age_60']) }}</div></td><td>Umur lebih dari 60 hari<div class="value">{{ $money($summary['age_over_60']) }}</div></td></tr></table>
<h2>Rincian Tunggakan per Warga</h2>
<table class="data"><thead><tr><th style="width:22%">Warga / Alamat</th><th style="width:6%" class="center">Tagihan</th><th style="width:15%" class="amount">Total</th><th style="width:15%" class="amount">1-30 Hari</th><th style="width:15%" class="amount">31-60 Hari</th><th style="width:15%" class="amount">Lebih 60 Hari</th><th style="width:12%">Jatuh Tempo Tertua</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td><strong>{{ $row->name }}</strong>@unless($row->is_active)<span class="status"> (Nonaktif)</span>@endunless<br><span class="muted">{{ $row->house_address }}</span></td><td class="center">{{ $row->invoice_count }}</td><td class="amount">{{ $money($row->total) }}</td><td class="amount">{{ $money($row->age_30) }}</td><td class="amount">{{ $money($row->age_60) }}</td><td class="amount">{{ $money($row->age_over_60) }}</td><td>{{ \Carbon\Carbon::parse($row->oldest_due)->format('d/m/Y') }}</td></tr>
    @empty<tr><td colspan="7">Tidak ada tunggakan yang sesuai filter.</td></tr>@endforelse
</tbody></table>
<p class="note">Hanya tagihan belum dibayar atau bukti ditolak yang telah melewati jatuh tempo. Pembayaran menunggu verifikasi tidak termasuk tunggakan. Filter umur memilih warga; nominal tetap mencakup seluruh tunggakan warga tersebut. Warga nonaktif tetap ditampilkan bila memiliki tunggakan.</p>
@endif
</body>
</html>

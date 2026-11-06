<nav class="nav nav-pills gap-2 mb-4" aria-label="Jenis laporan">
    @foreach(['arrears' => 'Tunggakan', 'billing' => 'Tagihan Bulanan', 'cashflow' => 'Kas Bulanan'] as $key => $label)
    <a class="nav-link {{ $activeReport === $key ? 'active' : '' }}" href="{{ route('pengurus.reports.index', ['report' => $key, 'month' => $month->format('Y-m')]) }}">{{ $label }}</a>
    @endforeach
    <a class="nav-link {{ $activeReport === 'annual' ? 'active' : '' }}" href="{{ route('pengurus.annual-report.index', ['year' => $month->year]) }}">Rekap Tahunan Rumah</a>
</nav>

@extends('layouts.app')

@section('title', 'Laporan Item Terlaris (Best Sellers)')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            <span>Produk & Layanan Terlaris (Best Sellers)</span>
        </h1>
        <p class="page-subtitle">Daftar item fisik dan jasa salon/perawatan dengan kuantitas penjualan tertinggi.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline">Kembali ke Laporan Penjualan</a>
    </div>
</div>

<!-- Period Filter Pills -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-body" style="padding:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;gap:0.5rem;">
            <a href="{{ route('reports.bestsellers', ['period' => 'today']) }}" 
               class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-outline' }} btn-sm">Hari Ini</a>
            <a href="{{ route('reports.bestsellers', ['period' => 'week']) }}" 
               class="btn {{ $period === 'week' ? 'btn-primary' : 'btn-outline' }} btn-sm">Minggu Ini</a>
            <a href="{{ route('reports.bestsellers', ['period' => 'month']) }}" 
               class="btn {{ $period === 'month' ? 'btn-primary' : 'btn-outline' }} btn-sm">Bulan Ini</a>
        </div>

        <form method="GET" action="{{ route('reports.bestsellers') }}" style="display:flex;gap:0.5rem;align-items:center;">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="start_date" value="{{ $start }}" class="form-control" style="width:150px;">
            <span>s/d</span>
            <input type="date" name="end_date" value="{{ $end }}" class="form-control" style="width:150px;">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
    </div>
</div>

<!-- Two Columns: Top Products vs Top Services -->
<div style="display:grid;grid-template-columns: 1fr 1fr;gap:1.25rem;">
    <!-- Top Products -->
    <div class="card">
        <div class="card-header" style="background:var(--primary-subtle);">
            <h2 class="card-title" style="color:var(--primary-dark);">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
                <span>Produk Fisik Terlaris</span>
            </h2>
        </div>
        <div class="card-body" style="padding:0;">
            @if ($products->isEmpty())
                <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);">
                    Tidak ada penjualan produk pada periode ini.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Produk</th>
                                <th style="text-align:center;">Qty Terjual</th>
                                <th style="text-align:right;">Total Omset</th>
                                <th style="text-align:right;">Est. Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $idx => $prod)
                                <tr>
                                    <td style="font-weight:700;color:var(--text-muted);width:30px;">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td>
                                        <div style="font-weight:600;">{{ $prod->item_name }}</div>
                                        @if ($prod->sku)
                                            <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted);">
                                                {{ $prod->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:center;font-weight:800;font-size:1.05rem;color:var(--primary);">
                                        {{ $prod->total_qty }}
                                    </td>
                                    <td style="text-align:right;font-weight:600;">
                                        Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align:right;color:var(--success);font-weight:600;">
                                        Rp {{ number_format($prod->gross_profit, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Top Services -->
    <div class="card">
        <div class="card-header" style="background:var(--info-light);">
            <h2 class="card-title" style="color:var(--info);">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                    <line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/>
                </svg>
                <span>Layanan Jasa Terlaris</span>
            </h2>
        </div>
        <div class="card-body" style="padding:0;">
            @if ($services->isEmpty())
                <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);">
                    Tidak ada penggunaan layanan pada periode ini.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Layanan</th>
                                <th style="text-align:center;">Jumlah Pengerjaan</th>
                                <th style="text-align:right;">Total Omset</th>
                                <th style="text-align:right;">Est. Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($services as $idx => $srv)
                                <tr>
                                    <td style="font-weight:700;color:var(--text-muted);width:30px;">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td>
                                        <div style="font-weight:600;">{{ $srv->item_name }}</div>
                                        @if ($srv->sku)
                                            <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted);">
                                                {{ $srv->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:center;font-weight:800;font-size:1.05rem;color:var(--info);">
                                        {{ $srv->total_qty }}x
                                    </td>
                                    <td style="text-align:right;font-weight:600;">
                                        Rp {{ number_format($srv->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align:right;color:var(--success);font-weight:600;">
                                        Rp {{ number_format($srv->gross_profit, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

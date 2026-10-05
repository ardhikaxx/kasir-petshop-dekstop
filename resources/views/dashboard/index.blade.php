@extends('layouts.app')

@section('title', 'Dashboard Pet Shop')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            <span>Ringkasan Operasional Toko</span>
        </h1>
        <p class="page-subtitle">
            Data operasional lokal hari ini — {{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('dddd, D MMMM Y') }}
        </p>
    </div>

    <div class="page-actions">
        <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <span>Buka Kasir POS</span>
        </a>
    </div>
</div>

<!-- Primary Stats Grid -->
<div class="stats-grid">
    <!-- Today Revenue -->
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pendapatan Hari Ini</div>
            <div class="stat-value">Rp {{ number_format($summary['today_revenue'], 0, ',', '.') }}</div>
            <div class="stat-desc">{{ $summary['today_transactions_count'] }} transaksi tercatat</div>
        </div>
    </div>

    <!-- Estimated Gross Profit -->
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Est. Laba Kotor Hari Ini</div>
            <div class="stat-value" style="color:var(--success);">Rp {{ number_format($summary['today_gross_profit'], 0, ',', '.') }}</div>
            <div class="stat-desc">Berdasarkan HPP barang & jasa</div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="stat-card">
        <div class="stat-icon {{ $summary['low_stock_count'] > 0 || $summary['out_of_stock_count'] > 0 ? 'warning' : 'info' }}">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Peringatan Stok</div>
            <div class="stat-value" style="color:{{ $summary['low_stock_count'] > 0 ? 'var(--warning)' : 'inherit' }};">
                {{ $summary['low_stock_count'] + $summary['out_of_stock_count'] }}
            </div>
            <div class="stat-desc">
                <span class="badge badge-warning" style="font-size:10px;">{{ $summary['low_stock_count'] }} Menipis</span>
                <span class="badge badge-danger" style="font-size:10px;">{{ $summary['out_of_stock_count'] }} Habis</span>
            </div>
        </div>
    </div>

    <!-- Inventory Overview -->
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Katalog</div>
            <div class="stat-value">{{ $summary['in_stock_count'] }} Produk</div>
            <div class="stat-desc">{{ $summary['service_count'] }} Layanan Jasa aktif</div>
        </div>
    </div>
</div>

<!-- Secondary Section: Payment Breakdown & Low Stock Alert Table -->
<div style="display:grid;grid-template-columns: 340px 1fr;gap:1.25rem;margin-bottom:1.25rem;">
    <!-- Payment Method Breakdown Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <span>Metode Pembayaran (Hari Ini)</span>
            </h2>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <!-- Tunai -->
                <div style="border:1px solid var(--border-light);padding:0.75rem 1rem;border-radius:var(--radius-md);background:var(--bg-subtle);">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:600;">Tunai (Cash)</span>
                        <span class="badge badge-secondary">{{ $summary['payment_breakdown']['cash']['count'] }} tx</span>
                    </div>
                    <div style="font-size:1.2rem;font-weight:700;color:var(--text-main);margin-top:0.25rem;">
                        Rp {{ number_format($summary['payment_breakdown']['cash']['total'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- QRIS -->
                <div style="border:1px solid var(--border-light);padding:0.75rem 1rem;border-radius:var(--radius-md);background:var(--bg-subtle);">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:600;">QRIS</span>
                        <span class="badge badge-secondary">{{ $summary['payment_breakdown']['qris']['count'] }} tx</span>
                    </div>
                    <div style="font-size:1.2rem;font-weight:700;color:var(--text-main);margin-top:0.25rem;">
                        Rp {{ number_format($summary['payment_breakdown']['qris']['total'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- Transfer Bank -->
                <div style="border:1px solid var(--border-light);padding:0.75rem 1rem;border-radius:var(--radius-md);background:var(--bg-subtle);">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:600;">Transfer Bank</span>
                        <span class="badge badge-secondary">{{ $summary['payment_breakdown']['transfer']['count'] }} tx</span>
                    </div>
                    <div style="font-size:1.2rem;font-weight:700;color:var(--text-main);margin-top:0.25rem;">
                        Rp {{ number_format($summary['payment_breakdown']['transfer']['total'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Produk Stok Menipis & Habis</span>
            </h2>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline btn-sm">Kelola Stok Masuk</a>
        </div>
        <div class="card-body" style="padding:0;">
            @if ($alertProducts->isEmpty())
                <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);">
                    <svg width="36" height="36" fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:0.5rem;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <p style="font-weight:600;">Semua stok produk dalam kondisi aman!</p>
                    <p style="font-size:0.8rem;margin-top:0.25rem;">Tidak ada produk dengan stok di bawah batas minimum.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Sisa Stok</th>
                                <th>Batas Min</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($alertProducts as $p)
                                <tr>
                                    <td>
                                        <div style="font-weight:600;">{{ $p->name }}</div>
                                        <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted);">SKU: {{ $p->sku }}</div>
                                    </td>
                                    <td>{{ $p->category?->name ?? '-' }}</td>
                                    <td style="font-weight:700;font-size:1.05rem;color:{{ $p->stock <= 0 ? 'var(--danger)' : 'var(--warning)' }};">
                                        {{ $p->stock }} {{ $p->unit }}
                                    </td>
                                    <td>{{ $p->min_stock }} {{ $p->unit }}</td>
                                    <td>
                                        @if ($p->stock <= 0)
                                            <span class="badge badge-danger">Habis</span>
                                        @else
                                            <span class="badge badge-warning">Menipis</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('inventory.index', ['product_id' => $p->id]) }}" class="btn btn-outline btn-sm">
                                            + Tambah Stok
                                        </a>
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

<!-- Third Section: Recent Transactions & Top Selling Items This Week -->
<div style="display:grid;grid-template-columns: 1fr 1fr;gap:1.25rem;">
    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/>
                </svg>
                <span>Transaksi Kasir Terbaru</span>
            </h2>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body" style="padding:0;">
            @if ($summary['recent_transactions']->isEmpty())
                <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                    Belum ada transaksi yang tercatat hari ini.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No Transaksi</th>
                                <th>Waktu</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['recent_transactions'] as $tx)
                                <tr>
                                    <td>
                                        <a href="{{ route('transactions.show', $tx) }}" style="font-weight:600;font-family:monospace;">
                                            {{ $tx->transaction_number }}
                                        </a>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($tx->transaction_date)->timezone('Asia/Jakarta')->format('H:i') }} WIB</td>
                                    <td style="font-weight:700;">Rp {{ number_format($tx->grand_total, 0, ',', '.') }}</td>
                                    <td>{{ $tx->payment_method_label }}</td>
                                    <td>
                                        <span class="badge {{ $tx->status_badge['class'] }}">
                                            {{ $tx->status_badge['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Top Selling Items This Week -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Item Terlaris (Minggu Ini)</span>
            </h2>
            <a href="{{ route('reports.bestsellers') }}" class="btn btn-outline btn-sm">Laporan Terlaris</a>
        </div>
        <div class="card-body" style="padding:0;">
            @if ($summary['top_items']->isEmpty())
                <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                    Belum ada data penjualan minggu ini.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Tipe</th>
                                <th>Terjual</th>
                                <th>Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['top_items'] as $item)
                                <tr>
                                    <td style="font-weight:600;">{{ $item->item_name }}</td>
                                    <td>
                                        <span class="badge {{ $item->item_type === 'product' ? 'badge-primary' : 'badge-info' }}">
                                            {{ $item->item_type === 'product' ? 'Produk' : 'Jasa' }}
                                        </span>
                                    </td>
                                    <td style="font-weight:700;">{{ $item->total_qty }}</td>
                                    <td>Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
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

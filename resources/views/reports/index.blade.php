@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span>Laporan Penjualan & Laba Kotor</span>
        </h1>
        <p class="page-subtitle">Analisis omset penjualan, estimasi laba kotor, dan distribusi metode pembayaran.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('reports.bestsellers') }}" class="btn btn-outline">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            <span>Laporan Item Terlaris</span>
        </a>
        <a href="{{ route('backup.export-csv', ['type' => 'transactions']) }}" class="btn btn-secondary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <span>Ekspor CSV</span>
        </a>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-body" style="padding:1rem;">
        <form method="GET" action="{{ route('reports.index') }}" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
            <div style="min-width:180px;">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control" required>
            </div>

            <div style="min-width:180px;">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control" required>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                <a href="{{ route('reports.index', ['start_date' => \Carbon\Carbon::today()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::today()->format('Y-m-d')]) }}" class="btn btn-outline" style="margin-left:0.25rem;">Hari Ini</a>
                <a href="{{ route('reports.index', ['start_date' => \Carbon\Carbon::today()->startOfMonth()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::today()->format('Y-m-d')]) }}" class="btn btn-outline">Bulan Ini</a>
            </div>
        </form>
    </div>
</div>

<!-- Metrics Cards Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Omset Pendapatan</div>
            <div class="stat-value">Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</div>
            <div class="stat-desc">{{ $report['total_transactions'] }} transaksi selesai</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Estimasi Laba Kotor</div>
            <div class="stat-value" style="color:var(--success);">Rp {{ number_format($report['estimated_gross_profit'], 0, ',', '.') }}</div>
            <div class="stat-desc">Setelah dikurangi HPP</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Diskon Diberikan</div>
            <div class="stat-value" style="color:var(--warning);">Rp {{ number_format($report['total_discount'], 0, ',', '.') }}</div>
            <div class="stat-desc">Potongan promo toko</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Pajak Terkumpul</div>
            <div class="stat-value">Rp {{ number_format($report['total_tax'], 0, ',', '.') }}</div>
            <div class="stat-desc">Pajak penjualan (PPN)</div>
        </div>
    </div>
</div>

<!-- Payment Breakdown & Visualizer -->
<div style="display:grid;grid-template-columns: 1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
    <!-- Payment Methods Breakdown -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <span>Pembagian Berdasarkan Metode Pembayaran</span>
            </h2>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div style="border:1px solid var(--border-light);padding:0.85rem;border-radius:var(--radius-md);background:var(--bg-subtle);">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-weight:700;">Tunai (Cash)</span>
                        <span class="badge badge-secondary">{{ $report['payment_breakdown']['cash']['count'] }} Transaksi</span>
                    </div>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--text-main);margin-top:0.35rem;">
                        Rp {{ number_format($report['payment_breakdown']['cash']['total'], 0, ',', '.') }}
                    </div>
                </div>

                <div style="border:1px solid var(--border-light);padding:0.85rem;border-radius:var(--radius-md);background:var(--bg-subtle);">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-weight:700;">QRIS</span>
                        <span class="badge badge-secondary">{{ $report['payment_breakdown']['qris']['count'] }} Transaksi</span>
                    </div>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--text-main);margin-top:0.35rem;">
                        Rp {{ number_format($report['payment_breakdown']['qris']['total'], 0, ',', '.') }}
                    </div>
                </div>

                <div style="border:1px solid var(--border-light);padding:0.85rem;border-radius:var(--radius-md);background:var(--bg-subtle);">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-weight:700;">Transfer Bank</span>
                        <span class="badge badge-secondary">{{ $report['payment_breakdown']['transfer']['count'] }} Transaksi</span>
                    </div>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--text-main);margin-top:0.35rem;">
                        Rp {{ number_format($report['payment_breakdown']['transfer']['total'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Revenue Chart (Lightweight Local HTML5/CSS Bar Visualization) -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Grafik Penjualan Harian</span>
            </h2>
        </div>
        <div class="card-body">
            @php
                $maxRev = 1;
                foreach ($report['daily_points'] as $dp) {
                    if ($dp['revenue'] > $maxRev) $maxRev = $dp['revenue'];
                }
            @endphp

            @if (empty($report['daily_points']) || $report['total_revenue'] <= 0)
                <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);">
                    Belum ada data penjualan pada periode tanggal ini.
                </div>
            @else
                <div style="display:flex;align-items:flex-end;gap:0.4rem;height:180px;padding-top:1.5rem;overflow-x:auto;">
                    @foreach ($report['daily_points'] as $dp)
                        @php
                            $heightPercent = max(6, round(($dp['revenue'] / $maxRev) * 100));
                        @endphp
                        <div style="flex:1;min-width:28px;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;" 
                             title="{{ $dp['label'] }}: Rp {{ number_format($dp['revenue'], 0, ',', '.') }}">
                            <div style="width:100%;height:{{ $heightPercent }}%;background:{{ $dp['revenue'] > 0 ? 'var(--primary)' : 'var(--border)' }};border-radius:4px 4px 0 0;transition:height 0.2s;"></div>
                            <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.35rem;white-space:nowrap;transform:rotate(-45deg);transform-origin:left top;">
                                {{ $dp['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Transactions In Period Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Rincian Transaksi Pada Periode Terpilih ({{ $report['total_transactions'] }} Transaksi)</h2>
    </div>
    <div class="card-body" style="padding:0;">
        @if ($report['transactions']->isEmpty())
            <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                Tidak ada transaksi pada rentang tanggal ini.
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Pelanggan</th>
                            <th>Metode</th>
                            <th style="text-align:right;">Subtotal</th>
                            <th style="text-align:right;">Diskon</th>
                            <th style="text-align:right;">Pajak</th>
                            <th style="text-align:right;">Grand Total</th>
                            <th style="text-align:right;">Est. Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['transactions'] as $tx)
                            <tr>
                                <td>
                                    <a href="{{ route('transactions.show', $tx) }}" style="font-family:monospace;font-weight:600;">
                                        {{ $tx->transaction_number }}
                                    </a>
                                </td>
                                <td style="font-size:0.8rem;color:var(--text-muted);">
                                    {{ $tx->transaction_date->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                </td>
                                <td>{{ $tx->customer_name ?: '-' }}</td>
                                <td>{{ $tx->payment_method_label }}</td>
                                <td style="text-align:right;">Rp {{ number_format($tx->subtotal, 0, ',', '.') }}</td>
                                <td style="text-align:right;color:{{ $tx->discount_amount > 0 ? 'var(--danger)' : 'inherit' }};">
                                    {{ $tx->discount_amount > 0 ? '-Rp ' . number_format($tx->discount_amount, 0, ',', '.') : '-' }}
                                </td>
                                <td style="text-align:right;">
                                    {{ $tx->tax_amount > 0 ? 'Rp ' . number_format($tx->tax_amount, 0, ',', '.') : '-' }}
                                </td>
                                <td style="text-align:right;font-weight:700;color:var(--primary);">
                                    Rp {{ number_format($tx->grand_total, 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-weight:600;color:var(--success);">
                                    Rp {{ number_format($tx->estimated_gross_profit, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Riwayat Transaksi Kasir</span>
        </h1>
        <p class="page-subtitle">Daftar seluruh transaksi penjualan barang dan jasa pet shop yang pernah dilakukan.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('pos.index') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Transaksi Baru (Kasir)</span>
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-body" style="padding:1rem;">
        <form method="GET" action="{{ route('transactions.index') }}" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label class="form-label">Cari Transaksi / Pelanggan</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" 
                       placeholder="No. Transaksi, Nama Pelanggan, atau Nama Hewan...">
            </div>

            <div style="min-width:140px;">
                <label class="form-label">Metode Pembayaran</label>
                <select name="payment_method" class="form-select">
                    <option value="">Semua Metode</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="qris" {{ request('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                </select>
            </div>

            <div style="min-width:140px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div style="min-width:140px;">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
            </div>

            <div style="min-width:140px;">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
            </div>

            <div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="{{ route('transactions.index') }}" class="btn btn-outline" style="margin-left:0.25rem;">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        @if ($transactions->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
                <p style="font-weight:600;font-size:1.05rem;">Tidak ada transaksi ditemukan.</p>
                <p style="font-size:0.85rem;margin-top:0.35rem;">Coba ubah tanggal atau kata kunci pencarian Anda.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Pelanggan & Hewan</th>
                            <th style="text-align:center;">Jumlah Item</th>
                            <th>Metode Bayar</th>
                            <th style="text-align:right;">Grand Total</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $t)
                            <tr>
                                <td>
                                    <a href="{{ route('transactions.show', $t) }}" style="font-family:monospace;font-weight:700;">
                                        {{ $t->transaction_number }}
                                    </a>
                                </td>
                                <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;">
                                    {{ $t->transaction_date->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                </td>
                                <td>
                                    @if ($t->customer_name || $t->pet_name)
                                        <div style="font-weight:600;">{{ $t->customer_name ?: 'Pelanggan' }}</div>
                                        @if ($t->pet_name)
                                            <div style="font-size:0.75rem;color:var(--text-muted);">
                                                🐾 {{ $t->pet_name }} {{ $t->pet_type ? "({$t->pet_type})" : '' }}
                                            </div>
                                        @endif
                                    @else
                                        <span style="color:var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge badge-secondary">{{ $t->items->count() }} item</span>
                                </td>
                                <td>
                                    <span style="font-weight:500;">{{ $t->payment_method_label }}</span>
                                </td>
                                <td style="text-align:right;font-weight:700;font-size:1rem;color:var(--primary);">
                                    Rp {{ number_format($t->grand_total, 0, ',', '.') }}
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge {{ $t->status_badge['class'] }}">
                                        {{ $t->status_badge['label'] }}
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <div style="display:inline-flex;gap:0.35rem;">
                                        <a href="{{ route('transactions.show', $t) }}" class="btn btn-outline btn-sm">
                                            Rincian
                                        </a>
                                        <a href="{{ route('transactions.receipt', $t) }}" target="_blank" class="btn btn-outline btn-sm" title="Cetak Struk">
                                            Struk
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:1rem 1.25rem;">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Detail Transaksi - ' . $transaction->transaction_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <span>Transaksi: {{ $transaction->transaction_number }}</span>
        </h1>
        <p class="page-subtitle">
            Tercatat pada {{ $transaction->transaction_date->timezone('Asia/Jakarta')->isoFormat('dddd, D MMMM Y - HH:mm') }} WIB
        </p>
    </div>

    <div class="page-actions">
        <a href="{{ route('transactions.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
        <button type="button" class="btn btn-outline" onclick="PetShop.copyToClipboard(`{{ addslashes($receiptText) }}`)">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
            </svg>
            <span>Salin Teks Struk</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            <span>Cetak Ulang Struk</span>
        </button>
        @if ($transaction->status !== 'cancelled')
            <button type="button" class="btn btn-danger" onclick="PetShop.openModal('modal-cancel-transaction')">
                Batalkan Transaksi
            </button>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns: 1fr 340px; gap:1.25rem;">
    <!-- Left Column: Transaction Details Table -->
    <div>
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <h2 class="card-title">Informasi Pesanan</h2>
                <span class="badge {{ $transaction->status_badge['class'] }}" style="font-size:0.85rem;">
                    {{ $transaction->status_badge['label'] }}
                </span>
            </div>
            <div class="card-body">
                <div class="form-row" style="margin-bottom:1rem;">
                    <div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Nama Pelanggan</div>
                        <div style="font-weight:600;font-size:1rem;">{{ $transaction->customer_name ?: '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Nama Hewan (Pet)</div>
                        <div style="font-weight:600;font-size:1rem;">
                            {{ $transaction->pet_name ?: '-' }} {{ $transaction->pet_type ? "({$transaction->pet_type})" : '' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Metode Pembayaran</div>
                        <div style="font-weight:600;font-size:1rem;">{{ $transaction->payment_method_label }}</div>
                    </div>
                </div>

                @if ($transaction->notes)
                    <div style="background:var(--bg-subtle);padding:0.75rem;border-radius:var(--radius-md);margin-bottom:1rem;font-size:0.85rem;">
                        <strong>Catatan:</strong> {{ $transaction->notes }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Tipe</th>
                                <th style="text-align:right;">Harga Satuan</th>
                                <th style="text-align:center;">Qty</th>
                                <th style="text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaction->items as $item)
                                <tr>
                                    <td>
                                        <div style="font-weight:600;">{{ $item->item_name }}</div>
                                        @if ($item->sku)
                                            <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted);">
                                                {{ $item->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->item_type === 'product' ? 'badge-primary' : 'badge-info' }}">
                                            {{ $item->item_type === 'product' ? 'Produk Fisik' : 'Layanan Jasa' }}
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align:center;font-weight:700;">
                                        {{ $item->quantity }}
                                    </td>
                                    <td style="text-align:right;font-weight:700;">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;font-weight:600;">Subtotal:</td>
                                <td style="text-align:right;font-weight:600;">
                                    Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @if ($transaction->discount_amount > 0)
                                <tr>
                                    <td colspan="4" style="text-align:right;font-weight:600;color:var(--danger);">
                                        Diskon {{ $transaction->discount_type === 'percent' ? "({$transaction->discount_value}%)" : '' }}:
                                    </td>
                                    <td style="text-align:right;font-weight:600;color:var(--danger);">
                                        -Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                            @if ($transaction->tax_amount > 0)
                                <tr>
                                    <td colspan="4" style="text-align:right;font-weight:600;">
                                        Pajak ({{ $transaction->tax_percentage }}%):
                                    </td>
                                    <td style="text-align:right;font-weight:600;">
                                        Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                            <tr style="font-size:1.15rem;border-top:2px solid var(--border);">
                                <td colspan="4" style="text-align:right;font-weight:800;color:var(--primary);">
                                    Grand Total:
                                </td>
                                <td style="text-align:right;font-weight:800;color:var(--primary);">
                                    Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align:right;">Uang Diterima:</td>
                                <td style="text-align:right;">
                                    Rp {{ number_format($transaction->payment_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                            @if ($transaction->payment_method === 'cash')
                                <tr>
                                    <td colspan="4" style="text-align:right;font-weight:700;color:var(--success);">
                                        Kembalian:
                                    </td>
                                    <td style="text-align:right;font-weight:700;color:var(--success);">
                                        Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Thermal Receipt Preview -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Preview Struk Kasir</h2>
            </div>
            <div class="card-body" style="background:#f8fafc;padding:0.75rem;">
                <div style="background:#ffffff;padding:0.5rem;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);">
                    @include('receipts.thermal', ['receiptData' => $receiptData])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Cancel Transaction -->
@if ($transaction->status !== 'cancelled')
<div class="modal-backdrop" id="modal-cancel-transaction">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('transactions.cancel', $transaction) }}">
            @csrf
            <div class="modal-header" style="background:var(--danger-light);">
                <h3 class="modal-title" style="color:#7f1d1d;">Konfirmasi Pembatalan Transaksi</h3>
                <button type="button" class="modal-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:1rem;color:var(--text-main);">
                    Apakah Anda yakin ingin membatalkan transaksi <strong>#{{ $transaction->transaction_number }}</strong>?
                </p>
                <div class="alert alert-warning" style="font-size:0.825rem;">
                    <strong>Perhatian:</strong> Seluruh stok produk fisik yang ada pada transaksi ini akan otomatis dikembalikan (reversal) ke inventaris dan dicatat dalam histori mutasi stok.
                </div>
                <div class="form-group">
                    <label class="form-label required">Alasan Pembatalan</label>
                    <input type="text" name="cancel_reason" class="form-control" 
                           placeholder="Contoh: Salah input pesanan, Pelanggan membatalkan" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-danger">Ya, Batalkan Transaksi & Kembalikan Stok</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

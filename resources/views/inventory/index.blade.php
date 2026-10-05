@extends('layouts.app')

@section('title', 'Manajemen Stok & Mutasi')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>
            </svg>
            <span>Manajemen Stok & Riwayat Mutasi</span>
        </h1>
        <p class="page-subtitle">Pantau seluruh pergerakan stok: stok awal, penerimaan barang masuk, penjualan kasir, dan penyesuaian opname.</p>
    </div>

    <div class="page-actions">
        <button type="button" class="btn btn-outline" onclick="PetShop.openModal('modal-stock-adjustment')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>Koreksi Stok Fisik</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="PetShop.openModal('modal-stock-in')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Catat Stok Masuk</span>
        </button>
    </div>
</div>

<!-- Stock Movements Filter Bar -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-body" style="padding:1rem;">
        <form method="GET" action="{{ route('inventory.index') }}" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label class="form-label">Pilih Produk</label>
                <select name="product_id" class="form-select">
                    <option value="">Semua Produk</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} (Sisa: {{ $p->stock }} {{ $p->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width:160px;">
                <label class="form-label">Tipe Pergerakan</label>
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stok Masuk</option>
                    <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Penjualan Kasir</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Koreksi/Penyesuaian</option>
                    <option value="initial" {{ request('type') === 'initial' ? 'selected' : '' }}>Stok Awal</option>
                    <option value="reversal" {{ request('type') === 'reversal' ? 'selected' : '' }}>Pembatalan Transaksi</option>
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
                <a href="{{ route('inventory.index') }}" class="btn btn-outline" style="margin-left:0.25rem;">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Stock Movements Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <span>Histori Perubahan Stok (Stock Movements)</span>
        </h2>
    </div>
    <div class="card-body" style="padding:0;">
        @if ($movements->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
                Belum ada riwayat mutasi stok yang sesuai dengan filter.
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu & Tanggal</th>
                            <th>Produk</th>
                            <th>Tipe Mutasi</th>
                            <th style="text-align:center;">Perubahan Qty</th>
                            <th style="text-align:center;">Stok Sebelum</th>
                            <th style="text-align:center;">Stok Sesudah</th>
                            <th>No. Referensi</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $m)
                            <tr>
                                <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;">
                                    {{ $m->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--text-main);">
                                        {{ $m->product?->name ?? 'Produk Dihapus' }}
                                    </div>
                                    @if ($m->product?->sku)
                                        <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted);">
                                            SKU: {{ $m->product->sku }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $m->type_badge['class'] }}">
                                        {{ $m->type_badge['label'] }}
                                    </span>
                                </td>
                                <td style="text-align:center;font-weight:700;font-size:1rem;color:{{ $m->quantity > 0 ? 'var(--success)' : ($m->quantity < 0 ? 'var(--danger)' : 'var(--text-muted)') }};">
                                    {{ $m->quantity > 0 ? "+{$m->quantity}" : $m->quantity }}
                                </td>
                                <td style="text-align:center;color:var(--text-muted);">
                                    {{ $m->before_stock }}
                                </td>
                                <td style="text-align:center;font-weight:700;">
                                    {{ $m->after_stock }}
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-size:0.825rem;">{{ $m->reference_number ?: '-' }}</span>
                                </td>
                                <td style="font-size:0.825rem;color:var(--text-muted);">
                                    {{ $m->notes ?: '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:1rem 1.25rem;">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>

<!-- MODAL: Stock In (Penerimaan Barang) -->
<div class="modal-backdrop" id="modal-stock-in">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('inventory.stock-in') }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Catat Stok Masuk (Restock)</h3>
                <button type="button" class="modal-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Pilih Produk</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} (Stok saat ini: {{ $p->stock }} {{ $p->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Jumlah Masuk</label>
                        <input type="number" name="quantity" class="form-control" placeholder="Contoh: 20" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Beli Baru / HPP (Rp)</label>
                        <input type="number" name="cost_price" class="form-control" placeholder="Kosongkan jika tetap" min="0" step="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Referensi / Faktur Supplier</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="Contoh: INV-SUPPLIER-202610">
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pembelian atau supplier..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Stok Masuk</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Physical Stock Adjustment (Stock Opname) -->
<div class="modal-backdrop" id="modal-stock-adjustment">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('inventory.adjust') }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Koreksi Stok Fisik (Stock Opname)</h3>
                <button type="button" class="modal-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Pilih Produk</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} (Stok sistem: {{ $p->stock }} {{ $p->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Stok Fisik Sebenarnya</label>
                    <input type="number" name="actual_stock" class="form-control" placeholder="Jumlah fisik yang dihitung di toko" min="0" required>
                    <span class="form-text">Sistem akan menghitung selisih dan mencatat koreksi otomatis.</span>
                </div>

                <div class="form-group">
                    <label class="form-label required">Alasan Penyesuaian</label>
                    <select name="reason" class="form-select" required>
                        <option value="Stock Opname Rutin">Stock Opname Rutin</option>
                        <option value="Barang Rusak / Bocor">Barang Rusak / Bocor</option>
                        <option value="Barang Kedaluwarsa">Barang Kedaluwarsa (Expired)</option>
                        <option value="Selisih Hitung Kasir">Selisih Hitung Kasir</option>
                        <option value="Lainnya">Alasan Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan detail penyesuaian..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Terapkan Koreksi Stok</button>
            </div>
        </form>
    </div>
</div>
@endsection

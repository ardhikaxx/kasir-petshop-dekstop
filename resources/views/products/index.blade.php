@extends('layouts.app')

@section('title', 'Katalog & Inventaris Produk')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
            <span>Katalog & Inventaris Produk</span>
        </h1>
        <p class="page-subtitle">Kelola master data produk fisik, barcode, harga jual, HPP, dan level stok.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Tambah Produk Baru</span>
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom: 1.25rem;">
    <div class="card-body" style="padding: 1rem;">
        <form method="GET" action="{{ route('products.index') }}" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
            <!-- Keyword search -->
            <div style="flex:1;min-width:200px;">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" 
                       placeholder="Cari nama, SKU, atau barcode...">
            </div>

            <!-- Category Filter -->
            <div style="min-width:180px;">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Stock Status Filter -->
            <div style="min-width:160px;">
                <label class="form-label">Status Stok</label>
                <select name="status" class="form-select">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Stok</option>
                    <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>Tersedia</option>
                    <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Stok Menipis</option>
                    <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Stok Habis</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-secondary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline" style="margin-left:0.25rem;">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        @if ($products->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
                <p style="font-weight:600;font-size:1.05rem;">Tidak ada produk yang sesuai dengan kriteria.</p>
                <p style="font-size:0.85rem;margin-top:0.35rem;">Silakan tambahkan produk baru atau ubah filter pencarian Anda.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SKU & Barcode</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Satuan / Jenis</th>
                            <th style="text-align:right;">Harga Beli (HPP)</th>
                            <th style="text-align:right;">Harga Jual</th>
                            <th style="text-align:center;">Stok / Min</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $p)
                            <tr>
                                <td>
                                    <div style="font-weight:600;font-family:monospace;">{{ $p->sku }}</div>
                                    @if ($p->barcode)
                                        <div style="font-size:0.75rem;color:var(--text-muted);font-family:monospace;">
                                            BAR: {{ $p->barcode }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--text-main);">{{ $p->name }}</div>
                                    @if ($p->description)
                                        <div style="font-size:0.75rem;color:var(--text-muted);max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $p->description }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $p->category?->name ?? '-' }}</td>
                                <td>
                                    <span>{{ $p->unit }}</span>
                                    <span style="color:var(--text-muted);font-size:0.75rem;">({{ $p->type }})</span>
                                </td>
                                <td style="text-align:right;color:var(--text-muted);">
                                    Rp {{ number_format($p->cost_price, 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-weight:700;color:var(--primary);">
                                    Rp {{ number_format($p->selling_price, 0, ',', '.') }}
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700;font-size:1rem;color:{{ $p->stock <= 0 ? 'var(--danger)' : ($p->stock <= $p->min_stock ? 'var(--warning)' : 'inherit') }};">
                                        {{ $p->stock }}
                                    </span>
                                    <span style="color:var(--text-muted);font-size:0.75rem;">/ {{ $p->min_stock }}</span>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge {{ $p->stock_badge['class'] }}">
                                        {{ $p->stock_badge['label'] }}
                                    </span>
                                    @if (!$p->is_active)
                                        <span class="badge badge-secondary" style="margin-top:2px;display:block;">Nonaktif</span>
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <div style="display:inline-flex;gap:0.35rem;">
                                        <a href="{{ route('inventory.index', ['product_id' => $p->id]) }}" class="btn btn-outline btn-sm" title="Tambah Stok">
                                            + Stok
                                        </a>
                                        <a href="{{ route('products.edit', $p) }}" class="btn btn-outline btn-sm" title="Edit Produk">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('products.toggle', $p) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm" title="{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                {{ $p->is_active ? 'Off' : 'On' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('products.destroy', $p) }}" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk [{{ $p->name }}]?')" 
                                              style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);" title="Hapus">
                                                ✕
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="padding: 1rem 1.25rem;">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

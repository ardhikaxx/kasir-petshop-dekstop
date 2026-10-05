@extends('layouts.app')

@section('title', 'Edit Produk - ' . $product->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            <span>Edit Produk: {{ $product->name }}</span>
        </h1>
        <p class="page-subtitle">Perubahan data produk tidak akan mengubah riwayat transaksi lama.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('products.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Perbarui Informasi Produk</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PUT')

            <!-- Product Name -->
            <div class="form-group">
                <label class="form-label required">Nama Produk</label>
                <input type="text" name="name" class="form-control" 
                       value="{{ old('name', $product->name) }}" required>
            </div>

            <div class="form-row">
                <!-- Category -->
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Type -->
                <div class="form-group">
                    <label class="form-label required">Jenis Produk</label>
                    <select name="type" class="form-select" required>
                        <option value="Makanan" {{ old('type', $product->type) == 'Makanan' ? 'selected' : '' }}>Makanan Hewan</option>
                        <option value="Obat/Vitamin" {{ old('type', $product->type) == 'Obat/Vitamin' ? 'selected' : '' }}>Obat & Vitamin</option>
                        <option value="Pasir" {{ old('type', $product->type) == 'Pasir' ? 'selected' : '' }}>Pasir & Kebersihan</option>
                        <option value="Aksesori" {{ old('type', $product->type) == 'Aksesori' ? 'selected' : '' }}>Aksesori & Tali</option>
                        <option value="Mainan" {{ old('type', $product->type) == 'Mainan' ? 'selected' : '' }}>Mainan Hewan</option>
                        <option value="Kandang" {{ old('type', $product->type) == 'Kandang' ? 'selected' : '' }}>Kandang & Tas</option>
                        <option value="Perlengkapan" {{ old('type', $product->type) == 'Perlengkapan' ? 'selected' : '' }}>Perlengkapan Grooming</option>
                        <option value="Lainnya" {{ old('type', $product->type) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <!-- Unit -->
                <div class="form-group">
                    <label class="form-label required">Satuan</label>
                    <select name="unit" class="form-select" required>
                        <option value="pcs" {{ old('unit', $product->unit) == 'pcs' ? 'selected' : '' }}>pcs</option>
                        <option value="kg" {{ old('unit', $product->unit) == 'kg' ? 'selected' : '' }}>kg</option>
                        <option value="pouch" {{ old('unit', $product->unit) == 'pouch' ? 'selected' : '' }}>pouch</option>
                        <option value="kaleng" {{ old('unit', $product->unit) == 'kaleng' ? 'selected' : '' }}>kaleng</option>
                        <option value="botol" {{ old('unit', $product->unit) == 'botol' ? 'selected' : '' }}>botol</option>
                        <option value="pack" {{ old('unit', $product->unit) == 'pack' ? 'selected' : '' }}>pack</option>
                        <option value="karung" {{ old('unit', $product->unit) == 'karung' ? 'selected' : '' }}>karung</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <!-- SKU -->
                <div class="form-group">
                    <label class="form-label required">Kode SKU</label>
                    <input type="text" name="sku" class="form-control" 
                           value="{{ old('sku', $product->sku) }}" required>
                </div>

                <!-- Barcode -->
                <div class="form-group">
                    <label class="form-label">Barcode Fisik</label>
                    <input type="text" name="barcode" class="form-control" 
                           value="{{ old('barcode', $product->barcode) }}" placeholder="Scan barcode kemasan produk">
                </div>
            </div>

            <div class="form-row">
                <!-- Cost Price / HPP -->
                <div class="form-group">
                    <label class="form-label required">Harga Beli / HPP (Rp)</label>
                    <input type="number" name="cost_price" class="form-control" 
                           value="{{ old('cost_price', $product->cost_price) }}" min="0" step="100" required>
                </div>

                <!-- Selling Price -->
                <div class="form-group">
                    <label class="form-label required">Harga Jual (Rp)</label>
                    <input type="number" name="selling_price" class="form-control" 
                           value="{{ old('selling_price', $product->selling_price) }}" min="0" step="100" required>
                </div>
            </div>

            <div class="form-row">
                <!-- Current Stock -->
                <div class="form-group">
                    <label class="form-label">Stok Fisik Saat Ini</label>
                    <input type="number" name="stock" class="form-control" 
                           value="{{ old('stock', $product->stock) }}" min="0">
                    <span class="form-text">Perubahan angka stok di sini akan otomatis dicatat sebagai koreksi stok.</span>
                </div>

                <!-- Minimum Stock Alert -->
                <div class="form-group">
                    <label class="form-label required">Batas Stok Minimum</label>
                    <input type="number" name="min_stock" class="form-control" 
                           value="{{ old('min_stock', $product->min_stock) }}" min="0" required>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Deskripsi / Keterangan</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>

            <!-- Is Active -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <span style="font-weight:600;">Produk Aktif (Tersedia untuk dijual di kasir)</span>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-light);">
                <a href="{{ route('products.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

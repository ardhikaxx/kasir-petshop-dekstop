@extends('layouts.app')

@section('title', 'Tambah Layanan Baru')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Tambah Layanan Baru</span>
        </h1>
        <p class="page-subtitle">Daftarkan paket grooming, mandi, atau jasa pet shop lainnya.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('services.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Formulir Layanan Jasa</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('services.store') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Kode Layanan</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $suggestedCode) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori Layanan</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Nama Layanan</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" 
                       placeholder="Contoh: Grooming Mandi Anti Kutu Kucing" required autofocus>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Tarif / Harga Jual (Rp)</label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', 50000) }}" min="0" step="500" required>
                    <span class="form-text">Biaya yang dikenakan ke pelanggan di kasir.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estimasi Biaya Dasar / HPP (Rp)</label>
                    <input type="number" name="cost_price" class="form-control" value="{{ old('cost_price', 0) }}" min="0" step="500">
                    <span class="form-text">Biaya shampo/obat kutu/upah per pengerjaan.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Estimasi Durasi Pengerjaan</label>
                <input type="text" name="estimated_duration" class="form-control" 
                       value="{{ old('estimated_duration') }}" placeholder="Contoh: 45 Menit, 1 Jam, 1 Hari">
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi / Rincian Perlakuan</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Rincian yang didapat (misal: potong kuku, bersihkan telinga, blow dry)...">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <span style="font-weight:600;">Layanan Aktif (Dapat dipilih di kasir POS)</span>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-light);">
                <a href="{{ route('services.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan Layanan</button>
            </div>
        </form>
    </div>
</div>
@endsection

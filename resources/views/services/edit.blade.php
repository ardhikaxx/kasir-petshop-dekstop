@extends('layouts.app')

@section('title', 'Edit Layanan - ' . $service->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            <span>Edit Layanan: {{ $service->name }}</span>
        </h1>
        <p class="page-subtitle">Perubahan tarif baru tidak akan mengubah riwayat struk lama.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('services.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Perbarui Data Layanan</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('services.update', $service) }}">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Kode Layanan</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $service->code) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori Layanan</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $service->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Nama Layanan</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $service->name) }}" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Tarif / Harga Jual (Rp)</label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', $service->price) }}" min="0" step="500" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estimasi Biaya Dasar / HPP (Rp)</label>
                    <input type="number" name="cost_price" class="form-control" value="{{ old('cost_price', $service->cost_price) }}" min="0" step="500">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Estimasi Durasi Pengerjaan</label>
                <input type="text" name="estimated_duration" class="form-control" 
                       value="{{ old('estimated_duration', $service->estimated_duration) }}">
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi / Rincian Perlakuan</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $service->description) }}</textarea>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                    <span style="font-weight:600;">Layanan Aktif (Dapat dipilih di kasir POS)</span>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-light);">
                <a href="{{ route('services.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

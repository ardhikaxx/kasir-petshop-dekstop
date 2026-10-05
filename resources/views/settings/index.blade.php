@extends('layouts.app')

@section('title', 'Pengaturan Toko')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            <span>Pengaturan Toko & Kasir</span>
        </h1>
        <p class="page-subtitle">Sesuaikan identitas pet shop, format struk printer thermal, dan konfigurasi pajak lokal.</p>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Konfigurasi Toko Lokal</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf

            <!-- Identitas Toko -->
            <div style="font-size:0.95rem;font-weight:700;color:var(--primary);margin-bottom:0.85rem;border-bottom:1px solid var(--border-light);padding-bottom:0.4rem;">
                Identitas Toko Pet Shop
            </div>

            <div class="form-group">
                <label class="form-label required">Nama Toko</label>
                <input type="text" name="store_name" class="form-control" 
                       value="{{ old('store_name', $settings['store_name'] ?? 'Pet Care & Shop') }}" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="store_phone" class="form-control" 
                           value="{{ old('store_phone', $settings['store_phone'] ?? '') }}" placeholder="0812-3456-7890">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Kontak</label>
                    <input type="email" name="store_email" class="form-control" 
                           value="{{ old('store_email', $settings['store_email'] ?? '') }}" placeholder="petshop@local.pos">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Lengkap Toko</label>
                <textarea name="store_address" class="form-control" rows="2">{{ old('store_address', $settings['store_address'] ?? '') }}</textarea>
            </div>

            <!-- Struk & Printer Thermal -->
            <div style="font-size:0.95rem;font-weight:700;color:var(--primary);margin:1.5rem 0 0.85rem 0;border-bottom:1px solid var(--border-light);padding-bottom:0.4rem;">
                Struk & Format Thermal Printer
            </div>

            <div class="form-group">
                <label class="form-label required">Ukuran Kertas Thermal Struk</label>
                <div style="display:flex;gap:1.5rem;">
                    <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
                        <input type="radio" name="receipt_paper_size" value="58mm" 
                               {{ ($settings['receipt_paper_size'] ?? '58mm') === '58mm' ? 'checked' : '' }}>
                        <span>58mm (Standar printer POS kecil / Bluetooth)</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
                        <input type="radio" name="receipt_paper_size" value="80mm" 
                               {{ ($settings['receipt_paper_size'] ?? '') === '80mm' ? 'checked' : '' }}>
                        <span>80mm (Printer thermal besar)</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Pesan Catatan Kaki Struk (Footer Nota)</label>
                <textarea name="receipt_footer" class="form-control" rows="3">{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}</textarea>
                <span class="form-text">Pesan ucapan terima kasih atau ketentuan garansi pada bagian bawah struk.</span>
            </div>

            <!-- Pajak & Mata Uang -->
            <div style="font-size:0.95rem;font-weight:700;color:var(--primary);margin:1.5rem 0 0.85rem 0;border-bottom:1px solid var(--border-light);padding-bottom:0.4rem;">
                Pajak Penjualan & Format Regional
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status Pajak Penjualan (PPN)</label>
                    <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;cursor:pointer;">
                        <input type="hidden" name="tax_enabled" value="0">
                        <input type="checkbox" name="tax_enabled" value="1" 
                               {{ ($settings['tax_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                        <span style="font-weight:600;">Aktifkan perhitungan pajak di kasir</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Persentase Pajak (%)</label>
                    <input type="number" name="tax_percentage" class="form-control" 
                           value="{{ old('tax_percentage', $settings['tax_percentage'] ?? 0) }}" min="0" max="100" step="0.1">
                    <span class="form-text">Isi 0 jika toko tidak memungut pajak.</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Simbol Mata Uang</label>
                    <input type="text" name="currency" class="form-control" 
                           value="{{ old('currency', $settings['currency'] ?? 'Rp') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Zona Waktu (Timezone)</label>
                    <input type="text" name="timezone" class="form-control" 
                           value="{{ old('timezone', $settings['timezone'] ?? 'Asia/Jakarta') }}" readonly>
                    <span class="form-text">Waktu Indonesia Barat (WIB).</span>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-light);">
                <button type="submit" class="btn btn-primary btn-lg">Simpan Pengaturan Toko</button>
            </div>
        </form>
    </div>
</div>
@endsection

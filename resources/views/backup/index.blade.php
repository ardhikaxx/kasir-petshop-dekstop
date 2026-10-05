@extends('layouts.app')

@section('title', 'Backup & Restore Data Lokal')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            <span>Pusat Backup & Restore Database Lokal</span>
        </h1>
        <p class="page-subtitle">Amankan database SQLite pet shop Anda secara mandiri ke komputer lokal tanpa bergantung pada cloud.</p>
    </div>

    <div class="page-actions">
        <form method="POST" action="{{ route('backup.create') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                </svg>
                <span>Buat Backup Database Sekarang</span>
            </button>
        </form>
    </div>
</div>

<!-- Database Info & Export Formats -->
<div style="display:grid;grid-template-columns: 1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
    <!-- Active Database Info -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Informasi Database Aktif</h2>
        </div>
        <div class="card-body">
            <div style="margin-bottom:0.75rem;">
                <div style="font-size:0.8rem;color:var(--text-muted);">Tipe Database</div>
                <div style="font-weight:700;font-size:1rem;">SQLite 3 (Local Embedded Database)</div>
            </div>
            <div style="margin-bottom:0.75rem;">
                <div style="font-size:0.8rem;color:var(--text-muted);">Lokasi File Database Aktif</div>
                <code style="word-break:break-all;font-size:0.8rem;background:var(--bg-subtle);padding:4px 8px;border-radius:4px;display:block;margin-top:2px;">
                    {{ $dbPath }}
                </code>
            </div>
            <div class="alert alert-info" style="font-size:0.825rem;margin-bottom:0;">
                Database SQLite ini menyimpan seluruh data master produk, transaksi kasir, histori mutasi stok, dan pengaturan toko.
            </div>
        </div>
    </div>

    <!-- Export Other Formats -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ekspor Cadangan Tambahan (JSON & CSV)</h2>
        </div>
        <div class="card-body">
            <p style="font-size:0.875rem;color:var(--text-muted);margin-bottom:1rem;">
                Selain backup file database SQLite utuh, Anda juga dapat mengunduh seluruh data dalam format teks ringan.
            </p>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <a href="{{ route('backup.export-json') }}" class="btn btn-outline" style="justify-content:flex-start;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
                    </svg>
                    <span>Unduh Cadangan Lengkap Format JSON (Semua Tabel)</span>
                </a>
                <a href="{{ route('backup.export-csv', ['type' => 'products']) }}" class="btn btn-outline" style="justify-content:flex-start;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    </svg>
                    <span>Ekspor Katalog Produk ke CSV (Excel)</span>
                </a>
                <a href="{{ route('backup.export-csv', ['type' => 'transactions']) }}" class="btn btn-outline" style="justify-content:flex-start;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    </svg>
                    <span>Ekspor Riwayat Transaksi ke CSV (Excel)</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Restore Database Section -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-header" style="background:var(--warning-light);">
        <h2 class="card-title" style="color:#78350f;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span>Restore / Pulihkan Database SQLite</span>
        </h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" 
              onsubmit="return confirm('PERINGATAN: Memulihkan database akan mengganti data saat ini dengan file yang Anda unggah. Sistem akan membuat backup pengaman otomatis sebelum proses dimulai. Lanjutkan?')">
            @csrf
            <div class="alert alert-warning" style="font-size:0.85rem;">
                <strong>Perhatian Keamanan:</strong> Sebelum proses restore diterapkan, sistem kasir akan secara otomatis membuat <em>backup pengaman cadangan</em> dari database aktif saat ini. File yang diunggah akan diverifikasi strukturnya terlebih dahulu agar aplikasi tidak rusak.
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label required">Pilih File Backup SQLite (.sqlite)</label>
                <input type="file" name="backup_file" accept=".sqlite" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-warning btn-lg">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                </svg>
                <span>Pulihkan Database Dari File</span>
            </button>
        </form>
    </div>
</div>

<!-- List of Available Local Backups -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Arsip Backup SQLite Lokal</h2>
    </div>
    <div class="card-body" style="padding:0;">
        @if (empty($backups))
            <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);">
                Belum ada file backup lokal yang tersimpan. Klik tombol <strong>"Buat Backup Database Sekarang"</strong> di atas.
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama File Backup</th>
                            <th>Tanggal & Jam Dibuat</th>
                            <th>Ukuran File</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $b)
                            <tr>
                                <td>
                                    <span style="font-family:monospace;font-weight:600;">{{ $b['filename'] }}</span>
                                </td>
                                <td>{{ $b['date'] }} WIB</td>
                                <td>{{ $b['size'] }}</td>
                                <td style="text-align:right;">
                                    <a href="{{ route('backup.download', ['filename' => $b['filename']]) }}" class="btn btn-outline btn-sm">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                        <span>Unduh File</span>
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
@endsection

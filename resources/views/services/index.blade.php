@extends('layouts.app')

@section('title', 'Layanan Pet Shop')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                <line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/>
                <line x1="8.12" y1="8.12" x2="12" y2="12"/>
            </svg>
            <span>Modul Layanan Jasa Pet Shop</span>
        </h1>
        <p class="page-subtitle">Kelola layanan non-fisik seperti salon/grooming, mandi, potong kuku, pet hotel, dan konsultasi.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('services.create') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Tambah Layanan Baru</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        @if ($services->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
                <p style="font-weight:600;font-size:1.05rem;">Belum ada layanan yang didaftarkan.</p>
                <p style="font-size:0.85rem;margin-top:0.35rem;">Silakan tambahkan layanan grooming atau penitipan pertama Anda.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Layanan</th>
                            <th>Nama Layanan</th>
                            <th>Kategori</th>
                            <th>Estimasi Durasi</th>
                            <th style="text-align:right;">Biaya Modal (HPP)</th>
                            <th style="text-align:right;">Tarif / Harga</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $srv)
                            <tr>
                                <td>
                                    <code style="font-weight:600;font-size:0.85rem;">{{ $srv->code }}</code>
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--text-main);">{{ $srv->name }}</div>
                                    @if ($srv->description)
                                        <div style="font-size:0.75rem;color:var(--text-muted);">{{ $srv->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $srv->category?->name ?? 'Layanan' }}</td>
                                <td>
                                    <span class="badge badge-secondary">{{ $srv->estimated_duration ?: '-' }}</span>
                                </td>
                                <td style="text-align:right;color:var(--text-muted);">
                                    Rp {{ number_format($srv->cost_price, 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-weight:700;color:var(--primary);font-size:1.05rem;">
                                    Rp {{ number_format($srv->price, 0, ',', '.') }}
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge {{ $srv->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $srv->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <div style="display:inline-flex;gap:0.35rem;">
                                        <a href="{{ route('services.edit', $srv) }}" class="btn btn-outline btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('services.toggle', $srv) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">
                                                {{ $srv->is_active ? 'Off' : 'On' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('services.destroy', $srv) }}" 
                                              onsubmit="return confirm('Hapus layanan [{{ $srv->name }}]? Histori transaksi lama tetap aman.')" 
                                              style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:1rem 1.25rem;">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

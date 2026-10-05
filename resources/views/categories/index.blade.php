@extends('layouts.app')

@section('title', 'Kategori Produk & Layanan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            <span>Manajemen Kategori</span>
        </h1>
        <p class="page-subtitle">Kelola kategori produk fisik dan kategori layanan pet shop.</p>
    </div>

    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="PetShop.openModal('modal-add-category')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Tambah Kategori</span>
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Slug</th>
                        <th>Tipe</th>
                        <th>Deskripsi</th>
                        <th style="text-align:center;">Jumlah Produk</th>
                        <th style="text-align:center;">Jumlah Layanan</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $cat)
                        <tr>
                            <td>
                                <span style="font-weight:700;color:var(--text-main);">{{ $cat->name }}</span>
                            </td>
                            <td>
                                <code style="font-size:0.8rem;background:var(--bg-subtle);padding:2px 6px;border-radius:4px;">{{ $cat->slug }}</code>
                            </td>
                            <td>
                                <span class="badge {{ $cat->type === 'product' ? 'badge-primary' : ($cat->type === 'service' ? 'badge-info' : 'badge-secondary') }}">
                                    {{ $cat->type === 'product' ? 'Produk Fisik' : ($cat->type === 'service' ? 'Layanan Jasa' : 'Keduanya') }}
                                </span>
                            </td>
                            <td>{{ $cat->description ?: '-' }}</td>
                            <td style="text-align:center;">
                                <span class="badge badge-pill badge-secondary">{{ $cat->products_count }}</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-pill badge-secondary">{{ $cat->services_count }}</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:0.35rem;">
                                    <button type="button" class="btn btn-outline btn-sm" 
                                            onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->slug }}', '{{ $cat->type }}', '{{ addslashes($cat->description ?? '') }}')">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('categories.destroy', $cat) }}" 
                                          onsubmit="return confirm('Hapus kategori [{{ $cat->name }}]? Produk terkait akan dipindahkan ke tanpa kategori.')" 
                                          style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:1rem 1.25rem;">
            {{ $categories->links() }}
        </div>
    </div>
</div>

<!-- MODAL: Add Category -->
<div class="modal-backdrop" id="modal-add-category">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('categories.store') }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Tambah Kategori Baru</h3>
                <button type="button" class="modal-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Nama Kategori</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Makanan Kucing, Grooming, dll" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label required">Tipe Kategori</label>
                    <select name="type" class="form-select" required>
                        <option value="product">Produk Fisik (Pakan, Aksesori, Pasir, dll)</option>
                        <option value="service">Layanan Jasa (Mandi, Grooming, Hotel)</option>
                        <option value="all">Keduanya (Produk & Layanan)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Keterangan singkat kategori..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Edit Category -->
<div class="modal-backdrop" id="modal-edit-category">
    <div class="modal-dialog">
        <form method="POST" id="form-edit-category" action="">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Edit Kategori</h3>
                <button type="button" class="modal-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Nama Kategori</label>
                    <input type="text" name="name" id="edit-cat-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Slug Kategori</label>
                    <input type="text" name="slug" id="edit-cat-slug" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Tipe Kategori</label>
                    <select name="type" id="edit-cat-type" class="form-select" required>
                        <option value="product">Produk Fisik</option>
                        <option value="service">Layanan Jasa</option>
                        <option value="all">Keduanya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="edit-cat-desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditCategoryModal(id, name, slug, type, desc) {
    const form = document.getElementById('form-edit-category');
    form.action = `/categories/${id}`;
    document.getElementById('edit-cat-name').value = name;
    document.getElementById('edit-cat-slug').value = slug;
    document.getElementById('edit-cat-type').value = type;
    document.getElementById('edit-cat-desc').value = desc;
    PetShop.openModal('modal-edit-category');
}
</script>
@endpush

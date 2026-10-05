@extends('layouts.app')

@section('title', 'Kasir POS')
@section('main_class', 'no-padding')

@section('content')
<div class="pos-layout">
    <!-- LEFT PANE: Search, Category Filter, and Product/Service Catalog -->
    <div class="pos-catalog-pane">
        <!-- Search & Barcode Scan Bar -->
        <div class="pos-search-bar">
            <!-- Barcode Input (F2 Focus) -->
            <div style="flex: 1; position: relative;">
                <div class="input-group">
                    <span class="input-group-text" style="background:#ffffff; border-right:none;" title="Tekan F2 untuk fokus scanner">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 5v4M3 5h4M21 5v4M21 5h-4M3 19v-4M3 19h4M21 19v-4M21 19h-4"/>
                            <line x1="7" y1="9" x2="7" y2="15"/><line x1="11" y1="9" x2="11" y2="15"/>
                            <line x1="14" y1="9" x2="14" y2="15"/><line x1="17" y1="9" x2="17" y2="15"/>
                        </svg>
                    </span>
                    <input type="text" id="pos-barcode-input" class="form-control" 
                           placeholder="Scan Barcode USB / Ketik Barcode lalu [Enter] (F2)" 
                           autocomplete="off" style="border-left:none;" autofocus>
                </div>
            </div>

            <!-- Offline Camera Scan Button -->
            <button type="button" class="btn btn-outline" onclick="PosEngine.openCameraScanner()" title="Buka Kamera Barcode Scanner">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <span>Kamera</span>
            </button>

            <!-- Search by Name / SKU -->
            <div style="flex: 1.2;">
                <input type="text" id="pos-search-input" class="form-control" 
                       placeholder="Cari nama produk, SKU, atau layanan...">
            </div>
        </div>

        <!-- Category & Type Tabs -->
        <div class="pos-category-tabs">
            <button type="button" class="category-tab-btn active" data-category="all" data-type="all">Semua Item</button>
            <button type="button" class="category-tab-btn" data-category="all" data-type="product">Hanya Produk Fisik</button>
            <button type="button" class="category-tab-btn" data-category="all" data-type="service">Hanya Layanan Jasa</button>
            
            @foreach ($categories as $cat)
                <button type="button" class="category-tab-btn" data-category="{{ $cat->id }}" data-type="{{ $cat->type }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        <!-- Catalog Items Grid -->
        <div class="pos-items-grid" id="pos-items-grid">
            <!-- Dynamically populated by pos-engine.js -->
        </div>
    </div>

    <!-- RIGHT PANE: Cart & Checkout Summary -->
    <div class="pos-cart-pane">
        <!-- Cart Header -->
        <div class="pos-cart-header">
            <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;font-size:1.05rem;">
                <svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span>Keranjang Transaksi</span>
                <span class="badge badge-primary badge-pill" id="cart-items-count">0</span>
            </div>

            <button type="button" class="btn btn-outline btn-sm" onclick="PosEngine.clearCart()" title="Kosongkan keranjang (F8)">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                <span>Hapus (F8)</span>
            </button>
        </div>

        <!-- Cart Items Scrollable List -->
        <div class="pos-cart-items-wrapper" id="pos-cart-items">
            <!-- Populated dynamically by pos-engine.js -->
        </div>

        <!-- Cart Totals & Discount/Tax Calculation Summary -->
        <div class="pos-cart-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <strong id="summary-subtotal">Rp 0</strong>
            </div>

            <div class="summary-row" id="summary-discount-row" style="display: none; color: var(--danger);">
                <span>Diskon Toko</span>
                <strong id="summary-discount">-Rp 0</strong>
            </div>

            @if (!empty($settings['tax_enabled']) && $settings['tax_enabled'] === '1' && (float)$settings['tax_percentage'] > 0)
                <div class="summary-row">
                    <span>Pajak ({{ $settings['tax_percentage'] }}%)</span>
                    <strong id="summary-tax">Rp 0</strong>
                </div>
            @endif

            <div class="summary-row grand-total">
                <span>TOTAL AKHIR</span>
                <span id="summary-grand-total">Rp 0</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pos-cart-actions">
            <div style="display:flex;gap:0.5rem;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="PosEngine.openDiscountModal()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
                    </svg>
                    <span>Atur Diskon</span>
                </button>
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="PetShop.openModal('modal-customer-info')">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Data Hewan / Pelanggan</span>
                </button>
            </div>

            <button type="button" class="btn btn-primary btn-lg btn-block" id="btn-pos-pay" onclick="PosEngine.openCheckoutModal()" disabled>
                <span>Bayar Transaksi</span>
                <kbd style="font-size:10px;background:rgba(255,255,255,0.2);padding:2px 5px;border-radius:3px;">F4</kbd>
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Discount Config -->
<div class="modal-backdrop" id="modal-discount">
    <div class="modal-dialog modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Atur Diskon Transaksi</h3>
            <button type="button" class="modal-close" data-dismiss="modal">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Tipe Diskon</label>
                <select id="discount-type-select" class="form-select">
                    <option value="none">Tidak Ada Diskon</option>
                    <option value="percent">Persentase (%)</option>
                    <option value="fixed">Nominal Tetap (Rp)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nilai Diskon</label>
                <input type="number" id="discount-val-input" class="form-control" placeholder="Contoh: 10 untuk 10% atau 15000" min="0">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" id="btn-apply-discount">Terapkan Diskon</button>
        </div>
    </div>
</div>

<!-- MODAL: Customer & Pet Information -->
<div class="modal-backdrop" id="modal-customer-info">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Informasi Pelanggan & Hewan (Opsional)</h3>
            <button type="button" class="modal-close" data-dismiss="modal">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1rem;">
                Data ini akan tercetak pada struk pembayaran (sangat cocok untuk layanan grooming dan penitipan hewan).
            </p>
            <div class="form-group">
                <label class="form-label">Nama Pemilik / Pelanggan</label>
                <input type="text" id="checkout-customer-name" class="form-control" placeholder="Contoh: Kak Sarah">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Hewan (Pet Name)</label>
                    <input type="text" id="checkout-pet-name" class="form-control" placeholder="Contoh: Milo">
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis Hewan</label>
                    <input type="text" id="checkout-pet-type" class="form-control" placeholder="Contoh: Kucing Persia, Anjing Poodle">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Tambahan</label>
                <textarea id="checkout-notes" class="form-control" rows="2" placeholder="Catatan khusus transaksi..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal">Simpan & Lanjutkan</button>
        </div>
    </div>
</div>

<!-- MODAL: Checkout & Payment Calculator -->
<div class="modal-backdrop" id="modal-checkout">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Pembayaran Kasir</h3>
            <button type="button" class="modal-close" data-dismiss="modal">✕</button>
        </div>
        <div class="modal-body">
            <!-- Grand Total Highlight -->
            <div style="background:var(--primary-subtle);border:1px solid var(--primary-light);padding:1rem;border-radius:var(--radius-md);text-align:center;margin-bottom:1.25rem;">
                <div style="font-size:0.85rem;font-weight:600;color:var(--primary-dark);text-transform:uppercase;">Total Tagihan</div>
                <div style="font-size:2rem;font-weight:800;color:var(--primary);" id="checkout-modal-grand-total">Rp 0</div>
            </div>

            <!-- Payment Method Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Metode Pembayaran</label>
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:0.5rem;">
                    <label style="border:1px solid var(--border);border-radius:var(--radius-md);padding:0.75rem 0.5rem;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                        <input type="radio" name="payment_method_radio" value="cash" id="pay-method-cash" checked>
                        <span style="font-weight:600;margin-top:0.25rem;">Tunai</span>
                    </label>
                    <label style="border:1px solid var(--border);border-radius:var(--radius-md);padding:0.75rem 0.5rem;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                        <input type="radio" name="payment_method_radio" value="qris">
                        <span style="font-weight:600;margin-top:0.25rem;">QRIS</span>
                    </label>
                    <label style="border:1px solid var(--border);border-radius:var(--radius-md);padding:0.75rem 0.5rem;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                        <input type="radio" name="payment_method_radio" value="transfer">
                        <span style="font-weight:600;margin-top:0.25rem;">Transfer Bank</span>
                    </label>
                </div>
            </div>

            <!-- Cash Payment Calculation Section -->
            <div id="checkout-cash-section">
                <div class="form-group">
                    <label class="form-label">Uang Tunai Diterima (Rp)</label>
                    <input type="number" id="pos-cash-input" class="form-control form-control-lg" 
                           placeholder="0" min="0" step="1000" style="font-weight:700;font-size:1.3rem;">
                    
                    <!-- Quick Money Chips -->
                    <div class="quick-money-grid">
                        <button type="button" class="quick-money-btn" onclick="PosEngine.setQuickMoney('exact')">Uang Pas</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.setQuickMoney(20000)">20.000</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.setQuickMoney(50000)">50.000</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.setQuickMoney(100000)">100.000</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.setQuickMoney(200000)">200.000</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.addQuickMoney(10000)">+10.000</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.addQuickMoney(50000)">+50.000</button>
                        <button type="button" class="quick-money-btn" onclick="PosEngine.addQuickMoney(100000)">+100.000</button>
                    </div>
                </div>

                <!-- Change Calculation Result -->
                <div style="background:#ffffff;border:1px solid var(--border);padding:0.85rem;border-radius:var(--radius-md);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:600;font-size:1rem;">Uang Kembalian:</span>
                    <span style="font-size:1.4rem;font-weight:800;color:var(--success);" id="checkout-change-amount">Rp 0</span>
                </div>

                <div id="checkout-payment-warning" class="alert alert-danger" style="margin-top:0.75rem;display:none;">
                    Uang pembayaran masih kurang.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-dismiss="modal">Kembali</button>
            <button type="button" class="btn btn-success btn-lg" id="btn-confirm-checkout">
                Selesaikan Transaksi & Cetak Struk
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Receipt & Transaction Success View -->
<div class="modal-backdrop" id="modal-receipt-success">
    <div class="modal-dialog">
        <div class="modal-header" style="background:var(--success-light);border-bottom-color:rgba(22,163,74,0.2);">
            <h3 class="modal-title" style="color:#14532d;">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>Transaksi Berhasil Disimpan!</span>
            </h3>
            <button type="button" class="modal-close" data-dismiss="modal">✕</button>
        </div>
        <div class="modal-body" style="background:#f8fafc;">
            <!-- Big Change Alert -->
            <div style="background:#ffffff;border:1px solid var(--border-light);padding:0.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:600;">Kembalian Kasir:</span>
                <span style="font-size:1.3rem;font-weight:800;color:var(--success);" id="receipt-change-display">Rp 0</span>
            </div>

            <!-- Thermal Receipt Preview -->
            <div id="receipt-modal-content" style="max-height:360px;overflow-y:auto;background:#ffffff;padding:0.5rem;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);">
                <!-- Populated dynamically with thermal receipt html -->
            </div>
        </div>
        <div class="modal-footer" style="justify-content:space-between;">
            <div>
                <button type="button" class="btn btn-outline" onclick="PosEngine.copyReceiptText()" title="Salin format teks struk ke clipboard">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    <span>Salin Teks Struk</span>
                </button>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="PosEngine.printReceipt()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    <span>Cetak Struk Thermal</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Offline Camera Barcode Scanner -->
<div class="modal-backdrop" id="modal-camera-scanner">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Scan Barcode Menggunakan Kamera</h3>
            <button type="button" class="modal-close" onclick="PosEngine.closeCameraScanner()">✕</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <video id="camera-scanner-video" style="width:100%;max-height:280px;background:#000000;border-radius:var(--radius-md);" autoplay playsinline></video>
            <div id="camera-scanner-msg" style="margin-top:0.5rem;">
                <p style="font-size:0.85rem;color:var(--text-muted);">Arahkan barcode produk ke kamera. Jika barcode terdeteksi, produk akan otomatis dimasukkan ke keranjang.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="PosEngine.closeCameraScanner()">Tutup Kamera</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pos-engine.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Prepare initial catalog items for 100% offline client-side reactivity
        const products = @json($products).map(p => ({
            id: p.id,
            type: 'product',
            name: p.name,
            sku: p.sku,
            barcode: p.barcode,
            unit: p.unit,
            price: Number(p.selling_price),
            stock: p.stock,
            min_stock: p.min_stock,
            category_id: p.category_id
        }));

        const services = @json($services).map(s => ({
            id: s.id,
            type: 'service',
            name: s.name,
            sku: s.code,
            barcode: null,
            unit: 'layanan',
            price: Number(s.price),
            stock: 999999,
            min_stock: 0,
            category_id: s.category_id,
            estimated_duration: s.estimated_duration
        }));

        const allItems = [...products, ...services];

        PosEngine.init({
            taxEnabled: {{ (!empty($settings['tax_enabled']) && $settings['tax_enabled'] === '1') ? 'true' : 'false' }},
            taxRate: {{ !empty($settings['tax_percentage']) ? (float)$settings['tax_percentage'] : 0 }},
            initialItems: allItems
        });
    });
</script>
@endpush

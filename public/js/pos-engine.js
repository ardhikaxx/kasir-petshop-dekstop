/**
 * Pet Shop Offline Desktop POS - Cart & Cashier Engine
 * 100% Offline - Zero External CDN Dependencies
 */

window.PosEngine = (function() {
  'use strict';

  // State
  let cart = [];
  let discount = { type: 'none', value: 0 };
  let taxRate = 0; // percentage
  let taxEnabled = false;
  let paymentMethod = 'cash';
  let paymentAmount = 0;
  let lastTransaction = null;
  let activeCategory = 'all';
  let activeTabType = 'all'; // 'all', 'product', 'service'
  let rawItems = []; // Initial list of products & services

  // Initialize POS
  function init(config) {
    taxEnabled = !!config.taxEnabled;
    taxRate = Number(config.taxRate) || 0;
    rawItems = config.initialItems || [];

    bindEvents();
    renderCatalog(rawItems);
    renderCart();
  }

  function bindEvents() {
    // Search input
    const searchInput = document.getElementById('pos-search-input');
    if (searchInput) {
      searchInput.addEventListener('input', debounce(handleSearch, 200));
    }

    // Barcode input (Enter key for USB scanner or manual)
    const barcodeInput = document.getElementById('pos-barcode-input');
    if (barcodeInput) {
      barcodeInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          handleBarcodeScan(barcodeInput.value.trim());
        }
      });
    }

    // Category Tabs
    document.querySelectorAll('.category-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.category-tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeCategory = btn.dataset.category || 'all';
        activeTabType = btn.dataset.type || 'all';
        handleSearch();
      });
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
      // F2: Focus Barcode / Search
      if (e.key === 'F2') {
        e.preventDefault();
        const bInput = document.getElementById('pos-barcode-input');
        if (bInput) bInput.focus();
      }
      // F4: Checkout
      if (e.key === 'F4') {
        e.preventDefault();
        openCheckoutModal();
      }
      // F8: Clear cart
      if (e.key === 'F8') {
        e.preventDefault();
        clearCart();
      }
    });

    // Payment method selector
    document.querySelectorAll('input[name="payment_method_radio"]').forEach(radio => {
      radio.addEventListener('change', (e) => {
        paymentMethod = e.target.value;
        handlePaymentMethodChange();
      });
    });

    // Payment amount input
    const cashInput = document.getElementById('pos-cash-input');
    if (cashInput) {
      cashInput.addEventListener('input', (e) => {
        paymentAmount = PetShop.parseRupiah(e.target.value);
        updateChangePreview();
      });
    }

    // Discount inputs inside discount modal
    const applyDiscountBtn = document.getElementById('btn-apply-discount');
    if (applyDiscountBtn) {
      applyDiscountBtn.addEventListener('click', applyDiscountFromModal);
    }

    // Submit checkout
    const submitBtn = document.getElementById('btn-confirm-checkout');
    if (submitBtn) {
      submitBtn.addEventListener('click', submitCheckout);
    }
  }

  // Barcode Scan Handler
  async function handleBarcodeScan(barcode) {
    if (!barcode) return;
    const barcodeInput = document.getElementById('pos-barcode-input');

    try {
      const res = await fetch(`/pos/search?barcode=${encodeURIComponent(barcode)}`);
      const data = await res.json();

      if (data.exact_match) {
        addItem(data.exact_match);
        PetShop.toast(`Ditambahkan: ${data.exact_match.name}`, 'success', 2000);
        if (barcodeInput) {
          barcodeInput.value = '';
          barcodeInput.focus();
        }
      } else {
        PetShop.toast(`Barcode [${barcode}] tidak ditemukan`, 'warning');
      }
    } catch (err) {
      // Offline fallback: search in loaded items
      const localMatch = rawItems.find(item => item.barcode && item.barcode.trim() === barcode);
      if (localMatch) {
        addItem(localMatch);
        PetShop.toast(`Ditambahkan: ${localMatch.name}`, 'success', 2000);
        if (barcodeInput) barcodeInput.value = '';
      } else {
        PetShop.toast(`Barcode [${barcode}] tidak ditemukan`, 'warning');
      }
    }
  }

  // Search & Filter
  function handleSearch() {
    const q = (document.getElementById('pos-search-input')?.value || '').toLowerCase().trim();

    let filtered = rawItems.filter(item => {
      // Category filter
      if (activeCategory !== 'all' && String(item.category_id) !== String(activeCategory)) {
        return false;
      }
      // Type filter
      if (activeTabType !== 'all' && item.type !== activeTabType) {
        return false;
      }
      // Search term
      if (q) {
        const nameMatch = (item.name || '').toLowerCase().includes(q);
        const skuMatch = (item.sku || '').toLowerCase().includes(q);
        const barcodeMatch = (item.barcode || '').toLowerCase().includes(q);
        return nameMatch || skuMatch || barcodeMatch;
      }
      return true;
    });

    renderCatalog(filtered);
  }

  // Render Catalog Grid
  function renderCatalog(items) {
    const grid = document.getElementById('pos-items-grid');
    if (!grid) return;

    if (items.length === 0) {
      grid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #94a3b8;">
          <p style="font-size: 1.1rem; font-weight: 600;">Tidak ada produk atau layanan ditemukan</p>
          <p style="font-size: 0.85rem; margin-top: 0.25rem;">Coba cari dengan kata kunci lain atau pilih kategori Semua.</p>
        </div>
      `;
      return;
    }

    grid.innerHTML = items.map(item => {
      const isProduct = item.type === 'product';
      const isOutOfStock = isProduct && item.stock <= 0;
      const stockBadge = isProduct 
        ? (item.stock <= 0 
            ? '<span class="badge badge-danger">Habis</span>' 
            : (item.stock <= (item.min_stock || 5) 
                ? `<span class="badge badge-warning">Sisa ${item.stock}</span>` 
                : `<span class="badge badge-success">Stok ${item.stock}</span>`))
        : '<span class="badge badge-info">Layanan</span>';

      return `
        <div class="pos-item-card ${isOutOfStock ? 'out-of-stock' : ''}" 
             onclick="PosEngine.addItemById(${item.id}, '${item.type}')">
          <div class="pos-item-header">
            <span class="pos-item-sku">${item.sku || ''}</span>
            ${stockBadge}
          </div>
          <div class="pos-item-name" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
          <div class="pos-item-footer">
            <span class="pos-item-price">${PetShop.formatRupiah(item.price)}</span>
            <span class="pos-item-stock">${isProduct ? item.unit || 'pcs' : (item.estimated_duration || 'Jasa')}</span>
          </div>
        </div>
      `;
    }).join('');
  }

  // Add Item to Cart
  function addItemById(id, type) {
    const item = rawItems.find(i => i.id === id && i.type === type);
    if (item) addItem(item);
  }

  function addItem(item) {
    const existingIndex = cart.findIndex(i => i.id === item.id && i.type === item.type);

    if (existingIndex > -1) {
      const current = cart[existingIndex];
      // Stock check for physical product
      if (item.type === 'product' && current.quantity + 1 > item.stock) {
        PetShop.toast(`Stok [${item.name}] tidak mencukupi (Tersedia: ${item.stock})`, 'error');
        return;
      }
      current.quantity += 1;
    } else {
      // Initial stock check
      if (item.type === 'product' && item.stock <= 0) {
        PetShop.toast(`Stok produk [${item.name}] sudah habis!`, 'error');
        return;
      }
      cart.push({
        id: item.id,
        type: item.type,
        name: item.name,
        sku: item.sku,
        unit: item.unit,
        price: Number(item.price),
        maxStock: item.type === 'product' ? item.stock : 999999,
        quantity: 1
      });
    }

    renderCart();
  }

  function updateQty(index, newQty) {
    newQty = parseInt(newQty, 10);
    if (isNaN(newQty) || newQty <= 0) {
      removeItem(index);
      return;
    }

    const item = cart[index];
    if (item.type === 'product' && newQty > item.maxStock) {
      PetShop.toast(`Stok [${item.name}] hanya tersedia ${item.maxStock}`, 'warning');
      item.quantity = item.maxStock;
    } else {
      item.quantity = newQty;
    }

    renderCart();
  }

  function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
  }

  function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Kosongkan semua item di keranjang?')) {
      cart = [];
      discount = { type: 'none', value: 0 };
      renderCart();
      PetShop.toast('Keranjang belanja telah dikosongkan', 'info');
    }
  }

  // Calculations
  function calculateTotals() {
    let subtotal = 0;
    cart.forEach(item => {
      subtotal += item.price * item.quantity;
    });

    let discountAmount = 0;
    if (discount.type === 'percent') {
      discountAmount = Math.round((subtotal * Math.min(100, discount.value)) / 100);
    } else if (discount.type === 'fixed') {
      discountAmount = Math.min(subtotal, discount.value);
    }

    const afterDiscount = Math.max(0, subtotal - discountAmount);

    let taxAmount = 0;
    if (taxEnabled && taxRate > 0) {
      taxAmount = Math.round((afterDiscount * taxRate) / 100);
    }

    const grandTotal = afterDiscount + taxAmount;

    return {
      subtotal,
      discountAmount,
      taxAmount,
      grandTotal
    };
  }

  // Render Cart View
  function renderCart() {
    const listWrapper = document.getElementById('pos-cart-items');
    const badgeCount = document.getElementById('cart-items-count');
    const totals = calculateTotals();

    const totalQty = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (badgeCount) badgeCount.textContent = totalQty;

    if (cart.length === 0) {
      if (listWrapper) {
        listWrapper.innerHTML = `
          <div class="cart-empty-state">
            <svg width="48" height="48" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24">
              <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <p style="font-weight: 600; margin-top: 0.5rem; font-size: 0.95rem;">Keranjang Masih Kosong</p>
            <p style="font-size: 0.8rem; margin-top: 0.2rem;">Pilih produk atau scan barcode untuk menambahkan transaksi</p>
          </div>
        `;
      }
    } else {
      if (listWrapper) {
        listWrapper.innerHTML = cart.map((item, idx) => `
          <div class="cart-item-row">
            <div class="cart-item-info">
              <div class="cart-item-name">${escapeHtml(item.name)}</div>
              <div class="cart-item-subtext">
                ${PetShop.formatRupiah(item.price)} ${item.type === 'service' ? '<span class="badge badge-info" style="font-size:10px;">Jasa</span>' : ''}
              </div>
            </div>
            <div class="cart-qty-ctrl">
              <button class="cart-qty-btn" onclick="PosEngine.updateQty(${idx}, ${item.quantity - 1})">-</button>
              <input type="text" class="cart-qty-input" value="${item.quantity}" 
                     onchange="PosEngine.updateQty(${idx}, this.value)" />
              <button class="cart-qty-btn" onclick="PosEngine.updateQty(${idx}, ${item.quantity + 1})">+</button>
            </div>
            <div class="cart-item-total">
              ${PetShop.formatRupiah(item.price * item.quantity)}
              <div style="margin-top:2px;">
                <button style="border:none;background:none;color:#dc2626;cursor:pointer;font-size:11px;" 
                        onclick="PosEngine.removeItem(${idx})">Hapus</button>
              </div>
            </div>
          </div>
        `).join('');
      }
    }

    // Update Totals Summary
    const elSubtotal = document.getElementById('summary-subtotal');
    const elDiscount = document.getElementById('summary-discount');
    const elDiscountRow = document.getElementById('summary-discount-row');
    const elTax = document.getElementById('summary-tax');
    const elGrandTotal = document.getElementById('summary-grand-total');
    const btnPay = document.getElementById('btn-pos-pay');

    if (elSubtotal) elSubtotal.textContent = PetShop.formatRupiah(totals.subtotal);
    if (elDiscount) elDiscount.textContent = '-' + PetShop.formatRupiah(totals.discountAmount);
    if (elDiscountRow) elDiscountRow.style.display = totals.discountAmount > 0 ? 'flex' : 'none';
    if (elTax) elTax.textContent = PetShop.formatRupiah(totals.taxAmount);
    if (elGrandTotal) elGrandTotal.textContent = PetShop.formatRupiah(totals.grandTotal);

    if (btnPay) {
      btnPay.disabled = cart.length === 0;
      btnPay.innerHTML = `Bayar (${PetShop.formatRupiah(totals.grandTotal)}) <kbd style="font-size:10px;background:rgba(255,255,255,0.2);padding:2px 4px;border-radius:3px;">F4</kbd>`;
    }
  }

  // Discount Modal Handlers
  function openDiscountModal() {
    const totals = calculateTotals();
    if (totals.subtotal <= 0) {
      PetShop.toast('Masukkan item terlebih dahulu sebelum mengatur diskon', 'warning');
      return;
    }
    document.getElementById('discount-type-select').value = discount.type;
    document.getElementById('discount-val-input').value = discount.value || '';
    PetShop.openModal('modal-discount');
  }

  function applyDiscountFromModal() {
    const type = document.getElementById('discount-type-select').value;
    const val = Number(document.getElementById('discount-val-input').value) || 0;

    if (val < 0) {
      PetShop.toast('Nilai diskon tidak boleh negatif', 'error');
      return;
    }

    discount = { type, value: val };
    PetShop.closeModal('modal-discount');
    renderCart();
    PetShop.toast('Diskon berhasil diterapkan', 'success');
  }

  // Checkout Modal
  function openCheckoutModal() {
    if (cart.length === 0) {
      PetShop.toast('Keranjang belanja kasir masih kosong!', 'warning');
      return;
    }

    const totals = calculateTotals();
    document.getElementById('checkout-modal-grand-total').textContent = PetShop.formatRupiah(totals.grandTotal);

    paymentMethod = 'cash';
    document.getElementById('pay-method-cash').checked = true;
    handlePaymentMethodChange();

    // Default cash amount = exact grand total
    paymentAmount = totals.grandTotal;
    const cashInput = document.getElementById('pos-cash-input');
    if (cashInput) cashInput.value = totals.grandTotal;

    updateChangePreview();
    PetShop.openModal('modal-checkout');
  }

  function handlePaymentMethodChange() {
    const cashSection = document.getElementById('checkout-cash-section');
    const totals = calculateTotals();

    if (paymentMethod === 'cash') {
      if (cashSection) cashSection.style.display = 'block';
      paymentAmount = totals.grandTotal;
      const cashInput = document.getElementById('pos-cash-input');
      if (cashInput) cashInput.value = totals.grandTotal;
    } else {
      if (cashSection) cashSection.style.display = 'none';
      paymentAmount = totals.grandTotal;
    }
    updateChangePreview();
  }

  function setQuickMoney(amount) {
    const totals = calculateTotals();
    if (amount === 'exact') {
      paymentAmount = totals.grandTotal;
    } else {
      paymentAmount = Number(amount);
    }

    const cashInput = document.getElementById('pos-cash-input');
    if (cashInput) cashInput.value = paymentAmount;
    updateChangePreview();
  }

  function addQuickMoney(amount) {
    paymentAmount += Number(amount);
    const cashInput = document.getElementById('pos-cash-input');
    if (cashInput) cashInput.value = paymentAmount;
    updateChangePreview();
  }

  function updateChangePreview() {
    const totals = calculateTotals();
    const changePreview = document.getElementById('checkout-change-amount');
    const warningText = document.getElementById('checkout-payment-warning');
    const submitBtn = document.getElementById('btn-confirm-checkout');

    if (paymentMethod === 'cash') {
      const change = paymentAmount - totals.grandTotal;
      if (changePreview) changePreview.textContent = PetShop.formatRupiah(Math.max(0, change));

      if (change < 0) {
        if (warningText) {
          warningText.style.display = 'block';
          warningText.textContent = `Uang pembayaran kurang ${PetShop.formatRupiah(Math.abs(change))}`;
        }
        if (submitBtn) submitBtn.disabled = true;
      } else {
        if (warningText) warningText.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
      }
    } else {
      if (changePreview) changePreview.textContent = PetShop.formatRupiah(0);
      if (warningText) warningText.style.display = 'none';
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  // Submit Checkout to Backend
  async function submitCheckout() {
    const totals = calculateTotals();
    const submitBtn = document.getElementById('btn-confirm-checkout');

    if (paymentMethod === 'cash' && paymentAmount < totals.grandTotal) {
      PetShop.toast('Uang pembayaran masih kurang!', 'error');
      return;
    }

    const payload = {
      items: cart.map(item => ({
        id: item.id,
        type: item.type,
        quantity: item.quantity
      })),
      discount_type: discount.type,
      discount_value: discount.value,
      payment_method: paymentMethod,
      payment_amount: paymentAmount,
      customer_name: document.getElementById('checkout-customer-name')?.value || null,
      pet_name: document.getElementById('checkout-pet-name')?.value || null,
      pet_type: document.getElementById('checkout-pet-type')?.value || null,
      notes: document.getElementById('checkout-notes')?.value || null,
    };

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Memproses Transaksi...';
    }

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch('/pos/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken || ''
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.success) {
        lastTransaction = data;
        PetShop.closeModal('modal-checkout');
        
        // Show receipt success modal
        document.getElementById('receipt-modal-content').innerHTML = data.receipt_html;
        document.getElementById('receipt-change-display').textContent = PetShop.formatRupiah(data.change_amount);
        PetShop.openModal('modal-receipt-success');

        // Reset cart
        cart = [];
        discount = { type: 'none', value: 0 };
        renderCart();

        // Update local stock cache
        payload.items.forEach(pItem => {
          if (pItem.type === 'product') {
            const prod = rawItems.find(i => i.id === pItem.id && i.type === 'product');
            if (prod) prod.stock -= pItem.quantity;
          }
        });
        handleSearch();

        PetShop.toast(`Transaksi #${data.transaction_number} sukses disimpan!`, 'success');
      } else {
        PetShop.toast(data.message || 'Transaksi gagal diproses', 'error');
      }
    } catch (err) {
      PetShop.toast('Terjadi gangguan jaringan lokal: ' + err.message, 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Konfirmasi & Selesaikan Transaksi';
      }
    }
  }

  // Camera Barcode Scanner (Offline HTML5 / BarcodeDetector)
  let videoStream = null;

  async function openCameraScanner() {
    PetShop.openModal('modal-camera-scanner');
    const video = document.getElementById('camera-scanner-video');
    const msg = document.getElementById('camera-scanner-msg');

    if (!('BarcodeDetector' in window)) {
      if (msg) {
        msg.innerHTML = `
          <div class="alert alert-warning" style="margin:1rem 0;">
            <strong>Catatan Desktop:</strong> Engine browser perangkat ini tidak memiliki native BarcodeDetector API.<br>
            Silakan gunakan <strong>Scanner Barcode USB</strong> (langsung scan di kolom barcode) atau ketik barcode secara manual.
          </div>
        `;
      }
      return;
    }

    try {
      videoStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
      });
      if (video) {
        video.srcObject = videoStream;
        video.play();
        scanVideoFrame(video);
      }
    } catch (err) {
      if (msg) {
        msg.innerHTML = `
          <div class="alert alert-danger" style="margin:1rem 0;">
            Kamera tidak dapat diakses (${err.message}).<br>
            Gunakan scanner barcode USB fisik atau input manual barcode.
          </div>
        `;
      }
    }
  }

  async function scanVideoFrame(video) {
    if (!videoStream || video.paused || video.ended) return;

    try {
      const barcodeDetector = new window.BarcodeDetector({
        formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'qr_code']
      });
      const barcodes = await barcodeDetector.detect(video);
      if (barcodes.length > 0) {
        const detectedCode = barcodes[0].rawValue;
        closeCameraScanner();
        handleBarcodeScan(detectedCode);
        return;
      }
    } catch (e) {
      // Continue next frame
    }

    requestAnimationFrame(() => scanVideoFrame(video));
  }

  function closeCameraScanner() {
    if (videoStream) {
      videoStream.getTracks().forEach(track => track.stop());
      videoStream = null;
    }
    PetShop.closeModal('modal-camera-scanner');
  }

  // Helpers
  function debounce(func, wait) {
    let timeout;
    return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  return {
    init,
    addItemById,
    updateQty,
    removeItem,
    clearCart,
    openDiscountModal,
    openCheckoutModal,
    setQuickMoney,
    addQuickMoney,
    openCameraScanner,
    closeCameraScanner,
    copyReceiptText: () => {
      if (lastTransaction && lastTransaction.receipt_text) {
        PetShop.copyToClipboard(lastTransaction.receipt_text);
      }
    },
    printReceipt: () => {
      window.print();
    }
  };
})();

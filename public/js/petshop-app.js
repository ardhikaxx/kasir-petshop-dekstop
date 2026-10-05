/**
 * Pet Shop Offline Desktop POS - Core Local JavaScript Helpers
 * 100% Offline - Zero CDN, Zero Remote Calls
 */

window.PetShop = (function() {
  'use strict';

  // 1. Toast Notification System
  function ensureToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  function toast(message, type = 'info', duration = 3500) {
    const container = ensureToastContainer();
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    
    // Choose icon
    let iconSvg = '';
    if (type === 'success') {
      iconSvg = '<svg width="18" height="18" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>';
    } else if (type === 'error') {
      iconSvg = '<svg width="18" height="18" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
    } else if (type === 'warning') {
      iconSvg = '<svg width="18" height="18" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
    } else {
      iconSvg = '<svg width="18" height="18" fill="none" stroke="#0284c7" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>';
    }

    el.innerHTML = `
      <div style="display:flex;align-items:center;gap:0.5rem;">
        ${iconSvg}
        <span>${message}</span>
      </div>
      <button style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:16px;line-height:1;" onclick="this.parentElement.remove()">✕</button>
    `;

    container.appendChild(el);

    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(8px)';
      setTimeout(() => el.remove(), 250);
    }, duration);
  }

  // 2. Modal Controller
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
      // Auto-focus first input if any
      const input = modal.querySelector('input:not([type=hidden]), select, textarea, button.btn-primary');
      if (input) setTimeout(() => input.focus(), 50);
    }
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('show');
      if (!document.querySelector('.modal-backdrop.show')) {
        document.body.style.overflow = '';
      }
    }
  }

  // 3. Currency / Number Formatter (Indonesian Rupiah)
  function formatRupiah(amount) {
    const num = Math.round(Number(amount) || 0);
    return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function parseRupiah(str) {
    if (typeof str === 'number') return str;
    const clean = String(str).replace(/[^0-9]/g, '');
    return parseInt(clean, 10) || 0;
  }

  // 4. Copy to Clipboard
  async function copyToClipboard(text) {
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
      } else {
        // Fallback for non-https local desktop
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
      }
      toast('Teks struk berhasil disalin ke clipboard!', 'success');
      return true;
    } catch (err) {
      toast('Gagal menyalin teks: ' + err.message, 'error');
      return false;
    }
  }

  // 5. Print Receipt Trigger
  function printReceipt() {
    window.print();
  }

  // Global listeners for modals and shortcuts
  document.addEventListener('DOMContentLoaded', () => {
    // Backdrop click closes modal
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.classList.remove('show');
          document.body.style.overflow = '';
        }
      });
    });

    // Close buttons inside modals
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal-backdrop');
        if (modal) {
          modal.classList.remove('show');
          document.body.style.overflow = '';
        }
      });
    });

    // Escape key closes modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal-backdrop.show');
        openModals.forEach(m => m.classList.remove('show'));
        document.body.style.overflow = '';
      }
    });
  });

  return {
    toast,
    openModal,
    closeModal,
    formatRupiah,
    parseRupiah,
    copyToClipboard,
    printReceipt
  };
})();

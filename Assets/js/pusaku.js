/**
 * Pusaku UI - komponen JavaScript vanilla.
 *
 * Kenapa tidak pakai Bootstrap JS?
 *  - Tailwind tidak menyediakan perilaku (hanya tampilan), jadi modal,
 *    dropdown, dan pencarian tabel tetap perlu ditulis manual.
 *  - Dua framework (Bootstrap JS + Tailwind) menghasilkan perilaku yang
 *    saling bertabrakan, terutama pada modal.
 *
 * API (dipakai lewat atribut data-attribute, tanpa perlu init manual):
 *
 *   <div data-modal-open="modalBuku">        -> buka modal
 *   <button data-modal-close>                 -> tutup modal
 *   <div data-modal="modalBuku">              -> elemen modal
 *   <button data-dropdown-toggle="menuFilter">-> buka/tutup dropdown
 *   <div  data-dropdown="menuFilter">         -> elemen dropdown
 *   <input data-table-search="#tabelBuku">    -> filter baris tabel
 *   <button data-copy="#id">                  -> salin teks
 *   <button data-confirm="Yakin?" url="...">  -> konfirmasi lalu navigasi
 *   <button data-toggle-password="#password"> -> lihat/sembunyikan sandi
 *
 * Helper global (dipakai dari <script> inline halaman):
 *   Pusaku.toast('Pesan', 'success')          -> notifikasi mengambang
 *   Pusaku.confirm({...})                     -> dialog konfirmasi kustom
 *   Pusaku.loadInto('#modalContent', 'url')   -> isi container via fetch
 *   Pusaku.modal.open('idModal')              -> buka/tutup modal
 *   Pusaku.mulaiLoading(btn) / .stopLoading(btn) -> spinner tombol manual
 *
 * Otomatis: begitu form dikirim, tombol submit-nya dapat spinner
 * (lihat "Spinner tombol"). Tambahkan data-no-loading pada <form> untuk
 * mengecualikannya (mis. form pencarian/filter).
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------
  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), ' +
                    'select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  /** Kunci scroll body dan simpan elemen yang sedang fokus. */
  let scrollLockCount = 0;
  let lastFocused = null;

  function lockScroll() {
    scrollLockCount++;
    if (scrollLockCount === 1) {
      const lebarBar = window.innerWidth - document.documentElement.clientWidth;
      document.body.style.overflow = 'hidden';
      if (lebarBar > 0) document.body.style.paddingRight = lebarBar + 'px';
    }
  }

  function unlockScroll() {
    scrollLockCount = Math.max(0, scrollLockCount - 1);
    if (scrollLockCount === 0) {
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    }
  }

  // ---------------------------------------------------------------------
  // Modal
  // ---------------------------------------------------------------------
  const Modal = {
    open(target) {
      const el = typeof target === 'string' ? document.getElementById(target) : target;
      if (!el) return;

      lastFocused = document.activeElement;
      el.classList.remove('hidden');
      el.classList.add('flex');
      lockScroll();

      requestAnimationFrame(() => {
        el.classList.add('is-open');
        const first = el.querySelector('[data-autofocus]') || el.querySelector(FOCUSABLE);
        first?.focus();
      });

      el.dispatchEvent(new CustomEvent('modal:open', { detail: { id: el.id || null } }));
    },

    close(id) {
      const el = typeof id === 'string' ? document.getElementById(id) : id;
      if (!el) return;

      el.classList.remove('is-open');
      unlockScroll();

      setTimeout(() => {
        el.classList.add('hidden');
        el.classList.remove('flex');
      }, 200);

      lastFocused?.focus?.();
      el.dispatchEvent(new CustomEvent('modal:close'));
    },

    /** Tutup semua modal yang sedang terbuka. */
    closeAll() {
      $$('[data-modal].is-open').forEach((m) => Modal.close(m));
    }
  };

  // Delegasi event: cukup satu listener untuk seluruh dokumen.
  document.addEventListener('click', (e) => {
    // Buka
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      e.preventDefault();
      Modal.open(opener.dataset.modalOpen);
      return;
    }

    // Tutup
    const closer = e.target.closest('[data-modal-close]');
    if (closer) {
      e.preventDefault();
      Modal.close(closer.dataset.modalClose || closer.closest('[data-modal]'));
      return;
    }

    // Klik backdrop
    if (e.target.matches('[data-modal-backdrop]')) {
      Modal.close(e.target.closest('[data-modal]'));
      return;
    }

    // Dropdown
    const ddToggle = e.target.closest('[data-dropdown-toggle]');
    if (ddToggle) {
      e.preventDefault();
      e.stopPropagation();
      const dd = document.getElementById(ddToggle.dataset.dropdownToggle);
      if (dd) dd.classList.toggle('hidden');
      return;
    }

    // Klik di luar dropdown -> tutup
    if (!e.target.closest('[data-dropdown]')) {
      $$('[data-dropdown]').forEach((d) => d.classList.add('hidden'));
    }
  });

  // ESC + focus trap
  document.addEventListener('keydown', (e) => {
    const aktif = $$('[data-modal].is-open');
    if (!aktif.length) return;

    if (e.key === 'Escape') {
      e.preventDefault();
      Modal.close(aktif[aktif.length - 1]);
      return;
    }

    if (e.key !== 'Tab') return;

    const modal = aktif[aktif.length - 1];
    const items = $$(FOCUSABLE, modal).filter((el) => el.offsetParent !== null);
    if (!items.length) return;

    const first = items[0];
    const last  = items[items.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });

  // ---------------------------------------------------------------------
  // Pencarian tabel (client-side)
  // ---------------------------------------------------------------------
  document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-table-search]');
    if (!input) return;

    const tabel = $(input.dataset.tableSearch);
    if (!tabel) return;

    const q     = input.value.trim().toLowerCase();
    const baris = $$('tbody tr', tabel);
    let tampil  = 0;

    baris.forEach((tr) => {
      // Baris "kosong" (colspan) tidak ikut dihitung.
      if (tr.children.length === 1) { tr.classList.add('hidden'); return; }

      const cocok = q === '' || tr.textContent.toLowerCase().includes(q);
      tr.classList.toggle('hidden', !cocok);
      if (cocok) tampil++;
    });

    const info = $(input.dataset.tableSearch + '-info') ||
                 $('[data-search-info-for="' + input.dataset.tableSearch + '"]');
    if (info) {
      info.textContent = q === ''
        ? baris.filter((t) => t.children.length > 1).length + ' baris'
        : tampil + ' baris cocok';
    }
  });

  // ---------------------------------------------------------------------
  // Konfirmasi sebelum navigasi / submit
  // Memakai dialog kustom (bukan window.confirm) agar konsisten.
  // ---------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;

    // Beri tanda agar handler ini tidak berjalan dua kali.
    if (btn.dataset.confirmBusy === '1') return;
    btn.dataset.confirmBusy = '1';

    e.preventDefault();

    konfirmasi({
      judul: btn.dataset.confirmTitle || 'Konfirmasi',
      pesan: btn.dataset.confirm,
      labelYa: btn.dataset.confirmYes || 'Ya, lanjutkan',
      tone: btn.dataset.confirmTone === 'primary' ? 'primary' : 'danger',
    }).then((ya) => {
      if (!ya) { btn.dataset.confirmBusy = ''; return; }

      if (btn.dataset.url) {
        window.location.href = btn.dataset.url;
        return;
      }
      // Tombol submit di dalam form -> jalankan submit aslinya.
      const form = btn.closest('form');
      if (form) {
        form.submit();
        return;
      }
      // Fallback: elemen non-form dipaksa mengikuti tautan.
      btn.dataset.confirmBusy = '';
    });
  });

  // ---------------------------------------------------------------------
  // Lihat / sembunyikan password
  // ---------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;

    const input = $(btn.dataset.togglePassword);
    if (!input) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-label', show ? 'Sembunyikan sandi' : 'Tampilkan sandi');
    btn.innerHTML = show ? ICON.eyeOff : ICON.eye;
  });

  const ICON = {
    eye: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    eyeOff: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>'
  };

  // ---------------------------------------------------------------------
  // Sidebar (mobile + desktop)
  // ---------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-sidebar-toggle]');
    if (toggle) {
      e.preventDefault();
      const sidebar = $('[data-sidebar]');
      const overlay = $('[data-sidebar-overlay]');
      sidebar?.classList.toggle('-translate-x-full');
      overlay?.classList.toggle('hidden');
      return;
    }

    if (e.target.closest('[data-sidebar-overlay]')) {
      $('[data-sidebar]')?.classList.add('-translate-x-full');
      $('[data-sidebar-overlay]')?.classList.add('hidden');
    }
  });

  // Tutup sidebar otomatis saat pindah halaman di mobile
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
      $('[data-sidebar]')?.classList.remove('-translate-x-full');
      $('[data-sidebar-overlay]')?.classList.add('hidden');
    }
  });

  // ---------------------------------------------------------------------
  // State awal
  // Modal dan dropdown selalu dalam keadaan tertutup saat halaman dimuat.
  // Dibiarkan begitu saja, markup akan tampil dalam keadaan "terbuka"
  // sampai pengguna mengeklik toggle untuk pertama kali.
  // ---------------------------------------------------------------------
  function initAwal() {
    $$('[data-dropdown]').forEach((d) => d.classList.add('hidden'));
    $$('[data-modal]').forEach((m) => {
      m.classList.add('hidden');
      m.classList.remove('flex');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAwal);
  } else {
    initAwal();
  }

  // ---------------------------------------------------------------------
  // Toast - notifikasi mengambang (pengganti alert JS bawaan browser)
  // ---------------------------------------------------------------------
  let toastStack = null;

  function toastStackEl() {
    if (!toastStack || !document.body.contains(toastStack)) {
      toastStack = document.createElement('div');
      toastStack.className = 'toast-stack';
      toastStack.setAttribute('role', 'status');
      toastStack.setAttribute('aria-live', 'polite');
      document.body.appendChild(toastStack);
    }
    return toastStack;
  }

  const TOAST_ICON = {
    success: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    danger:  '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>',
    info:    '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>',
  };
  const TOAST_TONE = {
    success: 'text-emerald-600',
    danger:  'text-rose-600',
    info:    'text-brand-600',
  };

  function toast(pesan, jenis = 'info', durasi = 4000) {
    const el = document.createElement('div');
    el.className = 'toast toast-' + jenis;
    el.innerHTML =
      '<svg class="mt-0.5 h-5 w-5 shrink-0 ' + (TOAST_TONE[jenis] || TOAST_TONE.info) + '" fill="none" ' +
      'viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">' +
      (TOAST_ICON[jenis] || TOAST_ICON.info) + '</svg>' +
      '<p class="flex-1 font-medium leading-relaxed"></p>' +
      '<button type="button" class="shrink-0 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" ' +
      'aria-label="Tutup notifikasi">' +
      '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
      '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>';

    el.querySelector('p').textContent = pesan;

    const tutup = () => {
      el.classList.remove('is-visible');
      setTimeout(() => el.remove(), 300);
    };
    el.querySelector('button').addEventListener('click', tutup);

    toastStackEl().appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-visible'));
    setTimeout(tutup, durasi);
  }

  // ---------------------------------------------------------------------
  // Dialog konfirmasi kustom (pengganti dialog bawaan browser yang
  // tampilannya tidak konsisten antar browser)
  // ---------------------------------------------------------------------
  function konfirmasi({
    judul = 'Konfirmasi',
    pesan = 'Apakah Anda yakin?',
    labelYa = 'Ya, lanjutkan',
    labelBatal = 'Batal',
    tone = 'danger',
    onYa = null,
  } = {}) {
    return new Promise((resolve) => {
      const warna = tone === 'danger'
        ? { bg: 'bg-rose-600 hover:bg-rose-700', ring: 'ring-rose-600/20', icon: 'text-rose-600 bg-rose-50', path: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>' }
        : { bg: 'bg-brand-600 hover:bg-brand-700', ring: 'ring-brand-600/20', icon: 'text-brand-600 bg-brand-50', path: '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>' };

      const root = document.createElement('div');
      root.setAttribute('data-modal', '');
      root.setAttribute('role', 'dialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-label', judul);
      root.className = 'modal-root flex';
      root.innerHTML =
        '<div class="modal-backdrop" data-modal-backdrop></div>' +
        '<div class="modal-panel !max-w-md !translate-y-0 !opacity-100 !rounded-3xl">' +
          '<div class="p-6">' +
            '<div class="flex h-12 w-12 items-center justify-center rounded-2xl ' + warna.icon + '">' +
              '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">' +
              warna.path + '</svg>' +
            '</div>' +
            '<h2 class="mt-4 text-lg font-bold tracking-tight text-slate-900"></h2>' +
            '<p class="mt-1.5 text-sm leading-relaxed text-slate-600"></p>' +
          '</div>' +
          '<div class="modal-footer">' +
            '<button type="button" data-batal class="btn-secondary btn-sm"></button>' +
            '<button type="button" data-ya class="btn btn-sm ' + warna.bg + '"></button>' +
          '</div>' +
        '</div>';

      root.querySelector('h2').textContent = judul;
      root.querySelector('p').textContent = pesan;
      root.querySelector('[data-batal]').textContent = labelBatal;
      root.querySelector('[data-ya]').textContent = labelYa;

      let sudah = false;

      // `tutup` menutup UI; `settle` menyelesaikan Promise.
      // Dipisah supaya ESC/backdrop (yang memicu `modal:close`) tidak
      // memanggil Modal.close() dua kali dan merusak scroll lock.
      const tutup = () => Modal.close(root);

      const settle = (ya) => {
        if (sudah) return;
        sudah = true;
        resolve(ya);
        if (ya && typeof onYa === 'function') onYa();
      };

      root.querySelector('[data-ya]').addEventListener('click', () => { tutup(); settle(true); });
      root.querySelector('[data-batal]').addEventListener('click', () => { tutup(); settle(false); });

      // ESC / klik backdrop ditangani listener global pusaku.js.
      root.addEventListener('modal:close', () => settle(false));

      document.body.appendChild(root);
      Modal.open(root);
    });
  }

  // ---------------------------------------------------------------------
  // Muat HTML dari endpoint AJAX ke dalam sebuah container.
  // Menghemat boilerplate fetch() yang sama di banyak halaman.
  // ---------------------------------------------------------------------
  const SKELETON_LOAD =
    '<div class="flex items-center justify-center gap-3 px-6 py-20 text-slate-400">' +
    '<svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">' +
    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>' +
    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>' +
    '<span class="text-sm">Memuat data...</span></div>';

  async function loadInto(selector, url, pesanGagal = 'Gagal memuat data.') {
    const box = typeof selector === 'string' ? $(selector) : selector;
    if (!box) return null;

    box.innerHTML = SKELETON_LOAD;
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const html = await res.text();
      box.innerHTML = html;
      return html;
    } catch (err) {
      box.innerHTML =
        '<div class="flex flex-col items-center gap-3 px-6 py-16 text-center">' +
        '<p class="text-sm font-medium text-rose-600">' + pesanGagal + '</p>' +
        '<button type="button" data-retry class="btn-secondary btn-sm">Coba lagi</button></div>';
      const retry = box.querySelector('[data-retry]');
      if (retry) retry.addEventListener('click', () => loadInto(box, url, pesanGagal));
      return null;
    }
  }

  // ---------------------------------------------------------------------
  // Spinner tombol
  //
  // Diterapkan otomatis ke tombol submit mana pun begitu form-nya dikirim,
  // jadi tombol "Simpan"/"Tambah"/"Hapus" di seluruh aplikasi tidak perlu
  // menulis markup spinner sendiri. Cukup satu delegation listener di
  // document, sehingga ikut berlaku untuk form yang baru di-insert lewat
  // Pusaku.loadInto (isi modal).
  // ---------------------------------------------------------------------
  const SPINNER_SVG =
    '<svg class="btn-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
    '<circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>' +
    '<path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 019 9h-3a6 6 0 00-6-6V3z"/></svg>';

  // Form yang sengaja tanpa spinner (filter/pencarian GET).
  function bolehLoading(form) {
    return form.dataset.loading !== 'off' && !form.hasAttribute('data-no-loading');
  }

  function tombolSubmit(form) {
    return form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
  }

  function mulaiLoading(btn) {
    if (!btn || btn.dataset.loadingAktif === '1') return;

    btn.dataset.loadingAktif = '1';
    // Simpan isi asli supaya bisa dikembalikan lagi, mis. kalau halaman
    // dikembalikan browser dari cache atau form gagal divalidasi.
    btn.dataset.labelAsli = btn.dataset.labelAsli || btn.innerHTML;
    btn.insertAdjacentHTML('afterbegin', SPINNER_SVG);
    btn.classList.add('btn-loading');
    btn.setAttribute('aria-busy', 'true');
    // Menonaktifkan tombol mencegah klik ganda. Tidak ada tombol submit di
    // aplikasi ini yang punya atribut name, jadi tidak ada nilai yang hilang
    // dari data form.
    btn.disabled = true;
  }

  function stopLoading(btn) {
    if (!btn || btn.dataset.loadingAktif !== '1') return;

    btn.classList.remove('btn-loading');
    btn.removeAttribute('aria-busy');
    btn.querySelector('.btn-spinner')?.remove();

    if (btn.dataset.labelAsli) btn.innerHTML = btn.dataset.labelAsli;
    delete btn.dataset.loadingAktif;
    delete btn.dataset.labelAsli;
    btn.disabled = false;
  }

  document.addEventListener(
    'submit',
    function (ev) {
      const form = ev.target;
      if (!(form instanceof HTMLFormElement) || !bolehLoading(form)) return;

      // Penjaga kedua: tombol yang dinonaktifkan masih bisa ditembus oleh
      // tombol Enter bila form punya lebih dari satu tombol submit.
      if (form.dataset.sudahKirim === '1') {
        ev.preventDefault();
        return;
      }
      form.dataset.sudahKirim = '1';

      // Submit via Enter tidak mengisi ev.submitter; fallback ke tombol pertama.
      mulaiLoading(ev.submitter || tombolSubmit(form));
    },
    true // fase capture, jalan sebelum handler lain
  );

  // Halaman yang dikembalikan dari cache browser (tombol Back) masih
  // menampilkan tombol dalam keadaan loading; kembalikan ke normal.
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-loading-aktif="1"]').forEach(stopLoading);
  });


  // ---------------------------------------------------------------------
  // Ekspor
  // ---------------------------------------------------------------------
  window.Pusaku = {
    Modal,
    openModal: Modal.open,
    closeModal: Modal.close,
    toast,
    confirm: konfirmasi,
    loadInto,
    mulaiLoading,
    stopLoading,
    $,
    $$,
  };
})();

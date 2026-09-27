/**
 * Halaman login: spinner di tombol + salam otomatis (SweetAlert2).
 *
 * Alur:
 *  1. Submit dicegat, tombol diganti jadi spinner lalu dinonaktifkan.
 *  2. Form dikirim lewat fetch, bukan navigasi, supaya halaman tidak
 *     berpindah sebelum salam sempat tampil.
 *  3. Berhasil -> Swal "Selamat datang" -> redirect ke dashboard.
 *     Gagal    -> Swal error, tombol dikembalikan ke keadaan semula.
 *
 * Progressive enhancement: preventDefault baru dipasang bila form, tombol,
 * dan Swal semuanya ada. Kalau JavaScript atau SweetAlert2 gagal dimuat,
 * form tetap POST seperti biasa dan halaman login yang menanganinya.
 */
(function () {
  'use strict';

  const form   = document.querySelector('form[data-login-form]');
  const tombol = document.querySelector('[data-login-submit]');

  if (!form || !tombol || typeof window.Swal === 'undefined') return;

  /** Isi tombol saat normal, disimpan supaya bisa dikembalikan utuh. */
  const ISI_AWAL = tombol.innerHTML;

  const SPINNER =
    '<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">' +
      '<circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>' +
      '<path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>' +
    '</svg><span>Memproses…</span>';

  /** Nama yang perlu disembunyikan selama proses agar tidak terkirim ulang. */
  const INPUTS = Array.from(form.querySelectorAll('input, select, textarea'));

  /**
   * Preferensi ringan di localStorage.
   *
   * Ini murni kenyamanan tampilan, BUKAN otentikasi: token aslinya tetap
   * HttpOnly di cookie server. Isinya sengaja tidak pernah menyimpan sandi.
   */
  const SIMPAN = {
    ingat: 'pusaku:ingat',
    identitas: 'pusaku:identitas',
  };

  const inputIdentitas = form.querySelector('input[name="identifier"]');
  const inputSandi     = form.querySelector('input[name="password"]');
  const checkIngat     = form.querySelector('input[name="ingat"]');

  /** Pull localStorage bisa ditolak browser (mode privat), jadi dibungkus. */
  function baca(kunci) {
    try {
      return window.localStorage.getItem(kunci);
    } catch (e) {
      return null;
    }
  }
  function tulis(kunci, nilai) {
    try {
      window.localStorage.setItem(kunci, nilai);
    } catch (e) {
      /* diabaikan: prefill cuma bonus */
    }
  }

  // Pulihkan pilihan "Ingat saya" dari kunjungan sebelumnya.
  if (checkIngat) {
    const tersimpan = baca(SIMPAN.ingat);
    if (tersimpan !== null) checkIngat.checked = tersimpan === '1';

    checkIngat.addEventListener('change', () => {
      tulis(SIMPAN.ingat, checkIngat.checked ? '1' : '0');
    });
  }

  // Isi ulang username, supaya yang tidak memakai "ingat" tetap hemat ketikan.
  if (inputIdentitas && inputIdentitas.value === '') {
    const terakhir = baca(SIMPAN.identitas);
    if (terakhir) inputIdentitas.value = terakhir;
  }

  /** Lama minimum spinner tampil, supaya tidak berkedip saat server cepat. */
  const SPINNER_MIN_MS = 600;

  /** SweetAlert2 bawaan biru; samakan dengan aksen indigo aplikasi. */
  const GAYA = `
    .swal2-popup { border-radius: 1rem; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .swal2-title { font-weight: 800; letter-spacing: -0.02em; }
    .swal2-confirm, .swal2-cancel {
      border-radius: .625rem; font-weight: 600; padding: .625rem 1.25rem;
      box-shadow: none; background: #4f46e5;
    }
    .swal2-confirm:focus-visible { box-shadow: 0 0 0 3px rgba(99,102,241,.35); }
    .swal2-icon-success { border-top-color: #10b981; }
    .swal2-x-mark { color: #e11d48; }
  `;

  const style = document.createElement('style');
  style.textContent = GAYA;
  document.head.appendChild(style);

  let sedangProses = false;

  function kunci() {
    sedangProses = true;
    tombol.disabled = true;
    tombol.innerHTML = SPINNER;
    INPUTS.forEach((el) => { el.disabled = true; });
  }

  function bukaKunci() {
    sedangProses = false;
    tombol.disabled = false;
    tombol.innerHTML = ISI_AWAL;
    INPUTS.forEach((el) => { el.disabled = false; });
  }

  const tunggu = (ms) => new Promise((selesai) => setTimeout(selesai, ms));

  form.addEventListener('submit', async (event) => {
    // Kirim ulang saat sudah diproses? Cegah, supaya tidak ada dua login.
    if (sedangProses) {
      event.preventDefault();
      return;
    }

    event.preventDefault();

    // Form punya `novalidate`, jadi validasi harus dipanggil manual.
    if (!form.reportValidity()) return;

    const data = new FormData(form);
    data.append('ajax', '1');

    kunci();
    const mulai = performance.now();

    try {
      const respons = await fetch(form.getAttribute('action') || window.location.href, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      // Server bisa balas HTML (mis. fatal error), jadi json() di dalam try.
      const hasil = await respons.json();

      const sisa = SPINNER_MIN_MS - (performance.now() - mulai);
      if (sisa > 0) await tunggu(sisa);

      if (!hasil.ok) {
        bukaKunci();
        Swal.fire({
          icon: 'error',
          title: 'Gagal masuk',
          text: hasil.pesan || 'Username/NIM atau password salah.',
          confirmButtonText: 'Coba lagi',
        });

        const sandi = inputSandi;
        if (sandi) {
          sandi.value = '';
          sandi.focus();
        }
        return;
      }

      // Tombol sengaja tetap terkunci: form tidak boleh dikirim ulang
      // selama salam terbuka.
      //
      // Ingat username-nya supaya form berikutnya tidak kosong. Yang
      // disimpan hanya identifier (username/NIM), tidak pernah sandi.
      if (inputIdentitas && inputIdentitas.value) {
        tulis(SIMPAN.identitas, inputIdentitas.value);
      }

      Swal.fire({
        icon: 'success',
        title: 'Selamat datang' + (hasil.nama ? ', ' + hasil.nama : '') + '!',
        html: 'Anda berhasil masuk sebagai <strong>' + (hasil.peran || 'pengguna') + '</strong>.'
              + (hasil.ingat
                  ? ' Perangkat ini akan mengingat login Anda selama 30 hari.'
                  : ''),
        confirmButtonText: 'Lanjut ke Dashboard',
        timer: hasil.ingat ? 3200 : 2600,
        timerProgressBar: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
      }).then(() => {
        window.location.href = hasil.redirect;
      });
    } catch (err) {
      bukaKunci();
      Swal.fire({
        icon: 'warning',
        title: 'Tidak dapat menghubungi server',
        text: 'Periksa koneksi Anda lalu coba lagi.',
        confirmButtonText: 'Tutup',
      });
    }
  });
})();

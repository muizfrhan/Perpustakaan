<?php
/**
 * Pemrosesan pengembalian buku.
 *
 * Rewrite dari versi lama. Perbedaan penting:
 *  - Auth guard + CSRF (versi lama bisa diakses siapa saja)
 *  - Dihitung lewat DendaService, bukan magic number
 *  - id_petugas diambil dari SESSION, bukan dari POST (anti pemalsuan)
 *  - Seluruh proses dibungkus transaksi database
 *  - Anti return-ganda ditangani database (uq_pengembalian_pinjam)
 *  - Pesan error tidak lagi membocorkan exception ke user
 *  - Semua nilai dari form di-escape
 *
 * Halaman ini juga dipakai sebagai fragment AJAX (modal "Tambah Pengembalian"
 * di pengembalian.php). Karena itu layout shell hanya disertakan untuk
 * permintaan normal; untuk XHHR hanya dikirim markup self-contained.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

use App\Services\KondisiBuku;
use App\Services\DendaService;
use App\Services\KodeTransaksi;

require_admin();

$denda = new DendaService($conn);
$kode  = new KodeTransaksi($conn);

/** Pesan error untuk ditampilkan, di-set oleh blok di bawah. */
$error = null;

/** Simulasi denda ketika petugas mengetik kode peminjaman (AJAX). */
if (isset($_GET['cek'])) {
    header('Content-Type: application/json; charset=utf-8');

    $kodeCari = trim((string) ($_GET['cek'] ?? ''));

    if ($kodeCari === '') {
        echo json_encode(['ok' => false, 'pesan' => 'Kode peminjaman kosong.']);
        exit;
    }

    $stmt = $conn->prepare(
        'SELECT p.kode_pinjam, p.estimasi_pinjam, p.tgl_pinjam, a.nama, b.judul_buku
         FROM peminjaman p
         JOIN anggota a ON a.nim = p.nim
         JOIN buku   b ON b.kode_buku = p.kode_buku
         LEFT JOIN pengembalian g ON g.kode_pinjam = p.kode_pinjam
         WHERE p.kode_pinjam = :kode AND g.kode_pinjam IS NULL'
    );
    $stmt->execute([':kode' => $kodeCari]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        echo json_encode(['ok' => false, 'pesan' => 'Peminjaman aktif tidak ditemukan.']);
        exit;
    }

    try {
        $proyeksi = $denda->proyeksi($p['estimasi_pinjam'], KondisiBuku::Bagus);
        $sisaHari = $denda->sisaHari($p['estimasi_pinjam']);

        echo json_encode([
            'ok'            => true,
            'kode_pinjam'   => $p['kode_pinjam'],
            'nama'          => $p['nama'],
            'judul_buku'    => $p['judul_buku'],
            'tgl_pinjam'    => date('d/m/Y', strtotime($p['tgl_pinjam'])),
            'estimasi'      => date('d/m/Y', strtotime($p['estimasi_pinjam'])),
            'sisa_hari'     => $sisaHari,
            'terlambat'     => $proyeksi->terlambat,
            'hari_terlambat' => $proyeksi->hariTerlambat,
            'denda_preview' => $proyeksi->formatRupiah(),
            'tarif_harian'  => number_format($proyeksi->tarifPerHari, 0, ',', '.'),
        ], JSON_UNESCAPED_UNICODE);
    } catch (RuntimeException $ex) {
        echo json_encode(['ok' => false, 'pesan' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// ---------------------------------------------------------------------------
// Simpan pengembalian
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kodePinjam  = trim((string) ($_POST['kode_pinjam'] ?? ''));
    $kondisi     = KondisiBuku::fromInput($_POST['kondisi_buku'] ?? 'Bagus');
    $idPetugas   = (int) ($_SESSION['id_petugas'] ?? 0);

    try {
        $conn->beginTransaction();

        // Lock baris peminjaman supaya tidak ada dua proses return bersamaan.
        $stmt = $conn->prepare(
            'SELECT p.kode_pinjam, p.kode_buku, p.estimasi_pinjam, b.judul_buku
             FROM peminjaman p
             JOIN buku b ON b.kode_buku = p.kode_buku
             WHERE p.kode_pinjam = :kode
             FOR UPDATE'
        );
        $stmt->execute([':kode' => $kodePinjam]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$p) {
            throw new RuntimeException('Kode peminjaman tidak ditemukan.');
        }

        // Anti return-ganda: dicek di aplikasi, ditegakkan juga oleh UNIQUE index.
        $sudahKembali = $conn->prepare(
            'SELECT kode_kembali FROM pengembalian WHERE kode_pinjam = :kode LIMIT 1'
        );
        $sudahKembali->execute([':kode' => $kodePinjam]);

        if ($sudahKembali->fetch()) {
            throw new RuntimeException('Peminjaman ini sudah pernah dikembalikan.');
        }

        $tglKembali = date('Y-m-d');
        $hasil      = $denda->hitung($p['estimasi_pinjam'], $tglKembali, $kondisi);

        $kodeKembali = $kode->berikutnya('kode_kembali');

        $status = $hasil->total() > 0 ? 'Belum Lunas' : 'Lunas';

        $stmt = $conn->prepare(
            'INSERT INTO pengembalian
                (kode_kembali, kode_pinjam, tgl_kembali, kondisi_buku, denda,
                 status, pembayaran, hari_terlambat, denda_per_hari,
                 dasar_hitung, grace_hari, id_petugas)
             VALUES (:kk, :kp, :tk, :kb, :denda, :status, :bayar, :hari,
                     :per_hari, :dasar, :grace, :petugas)'
        );

        $stmt->execute([
            ':kk'       => $kodeKembali,
            ':kp'       => $kodePinjam,
            ':tk'       => $tglKembali . ' ' . date('H:i:s'),
            ':kb'       => $kondisi->value,
            ':denda'    => $hasil->total(),
            ':status'   => $status,
            ':bayar'    => 'Tidak Ada',
            ':hari'     => $hasil->hariTerlambat,
            ':per_hari' => $hasil->tarifPerHari,
            ':dasar'    => $hasil->dasarHitung,
            ':grace'    => $hasil->graceHari,
            ':petugas'  => $idPetugas > 0 ? $idPetugas : null,
        ]);

        // Stok naik kembali, kecuali buku dinyatakan hilang.
        if ($kondisi !== KondisiBuku::Hilang) {
            $conn->prepare('UPDATE buku SET stok = stok + 1 WHERE kode_buku = :kode')
                 ->execute([':kode' => $p['kode_buku']]);
        }

        // Tandai peminjaman selesai + simpan rincian denda.
        $conn->prepare(
            "UPDATE peminjaman
             SET status = 'Dikembalikan',
                 tgl_kembali_aktual = :tk,
                 hari_terlambat = :hari,
                 denda_terlambat = :denda,
                 denda_kondisi = :denda_kondisi
             WHERE kode_pinjam = :kp"
        )->execute([
            ':tk'            => $tglKembali,
            ':hari'          => $hasil->hariTerlambat,
            ':denda'         => $hasil->dendaTerlambat,
            ':denda_kondisi' => $hasil->dendaKondisi,
            ':kp'            => $kodePinjam,
        ]);

        $conn->commit();

        audit(
            'pengembalian.tambah',
            'pengembalian',
            $kodeKembali,
            sprintf(
                'pinjam=%s kondisi=%s terlambat=%dh dend=%s',
                $kodePinjam,
                $kondisi->value,
                $hasil->hariTerlambat,
                $hasil->formatRupiah()
            )
        );

        flash(
            'success',
            sprintf(
                'Pengembalian berhasil. Kode %s. %s%s',
                $kodeKembali,
                $hasil->ringkas(),
                $hasil->total() > 0 ? ' (belum lunas)' : ''
            )
        );

        header('Location: pengembalian.php');
        exit;
    } catch (RuntimeException $ex) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $ex->getMessage();
    } catch (Throwable $ex) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        // Detail hanya ke log server, tidak ke browser.
        error_log('Pengembalian gagal: ' . $ex->getMessage());
        audit('pengembalian.gagal', 'pengembalian', $kodePinjam, $ex->getMessage());

        $error = 'Terjadi kesalahan saat menyimpan. Silakan coba lagi.';
    }
}

// ---------------------------------------------------------------------------
// Tampilan
//
// PENTING: include header harus SETELAH semua proses di atas selesai.
// Menginclude-nya lebih awal akan mengirim header HTTP sebelum
// http_response_code() dipanggil, sehingga status 419/403 ikut hilang.
// ---------------------------------------------------------------------------

// Permintaan XHR (modal di pengembalian.php) tidak boleh memakai shell.
$fragment = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

// Tarif harian, dibaca untuk ditampilkan di halaman.
$tarif = $conn->query("SELECT nominal FROM aturan_denda WHERE kode_aturan = 'TERLAMBAT'")->fetchColumn();
$tarif = $tarif === false ? 0 : (int) round((float) $tarif);

if (!$fragment) {
    $menuAktif = 'pengembalian';
    include '../Layouts/header.php';
}

$flash = ambil_flash();

$peminjamanAktif = $conn->query(
    "SELECT p.kode_pinjam, p.estimasi_pinjam, a.nama, b.judul_buku
     FROM peminjaman p
     JOIN anggota a ON a.nim = p.nim
     JOIN buku b ON b.kode_buku = p.kode_buku
     LEFT JOIN pengembalian g ON g.kode_pinjam = p.kode_pinjam
     WHERE p.status = 'Dipinjam' AND g.kode_pinjam IS NULL
     ORDER BY p.estimasi_pinjam ASC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Pengembalian Buku</h1>
    <p class="text-muted mt-1">
      Denda keterlambatan saat ini
      <strong class="font-semibold text-slate-700"><?= e('Rp' . number_format($tarif, 0, ',', '.')) ?> per hari</strong>
    </p>
  </div>
</section>

<?php if ($flash): ?>
  <div class="alert-<?= e($flash['tipe'] === 'success' ? 'success' : 'danger') ?> mt-6">
    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
      <?php if ($flash['tipe'] === 'success'): ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      <?php else: ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
      <?php endif; ?>
    </svg>
    <p><?= e($flash['pesan']) ?></p>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert-danger mt-6">
    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
    <p><?= e($error) ?></p>
  </div>
<?php endif; ?>

<!-- ================= FORM ================= -->
<section class="card-base mt-6">
  <div class="border-b border-slate-200 px-6 py-4">
    <h2 class="text-section">Form Pengembalian</h2>
    <p class="text-muted mt-1">Ketik kode peminjaman untuk melihat simulasi denda sebelum disimpan.</p>
  </div>

  <div class="p-6">
    <form method="POST" action="add_pengembalian.php" id="formKembali" class="max-w-xl space-y-5" novalidate>
      <?= csrf_field() ?>

      <div class="autocomplete">
        <label for="kode_pinjam" class="field-label">Kode Peminjaman <span class="text-rose-500">*</span></label>
        <div class="relative">
          <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
               fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
          <input type="text" id="kode_pinjam" name="kode_pinjam" class="input-icon"
                 placeholder="Contoh: PN001" autocomplete="off" data-autofocus required />
        </div>
        <div id="detailPinjam"></div>
      </div>

      <div>
        <label for="kondisi_buku" class="field-label">Kondisi Buku <span class="text-rose-500">*</span></label>
        <select name="kondisi_buku" id="kondisi_buku" class="field-select" required>
          <?php foreach (KondisiBuku::cases() as $k): ?>
            <option value="<?= e($k->value) ?>"><?= e($k->label()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="previewDenda"></div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
        <button type="reset" class="btn-secondary btn-sm">Reset</button>
        <button type="submit" class="btn-primary btn-sm">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3m-9-6h.01" />
          </svg>
          Proses Pengembalian
        </button>
      </div>
    </form>
  </div>
</section>

<!-- ================= TABEL PEMINJAMAN AKTIF ================= -->
<section class="mt-8">
  <h2 class="text-section">Peminjaman Belum Dikembalikan</h2>

  <div class="card-base mt-3 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="table-base" id="peminjamanAktifTable">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Anggota</th>
            <th>Judul</th>
            <th>Batas Kembali</th>
            <th>Status</th>
            <th class="text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$peminjamanAktif): ?>
            <tr>
              <td colspan="6" class="py-12 text-center text-slate-500">
                Tidak ada peminjaman aktif.
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($peminjamanAktif as $p):
              $sisa   = $denda->sisaHari($p['estimasi_pinjam']);
              $lewat  = $sisa < 0;
              $badge  = $lewat ? 'badge-late' : ($sisa <= 1 ? 'badge-due' : 'badge-safe');
              $teks   = $lewat
                  ? 'Terlambat ' . abs($sisa) . ' hari'
                  : ($sisa === 0 ? 'Jatuh tempo hari ini' : $sisa . ' hari lagi');
          ?>
            <tr>
              <td><code class="code-chip"><?= e($p['kode_pinjam']) ?></code></td>
              <td class="whitespace-nowrap font-semibold text-slate-900"><?= e($p['nama']) ?></td>
              <td class="whitespace-nowrap"><?= e($p['judul_buku']) ?></td>
              <td class="whitespace-nowrap"><?= e(date('d/m/Y', strtotime($p['estimasi_pinjam']))) ?></td>
              <td><span class="<?= $badge ?>"><?= e($teks) ?></span></td>
              <td class="text-right">
                <button type="button" class="btn-secondary btn-sm" data-kode="<?= e($p['kode_pinjam']) ?>">
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                  Pilih
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
/* Dijalankan otomatis saat halaman dibuka normal, dan dipanggil manual oleh
   pengembalian.php setelah fragment ini di-inject ke dalam modal
   (innerHTML tidak mengeksekusi <script>). */
window.initFormKembali = function () {
  const inputKode = document.getElementById('kode_pinjam');
  const detail   = document.getElementById('detailPinjam');
  const preview  = document.getElementById('previewDenda');
  if (!inputKode || !detail || !preview) return;
  if (inputKode.dataset.kembaliSiap === '1') return;
  inputKode.dataset.kembaliSiap = '1';

  let timer = null;

   // Escape sebelum masuk innerHTML. Data berasal dari database,
   // tetapi tetap harus diperlakukan sebagai input yang tidak dipercaya.
  const esc = (v) => String(v).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));

  const ICON = {
    sukses: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
  };

  function badgeDenda(d) {
      if (!d.ok) { return ''; }

      if (!d.terlambat) {
          return '<div class="alert-success">'
               + '<svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">'
               + ICON.sukses + '</svg>'
               + '<p>Tepat waktu. Tidak ada denda.</p></div>';
      }

      return '<div class="alert-danger">'
           + '<svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">'
           + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>'
           + '</svg>'
           + '<p><strong>Terlambat ' + esc(d.hari_terlambat) + ' hari.</strong> '
           + 'Proyeksi denda <strong>' + esc(d.denda_preview) + '</strong> '
           + '(tarif ' + esc(d.tarif_harian) + '/hari).</p></div>';
  }

  function cari() {
      const kode = inputKode.value.trim();
      if (kode.length < 2) { detail.innerHTML = ''; preview.innerHTML = ''; return; }

      fetch('add_pengembalian.php?cek=' + encodeURIComponent(kode))
          .then((r) => r.json())
          .then((d) => {
              if (!d.ok) {
                  detail.innerHTML = '<div class="alert-warning">'
                      + '<svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">'
                      + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>'
                      + '</svg>'
                      + '<p>' + esc(d.pesan) + '</p></div>';
                  preview.innerHTML = '';
                  return;
              }

              detail.innerHTML = '<div class="card-base p-4">'
                  + '<p class="text-sm text-slate-800"><strong>' + esc(d.nama) + '</strong> meminjam &ldquo;' + esc(d.judul_buku) + '&rdquo;</p>'
                  + '<p class="mt-1 text-xs text-slate-500">Pinjam ' + esc(d.tgl_pinjam)
                  + ' &middot; Batas kembali ' + esc(d.estimasi) + '</p>'
                  + '</div>';

              preview.innerHTML = badgeDenda(d);
          })
          .catch(() => {
              detail.innerHTML = '<div class="alert-neutral">'
                  + '<svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">'
                  + '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>'
                  + '</svg>'
                  + '<p>Gagal memuat detail.</p></div>';
          });
  }

  inputKode.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(cari, 300);   // debounce: jangan request tiap ketikan
  });

  document.querySelectorAll('[data-kode]').forEach((btn) => {
      btn.addEventListener('click', () => {
          inputKode.value = btn.dataset.kode;
          cari();
      });
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', window.initFormKembali);
} else {
  window.initFormKembali();
}
</script>

<?php
if (!$fragment) {
    include '../Layouts/footer.php';
}

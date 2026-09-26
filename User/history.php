<?php
require_once '../Config/koneksi.php';
include 'header.php';

$nim = $_SESSION['nim'];

// Query History
$queryHistory = $conn->prepare("
    SELECT 
        p.kode_pinjam,
        p.tgl_pinjam,
        p.estimasi_pinjam,
        p.status AS status_peminjaman,
        pg.tgl_kembali,
        pg.kondisi_buku,
        pg.denda,
        pg.status AS status_pengembalian,
        pg.pembayaran,
        b.judul_buku,
        b.cover
    FROM 
        peminjaman p
    LEFT JOIN 
        pengembalian pg ON p.kode_pinjam = pg.kode_pinjam
    LEFT JOIN 
        buku b ON p.kode_buku = b.kode_buku
    WHERE 
        p.nim = :nim
    ORDER BY 
        p.tgl_pinjam DESC
");
$queryHistory->bindParam(':nim', $nim);
$queryHistory->execute();
$history = $queryHistory->fetchAll(PDO::FETCH_ASSOC);

// Query Statistik
$queryTotal = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE nim = :nim");
$queryTotal->bindParam(':nim', $nim);
$queryTotal->execute();
$totalPinjaman = $queryTotal->fetch(PDO::FETCH_ASSOC)['total'];

$queryLate = $conn->prepare("
    SELECT 
        SUM(
            CASE WHEN pg.tgl_kembali > p.estimasi_pinjam 
            THEN DATEDIFF(pg.tgl_kembali, p.estimasi_pinjam) 
            ELSE 0 
            END
        ) as total_hari 
    FROM peminjaman p 
    LEFT JOIN pengembalian pg ON p.kode_pinjam = pg.kode_pinjam 
    WHERE p.nim = :nim
");
$queryLate->bindParam(':nim', $nim);
$queryLate->execute();
$totalTerlambat = $queryLate->fetch(PDO::FETCH_ASSOC)['total_hari'] ?? 0;

// Hitung persentase progress bar
$progressPinjaman = min($totalPinjaman * 6.25, 100); // 16 buku = 100%
$progressTerlambat = min($totalTerlambat * 7.5, 100); // 13 hari = 100%
?>

<section class="conten ios-mobile">
    <!-- Header Mobile -->
    <div class="mobile-header">
        <h1 class="ios-title">Riwayat Peminjaman</h1>
    </div>

    <!-- Stats Mobile -->
    <div class="mobile-stats">
        <div class="stat-item">
            <div class="stat-value"><?= $totalPinjaman ?></div>
            <div class="stat-label">Buku Dipinjam</div>
        </div>
        <div class="stat-item">
            <div class="stat-value text-danger"><?= $totalTerlambat ?></div>
            <div class="stat-label">Hari Terlambat</div>
        </div>
    </div>

    <!-- List Mobile -->
    <div class="mobile-list">
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <p>Belum ada riwayat peminjaman</p>
            </div>
        <?php else: ?>
            <?php foreach ($history as $h):
                $isLate = $h['tgl_kembali'] && (strtotime($h['tgl_kembali']) > strtotime($h['estimasi_pinjam']));
            ?>
                <div class="list-item shadow-sm rounded-3 mb-4 border-1">
                    <img src="../Assets/uploads/<?= htmlspecialchars($h['cover'] ?? 'default-cover.jpg') ?>"
                        class="ms-2 item-cover" alt="Cover Buku">
                    <div class="item-content">
                        <div class="item-header">
                            <h3><?= htmlspecialchars($h['judul_buku']) ?></h3>
                            <span class="status-badge <?= ($h['status_pengembalian'] === 'Lunas') ? 'returned' : 'borrowed' ?>">
                                <?= $h['status_pengembalian'] ?? 'Dipinjam' ?>
                            </span>
                        </div>

                        <div class="item-meta">
                            <div class="meta-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d M Y', strtotime($h['tgl_pinjam'])) ?>
                            </div>
                            <?php if ($isLate): ?>
                                <div class="meta-late">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Terlambat <?= $h['denda'] ? 'Rp' . number_format($h['denda'], 0, ',', '.') : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="item-chevron" data-bs-toggle="modal"
                        data-bs-target="#detailModal<?= $h['kode_pinjam'] ?>">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Mobile -->
<?php foreach ($history as $h): ?>
    <div class="modal fade mobile-modal" id="detailModal<?= $h['kode_pinjam'] ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Detail Peminjaman</h2>
                    <button type="button" class="close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="detail-item">
                        <label>Judul Buku</label>
                        <p><?= htmlspecialchars($h['judul_buku']) ?></p>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-item">
                            <label><i class="fas fa-calendar-check"></i> Pinjam</label>
                            <p><?= date('d M Y', strtotime($h['tgl_pinjam'])) ?></p>
                        </div>

                        <div class="detail-item">
                            <label><i class="fas fa-calendar-times"></i> Kembali</label>
                            <p><?= $h['tgl_kembali'] ? date('d M Y', strtotime($h['tgl_kembali'])) : '-' ?></p>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-item">
                            <label><i class="fas fa-heart"></i> Kondisi</label>
                            <p><?= $h['kondisi_buku'] ?? '-' ?></p>
                        </div>

                        <div class="detail-item">
                            <label><i class="fas fa-money-bill"></i> Denda</label>
                            <p><?= $h['denda'] ? 'Rp ' . number_format($h['denda'], 0, ',', '.') : '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
    /* Base Mobile Styles */
    .ios-mobile {
        --primary: #007AFF;
        --text: #1D1D1F;
        --background: #FFFFFF;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        padding: 0 8px;
    }

    .mobile-header {
        padding: 24px 0 16px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 16px;
    }

    .ios-title {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }

    /* Stats */
    .mobile-stats {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-item {
        flex: 1;
        text-align: center;
        padding: 12px;
        background: #F8F9FA;
        border-radius: 12px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 12px;
        color: #8E8E93;
    }

    /* List Items */
    .mobile-list {
        margin-bottom: 32px;
    }

    .list-item {
        display: flex;
        align-items: center;
        padding: 8px 0;
    }

    .item-cover {
        width: 60px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
        margin-right: 16px;
    }

    .item-content {
        flex: 1;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .item-header h3 {
        font-size: 16px;
        margin: 0;
        flex: 1;
        margin-right: 12px;
    }

    .status-badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .status-badge.borrowed {
        background: #FFEECC;
        color: #FF9500;
    }

    .status-badge.returned {
        background: #D6F5E0;
        color: #34C759;
    }

    .item-meta {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .meta-date,
    .meta-late {
        display: flex;
        align-items: center;
        font-size: 13px;
        color: #8E8E93;
    }

    .meta-date i,
    .meta-late i {
        margin-right: 8px;
        font-size: 12px;
    }

    .meta-late {
        color: #FF3B30;
    }

    .item-chevron {
        border: none;
        background: none;
        color: #C7C7CC;
        padding: 8px;
    }

    /* Modal Mobile */
    .mobile-modal .modal-content {
        border-radius: 16px;
        margin: 0px;
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--border);
        position: relative;
    }

    .modal-header h2 {
        font-size: 20px;
        margin: 0;
    }

    .close-btn {
        position: absolute;
        right: 16px;
        top: 16px;
        border: none;
        background: none;
        padding: 8px;
    }

    .detail-item {
        margin-bottom: 16px;
    }

    .detail-item label {
        display: block;
        font-size: 12px;
        color: #8E8E93;
        margin-bottom: 4px;
    }

    .detail-item p {
        font-size: 16px;
        margin: 0;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 0;
    }

    .empty-state i {
        font-size: 48px;
        color: #C7C7CC;
        margin-bottom: 16px;
    }

    .empty-state p {
        color: #8E8E93;
    }
</style>
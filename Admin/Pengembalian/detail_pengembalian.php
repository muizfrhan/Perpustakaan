<?php
require_once '../../Config/koneksi.php';

if (isset($_GET['id'])) {
    $idKembali = (int)$_GET['id'];

    $query = $conn->prepare("
        SELECT 
            pengembalian.id_kembali,
            anggota.nim,
            anggota.nama AS nama_anggota,
            buku.kode_buku,
            buku.judul_buku,
            petugas.nama_petugas,
            peminjaman.tgl_pinjam,
            pengembalian.tgl_kembali,
            pengembalian.status_kembali,
            pengembalian.denda
        FROM pengembalian
        JOIN peminjaman ON pengembalian.id_pinjam = peminjaman.id_pinjam
        JOIN anggota ON peminjaman.nim = anggota.nim
        JOIN buku ON peminjaman.kode_buku = buku.kode_buku
        JOIN petugas ON peminjaman.id_petugas = petugas.id_petugas
        WHERE pengembalian.id_kembali = :id
    ");
    $query->bindValue(':id', $idKembali, PDO::PARAM_INT);
    $query->execute();
    $detail = $query->fetch(PDO::FETCH_ASSOC);

    if ($detail): ?>
        <div class="container mt-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <td><?php echo htmlspecialchars($detail['id_kembali']); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Anggota</th>
                                <td><?php echo htmlspecialchars($detail['nama_anggota']); ?></td>
                            </tr>
                            <tr>
                                <th>NIM</th>
                                <td><?php echo htmlspecialchars($detail['nim']); ?></td>
                            </tr>
                            <tr>
                                <th>Kode Buku</th>
                                <td><?php echo htmlspecialchars($detail['kode_buku']); ?></td>
                            </tr>
                            <tr>
                                <th>Judul Buku</th>
                                <td><?php echo htmlspecialchars($detail['judul_buku']); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Petugas</th>
                                <td><?php echo htmlspecialchars($detail['nama_petugas']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Pinjam</th>
                                <td><?php echo htmlspecialchars($detail['tgl_pinjam']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Kembali</th>
                                <td><?php echo htmlspecialchars($detail['tgl_kembali']); ?></td>
                            </tr>
                            <tr>
                                <th>Status Kembali</th>
                                <td>
                                    <span class="badge 
                                        <?php
                                            switch ($detail['status_kembali']) {
                                                case 'Aman':
                                                    echo 'bg-success';
                                                    break;
                                                case 'Terlambat':
                                                    echo 'bg-warning text-dark';
                                                    break;
                                                case 'Hilang':
                                                    echo 'bg-danger';
                                                    break;
                                                case 'Rusak':
                                                    echo 'bg-secondary';
                                                    break;
                                                default:
                                                    echo 'bg-light text-dark';
                                            }
                                        ?>">
                                        <?php echo htmlspecialchars($detail['status_kembali']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Denda</th>
                                <td><?php echo number_format($detail['denda'], 2, ',', '.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger mt-4" role="alert">
            Data tidak ditemukan.
        </div>
    <?php endif; 
} else {
    echo "<div class='alert alert-danger mt-4' role='alert'>ID tidak valid.</div>";
}
?>

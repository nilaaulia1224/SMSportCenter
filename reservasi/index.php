<?php
$page_title = "Data Reservasi";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

// Menangani pencarian
$search = $_GET['search'] ?? '';

// Query data reservasi
$sql = "SELECT r.*, p.nama AS nama_pelanggan, p.no_hp, l.nama_lapangan, l.jenis 
        FROM reservasi r
        JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
        JOIN lapangan l ON r.id_lapangan = l.id_lapangan";

$params = [];
if ($search) {
    $sql .= " WHERE p.nama LIKE ? OR l.nama_lapangan LIKE ? OR r.tanggal LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$sql .= " ORDER BY r.tanggal DESC, r.jam_mulai DESC";

$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$reservasi = $stmt->fetchAll();

// Helper status badge
function getStatusBadge($status) {
    if ($status === 'Selesai') return '<span class="badge bg-success">Selesai</span>';
    if ($status === 'Menunggu Pembayaran') return '<span class="badge bg-warning text-dark">Menunggu Pembayaran</span>';
    return '<span class="badge bg-danger">Dibatalkan</span>';
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-calendar2-check-fill me-2 text-warning"></i>Manajemen Reservasi</h4>
        <p class="text-muted mb-0">Kelola data pemesanan lapangan pelanggan</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Reservasi
    </a>
</div>

<!-- Flash Message jika ada operasi sukses/gagal -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        
        <!-- Search form -->
        <form action="" method="GET" class="mb-4">
            <div class="input-group" style="max-width: 400px;">
                <input type="text" class="form-control" name="search" placeholder="Cari pelanggan, lapangan, atau YYYY-MM-DD..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Cari</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-outline-danger"><i class="bi bi-x"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Lapangan</th>
                        <th>Jadwal</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reservasi) > 0): ?>
                        <?php foreach ($reservasi as $row): ?>
                            <tr>
                                <td>#<?= $row['id_reservasi'] ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['nama_lapangan']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['jenis']) ?></small>
                                </td>
                                <td>
                                    <div><i class="bi bi-calendar me-1"></i> <?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> <?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></small>
                                </td>
                                <td class="fw-bold text-success">
                                    Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?>
                                </td>
                                <td><?= getStatusBadge($row['status']) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $row['id_reservasi'] ?>" class="btn btn-sm btn-outline-primary mb-1" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $row['id_reservasi'] ?>" class="btn btn-sm btn-outline-danger mb-1" title="Hapus" onclick="return confirm('Yakin ingin menghapus data reservasi ini?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php if ($row['status'] === 'Selesai' && !empty($row['no_hp'])): ?>
                                        <?php 
                                            // Format no_hp (ubah 08 menjadi 628)
                                            $no_wa = $row['no_hp'];
                                            if(strpos($no_wa, '0') === 0) $no_wa = '62' . substr($no_wa, 1);
                                            // Asumsi base_url untuk link struk (sesuaikan dengan domain/ip yang dipakai, ini menggunakan localhost)
                                            $struk_url = "http://localhost/SMSportCenter/struk.php?id=" . $row['id_reservasi'];
                                            $pesan_wa = "Terima kasih, reservasi Anda telah kami konfirmasi dan berstatus Selesai. Anda dapat melihat E-Receipt / struk lunas Anda di link berikut: " . $struk_url;
                                            $wa_link = "https://wa.me/" . $no_wa . "?text=" . urlencode($pesan_wa);
                                        ?>
                                        <a href="<?= $wa_link ?>" target="_blank" class="btn btn-sm btn-outline-success mb-1" title="Kirim Struk via WA">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data reservasi ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

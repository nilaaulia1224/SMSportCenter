<?php
$page_title = "Data Pelanggan";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

// Pencarian
$search = $_GET['search'] ?? '';

// Query pelanggan
$sql = "SELECT * FROM pelanggan";
$params = [];
if ($search) {
    $sql .= " WHERE nama LIKE ? OR no_hp LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY nama ASC";

$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$pelanggan = $stmt->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-people-fill me-2 text-primary"></i>Manajemen Pelanggan Baru</h4>
        <p class="text-muted mb-0">Kelola data pendaftar dan member</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Pelanggan
    </a>
</div>

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
                <input type="text" class="form-control" name="search" placeholder="Cari nama atau no. whatsapp..." value="<?= htmlspecialchars($search) ?>">
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
                        <th>Nama Lengkap</th>
                        <th>No. WhatsApp</th>
                        <th>Email</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pelanggan) > 0): ?>
                        <?php foreach ($pelanggan as $row): ?>
                            <tr>
                                <td>#<?= $row['id_pelanggan'] ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $row['no_hp']) ?>" target="_blank" class="text-decoration-none text-success">
                                        <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($row['no_hp']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus pelanggan ini? Seluruh riwayat reservasinya juga akan ikut terhapus!');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Tidak ada data pelanggan ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

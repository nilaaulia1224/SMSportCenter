<?php
$page_title = "Lapangan";
require_once '../config/koneksi.php';
require_once '../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-layout-text-window-reverse me-2 text-success"></i>Manajemen Lapangan</h4>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lapangan</th>
                    <th>Kapasitas</th>
                    <th>Harga per Jam</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($conn, "SELECT * FROM lapangan");
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)) { ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                        <td><?= htmlspecialchars($row['kapasitas'] ?? 10) ?> Orang</td>
                        <td>Rp <?= number_format($row['harga_per_jam'], 0, ',', '.') ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>

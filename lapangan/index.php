<?php
$page_title = "Lapangan";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

// Ambil tanggal hari ini (Format: YYYY-MM-DD)
$hari_ini = date('Y-m-d');
$hari_ini_formatted = date('d F Y');

// Ambil semua reservasi khusus hari ini yang belum dibatalkan
$stmt_res = $koneksi->prepare("
    SELECT r.*, p.nama AS nama_pelanggan, p.no_hp 
    FROM reservasi r
    JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    WHERE r.tanggal = ? AND r.status != 'Dibatalkan'
    ORDER BY r.jam_mulai ASC
");
$stmt_res->execute([$hari_ini]);
$all_today_res = $stmt_res->fetchAll();

// Grouping reservasi berdasarkan id_lapangan
$reservasi_per_lapangan = [];
foreach ($all_today_res as $res) {
    $reservasi_per_lapangan[$res['id_lapangan']][] = $res;
}

// Ambil data lapangan
$stmt_lapangan = $koneksi->query("SELECT * FROM lapangan ORDER BY id_lapangan ASC");
$lapangan_list = $stmt_lapangan->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-layout-text-window-reverse me-2 text-primary"></i>Manajemen & Jadwal Lapangan</h4>
        <p class="text-muted mb-0 small">Klik tombol <strong>"Lihat Jadwal Hari Ini"</strong> untuk melihat ketersediaan jam khusus hari ini (<?= $hari_ini_formatted ?>).</p>
    </div>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
        <i class="bi bi-calendar-check me-1"></i> Status Tanggal: <?= date('d/m/Y') ?>
    </span>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>Nama Lapangan</th>
                        <th>Jenis</th>
                        <th>Harga per Jam</th>
                        <th>Reservasi Hari Ini</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($lapangan_list as $row) {
                        $id_lap = $row['id_lapangan'];
                        $list_booking = $reservasi_per_lapangan[$id_lap] ?? [];
                        $jumlah_booking = count($list_booking);
                    ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted"><?= $no++ ?></td>
                            <td>
                                <a href="#" class="fw-bold text-decoration-none text-dark hover-primary" data-bs-toggle="modal" data-bs-target="#modalJadwal<?= $id_lap ?>">
                                    <?= htmlspecialchars($row['nama_lapangan']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary rounded-pill px-3"><?= htmlspecialchars($row['jenis']) ?></span></td>
                            <td class="fw-semibold text-success">Rp <?= number_format($row['harga_per_jam'], 0, ',', '.') ?> / jam</td>
                            <td>
                                <?php if ($jumlah_booking > 0): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">
                                        <i class="bi bi-clock-history me-1"></i> <?= $jumlah_booking ?> Terisi Hari Ini
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                                        <i class="bi bi-check-circle me-1"></i> Kosong (Tersedia)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalJadwal<?= $id_lap ?>">
                                    <i class="bi bi-calendar3 me-1"></i> Lihat Jadwal Hari Ini
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL JADWAL PER LAPANGAN (KHUSUS HARI INI) -->
<?php foreach ($lapangan_list as $row) {
    $id_lap = $row['id_lapangan'];
    $list_booking = $reservasi_per_lapangan[$id_lap] ?? [];
?>
    <div class="modal fade" id="modalJadwal<?= $id_lap ?>" tabindex="-1" aria-labelledby="modalLabel<?= $id_lap ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-light border-0 pb-0 pt-4 px-4">
                    <div>
                        <h5 class="modal-title fw-bold id="modalLabel<?= $id_lap ?>">
                            <i class="bi bi-calendar-event me-2 text-primary"></i>Jadwal Reservasi: <?= htmlspecialchars($row['nama_lapangan']) ?>
                        </h5>
                        <p class="text-muted small mb-0 mt-1">
                            <i class="bi bi-info-circle me-1 text-primary"></i>Informasi khusus untuk <strong>Hari Ini (<?= $hari_ini_formatted ?>)</strong>.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 d-flex align-items-center mb-3">
                        <i class="bi bi-shield-lock-fill fs-4 me-3 text-info"></i>
                        <div class="small">
                            <strong>Catatan Sistem:</strong> Halaman ini hanya menampilkan jadwal reservasi aktif untuk <strong>hari ini saja</strong> (tidak menampilkan riwayat hari sebelumnya).
                        </div>
                    </div>

                    <?php if (empty($list_booking)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-check text-success display-4 d-block mb-3"></i>
                            <h6 class="fw-bold text-dark mb-1">Lapangan Masih Kosong / Tersedia Penuh</h6>
                            <p class="text-muted small">Belum ada penyewaan yang terdaftar pada tanggal hari ini (<?= date('d/m/Y') ?>).</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive rounded-3 border">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Jam Sewa</th>
                                        <th>Nama Pelanggan</th>
                                        <th>No. WhatsApp</th>
                                        <th>Status Reservasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $b_no = 1;
                                    foreach ($list_booking as $b): 
                                        $jam_mulai_f = date('H:i', strtotime($b['jam_mulai']));
                                        $jam_selesai_f = date('H:i', strtotime($b['jam_selesai']));
                                    ?>
                                        <tr>
                                            <td><?= $b_no++ ?></td>
                                            <td>
                                                <span class="fw-bold text-primary">
                                                    <i class="bi bi-clock me-1"></i><?= $jam_mulai_f ?> - <?= $jam_selesai_f ?> WIB
                                                </span>
                                            </td>
                                            <td class="fw-semibold"><?= htmlspecialchars($b['nama_pelanggan']) ?></td>
                                            <td>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_hp']) ?>" target="_blank" class="text-decoration-none text-success small">
                                                    <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($b['no_hp']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($b['status'] === 'Selesai'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai / Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Pembayaran</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php require_once '../includes/footer.php'; ?>

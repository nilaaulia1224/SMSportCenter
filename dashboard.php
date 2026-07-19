<?php
/**
 * Dashboard Utama - index.php
 * Menampilkan statistik ringkasan sistem reservasi
 */

$page_title = "Dashboard";
require_once 'config/koneksi.php';
require_once 'includes/header.php';

// ============================================================
// Query Statistik
// ============================================================

// Jumlah pelanggan
$total_pelanggan = $koneksi->query("SELECT COUNT(*) FROM pelanggan")->fetchColumn();

// Jumlah lapangan
$total_lapangan  = $koneksi->query("SELECT COUNT(*) FROM lapangan")->fetchColumn();
$lapangan_tersedia = $koneksi->query("SELECT COUNT(*) FROM lapangan WHERE status = 'Tersedia'")->fetchColumn();

// Jumlah reservasi total
$total_reservasi = $koneksi->query("SELECT COUNT(*) FROM reservasi")->fetchColumn();

// Reservasi hari ini
$stmt = $koneksi->prepare("SELECT COUNT(*) FROM reservasi WHERE tanggal = CURRENT_DATE()");
$stmt->execute();
$reservasi_hari_ini = $stmt->fetchColumn();

// Total pendapatan
$total_pendapatan = $koneksi->query("SELECT COALESCE(SUM(total_bayar),0) FROM reservasi WHERE status = 'Selesai'")->fetchColumn();

// ============================================================
// 5 Reservasi Terbaru
// ============================================================
$reservasi_terbaru = $koneksi->query("
    SELECT r.id_reservasi, p.nama AS nama_pelanggan,
           l.nama_lapangan, l.jenis,
           r.tanggal, r.jam_mulai, r.jam_selesai,
           r.total_bayar, r.status
    FROM reservasi r
    JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    JOIN lapangan  l ON r.id_lapangan  = l.id_lapangan
    ORDER BY r.id_reservasi DESC
    LIMIT 5
")->fetchAll();

// Helper: badge warna status
function statusBadge(string $status): string {
    return match($status) {
        'Selesai'              => '<span class="badge bg-success">Selesai</span>',
        'Menunggu Pembayaran'  => '<span class="badge bg-warning text-dark">Menunggu Bayar</span>',
        'Dibatalkan'           => '<span class="badge bg-danger">Dibatalkan</span>',
        default                => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>',
    };
}
?>

<!-- ============================================================
     KONTEN DASHBOARD
     ============================================================ -->

<!-- Page Header -->
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4 class="mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h4>
        <p class="mb-0">Selamat datang, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>!
           Berikut ringkasan data per hari ini, <strong><?= date('d F Y') ?></strong>.</p>
    </div>
    <a href="reservasi/tambah.php" class="btn btn-primary d-none d-sm-inline-flex align-items-center gap-2">
        <i class="bi bi-plus-circle"></i> Tambah Reservasi
    </a>
</div>

<!-- ============================================================
     KARTU STATISTIK
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Pelanggan -->
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body bg-blue-soft">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="stat-icon icon-blue"><i class="bi bi-people-fill"></i></div>
                    <span class="badge bg-primary bg-opacity-15 text-primary stat-badge">Total</span>
                </div>
                <div class="stat-value"><?= number_format($total_pelanggan) ?></div>
                <div class="stat-label">Pelanggan Terdaftar</div>
                <div class="bg-blob bg-primary"></div>
            </div>
        </div>
    </div>

    <!-- Lapangan -->
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body bg-green-soft">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="stat-icon icon-green"><i class="bi bi-layout-text-window-reverse"></i></div>
                    <span class="badge bg-success bg-opacity-15 text-success stat-badge"><?= $lapangan_tersedia ?> Tersedia</span>
                </div>
                <div class="stat-value"><?= number_format($total_lapangan) ?></div>
                <div class="stat-label">Total Lapangan</div>
                <div class="bg-blob bg-success"></div>
            </div>
        </div>
    </div>

    <!-- Reservasi Total -->
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body bg-amber-soft">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="stat-icon icon-amber"><i class="bi bi-calendar2-check-fill"></i></div>
                    <span class="badge bg-warning bg-opacity-25 text-warning stat-badge">Total</span>
                </div>
                <div class="stat-value"><?= number_format($total_reservasi) ?></div>
                <div class="stat-label">Total Reservasi</div>
                <div class="bg-blob bg-warning"></div>
            </div>
        </div>
    </div>

    <!-- Reservasi Hari Ini -->
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body bg-purple-soft">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="stat-icon icon-purple"><i class="bi bi-clock-history"></i></div>
                    <span class="badge bg-purple-soft text-purple stat-badge"
                          style="background:#ede9fe;color:#7c3aed;">Hari Ini</span>
                </div>
                <div class="stat-value"><?= number_format($reservasi_hari_ini) ?></div>
                <div class="stat-label">Reservasi Hari Ini</div>
                <div class="bg-blob bg-purple"></div>
            </div>
        </div>
    </div>

</div><!-- end row statistik -->

<!-- ============================================================
     TOTAL PENDAPATAN + TABEL RESERVASI TERBARU
     ============================================================ -->
<div class="row g-4">

    <!-- Card Pendapatan -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <h6 class="fw-semibold text-muted mb-1">
                    <i class="bi bi-cash-stack me-1 text-success"></i> Total Pendapatan
                </h6>
                <small class="text-muted mb-3">Dari reservasi berstatus "Selesai"</small>
                <div class="mt-auto">
                    <div class="fs-3 fw-bold text-success">
                        Rp <?= number_format($total_pendapatan, 0, ',', '.') ?>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="fw-bold text-dark"><?= $total_reservasi ?></div>
                        <div class="text-muted" style="font-size:.75rem;">Total Reservasi</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-dark"><?= $reservasi_hari_ini ?></div>
                        <div class="text-muted" style="font-size:.75rem;">Hari Ini</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Reservasi Terbaru -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-1 text-primary"></i> Reservasi Terbaru
                    </h6>
                    <a href="reservasi/index.php" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <?php if (empty($reservasi_terbaru)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        Belum ada data reservasi.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Bayar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservasi_terbaru as $r): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['nama_pelanggan']) ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($r['nama_lapangan']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($r['jenis']) ?></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                <td class="text-nowrap">
                                    <?= substr($r['jam_mulai'],0,5) ?> – <?= substr($r['jam_selesai'],0,5) ?>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-semibold text-success">
                                        Rp <?= number_format($r['total_bayar'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td><?= statusBadge($r['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- end row bawah -->

<?php require_once 'includes/footer.php'; ?>

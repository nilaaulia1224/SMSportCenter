<?php
/**
 * Sidebar Global Aplikasi SM Sport Center
 */

// Menentukan path aktif untuk menyorot menu
$self = $_SERVER['PHP_SELF'];
$current_dir = basename(dirname($self));
$current_file = basename($self);

// Cek apakah di halaman utama atau sub-modul
$is_dashboard = ($current_file === 'dashboard.php');
$is_pelanggan = ($current_dir === 'pelanggan');
$is_lapangan = ($current_dir === 'lapangan');
$is_reservasi = ($current_dir === 'reservasi');
$is_laporan = ($current_dir === 'laporan');

// Set relative path base
$base_path = "";
if ($is_pelanggan || $is_lapangan || $is_reservasi || $is_laporan) {
    $base_path = "../";
}
?>
<!-- Sidebar -->
<nav id="sidebar" class="bg-dark text-white border-end shadow-sm">
    <div class="sidebar-header p-4 border-bottom border-secondary d-flex align-items-center">
        <i class="bi bi-trophy-fill text-warning fs-3 me-2"></i>
        <div>
            <h5 class="mb-0 text-white fw-bold">SM Sport Center</h5>
            <small class="text-muted">Aplikasi Reservasi</small>
        </div>
    </div>
    
    <div class="p-3">
        <div class="text-uppercase text-muted fw-bold px-3 mb-2" style="font-size: 0.75rem;">Utama</div>
        <ul class="nav flex-column mb-4">
            <li class="nav-item mb-1">
                <a href="<?= $base_path ?>dashboard.php" class="nav-link text-white rounded px-3 py-2.5 d-flex align-items-center <?= $is_dashboard ? 'active bg-primary' : 'hover-bg' ?>">
                    <i class="bi bi-grid-1x2-fill me-3 text-secondary"></i>
                    Dashboard
                </a>
            </li>
        </ul>
        
        <div class="text-uppercase text-muted fw-bold px-3 mb-2" style="font-size: 0.75rem;">Manajemen Data</div>
        <ul class="nav flex-column mb-4">
            <li class="nav-item mb-1">
                <a href="<?= $base_path ?>pelanggan/index.php" class="nav-link text-white rounded px-3 py-2.5 d-flex align-items-center <?= $is_pelanggan ? 'active bg-primary' : 'hover-bg' ?>">
                    <i class="bi bi-people-fill me-3 text-secondary"></i>
                    Pelanggan
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="<?= $base_path ?>lapangan/index.php" class="nav-link text-white rounded px-3 py-2.5 d-flex align-items-center <?= $is_lapangan ? 'active bg-primary' : 'hover-bg' ?>">
                    <i class="bi bi-card-checklist me-3 text-secondary"></i>
                    Lapangan
                </a>
            </li>
        </ul>
        
        <div class="text-uppercase text-muted fw-bold px-3 mb-2" style="font-size: 0.75rem;">Transaksi & Laporan</div>
        <ul class="nav flex-column mb-4">
            <li class="nav-item mb-1">
                <a href="<?= $base_path ?>reservasi/index.php" class="nav-link text-white rounded px-3 py-2.5 d-flex align-items-center <?= $is_reservasi ? 'active bg-primary' : 'hover-bg' ?>">
                    <i class="bi bi-calendar2-week-fill me-3 text-secondary"></i>
                    Reservasi
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="<?= $base_path ?>laporan/index.php" class="nav-link text-white rounded px-3 py-2.5 d-flex align-items-center <?= $is_laporan ? 'active bg-primary' : 'hover-bg' ?>">
                    <i class="bi bi-file-earmark-bar-graph-fill me-3 text-secondary"></i>
                    Laporan
                </a>
            </li>
        </ul>
        
        <div class="text-uppercase text-muted fw-bold px-3 mb-2" style="font-size: 0.75rem;">Sistem</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= $base_path ?>logout.php" class="nav-link text-danger rounded px-3 py-2.5 d-flex align-items-center hover-danger">
                    <i class="bi bi-box-arrow-right me-3"></i>
                    Keluar (Logout)
                </a>
            </li>
        </ul>
    </div>
</nav>

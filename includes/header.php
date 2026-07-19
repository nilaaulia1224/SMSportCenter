<?php
/**
 * Header Global Aplikasi SM Sport Center
 */

// Memulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek status login, jika belum login dan bukan di halaman login.php, redirect ke login.php
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_page !== 'login.php') {
    header("Location: login.php");
    exit();
}

// Definisikan base URL atau path relatif untuk memudahkan pemuatan asset jika di sub-folder
// Karena kita menggunakan struktur flat untuk login/index dan subfolder untuk modul, kita bisa menghitung relative path
$base_path = "";
if (in_array(dirname($_SERVER['PHP_SELF']), ['/pelanggan', '/lapangan', '/reservasi', '/laporan', '/users'])) {
    $base_path = "../";
} else {
    // Cek jika saat ini kita berada di subfolder secara dinamis
    $dir = basename(dirname($_SERVER['PHP_SELF']));
    if ($dir !== 'Project ANALIS' && $dir !== 'www' && $dir !== 'public_html' && $dir !== '') {
        // Jika file berada dalam folder modul (seperti pelanggan, lapangan, dll)
        $base_path = "../";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . " - SM Sport Center" : "SM Sport Center" ?></title>
    
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= $base_path ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php if (isset($_SESSION['user_id'])): ?>
<div class="wrapper">
    <!-- Include Sidebar -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- Page Content -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 py-3 sticky-top">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary me-3 d-lg-none">
                    <i class="bi bi-list"></i>
                </button>
                
                <h4 class="mb-0 text-dark fw-semibold d-none d-sm-block"><?= isset($page_title) ? $page_title : "Dashboard" ?></h4>
                
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-3 text-muted d-none d-md-inline">
                        Login sebagai: <strong class="text-primary"><?= htmlspecialchars($_SESSION['username']) ?></strong> 
                        <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($_SESSION['role']) ?></span>
                    </span>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header">Menu Pengguna</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_path ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
<?php endif; ?>

<?php
/**
 * Header Global Aplikasi SM Sport Center (Horizontal Navbar)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_page !== 'login.php') {
    header("Location: login.php");
    exit();
}

$base_path = "";
$dir = basename(dirname($_SERVER['PHP_SELF']));
if (in_array(dirname($_SERVER['PHP_SELF']), ['/pelanggan', '/lapangan', '/reservasi', '/laporan', '/users']) || ($dir !== 'Project ANALIS' && $dir !== 'www' && $dir !== 'public_html' && $dir !== 'SMSportCenter' && $dir !== '')) {
    $base_path = "../";
}

// Active menu logic
$self = $_SERVER['PHP_SELF'];
$current_dir = basename(dirname($self));
$current_file = basename($self);

$is_dashboard = ($current_file === 'dashboard.php');
$is_pelanggan = ($current_dir === 'pelanggan');
$is_lapangan = ($current_dir === 'lapangan');
$is_reservasi = ($current_dir === 'reservasi');
$is_laporan = ($current_dir === 'laporan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . " - SM Sport Center" : "SM Sport Center" ?></title>
    
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $base_path ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    
    <style>
        .admin-navbar {
            background: linear-gradient(90deg, #0B132B 0%, #1C2541 100%);
            padding: 1rem 2rem;
        }
        .admin-navbar .navbar-brand {
            font-weight: 700;
            color: #fff;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-navbar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--rounded-md);
            transition: all 0.2s;
            margin: 0 0.2rem;
        }
        .admin-navbar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        .admin-navbar .nav-link.active {
            color: #fff;
            background: var(--primary);
        }
        .admin-navbar .navbar-toggler {
            border: none;
            color: #fff;
        }
        .admin-navbar .navbar-toggler:focus {
            box-shadow: none;
        }
        .avatar-circle {
            width: 40px; height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>
<body class="bg-light">

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Horizontal Navbar -->
<nav class="navbar navbar-expand-lg admin-navbar shadow-sm sticky-top">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?= $base_path ?>dashboard.php">
            <img src="<?= $base_path ?>assets/img/logo.png" alt="SM Sport Center" style="height: 36px; width: 36px; object-fit: cover;" class="me-2 rounded-circle border border-light">
            <span>SM Sport Center</span>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2"></i>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= $base_path ?>dashboard.php">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $is_pelanggan ? 'active' : '' ?>" href="<?= $base_path ?>pelanggan/index.php">
                        <i class="bi bi-people-fill me-1"></i> Pelanggan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $is_lapangan ? 'active' : '' ?>" href="<?= $base_path ?>lapangan/index.php">
                        <i class="bi bi-card-checklist me-1"></i> Lapangan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $is_reservasi ? 'active' : '' ?>" href="<?= $base_path ?>reservasi/index.php">
                        <i class="bi bi-calendar2-week-fill me-1"></i> Reservasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $is_laporan ? 'active' : '' ?>" href="<?= $base_path ?>laporan/index.php">
                        <i class="bi bi-file-earmark-bar-graph-fill me-1"></i> Laporan
                    </a>
                </li>
            </ul>
            
            <!-- User Menu -->
            <div class="d-flex align-items-center mt-3 mt-lg-0">
                <span class="text-white-50 me-3 d-none d-md-inline">
                    Hai, <strong class="text-white"><?= htmlspecialchars($_SESSION['username']) ?></strong>
                </span>
                <div class="dropdown">
                    <a class="text-decoration-none dropdown-toggle d-flex align-items-center" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle">
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header">Menu Pengguna</h6></li>
                        <li><a class="dropdown-item text-danger" href="<?= $base_path ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container-fluid p-4" style="max-width: 1400px; margin: 0 auto;">
<?php endif; ?>

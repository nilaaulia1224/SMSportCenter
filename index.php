<?php
require_once 'config/koneksi.php';

// Ambil data lapangan
$stmt_lapangan = $koneksi->query("SELECT * FROM lapangan ORDER BY nama_lapangan");
$lapangan = $stmt_lapangan->fetchAll();

// Ambil tanggal hari ini untuk jadwal
$hari_ini = date('Y-m-d');

// Ambil reservasi hari ini yang belum dibatalkan
$stmt_reservasi = $koneksi->prepare("
    SELECT id_lapangan, jam_mulai, jam_selesai 
    FROM reservasi 
    WHERE tanggal = ? AND status != 'Dibatalkan'
");
$stmt_reservasi->execute([$hari_ini]);
$reservasi_hari_ini = $stmt_reservasi->fetchAll();

// Buat array referensi jadwal untuk mempermudah visualisasi
$booked_slots = [];
foreach ($reservasi_hari_ini as $res) {
    $id_lap = $res['id_lapangan'];
    $start_hour = (int)date('H', strtotime($res['jam_mulai']));
    $end_hour = (int)date('H', strtotime($res['jam_selesai']));
    
    if (!isset($booked_slots[$id_lap])) {
        $booked_slots[$id_lap] = [];
    }
    for ($i = $start_hour; $i < $end_hour; $i++) {
        $booked_slots[$id_lap][] = $i;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - SM Sport Center</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Figma UI System CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Specific overrides for index */
        .navbar-minimal {
            background: linear-gradient(90deg, #0B132B 0%, #1C2541 100%);
            padding: 16px 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .hero-title-brand {
            font-family: var(--font-sans);
            color: #fff !important;
            font-weight: 800;
        }
        .nav-link.custom-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-link.custom-link:hover {
            color: #fff !important;
        }
        .court-image-box {
            width: 100%;
            height: 180px;
            background-color: var(--surface-soft);
            border-radius: var(--rounded-md);
            margin-bottom: 24px;
            overflow: hidden;
            position: relative;
        }
        .court-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .editorial-card:hover .court-image-box img {
            transform: scale(1.05);
        }
        .status-badge-absolute {
            position: absolute;
            top: 12px;
            right: 12px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: var(--rounded-pill);
            text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: var(--rounded-pill);
            text-transform: uppercase;
        }
        .schedule-grid-box {
            background-color: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--rounded-md);
            padding: 24px;
            height: 100%;
        }
        .time-slot-pill {
            padding: 8px;
            text-align: center;
            border-radius: var(--rounded-md);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
            font-family: var(--font-mono);
        }
        .slot-available {
            background-color: var(--surface-soft);
            color: var(--ink);
            border: 1px solid var(--hairline);
        }
        .slot-booked {
            background-color: #e2e8f0 !important;
            color: #94a3b8 !important;
            text-decoration: line-through;
            opacity: 0.75;
        }
        .footer-minimal {
            background-color: var(--inverse-canvas);
            color: var(--inverse-ink);
            padding: 64px 32px;
        }
        /* Sporty Hero Block */
        .bg-sporty {
            background: linear-gradient(135deg, #0055ff 0%, #0099FF 100%);
            color: #fff;
            box-shadow: 0 20px 40px rgba(0, 85, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        .bg-sporty h1, .bg-sporty p {
            color: #fff !important;
        }
        .btn-pill-light {
            background-color: #fff;
            color: #0055FF;
            font-weight: 600;
            border-radius: var(--rounded-pill);
            padding: 11px 27px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-pill-light:hover {
            background-color: #f8fafc;
            transform: translateY(-2px);
        }
        .btn-pill-outline-light {
            background-color: transparent;
            color: #fff;
            border: 2px solid #fff;
            font-weight: 600;
            border-radius: var(--rounded-pill);
            padding: 10px 25px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-pill-outline-light:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        /* Animated Decorations */
        @keyframes floatCute {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(15deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        @keyframes bounceSpin {
            0% { transform: translateY(0px) rotate(0deg) scale(1); }
            50% { transform: translateY(-40px) rotate(180deg) scale(1.1); }
            100% { transform: translateY(0px) rotate(360deg) scale(1); }
        }
        @keyframes floatReverse {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(20px) rotate(-15deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        .deco-icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.15);
            z-index: 0;
            pointer-events: none;
        }
        .deco-1 { top: 15%; left: 10%; font-size: 5rem; animation: floatCute 6s ease-in-out infinite; }
        .deco-2 { bottom: 10%; left: 40%; font-size: 4rem; animation: floatReverse 7s ease-in-out infinite; }
        .deco-3 { top: 25%; right: 35%; font-size: 3rem; animation: bounceSpin 9s linear infinite; }
        .deco-4 { bottom: 20%; right: 10%; font-size: 7rem; animation: floatCute 8s ease-in-out infinite reverse; }
        .deco-5 { top: 5%; right: 5%; font-size: 4.5rem; animation: floatReverse 6.5s ease-in-out infinite; }
    </style>
</head>
<body>

    <!-- Top Nav -->
    <nav class="navbar navbar-expand-lg navbar-minimal fixed-top navbar-dark">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand hero-title-brand d-flex align-items-center" href="#">
                <img src="assets/img/logo.png" alt="SM Sport Center" style="height: 38px; width: 38px; object-fit: cover;" class="me-2 rounded-circle border border-light">
                SM Sport Center
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1 text-white"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item">
                        <a class="nav-link custom-link" href="#fasilitas">Fasilitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link custom-link" href="#jadwal">Jadwal</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="booking.php" class="btn-pill">Booking Sekarang</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed nav -->
    <div style="height: 80px;"></div>

    <!-- Hero Section (Color Block Sporty) -->
    <section class="color-block bg-sporty">
        <!-- Floating Cute Animated Icons -->
        <i class="bi bi-dribbble deco-icon deco-1"></i> <!-- Bola -->
        <i class="bi bi-stopwatch deco-icon deco-2"></i> <!-- Stopwatch -->
        <i class="bi bi-trophy deco-icon deco-3"></i> <!-- Piala -->
        <i class="bi bi-activity deco-icon deco-4"></i> <!-- Detak Jantung / Sport -->
        <i class="bi bi-record-circle deco-icon deco-5"></i> <!-- Target/Bola -->
        
        <div class="row align-items-center position-relative z-1">
            <div class="col-lg-8">
                <p class="eyebrow mb-3 opacity-75">Reservasi Real-Time</p>
                <h1 class="display-xl mb-4 text-white">Main lebih puas. Booking tanpa ribet.</h1>
                <p class="fs-4 fw-light text-white opacity-75 mb-5" style="max-width: 600px; line-height: 1.4;">
                    Platform penyewaan lapangan futsal dan badminton premium. Pilih jadwal kosong dan langsung konfirmasi secara instan.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="booking.php" class="btn-pill-light">Mulai Booking</a>
                    <a href="#jadwal" class="btn-pill-outline-light">Lihat Jadwal</a>
                </div>
            </div>
            <!-- Decorative Large Lightning (Static Background) -->
            <div class="col-lg-4 d-none d-lg-flex justify-content-end">
                <i class="bi bi-lightning-charge-fill" style="font-size: 14rem; color: rgba(255,255,255,0.08); transform: rotate(15deg);"></i>
            </div>
        </div>
    </section>

    <!-- Fasilitas Section -->
    <section id="fasilitas" class="container py-5" style="margin-top: 96px; margin-bottom: 96px;">
        <p class="eyebrow text-muted mb-3 text-center">Fasilitas Kami</p>
        <h2 class="display-lg mb-5 text-center">Pilihan Lapangan</h2>
        
        <div class="row g-4 justify-content-center">
            <?php foreach($lapangan as $lap): ?>
            <div class="col-md-6 col-lg-4">
                <div class="editorial-card h-100">
                    <div class="court-image-box">
                        <?php 
                        $nama_lower = strtolower($lap['nama_lapangan']);
                        if (strpos($nama_lower, 'futsal') !== false) {
                            $img_src = strpos($nama_lower, 'sintetis') !== false ? 'assets/img/futsal_sintetis.png' : 'assets/img/futsal.png';
                        } else {
                            $img_src = 'assets/img/badminton.png';
                        }
                        ?>
                        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($lap['nama_lapangan']) ?>">
                        <span class="status-badge-absolute <?= $lap['status'] == 'Tersedia' ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                            <?= $lap['status'] ?>
                        </span>
                    </div>
                    
                    <h4 class="mb-2 fw-bold"><?= htmlspecialchars($lap['nama_lapangan']) ?></h4>
                    <p class="text-muted mb-4">Lap. <?= htmlspecialchars($lap['jenis']) ?></p>
                    
                    <div class="mt-auto pt-3 border-top" style="border-color: var(--hairline) !important;">
                        <span class="fs-3 fw-bold">Rp <?= number_format($lap['harga_per_jam'], 0, ',', '.') ?></span>
                        <span class="text-muted fs-6">/ jam</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Jadwal Interaktif Section (Color Block Lime) -->
    <section id="jadwal" class="color-block bg-lime">
        <div class="text-center mb-5">
            <p class="eyebrow mb-2">Jadwal Hari Ini</p>
            <h2 class="display-lg"><?= date('d F Y') ?></h2>
            <div class="d-flex justify-content-center gap-4 mt-4">
                <span class="fw-medium"><i class="bi bi-circle-fill text-white border rounded-circle me-2"></i>Tersedia</span>
                <span class="fw-medium"><i class="bi bi-circle-fill text-secondary me-2"></i>Dibooking</span>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach($lapangan as $lap): ?>
            <div class="col-lg-6">
                <div class="schedule-grid-box">
                    <h5 class="fw-bold mb-4 pb-3 border-bottom" style="border-color: var(--hairline) !important;"><?= htmlspecialchars($lap['nama_lapangan']) ?></h5>
                    <div class="row g-2">
                        <?php 
                        $id_lap = $lap['id_lapangan'];
                        $jam_sekarang = (int)date('H');
                        for ($jam = 8; $jam <= 22; $jam++): 
                            $is_booked = isset($booked_slots[$id_lap]) && in_array($jam, $booked_slots[$id_lap]);
                            $is_past = ($jam < $jam_sekarang);
                            $time_label = sprintf("%02d:00 - %02d:00", $jam, $jam+1);
                        ?>
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="time-slot-pill <?= ($is_booked || $is_past) ? 'slot-booked' : 'slot-available' ?>" title="<?= $is_past ? 'Sudah lewat' : ($is_booked ? 'Dibooking' : 'Tersedia') ?>">
                                    <?= $time_label ?>
                                    <?php if ($is_past): ?>
                                        <span class="d-block" style="font-size:0.7rem; text-decoration:none; opacity:0.8;">(Lewat)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5 pt-4">
            <a href="booking.php" class="btn-pill">Pesan Sekarang</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-minimal mt-5">
        <div class="container-fluid px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-4 mb-md-0">
                <h3 class="fw-bold mb-1">SM Sport Center</h3>
                <p class="opacity-75 mb-0" style="font-size: 0.875rem;">Sistem Reservasi Lapangan Olahraga</p>
            </div>
            <div class="text-md-end">
                <p class="eyebrow mb-2 opacity-50">&copy; <?= date('Y') ?></p>
                <a href="login.php" class="text-white text-decoration-none border-bottom pb-1" style="font-size: 0.875rem;">Login Administrator</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

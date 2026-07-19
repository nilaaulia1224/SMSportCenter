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
    // Tandai setiap jam yang direkues
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: pulse 15s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1rem;
        }
        .btn-booking-lg {
            background: #fff;
            color: #4f46e5;
            font-weight: 700;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1.2rem;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-booking-lg:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            color: #4338ca;
        }
        /* Court Cards */
        .court-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            height: 100%;
        }
        .court-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }
        .court-icon {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #4f46e5;
            margin-bottom: 1.5rem;
        }
        /* Schedule Grid */
        .schedule-container {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .time-slot {
            padding: 0.5rem;
            text-align: center;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .slot-available {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        .slot-booked {
            background: #f1f5f9;
            color: #94a3b8;
            border-color: #e2e8f0;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        .legend-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="#">
                <i class="bi bi-trophy-fill me-2 fs-4"></i> SM Sport Center
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#fasilitas">Fasilitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#jadwal">Cek Jadwal</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                        <a class="btn btn-primary rounded-pill px-4" href="booking.php">Booking Sekarang</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container position-relative z-1">
            <span class="badge bg-white text-primary px-3 py-2 rounded-pill mb-3 fw-bold">Platform Reservasi Olahraga #1</span>
            <h1 class="hero-title">Main Lebih Puas, Booking Tanpa Ribet.</h1>
            <p class="lead mb-5 opacity-75 mx-auto" style="max-width: 600px;">
                Nikmati fasilitas lapangan futsal dan badminton premium di SM Sport Center. Pesan lapangan Anda secara online sekarang juga.
            </p>
            <a href="booking.php" class="btn btn-booking-lg text-decoration-none">
                <i class="bi bi-calendar-check me-2"></i> Lanjut Booking
            </a>
        </div>
    </section>

    <!-- Fasilitas Section -->
    <section id="fasilitas" class="py-5 mt-4">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Pilihan Lapangan</h2>
                <p class="text-muted">Fasilitas terbaik untuk pengalaman olahraga maksimal.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php foreach($lapangan as $lap): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="court-card p-4">
                        <div class="court-icon">
                            <i class="bi <?= strpos(strtolower($lap['nama_lapangan']), 'futsal') !== false ? 'bi-dribbble' : 'bi-activity' ?>"></i>
                        </div>
                        <h4 class="fw-bold mb-2"><?= htmlspecialchars($lap['nama_lapangan']) ?></h4>
                        <div class="d-flex align-items-center mb-3 text-muted">
                            <i class="bi bi-people-fill me-2"></i> Kapasitas Maksimal: <?= $lap['kapasitas'] ?? 10 ?> Orang
                        </div>
                        <div class="mt-auto">
                            <div class="fs-4 fw-bold text-success mb-1">
                                Rp <?= number_format($lap['harga_per_jam'], 0, ',', '.') ?> <span class="fs-6 text-muted fw-normal">/ jam</span>
                            </div>
                            <span class="badge bg-<?= $lap['status'] == 'Tersedia' ? 'primary' : 'danger' ?> bg-opacity-10 text-<?= $lap['status'] == 'Tersedia' ? 'primary' : 'danger' ?> px-3 py-2 rounded-pill">
                                Status: <?= $lap['status'] ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Jadwal Interaktif Section -->
    <section id="jadwal" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Jadwal Hari Ini</h2>
                <p class="text-muted mb-2"><?= date('d F Y') ?></p>
                <div class="d-flex justify-content-center gap-3">
                    <span class="small fw-medium"><span class="legend-dot bg-success"></span> Tersedia</span>
                    <span class="small fw-medium"><span class="legend-dot bg-secondary"></span> Sudah Dibooking</span>
                </div>
            </div>

            <div class="schedule-container">
                <div class="row g-4">
                    <?php foreach($lapangan as $lap): ?>
                    <div class="col-lg-6">
                        <h5 class="fw-bold mb-3 border-bottom pb-2"><?= htmlspecialchars($lap['nama_lapangan']) ?></h5>
                        <div class="row g-2">
                            <?php 
                            $id_lap = $lap['id_lapangan'];
                            // Loop jam operasi: 08:00 - 22:00
                            for ($jam = 8; $jam <= 21; $jam++): 
                                $is_booked = isset($booked_slots[$id_lap]) && in_array($jam, $booked_slots[$id_lap]);
                                $time_label = sprintf("%02d:00 - %02d:00", $jam, $jam+1);
                            ?>
                                <div class="col-4 col-md-3">
                                    <div class="time-slot <?= $is_booked ? 'slot-booked' : 'slot-available' ?>">
                                        <?= $time_label ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="booking.php" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                    Booking Jadwal Kosong Sekarang <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-auto">
        <div class="container">
            <p class="mb-0 opacity-75">&copy; <?= date('Y') ?> SM Sport Center. All rights reserved.</p>
            <div class="mt-2">
                <a href="login.php" class="text-white-50 text-decoration-none small">Admin Login</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

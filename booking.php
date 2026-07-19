<?php
/**
 * Halaman Booking Publik
 * Tanpa perlu login, cukup masukkan Nama dan No. HP.
 */
require_once 'config/koneksi.php';

// Ambil data lapangan
$lapangan = $koneksi->query("SELECT * FROM lapangan WHERE status = 'Tersedia' ORDER BY nama_lapangan")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama'] ?? '');
    $no_hp       = trim($_POST['no_hp'] ?? '');
    $id_lapangan = $_POST['id_lapangan'] ?? '';
    $tanggal     = $_POST['tanggal'] ?? '';
    $jam_mulai   = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    
    if (empty($nama) || empty($no_hp) || empty($id_lapangan) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        // Validasi waktu terlewat (Past date/time validation)
        $currentDateTime = new DateTime(); // Waktu sekarang
        $bookingDateTime = new DateTime("$tanggal $jam_mulai"); // Waktu yang dipilih pelanggan
        
        if ($bookingDateTime < $currentDateTime) {
            $error = "Tidak dapat melakukan booking untuk waktu yang sudah berlalu.";
        } else {
        try {
            $koneksi->beginTransaction();
            
            // 1. Validasi Double Booking
            $cek = $koneksi->prepare("
                SELECT COUNT(*) FROM reservasi 
                WHERE id_lapangan = ? AND tanggal = ? 
                AND status != 'Dibatalkan'
                AND (
                    (jam_mulai < ? AND jam_selesai > ?)
                )
            ");
            $cek->execute([$id_lapangan, $tanggal, $jam_selesai, $jam_mulai]);
            
            if ($cek->fetchColumn() > 0) {
                throw new Exception("Gagal! Lapangan sudah dipesan pada jadwal tersebut. Silakan pilih waktu lain.");
            }

            // 2. Cek apakah pelanggan sudah ada (berdasarkan no_hp)
            $stmt_pelanggan = $koneksi->prepare("SELECT id_pelanggan FROM pelanggan WHERE no_hp = ? LIMIT 1");
            $stmt_pelanggan->execute([$no_hp]);
            $pelanggan = $stmt_pelanggan->fetch();
            
            $id_pelanggan = null;
            if ($pelanggan) {
                $id_pelanggan = $pelanggan['id_pelanggan'];
            } else {
                // Buat pelanggan baru dengan data dummy untuk field yang wajib tapi tidak ditanyakan
                $username_dummy = "user_" . time();
                $password_dummy = password_hash("pembeli123", PASSWORD_DEFAULT);
                
                $stmt_insert = $koneksi->prepare("INSERT INTO pelanggan (nama, no_hp, email, username, password) VALUES (?, ?, '-', ?, ?)");
                $stmt_insert->execute([$nama, $no_hp, $username_dummy, $password_dummy]);
                $id_pelanggan = $koneksi->lastInsertId();
            }

            // 3. Ambil Harga Lapangan
            $stmt_harga = $koneksi->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ?");
            $stmt_harga->execute([$id_lapangan]);
            $harga_per_jam = $stmt_harga->fetchColumn();
            
            if (!$harga_per_jam) {
                throw new Exception("Lapangan tidak ditemukan.");
            }

            // 4. Hitung Total Bayar
            $waktu_mulai = strtotime($jam_mulai);
            $waktu_selesai = strtotime($jam_selesai);
            
            if ($waktu_selesai <= $waktu_mulai) {
                throw new Exception("Jam Selesai harus lebih besar dari Jam Mulai.");
            }
            
            $durasi_jam = ($waktu_selesai - $waktu_mulai) / 3600;
            $total_bayar = $durasi_jam * $harga_per_jam;

            // 5. Simpan Reservasi
            $stmt_res = $koneksi->prepare("
                INSERT INTO reservasi (id_pelanggan, id_lapangan, tanggal, jam_mulai, jam_selesai, total_bayar, status)
                VALUES (?, ?, ?, ?, ?, ?, 'Menunggu Pembayaran')
            ");
            $stmt_res->execute([$id_pelanggan, $id_lapangan, $tanggal, $jam_mulai, $jam_selesai, $total_bayar]);
            
            $id_reservasi = $koneksi->lastInsertId();

            $koneksi->commit();
            // Redirect ke halaman pembayaran QRIS
            header("Location: pembayaran.php?id=" . $id_reservasi);
            exit();
            
        } catch (Exception $e) {
            $koneksi->rollBack();
            $error = $e->getMessage();
        }
        } // close validation else
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Online - SM Sport Center</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .booking-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.5);
            transition: transform 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
        .header-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 3rem 2rem;
            color: white;
            text-align: center;
            position: relative;
        }
        .header-bg::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 15px 15px 0;
            border-style: solid;
            border-color: #7c3aed transparent transparent transparent;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.8rem 1.2rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            background-color: #fff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-custom {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: white;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }
        .total-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin-top: 1rem;
        }
        .price-text {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="booking-card">
                
                <div class="header-bg">
                    <h2 class="fw-bold mb-2"><i class="bi bi-calendar-event me-2"></i>Booking Lapangan</h2>
                    <p class="mb-0 text-white-50">SM Sport Center - Mudah, Cepat, Tanpa Ribet.</p>
                </div>

                <div class="p-4 p-md-5 mt-3">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-4 py-3 border-0 bg-success bg-opacity-10 text-success fw-medium" role="alert">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= $success ?>
                            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-4 py-3 border-0 bg-danger bg-opacity-10 text-danger fw-medium" role="alert">
                            <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i> <?= $error ?>
                            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="bookingForm">
                        
                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-2">Informasi Pemesan</h5>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" name="nama" class="form-control border-start-0" placeholder="Contoh: Budi Santoso" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">No. WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-whatsapp text-muted"></i></span>
                                    <input type="text" name="no_hp" class="form-control border-start-0" placeholder="08123456xxx" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <p class="form-text text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Pastikan No. WhatsApp aktif untuk konfirmasi pembayaran.</p>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-2 mt-5">Detail Jadwal</h5>
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-medium text-secondary">Pilih Lapangan</label>
                                <select name="id_lapangan" id="id_lapangan" class="form-select" required onchange="hitungTotal()">
                                    <option value="" data-harga="0">-- Pilih Lapangan Tersedia --</option>
                                    <?php foreach ($lapangan as $l): ?>
                                        <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>">
                                            <?= htmlspecialchars($l['nama_lapangan']) ?> (Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium text-secondary">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium text-secondary">Jam Mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" value="08:00" required onchange="hitungTotal()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium text-secondary">Jam Selesai</label>
                                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" value="09:00" required onchange="hitungTotal()">
                            </div>
                        </div>

                        <div class="total-box mt-5 mb-4 shadow-sm">
                            <span class="text-muted fw-medium d-block mb-1">Estimasi Total Pembayaran</span>
                            <div class="price-text" id="display_total">Rp 0</div>
                        </div>

                        <button type="submit" class="btn btn-custom w-100 fs-5 mt-2">
                            <i class="bi bi-check2-circle me-2"></i> Konfirmasi Booking
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-decoration-none text-muted small"><i class="bi bi-shield-lock me-1"></i> Login Admin/Staff</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function hitungTotal() {
    const elLapangan = document.getElementById('id_lapangan');
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;
    const elTotal = document.getElementById('display_total');

    if (!elLapangan.value || !jamMulai || !jamSelesai) {
        elTotal.innerHTML = 'Rp 0';
        return;
    }

    const hargaPerJam = parseInt(elLapangan.options[elLapangan.selectedIndex].getAttribute('data-harga')) || 0;
    
    const startParts = jamMulai.split(':');
    const endParts = jamSelesai.split(':');
    
    const startMinutes = (parseInt(startParts[0]) * 60) + parseInt(startParts[1]);
    const endMinutes = (parseInt(endParts[0]) * 60) + parseInt(endParts[1]);

    if (endMinutes <= startMinutes) {
        elTotal.innerHTML = '<span class="text-danger fs-5">Waktu tidak valid</span>';
        return;
    }

    const durationHours = (endMinutes - startMinutes) / 60;
    const total = Math.round(durationHours * hargaPerJam);
    
    elTotal.innerHTML = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Inisialisasi Flatpickr untuk format 24 jam (menghilangkan AM/PM bawaan browser)
flatpickr("#jam_mulai", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true,
    onChange: hitungTotal
});

flatpickr("#jam_selesai", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true,
    onChange: hitungTotal
});
</script>
</body>
</html>

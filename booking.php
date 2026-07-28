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
    $email       = trim($_POST['email'] ?? '');
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
            $error = "Tidak dapat melakukan booking untuk tanggal atau jam yang sudah berlalu.";
        } else if ($jam_mulai < '08:00' || $jam_mulai > '22:00') {
            $error = "Jam mulai booking hanya tersedia antara jam 08:00 hingga 22:00 (Jam operasional 08:00 - 23:00).";
        } else if ($jam_selesai > '23:00' || $jam_selesai <= $jam_mulai) {
            $error = "Jam selesai penyewaan tidak boleh melebih jam operasional tutup (23:00).";
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
                throw new Exception("sudah dipesan, silahkan pesan jam lain");
            }

            // 2. Cek apakah pelanggan sudah ada (berdasarkan no_hp)
            $stmt_pelanggan = $koneksi->prepare("SELECT id_pelanggan FROM pelanggan WHERE no_hp = ? LIMIT 1");
            $stmt_pelanggan->execute([$no_hp]);
            $pelanggan = $stmt_pelanggan->fetch();
            
            $id_pelanggan = null;
            if ($pelanggan) {
                $id_pelanggan = $pelanggan['id_pelanggan'];
            } else {
                // Buat pelanggan baru
                $stmt_insert = $koneksi->prepare("INSERT INTO pelanggan (nama, no_hp, email) VALUES (?, ?, ?)");
                $stmt_insert->execute([$nama, $no_hp, $email]);
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Figma UI System CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: var(--surface-soft);
        }
        .booking-card {
            background-color: var(--canvas);
            border-radius: var(--rounded-lg);
            border: 1px solid var(--hairline);
            padding: 24px;
            margin: 24px auto;
            max-width: 700px;
        }
        .header-bg {
            background-color: var(--primary);
            color: var(--on-primary);
            padding: 24px;
            border-radius: var(--rounded-md);
            margin-bottom: 24px;
        }
        .total-box {
            background-color: var(--block-lime);
            border-radius: var(--rounded-md);
            padding: 24px;
            text-align: center;
        }
        .price-text {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--ink);
            line-height: 1;
            letter-spacing: -0.02em;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="booking-card shadow-sm">
        
        <div class="header-bg d-flex flex-column align-items-center text-center">
            <h2 class="display-lg mb-2 text-white">Booking Lapangan</h2>
            <p class="mb-0 fs-5">Reservasi Real-Time SM Sport Center</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 py-3 border-0 bg-success bg-opacity-10 text-success fw-medium" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= $success ?>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 py-3 border-0 bg-danger bg-opacity-10 text-danger fw-medium" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i> <?= $error ?>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="bookingForm">
            
            <p class="eyebrow text-muted mb-3 border-bottom pb-2">Informasi Pemesan</p>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-medium text-dark">Nama Lengkap</label>
                    <input type="text" name="nama" class="figma-input" placeholder="Contoh: Budi" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium text-dark">No. WhatsApp</label>
                    <input type="text" name="no_hp" class="figma-input" placeholder="0812xxx" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium text-dark">Email</label>
                    <input type="email" name="email" class="figma-input" placeholder="email@contoh.com">
                </div>
                <div class="col-12 mt-2">
                    <p class="form-text text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Pastikan No. WhatsApp aktif untuk konfirmasi pembayaran.</p>
                </div>
            </div>

            <p class="eyebrow text-muted mb-3 border-bottom pb-2">Detail Jadwal</p>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label fw-medium text-dark">Pilih Lapangan</label>
                    <select name="id_lapangan" id="id_lapangan" class="figma-input" required onchange="hitungTotal()">
                        <option value="" data-harga="0">-- Pilih Lapangan Tersedia --</option>
                        <?php foreach ($lapangan as $l): ?>
                            <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>">
                                <?= htmlspecialchars($l['nama_lapangan']) ?> (Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
                $current_hour = (int)date('H');
                $default_start_hour = max(8, min(22, $current_hour + 1));
                $default_end_hour = min(23, $default_start_hour + 1);
                $default_start_str = sprintf("%02d:00", $default_start_hour);
                $default_end_str   = sprintf("%02d:00", $default_end_hour);
                ?>
                <div class="col-md-4">
                    <label class="form-label fw-medium text-dark">Tanggal</label>
                    <input type="date" name="tanggal" id="input_tanggal" class="figma-input" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium text-dark">Jam Mulai</label>
                    <input type="time" name="jam_mulai" id="jam_mulai" class="figma-input" value="<?= $default_start_str ?>" required onchange="hitungTotal()">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium text-dark">Jam Selesai</label>
                    <input type="time" name="jam_selesai" id="jam_selesai" class="figma-input" value="<?= $default_end_str ?>" required onchange="hitungTotal()">
                </div>
            </div>

            <div class="total-box mb-4">
                <p class="eyebrow text-dark mb-2">Estimasi Total Pembayaran</p>
                <div class="price-text" id="display_total">Rp 0</div>
            </div>

            <button type="button" class="btn-pill w-100 fs-5 mt-2" onclick="bukaModalKonfirmasi()">
                Konfirmasi Booking
            </button>
        </form>
        
        <div class="text-center mt-5">
            <a href="index.php" class="text-decoration-none text-muted border-bottom pb-1 me-4">Kembali ke Beranda</a>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Pesanan & Warning Double Booking -->
<div class="modal fade" id="modalKonfirmasiBooking" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 28px; overflow: hidden;">
            
            <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
                <div>
                    <h3 class="modal-title fw-extrabold text-dark mb-1" style="font-size: 1.6rem; letter-spacing: -0.02em;">Konfirmasi Pesanan Anda</h3>
                    <p class="text-muted small mb-0" style="line-height: 1.4;">Periksa kembali detail reservasi lapangan sebelum melanjutkan ke proses pembayaran.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-4" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                
                <!-- Ringkasan Card -->
                <div class="p-4 mb-3 rounded-4" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary small">Lapangan</span>
                        <span class="fw-bold text-dark text-end" id="m_nama_lapangan">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary small">Tanggal</span>
                        <span class="fw-bold text-dark" id="m_tanggal">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary small">Jam & Durasi</span>
                        <span class="fw-bold text-dark" id="m_jam_durasi">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary small">Tarif per Jam</span>
                        <span class="fw-bold text-dark" id="m_tarif">-</span>
                    </div>
                    <hr class="my-3" style="border-color: #e2e8f0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark fs-6">Total Biaya</span>
                        <span class="fw-bold fs-3" style="color: #1e3a8a;" id="m_total_biaya">Rp 0</span>
                    </div>
                </div>

                <!-- Info Box 10 Menit -->
                <div class="p-3 mb-3 rounded-4 d-flex align-items-center gap-3" style="background-color: #f5f3ff; border: 1px solid #ede9fe; color: #5b21b6;">
                    <span style="font-size: 1.4rem;">💡</span>
                    <div class="small" style="line-height: 1.35; color: #4c1d95;">
                        Setelah tombol di bawah ditekan, slot ini akan ditahan otomatis selama <strong>10 menit</strong> untuk proses pembayaran.
                    </div>
                </div>

                <!-- Warning Box Pesanan Ditolak (Double Booking) -->
                <?php if ($error && strpos(strtolower($error), 'sudah dipesan') !== false): ?>
                <div class="p-3 mb-3 rounded-4 d-flex align-items-center gap-3" style="background-color: #fef2f2; border: 1px solid #fecaca;">
                    <span style="font-size: 1.4rem;">⚠️</span>
                    <div>
                        <div class="fw-bold text-danger uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">PESANAN DITOLAK</div>
                        <div class="fw-bold text-danger" style="font-size: 0.95rem;">sudah dipesan, silahkan pesan jam lain</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-flex flex-column gap-2 mt-4">
                    <?php if ($error && strpos(strtolower($error), 'sudah dipesan') !== false): ?>
                        <button type="button" class="btn btn-secondary rounded-pill py-3 fw-bold disabled" style="background-color: #94a3b8; border:none;">
                            Lanjut Bayar & Konfirmasi
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="submitBookingForm()" class="btn-pill w-100 py-3 fs-6">
                            Lanjut Bayar & Konfirmasi
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-light rounded-pill py-3 fw-semibold text-secondary" data-bs-dismiss="modal">
                        Periksa Kembali / Batal
                    </button>
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
const fpStart = flatpickr("#jam_mulai", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true,
    minTime: "08:00",
    maxTime: "22:00",
    onChange: hitungTotal
});

const fpEnd = flatpickr("#jam_selesai", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true,
    minTime: "09:00",
    maxTime: "23:00",
    onChange: hitungTotal
});

function updateTimeLimits() {
    const tanggalInput = document.getElementById('input_tanggal').value;
    const todayStr = "<?= date('Y-m-d') ?>";
    const currentHour = <?= (int)date('H') ?>;

    if (tanggalInput === todayStr) {
        if (currentHour >= 23) {
            alert("Jam operasional hari ini (08:00 - 23:00) telah berakhir. Silakan pilih tanggal besok atau hari berikutnya untuk booking.");
            // Otomatis ubah tanggal ke besok
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            document.getElementById('input_tanggal').value = tomorrowStr;
            fpStart.set('minTime', '08:00');
            document.getElementById('jam_mulai').value = '08:00';
            document.getElementById('jam_selesai').value = '09:00';
            hitungTotal();
            return;
        }

        let minHour = Math.max(8, currentHour + 1);
        if (minHour > 22) minHour = 22;
        const minTimeStr = (minHour < 10 ? '0' : '') + minHour + ':00';
        fpStart.set('minTime', minTimeStr);
        
        const currentStartVal = document.getElementById('jam_mulai').value;
        if (currentStartVal < minTimeStr) {
            document.getElementById('jam_mulai').value = minTimeStr;
            const nextHour = Math.min(23, minHour + 1);
            document.getElementById('jam_selesai').value = (nextHour < 10 ? '0' : '') + nextHour + ':00';
            hitungTotal();
        }
    } else {
        fpStart.set('minTime', '08:00');
    }
}

document.getElementById('input_tanggal').addEventListener('change', updateTimeLimits);
updateTimeLimits();

function bukaModalKonfirmasi() {
    const form = document.getElementById('bookingForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const elLapangan = document.getElementById('id_lapangan');
    const namaLap = elLapangan.options[elLapangan.selectedIndex].text.split('(')[0].trim();
    const hargaPerJam = parseInt(elLapangan.options[elLapangan.selectedIndex].getAttribute('data-harga')) || 0;
    const tanggal = document.getElementById('input_tanggal').value;
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;

    const startParts = jamMulai.split(':');
    const endParts = jamSelesai.split(':');
    const startMins = (parseInt(startParts[0]) * 60) + parseInt(startParts[1]);
    const endMins = (parseInt(endParts[0]) * 60) + parseInt(endParts[1]);
    
    if (endMins <= startMins) {
        alert("Jam Selesai harus lebih besar dari Jam Mulai!");
        return;
    }

    const durasiJam = (endMins - startMins) / 60;
    const total = Math.round(durasiJam * hargaPerJam);

    document.getElementById('m_nama_lapangan').innerText = namaLap;
    document.getElementById('m_tanggal').innerText = tanggal;
    document.getElementById('m_jam_durasi').innerText = jamMulai + ' - ' + jamSelesai + ' WIB (' + durasiJam + ' Jam)';
    document.getElementById('m_tarif').innerText = 'Rp ' + hargaPerJam.toLocaleString('id-ID');
    document.getElementById('m_total_biaya').innerText = 'Rp ' + total.toLocaleString('id-ID');

    const modal = new bootstrap.Modal(document.getElementById('modalKonfirmasiBooking'));
    modal.show();
}

function submitBookingForm() {
    document.getElementById('bookingForm').submit();
}

<?php if ($error): ?>
window.addEventListener('DOMContentLoaded', function() {
    bukaModalKonfirmasi();
});
<?php endif; ?>
</script>
</body>
</html>

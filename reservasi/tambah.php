<?php
$page_title = "Tambah Reservasi";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

$error = '';

// Mengambil data untuk dropdown
$pelanggan = $koneksi->query("SELECT * FROM pelanggan ORDER BY nama")->fetchAll();
$lapangan = $koneksi->query("SELECT * FROM lapangan ORDER BY nama_lapangan")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelanggan = $_POST['id_pelanggan'] ?? '';
    $id_lapangan  = $_POST['id_lapangan'] ?? '';
    $tanggal      = $_POST['tanggal'] ?? '';
    $jam_mulai    = $_POST['jam_mulai'] ?? '';
    $jam_selesai  = $_POST['jam_selesai'] ?? '';
    $total_bayar  = $_POST['total_bayar'] ?? 0;
    $status       = $_POST['status'] ?? 'Menunggu Pembayaran';

    if (empty($id_pelanggan) || empty($id_lapangan) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai) || empty($total_bayar)) {
        $error = "Semua kolom wajib diisi!";
    } else if ($jam_mulai < '08:00' || $jam_mulai > '22:00') {
        $error = "Jam mulai booking hanya tersedia antara jam 08:00 hingga 22:00 (Jam operasional 08:00 - 23:00).";
    } else if ($jam_selesai > '23:00' || $jam_selesai <= $jam_mulai) {
        $error = "Jam selesai penyewaan tidak boleh melebihi jam operasional tutup (23:00).";
    } else {
        // Validasi Double Booking: Mengecek jadwal yang beririsan
        $is_bentrok = false;

        $cek = $koneksi->prepare("
            SELECT COUNT(*) FROM reservasi 
            WHERE id_lapangan = ? AND tanggal = ? 
            AND status != 'Dibatalkan'
            AND (
                (jam_mulai < ? AND jam_selesai > ?)
            )
        ");
        // jam_selesai input dibandingkan dengan jam_mulai existing, dan sebaliknya
        $cek->execute([$id_lapangan, $tanggal, $jam_selesai, $jam_mulai]);
        
        if ($cek->fetchColumn() > 0) {
            $is_bentrok = true;
        }

        if ($is_bentrok) {
            $error = "Gagal! Lapangan sudah dipesan pada jadwal tersebut (Double Booking).";
        } else {
            // Simpan ke database
            $stmt = $koneksi->prepare("
                INSERT INTO reservasi (id_pelanggan, id_lapangan, tanggal, jam_mulai, jam_selesai, total_bayar, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pelanggan, $id_lapangan, $tanggal, $jam_mulai, $jam_selesai, $total_bayar, $status]);
            
            $_SESSION['success'] = "Data reservasi berhasil ditambahkan!";
            echo "<script>window.location.href = 'index.php';</script>";
            exit();
        }
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Reservasi</h4>
        <p class="text-muted mb-0">Masukkan data pemesanan baru</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form action="" method="POST" id="formReservasi">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <select name="id_pelanggan" class="form-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($pelanggan as $p): ?>
                            <option value="<?= $p['id_pelanggan'] ?>" <?= (($_POST['id_pelanggan'] ?? '') == $p['id_pelanggan']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['no_hp']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Lapangan</label>
                    <select name="id_lapangan" id="id_lapangan" class="form-select" required onchange="hitungTotal()">
                        <option value="" data-harga="0">-- Pilih Lapangan --</option>
                        <?php foreach ($lapangan as $l): ?>
                            <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>" <?= (($_POST['id_lapangan'] ?? '') == $l['id_lapangan']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['nama_lapangan']) ?> (Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Jam Mulai</label>
                    <select name="jam_mulai" id="jam_mulai" class="form-select" required onchange="hitungTotal()">
                        <?php 
                        $val_mulai = $_POST['jam_mulai'] ?? '08:00';
                        for ($h = 8; $h <= 22; $h++) {
                            foreach (['00', '30'] as $m) {
                                $t = sprintf('%02d:%s', $h, $m);
                                $sel = (substr($val_mulai,0,5) == $t) ? 'selected' : '';
                                echo "<option value=\"$t\" $sel>$t WIB</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Jam Selesai</label>
                    <select name="jam_selesai" id="jam_selesai" class="form-select" required onchange="hitungTotal()">
                        <?php 
                        $val_selesai = $_POST['jam_selesai'] ?? '09:00';
                        for ($h = 8; $h <= 23; $h++) {
                            foreach (['00', '30'] as $m) {
                                if ($h == 23 && $m == '30') continue; // jam tutup 23:00
                                if ($h == 8 && $m == '00') continue;
                                $t = sprintf('%02d:%s', $h, $m);
                                $sel = (substr($val_selesai,0,5) == $t) ? 'selected' : '';
                                echo "<option value=\"$t\" $sel>$t WIB</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Total Bayar (Rp)</label>
                    <input type="number" name="total_bayar" id="total_bayar" class="form-control" value="<?= htmlspecialchars($_POST['total_bayar'] ?? 0) ?>" required readonly style="background-color: #e9ecef;">
                    <small class="text-muted">Total bayar dihitung otomatis berdasarkan durasi dan harga lapangan.</small>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Menunggu Pembayaran" <?= (($_POST['status'] ?? '') == 'Menunggu Pembayaran') ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                        <option value="Selesai" <?= (($_POST['status'] ?? '') == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                        <option value="Dibatalkan" <?= (($_POST['status'] ?? '') == 'Dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-light border">Reset</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Reservasi</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi otomatisasi hitung total bayar
function hitungTotal() {
    const elLapangan = document.getElementById('id_lapangan');
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;
    const elTotal = document.getElementById('total_bayar');

    if (!elLapangan.value || !jamMulai || !jamSelesai) {
        elTotal.value = 0;
        return;
    }

    // Ambil atribut data-harga dari option yang dipilih
    const hargaPerJam = parseInt(elLapangan.options[elLapangan.selectedIndex].getAttribute('data-harga')) || 0;

    // Konversi jam ke menit
    const startParts = jamMulai.split(':');
    const endParts = jamSelesai.split(':');
    
    const startMinutes = (parseInt(startParts[0]) * 60) + parseInt(startParts[1]);
    const endMinutes = (parseInt(endParts[0]) * 60) + parseInt(endParts[1]);

    if (endMinutes <= startMinutes) {
        elTotal.value = 0;
        return;
    }

    // Hitung selisih jam
    const durationHours = (endMinutes - startMinutes) / 60;
    
    // Hitung total
    const total = Math.round(durationHours * hargaPerJam);
    elTotal.value = total;
}

// Panggil sekali saat load (untuk menangani isian form saat error submit)
window.onload = hitungTotal;
</script>

<?php require_once '../includes/footer.php'; ?>

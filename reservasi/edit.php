<?php
$page_title = "Edit Reservasi";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

$error = '';
$id = $_GET['id'] ?? 0;

// Cek data
$stmt = $koneksi->prepare("SELECT * FROM reservasi WHERE id_reservasi = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    echo "<div class='alert alert-danger m-4'>Data tidak ditemukan! <a href='index.php'>Kembali</a></div>";
    require_once '../includes/footer.php';
    exit;
}

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

    if (empty($id_pelanggan) || empty($id_lapangan) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        $is_bentrok = false;

        $cek = $koneksi->prepare("
            SELECT COUNT(*) FROM reservasi 
            WHERE id_lapangan = ? AND tanggal = ? 
            AND id_reservasi != ? 
            AND status != 'Dibatalkan'
            AND (
                (jam_mulai < ? AND jam_selesai > ?)
            )
        ");
        $cek->execute([$id_lapangan, $tanggal, $id, $jam_selesai, $jam_mulai]);
        
        if ($cek->fetchColumn() > 0) {
            $is_bentrok = true;
        }

        if ($is_bentrok) {
            $error = "Gagal! Lapangan sudah dipesan pada jadwal tersebut (Double Booking).";
        } else {
            // Update ke database
            $stmt = $koneksi->prepare("
                UPDATE reservasi SET 
                id_pelanggan = ?, id_lapangan = ?, tanggal = ?, 
                jam_mulai = ?, jam_selesai = ?, total_bayar = ?, status = ?
                WHERE id_reservasi = ?
            ");
            $stmt->execute([$id_pelanggan, $id_lapangan, $tanggal, $jam_mulai, $jam_selesai, $total_bayar, $status, $id]);
            
            $_SESSION['success'] = "Data reservasi berhasil diupdate!";
            echo "<script>window.location.href = 'index.php';</script>";
            exit();
        }
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Reservasi</h4>
        <p class="text-muted mb-0">Ubah data pemesanan #<?= htmlspecialchars($id) ?></p>
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
                            <option value="<?= $p['id_pelanggan'] ?>" <?= (($data['id_pelanggan'] == $p['id_pelanggan'])) ? 'selected' : '' ?>>
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
                            <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>" <?= (($data['id_lapangan'] == $l['id_lapangan'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['nama_lapangan']) ?> (Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($data['tanggal']) ?>" required>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Jam Mulai</label>
                    <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" value="<?= htmlspecialchars(substr($data['jam_mulai'],0,5)) ?>" required onchange="hitungTotal()">
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Jam Selesai</label>
                    <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" value="<?= htmlspecialchars(substr($data['jam_selesai'],0,5)) ?>" required onchange="hitungTotal()">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Total Bayar (Rp)</label>
                    <input type="number" name="total_bayar" id="total_bayar" class="form-control" value="<?= htmlspecialchars($data['total_bayar']) ?>" required readonly style="background-color: #e9ecef;">
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Menunggu Pembayaran" <?= ($data['status'] == 'Menunggu Pembayaran') ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                        <option value="Selesai" <?= ($data['status'] == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                        <option value="Dibatalkan" <?= ($data['status'] == 'Dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Reservasi</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi otomatisasi hitung total bayar (sama seperti di tambah.php)
function hitungTotal() {
    const elLapangan = document.getElementById('id_lapangan');
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;
    const elTotal = document.getElementById('total_bayar');

    if (!elLapangan.value || !jamMulai || !jamSelesai) {
        return; // Jangan set 0 jika lagi loading data lama
    }

    const hargaPerJam = parseInt(elLapangan.options[elLapangan.selectedIndex].getAttribute('data-harga')) || 0;
    const startParts = jamMulai.split(':');
    const endParts = jamSelesai.split(':');
    
    const startMinutes = (parseInt(startParts[0]) * 60) + parseInt(startParts[1]);
    const endMinutes = (parseInt(endParts[0]) * 60) + parseInt(endParts[1]);

    if (endMinutes <= startMinutes) {
        return;
    }

    const durationHours = (endMinutes - startMinutes) / 60;
    const total = Math.round(durationHours * hargaPerJam);
    
    // Update hanya jika user mengubah dropdown (manual interaction)
    // agar harga lama yang fix tidak kerubah otomatis kalau gak diapa2in
    elTotal.value = total;
}
</script>

<?php require_once '../includes/footer.php'; ?>

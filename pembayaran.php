<?php
require_once 'config/koneksi.php';

$id_reservasi = $_GET['id'] ?? 0;

// Ambil detail reservasi
$stmt = $koneksi->prepare("
    SELECT r.*, p.nama, p.no_hp, l.nama_lapangan 
    FROM reservasi r
    JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    JOIN lapangan l ON r.id_lapangan = l.id_lapangan
    WHERE r.id_reservasi = ?
");
$stmt->execute([$id_reservasi]);
$data = $stmt->fetch();

if (!$data) {
    echo "Data reservasi tidak ditemukan.";
    exit;
}

// Proses Upload Bukti Pembayaran
$upload_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti'])) {
    $file = $_FILES['bukti'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array(strtolower($ext), $allowed)) {
            $filename = "bukti_" . $id_reservasi . "_" . time() . "." . $ext;
            $destination = "uploads/bukti_pembayaran/" . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt_up = $koneksi->prepare("UPDATE reservasi SET bukti_pembayaran = ? WHERE id_reservasi = ?");
                $stmt_up->execute([$filename, $id_reservasi]);
                // Refresh data
                $data['bukti_pembayaran'] = $filename;
                $upload_msg = "<div class='alert alert-success'>Bukti pembayaran berhasil diunggah!</div>";
            } else {
                $upload_msg = "<div class='alert alert-danger'>Gagal menyimpan file.</div>";
            }
        } else {
            $upload_msg = "<div class='alert alert-danger'>Format file harus JPG atau PNG.</div>";
        }
    } else {
        $upload_msg = "<div class='alert alert-danger'>Terjadi kesalahan saat upload.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran QRIS - SM Sport Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .payment-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .header-qris {
            background: #ea0a2a; /* Warna khas merah QRIS */
            color: white;
            padding: 1.5rem;
        }
        .qr-placeholder {
            width: 250px;
            height: 250px;
            background: #fff;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            margin: 2rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .qr-placeholder img {
            max-width: 90%;
            border-radius: 8px;
        }
        .price-box {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        .btn-wa {
            background: #25D366;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-wa:hover {
            background: #128C7E;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="payment-card">
        <div class="header-qris">
            <h3 class="fw-bold mb-0">Pembayaran QRIS</h3>
            <p class="mb-0 text-white-50">Scan kode di bawah untuk membayar</p>
        </div>
        
        <div class="p-4 p-md-5">
            <h5 class="fw-bold text-secondary mb-1">Total Tagihan</h5>
            <div class="price-box">
                Rp <?= number_format($data['total_bayar'], 0, ',', '.') ?>
            </div>

            <div class="qr-placeholder">
                <!-- Dummy QR Code (menggunakan API publik untuk dummy QR) -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=DUMMY_QRIS_<?= $data['id_reservasi'] ?>" alt="QRIS">
            </div>

            <div class="bg-light rounded-3 p-3 text-start mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Kode Booking:</span>
                    <span class="fw-bold">#<?= $data['id_reservasi'] ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nama:</span>
                    <span class="fw-semibold"><?= htmlspecialchars($data['nama']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Lapangan:</span>
                    <span class="fw-semibold"><?= htmlspecialchars($data['nama_lapangan']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Jadwal:</span>
                    <span class="fw-semibold text-end">
                        <?= date('d M Y', strtotime($data['tanggal'])) ?><br>
                        <?= substr($data['jam_mulai'],0,5) ?> - <?= substr($data['jam_selesai'],0,5) ?>
                    </span>
                </div>
            </div>

            <?= $upload_msg ?>
            
            <?php if (empty($data['bukti_pembayaran'])): ?>
                <!-- Form Upload -->
                <form action="pembayaran.php?id=<?= $id_reservasi ?>" method="POST" enctype="multipart/form-data" class="mb-4 text-start">
                    <div class="p-4 rounded-4" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                        <label class="form-label fw-bold text-dark mb-3"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Bukti Pembayaran</label>
                        <input type="file" name="bukti" class="form-control form-control-lg mb-3 shadow-sm" accept=".jpg,.jpeg,.png" style="border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.9rem;" required>
                        <button type="submit" class="btn w-100 fw-bold" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; border-radius: 12px; padding: 0.8rem; border: none; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);">
                            Kirim Bukti Pembayaran
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Bukti Sudah Diupload -->
                <div class="alert alert-success d-flex align-items-center p-4 rounded-4 mb-4 border-0" style="background-color: #dcfce7; color: #166534;">
                    <i class="bi bi-check-circle-fill fs-3 me-3"></i> 
                    <div>
                        <h6 class="fw-bold mb-1">Berhasil Diunggah!</h6>
                        <span class="small">Bukti pembayaran Anda telah tersimpan di sistem kami.</span>
                    </div>
                </div>
                <?php
                // Text WA Konfirmasi
                $pesan = "Halo Admin SM Sport Center, saya sudah melakukan pembayaran QRIS untuk booking: \n\nKode: #{$data['id_reservasi']}\nNama: {$data['nama']}\nLapangan: {$data['nama_lapangan']}\nTanggal: {$data['tanggal']}\nTotal: Rp " . number_format($data['total_bayar'],0,',','.') . "\n\nTerlampir bukti transfer saya (Sudah diupload di sistem).";
                $wa_url = "https://wa.me/6281234567890?text=" . urlencode($pesan);
                ?>
                <a href="<?= $wa_url ?>" target="_blank" class="btn btn-wa text-decoration-none d-block">
                    <i class="bi bi-whatsapp me-2"></i> Konfirmasi via WhatsApp
                </a>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="booking.php" class="text-muted text-decoration-none small">Kembali ke Halaman Booking</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

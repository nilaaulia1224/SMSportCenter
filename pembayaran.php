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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Figma UI System CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--surface-soft);
        }
        .payment-card {
            background-color: var(--canvas);
            border-radius: var(--rounded-lg);
            border: 1px solid var(--hairline);
            padding: 24px;
            margin: 24px auto;
            max-width: 500px;
            text-align: center;
        }
        .header-qris {
            background-color: var(--block-cream);
            color: var(--ink);
            padding: 24px;
            border-radius: var(--rounded-md);
            margin-bottom: 24px;
            border: 1px solid var(--hairline);
        }
        .qr-placeholder {
            width: 200px;
            height: 200px;
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--rounded-md);
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .qr-placeholder img {
            max-width: 100%;
            border-radius: var(--rounded-xs);
        }
        .price-box {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1;
            letter-spacing: -0.02em;
        }
        .btn-wa {
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: var(--rounded-pill);
            padding: 12px 28px;
            font-weight: 600;
            font-size: 1.125rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition);
            text-decoration: none;
        }
        .btn-wa:hover {
            background-color: #128C7E;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="payment-card shadow-sm">
        <div class="header-qris">
            <h3 class="display-lg mb-2" style="font-size: 2rem;">Pembayaran QRIS</h3>
            <p class="mb-0 fs-6 text-muted">Scan kode di bawah untuk membayar</p>
        </div>
        
        <div>
            <p class="eyebrow text-muted mb-2">Total Tagihan</p>
            <div class="price-box">
                Rp <?= number_format($data['total_bayar'], 0, ',', '.') ?>
            </div>

            <div class="qr-placeholder shadow-sm">
                <!-- Dummy QR Code (menggunakan API publik untuk dummy QR) -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=DUMMY_QRIS_<?= $data['id_reservasi'] ?>" alt="QRIS">
            </div>

            <div class="bg-light rounded-3 p-4 text-start mb-4 border" style="border-color: var(--hairline) !important; background-color: var(--surface-soft) !important;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fs-6">Kode Booking:</span>
                    <span class="fw-bold text-dark">#<?= $data['id_reservasi'] ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fs-6">Nama:</span>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($data['nama']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fs-6">Lapangan:</span>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($data['nama_lapangan']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted fs-6">Jadwal:</span>
                    <span class="fw-bold text-dark text-end">
                        <?= date('d M Y', strtotime($data['tanggal'])) ?><br>
                        <?= substr($data['jam_mulai'],0,5) ?> - <?= substr($data['jam_selesai'],0,5) ?>
                    </span>
                </div>
            </div>

            <?= $upload_msg ?>
            
            <?php if (empty($data['bukti_pembayaran'])): ?>
                <!-- Form Upload -->
                <form action="pembayaran.php?id=<?= $id_reservasi ?>" method="POST" enctype="multipart/form-data" class="mb-4 text-start">
                    <div class="p-4 rounded-3 border" style="background-color: var(--canvas); border-color: var(--hairline) !important;">
                        <label class="form-label fw-bold text-dark mb-3"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Bukti Pembayaran</label>
                        <input type="file" name="bukti" class="figma-input mb-4" accept=".jpg,.jpeg,.png" required>
                        <button type="submit" class="btn-pill w-100 fs-5">
                            Kirim Bukti Pembayaran
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Bukti Sudah Diupload -->
                <div class="alert alert-success d-flex align-items-center p-4 rounded-3 mb-4 border" style="background-color: var(--block-lime); color: var(--ink); border-color: var(--hairline) !important;">
                    <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i> 
                    <div class="text-start">
                        <h6 class="fw-bold mb-1">Berhasil Diunggah!</h6>
                        <span class="small opacity-75">Bukti pembayaran Anda telah tersimpan.</span>
                    </div>
                </div>
                <?php
                // Text WA Konfirmasi
                $pesan = "Halo Admin SM Sport Center, saya sudah melakukan pembayaran QRIS untuk booking: \n\nKode: #{$data['id_reservasi']}\nNama: {$data['nama']}\nLapangan: {$data['nama_lapangan']}\nTanggal: {$data['tanggal']}\nTotal: Rp " . number_format($data['total_bayar'],0,',','.') . "\n\nTerlampir bukti transfer saya (Sudah diupload di sistem).";
                $wa_url = "https://wa.me/6283160763177?text=" . urlencode($pesan);
                ?>
                <a href="<?= $wa_url ?>" target="_blank" class="btn-wa mt-3 w-100">
                    <i class="bi bi-whatsapp me-2"></i> Konfirmasi via WhatsApp
                </a>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="index.php" class="text-muted text-decoration-none border-bottom pb-1">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

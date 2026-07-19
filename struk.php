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

// Cek apakah lunas
$is_lunas = ($data['status'] === 'Selesai');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #<?= $id_reservasi ?> - SM Sport Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9;
            padding: 2rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .receipt-card {
            background: #fff;
            max-width: 450px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            position: relative;
        }
        .receipt-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .receipt-header h3 {
            font-weight: 800;
            margin-bottom: 0;
            letter-spacing: 1px;
        }
        .receipt-body {
            padding: 2rem;
        }
        .stamp-lunas {
            position: absolute;
            top: 20px;
            right: 20px;
            border: 3px solid #fff;
            color: #fff;
            padding: 5px 15px;
            font-size: 1.2rem;
            font-weight: 800;
            transform: rotate(15deg);
            border-radius: 8px;
            opacity: 0.9;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .detail-label {
            color: #64748b;
            font-weight: 500;
        }
        .detail-value {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
        }
        .total-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin-top: 1.5rem;
        }
        .total-price {
            font-size: 2rem;
            font-weight: 800;
            color: #10b981;
        }
        .btn-print {
            background: #4f46e5;
            color: white;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-print:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
        @media print {
            body { background: white; padding: 0; }
            .receipt-card { box-shadow: none; max-width: 100%; border-radius: 0; }
            .btn-print, .btn-home { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="receipt-card">
        <div class="receipt-header">
            <?php if ($is_lunas): ?>
                <div class="stamp-lunas">LUNAS</div>
            <?php endif; ?>
            <h3 class="mb-1">E-RECEIPT</h3>
            <p class="mb-0 text-white-50">SM Sport Center</p>
        </div>
        
        <div class="receipt-body">
            <div class="text-center mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h5 class="fw-bold mt-2">Pembayaran Berhasil</h5>
                <p class="text-muted small">Terima kasih telah melakukan reservasi.</p>
            </div>

            <div class="detail-row">
                <span class="detail-label">Kode Booking</span>
                <span class="detail-value">#<?= $data['id_reservasi'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Nama Pelanggan</span>
                <span class="detail-value"><?= htmlspecialchars($data['nama']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Lapangan</span>
                <span class="detail-value"><?= htmlspecialchars($data['nama_lapangan']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tanggal Main</span>
                <span class="detail-value"><?= date('d F Y', strtotime($data['tanggal'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Waktu</span>
                <span class="detail-value"><?= substr($data['jam_mulai'],0,5) ?> - <?= substr($data['jam_selesai'],0,5) ?></span>
            </div>

            <div class="total-box">
                <div class="text-muted fw-semibold mb-1">TOTAL DIBAYAR</div>
                <div class="total-price">Rp <?= number_format($data['total_bayar'], 0, ',', '.') ?></div>
            </div>
            
            <div class="d-flex justify-content-between mt-4 gap-2">
                <a href="index.php" class="btn btn-light fw-semibold flex-fill btn-home"><i class="bi bi-house me-2"></i>Beranda</a>
                <button onclick="window.print()" class="btn btn-print flex-fill"><i class="bi bi-printer me-2"></i>Cetak</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>

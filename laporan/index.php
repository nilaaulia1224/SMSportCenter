<?php
$page_title = "Laporan Pendapatan";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01'); // Default awal bulan ini
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');  // Default akhir bulan ini

$laporan = [];
$total_pendapatan = 0;
$total_transaksi = 0;

if (isset($_GET['filter'])) {
    $sql = "SELECT r.*, p.nama, l.nama_lapangan 
            FROM reservasi r
            JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
            JOIN lapangan l ON r.id_lapangan = l.id_lapangan
            WHERE r.status = 'Selesai' 
            AND r.tanggal BETWEEN ? AND ?
            ORDER BY r.tanggal ASC, r.jam_mulai ASC";
    
    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$tgl_mulai, $tgl_akhir]);
    $laporan = $stmt->fetchAll();

    foreach ($laporan as $row) {
        $total_pendapatan += $row['total_bayar'];
        $total_transaksi++;
    }
}
?>

<style>
@media print {
    /* Sembunyikan elemen yang tidak perlu dicetak */
    .sidebar, .navbar, .page-header, .filter-card, .btn, footer, .wrapper {
        display: none !important;
    }
    body, #content {
        margin: 0;
        padding: 0;
        width: 100%;
        background: #fff;
    }
    .print-area {
        display: block !important;
        width: 100%;
    }
    .print-header {
        text-align: center;
        margin-bottom: 2rem;
        border-bottom: 2px solid #000;
        padding-bottom: 1rem;
    }
    .print-header h2 {
        margin: 0;
        font-weight: bold;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    table, th, td {
        border: 1px solid #000 !important;
    }
    th {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact; 
    }
    .total-row {
        font-weight: bold;
        background-color: #e9ecef !important;
        -webkit-print-color-adjust: exact; 
    }
}
</style>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-bar-chart-fill me-2 text-danger"></i>Laporan Pendapatan</h4>
        <p class="text-muted mb-0">Cetak laporan penggunaan lapangan (Status: Selesai)</p>
    </div>
    <?php if (isset($_GET['filter'])): ?>
    <button onclick="window.print()" class="btn btn-success shadow-sm">
        <i class="bi bi-printer me-1"></i> Cetak Laporan
    </button>
    <?php endif; ?>
</div>

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4 filter-card">
    <div class="card-body p-4">
        <form action="" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Dari Tanggal</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" name="filter" value="1" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['filter'])): ?>
<!-- Area Cetak Laporan -->
<div class="print-area card shadow-sm border-0">
    <div class="card-body p-4 p-print-0">
        
        <!-- Header Khusus Print -->
        <div class="print-header d-none d-print-block">
            <h2>SM SPORT CENTER</h2>
            <p class="mb-0">Sistem Reservasi Lapangan Olahraga</p>
            <p class="mb-0"><strong>Laporan Pendapatan Reservasi</strong></p>
            <p class="mb-0">Periode: <?= date('d M Y', strtotime($tgl_mulai)) ?> s.d <?= date('d M Y', strtotime($tgl_akhir)) ?></p>
        </div>

        <div class="d-print-none mb-3">
            <h5 class="fw-bold">Hasil Filter: <span class="text-primary"><?= date('d M Y', strtotime($tgl_mulai)) ?> - <?= date('d M Y', strtotime($tgl_akhir)) ?></span></h5>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">No</th>
                        <th>Tanggal</th>
                        <th>Kode Booking</th>
                        <th>Nama Pelanggan</th>
                        <th>Lapangan</th>
                        <th>Jam</th>
                        <th class="text-end">Pendapatan (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_transaksi > 0): ?>
                        <?php $no=1; foreach ($laporan as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td>#<?= $row['id_reservasi'] ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                                <td><?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></td>
                                <td class="text-end"><?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Baris Total -->
                        <tr class="total-row">
                            <td colspan="6" class="text-end pe-3">TOTAL PENDAPATAN :</td>
                            <td class="text-end text-success fs-5">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada transaksi Selesai pada rentang tanggal tersebut.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_transaksi > 0): ?>
        <div class="d-none d-print-block mt-5 pt-4 text-end">
            <p>Dicetak pada: <?= date('d M Y H:i') ?></p>
            <br><br><br>
            <p><strong>Admin SM Sport Center</strong></p>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

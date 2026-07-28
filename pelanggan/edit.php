<?php
$page_title = "Edit Pelanggan";
require_once '../config/koneksi.php';
require_once '../includes/header.php';

$error = '';
$id = $_GET['id'] ?? 0;

$stmt = $koneksi->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    echo "<div class='alert alert-danger m-4'>Data tidak ditemukan! <a href='index.php'>Kembali</a></div>";
    require_once '../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($nama) || empty($no_hp)) {
        $error = "Nama dan No. WhatsApp wajib diisi!";
    } else {
        // Cek duplikasi no hp selain dirinya sendiri
        $cek = $koneksi->prepare("SELECT COUNT(*) FROM pelanggan WHERE no_hp = ? AND id_pelanggan != ?");
        $cek->execute([$no_hp, $id]);
        
        if ($cek->fetchColumn() > 0) {
            $error = "Nomor WhatsApp tersebut sudah digunakan oleh pelanggan lain!";
        } else {
            $stmt = $koneksi->prepare("UPDATE pelanggan SET nama=?, no_hp=?, email=? WHERE id_pelanggan=?");
            $stmt->execute([$nama, $no_hp, $email, $id]);
            
            $_SESSION['success'] = "Data pelanggan berhasil diperbarui!";
            echo "<script>window.location.href = 'index.php';</script>";
            exit();
        }
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Pelanggan</h4>
        <p class="text-muted mb-0">Ubah data profil pelanggan</p>
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

<div class="card shadow-sm border-0 mb-4" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="" method="POST">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? $data['nama']) ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">No. WhatsApp <span class="text-danger">*</span></label>
                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? $data['no_hp']) ?>" required>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $data['email']) ?>">
                </div>
            </div>

            
            
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Pelanggan</button>
            </div>

        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
/**
 * Halaman Login - login.php
 * Autentikasi pengguna dengan session dan password_verify()
 */
session_start();

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/koneksi.php';

$error = '';

// Proses form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan sanitasi input
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        // Cari user berdasarkan username (Prepared Statement)
        $stmt = $koneksi->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil — simpan ke session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SM Sport Center</title>
    <meta name="description" content="Halaman login Sistem Reservasi SM Sport Center">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Logo -->
        <div class="text-center mb-4">
            <div class="login-logo">
                <i class="bi bi-trophy-fill text-white"></i>
            </div>
            <h3 class="fw-bold mt-3">SM Sport Center</h3>
            <p class="text-muted mb-0">Sistem Reservasi Lapangan Olahraga</p>
            <p class="text-muted" style="font-size:0.8rem;">Masuk untuk mengakses dashboard</p>
        </div>

        <!-- Alert error -->
        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4 py-3" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2 flex-shrink-0"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form action="login.php" method="POST" novalidate id="formLogin">

            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-1"></i> Username
                </label>
                <input
                    type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i> Password
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control border-end-0"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="btn btn-light border border-start-0"
                            id="togglePassword" title="Tampilkan/Sembunyikan Password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btnLogin">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Masuk
            </button>
        </form>

        <!-- Info akun default -->
        <div class="mt-4 p-3 rounded-3" style="background:#f8fafc;border:1px dashed #cbd5e1;">
            <p class="mb-1 text-muted" style="font-size:0.78rem;font-weight:600;">
                <i class="bi bi-info-circle me-1"></i> AKUN DEMO
            </p>
            <p class="mb-0" style="font-size:0.78rem;color:#475569;">
                Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code>
            </p>
        </div>

        <p class="text-center text-muted mt-4 mb-0" style="font-size:0.75rem;">
            &copy; <?= date('Y') ?> SM Sport Center — Studi Kasus Sertifikasi Analis Program
        </p>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle show/hide password
document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

// Nonaktifkan tombol saat submit agar tidak double submit
document.getElementById('formLogin').addEventListener('submit', function () {
    const btn = document.getElementById('btnLogin');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>
</body>
</html>

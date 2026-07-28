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
            <img src="assets/img/logo.png" alt="SM Sport Center" style="width: 80px; height: 80px; object-fit: cover;" class="rounded-circle border border-2 border-primary shadow-sm mb-3">
            <h3 class="fw-bold text-dark">SM Sport Center</h3>
            <p class="text-muted fs-6 mb-0">Sistem Reservasi Lapangan Olahraga</p>
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

            <div class="mb-4">
                <label for="username" class="form-label text-dark fw-medium">
                    <i class="bi bi-person me-1"></i> Username
                </label>
                <input
                    type="text"
                    class="figma-input"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-dark fw-medium">
                    <i class="bi bi-lock me-1"></i> Password
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        class="figma-input border-end-0"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                        style="border-top-right-radius: 0; border-bottom-right-radius: 0;"
                    >
                    <button type="button" class="btn btn-light border"
                            id="togglePassword" title="Tampilkan/Sembunyikan Password" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px; border-color: var(--hairline) !important; background: var(--canvas);">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-pill w-100 py-3 mt-2" id="btnLogin">
                Masuk ke Dasbor
            </button>
        </form>

        <!-- Info akun default -->
        <div class="mt-5 p-3 rounded-md" style="background:var(--block-cream); border:1px solid var(--hairline);">
            <p class="mb-1 text-dark eyebrow">
                <i class="bi bi-info-circle me-1"></i> AKUN DEMO
            </p>
            <p class="mb-0 fs-6 text-dark">
                Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code>
            </p>
        </div>

        <p class="text-center text-muted mt-4 mb-0" style="font-size:0.75rem;">
            &copy; <?= date('Y') ?> SM Sport Center
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

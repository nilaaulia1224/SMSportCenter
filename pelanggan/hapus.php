<?php
require_once '../config/koneksi.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id) {
    // Karena ON DELETE CASCADE diatur pada database.sql, 
    // maka reservasi milik pelanggan ini otomatis akan terhapus jika databasenya mendukung.
    // Jika tidak, kita bisa hapus secara eksplisit.
    
    $stmt = $koneksi->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Data pelanggan beserta riwayat booking-nya berhasil dihapus!";
}

header("Location: index.php");
exit();

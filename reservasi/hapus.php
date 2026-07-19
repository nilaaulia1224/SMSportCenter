<?php
require_once '../config/koneksi.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $koneksi->prepare("DELETE FROM reservasi WHERE id_reservasi = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Data reservasi berhasil dihapus!";
}

header("Location: index.php");
exit();

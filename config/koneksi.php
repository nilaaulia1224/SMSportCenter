<?php
/**
 * Koneksi Database SM Sport Center
 * Menggunakan PDO (PHP Data Objects) untuk keamanan (Prepared Statement)
 */

$host     = 'localhost';
$db_name  = 'db_smsportcenter';
$username = 'root';
$password = ''; // Default password Laragon/XAMPP adalah kosong

try {
    $koneksi = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Set error mode ke exception untuk mempermudah debugging dan error handling
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode ke associative array
    $koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Tampilkan pesan error yang aman, jangan tampilkan informasi sensitif di production
    die("Koneksi database gagal: " . $e->getMessage());
}
?>

-- Database: db_smsportcenter
-- Dibuat untuk studi kasus sertifikasi Analis Program

CREATE DATABASE IF NOT EXISTS `db_smsportcenter`;
USE `db_smsportcenter`;

-- --------------------------------------------------------
-- 1. Tabel users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Tabel pelanggan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pelanggan` (
  `id_pelanggan` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Tabel lapangan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lapangan` (
  `id_lapangan` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_lapangan` VARCHAR(100) NOT NULL,
  `jenis` VARCHAR(50) NOT NULL, -- e.g. Futsal, Badminton, Basket
  `harga_per_jam` INT NOT NULL,
  `status` ENUM('Tersedia', 'Tidak Tersedia') NOT NULL DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Tabel reservasi
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservasi` (
  `id_reservasi` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelanggan` INT NOT NULL,
  `id_lapangan` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `total_bayar` INT NOT NULL,
  `bukti_pembayaran` VARCHAR(255) NULL,
  `status` ENUM('Menunggu Pembayaran', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu Pembayaran',
  FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_lapangan`) REFERENCES `lapangan` (`id_lapangan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert Data Dummy
-- --------------------------------------------------------

-- Data Dummy users (password: admin123)
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu', 'admin'),
(2, 'staff', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu', 'staff');

-- Data Dummy pelanggan (password default: pembeli123)
INSERT INTO `pelanggan` (`id_pelanggan`, `nama`, `no_hp`, `email`, `username`, `password`) VALUES
(1, 'Budi Santoso', '081234567890', 'budi@gmail.com', 'budi', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu'),
(2, 'Siti Aminah', '082345678901', 'siti@gmail.com', 'siti', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu'),
(3, 'Joko Widodo', '083456789012', 'joko@gmail.com', 'joko', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu'),
(4, 'Dewi Lestari', '084567890123', 'dewi@gmail.com', 'dewi', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu'),
(5, 'Rian Hidayat', '085678901234', 'rian@gmail.com', 'rian', '$2y$10$A4VwD/G2f.f4.ujLzhcJNex59suWyGPKigP4GzXX7d/ff9i1IeURu');

-- Data Dummy lapangan
INSERT INTO `lapangan` (`id_lapangan`, `nama_lapangan`, `jenis`, `harga_per_jam`, `status`) VALUES
(1, 'Lapangan Futsal A (Sintetis)', 'Futsal', 150000, 'Tersedia'),
(2, 'Lapangan Futsal B (Vinyl)', 'Futsal', 130000, 'Tersedia'),
(3, 'Lapangan Badminton 1', 'Badminton', 50000, 'Tersedia'),
(4, 'Lapangan Badminton 2', 'Badminton', 50000, 'Tidak Tersedia'),
(5, 'Lapangan Basket Indoor', 'Basket', 200000, 'Tersedia');

-- Data Dummy reservasi
-- Menggunakan tanggal hari ini dan besok untuk demo reservasi yang dinamis
INSERT INTO `reservasi` (`id_reservasi`, `id_pelanggan`, `id_lapangan`, `tanggal`, `jam_mulai`, `jam_selesai`, `total_bayar`, `status`) VALUES
(1, 1, 1, CURRENT_DATE(), '16:00:00', '18:00:00', 300000, 'Selesai'),
(2, 2, 3, CURRENT_DATE(), '19:00:00', '21:00:00', 100000, 'Menunggu Pembayaran'),
(3, 3, 2, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '08:00:00', '10:00:00', 260000, 'Selesai'),
(4, 4, 2, DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), '14:00:00', '16:00:00', 260000, 'Menunggu Pembayaran'),
(5, 5, 1, CURRENT_DATE(), '20:00:00', '22:00:00', 300000, 'Selesai');

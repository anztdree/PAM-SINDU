-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 29 Agu 2026 pada 05.19
-- Versi server: 10.4.34-MariaDB
-- Versi PHP: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `pam`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_pelanggan`
--

CREATE TABLE `transaksi_pelanggan` (
  `id` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `meter_awal` int(11) DEFAULT 0,
  `meter_akhir` int(11) DEFAULT 0,
  `cash` int(11) DEFAULT 0,
  `biaya_beban` int(11) DEFAULT 2000,
  `saldo_lalu` int(11) DEFAULT 0,
  `status` enum('Belum Bayar','Sudah Bayar') DEFAULT 'Belum Bayar',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `transaksi_pelanggan`
--

INSERT INTO `transaksi_pelanggan` (`id`, `periode`, `nama`, `meter_awal`, `meter_akhir`, `cash`, `biaya_beban`, `saldo_lalu`, `status`, `updated_at`) VALUES
(1, 'Juni 2026', 'HARTOYO', 18, 18, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(2, 'Juni 2026', 'SUPOYO', 10, 10, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(3, 'Juni 2026', 'HERU', 1, 1, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(4, 'Juni 2026', 'LEGIMAN / TINAH', 134, 150, 42000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(5, 'Juni 2026', 'SUWARNI', 209, 211, 7000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(6, 'Juni 2026', 'TRIYANTO', 76, 76, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(7, 'Juni 2026', 'BU SITI', 0, 0, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:06:23'),
(8, 'Juni 2026', 'KISWANTO', 68, 70, 0, 2000, 0, 'Belum Bayar', '2026-07-25 21:05:23'),
(9, 'Juni 2026', 'MARNI SATE', 85, 85, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:06:28'),
(10, 'Juni 2026', 'ENI ( OUTLET)', 277, 287, 27000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(11, 'Juni 2026', 'ERTA', 12, 12, 0, 2000, 0, 'Belum Bayar', '2026-07-25 21:05:23'),
(12, 'Juni 2026', 'SUTOPO', 0, 0, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:46'),
(13, 'Juni 2026', 'SUPRAPTI', 5, 5, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:06:07'),
(14, 'Juni 2026', 'SUMARNO', 85, 85, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(15, 'Juni 2026', 'NUR W', 16, 16, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(16, 'Juni 2026', 'ADIT', 92, 94, 7000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(17, 'Juni 2026', 'BOWO', 1, 1, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:52'),
(18, 'Juni 2026', 'SUGINO', 116, 116, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:23'),
(19, 'Juni 2026', 'MADRIM', 518, 558, 102000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(20, 'Juni 2026', 'GALIH (LAMPU)', 866, 875, 24500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(21, 'Juni 2026', 'GAGAN (BENGKEL)', 145, 154, 24500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(22, 'Juni 2026', 'SUCI ( PIJAT)', 450, 455, 14500, 2000, 0, 'Sudah Bayar', '2026-07-25 22:15:20'),
(23, 'Juni 2026', 'ATIK', 22, 22, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:57'),
(24, 'Juni 2026', 'RINI', 669, 682, 34500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(25, 'Juni 2026', 'SUKINO', 98, 100, 7000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(26, 'Juni 2026', 'AGUS', 57, 57, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:06:02'),
(27, 'Juni 2026', 'TRI W', 707, 715, 22000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(28, 'Juni 2026', 'DAKIMAH', 770, 780, 27000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(29, 'Juni 2026', 'NARTI', 173, 196, 59500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(30, 'Juni 2026', 'WAGIYO', 25, 25, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(31, 'Juni 2026', 'KATIYO', 473, 484, 29500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(32, 'Juni 2026', 'ANI', 565, 584, 49500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(33, 'Juni 2026', 'ABDULLAH', 46, 46, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(34, 'Juni 2026', 'ISKAK KISNO', 257, 275, 47000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(35, 'Juni 2026', 'BAMBANG PARLIS', 165, 171, 17000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(36, 'Juni 2026', 'MULYONO', 147, 148, 4500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(37, 'Juni 2026', 'SUTARNO', 370, 378, 22000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(38, 'Juni 2026', 'SARPAN', 273, 277, 12000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(39, 'Juni 2026', 'SUHAR (ADEK)', 1766, 1827, 154500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(40, 'Juni 2026', 'SUPRIYONO (TAMBAL BAN)', 170, 170, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(41, 'Juni 2026', 'GITO', 255, 255, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(42, 'Juni 2026', 'NGATIRAN', 87, 92, 14500, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(43, 'Juni 2026', 'CANDRA', 1293, 1313, 52000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(44, 'Juni 2026', 'ISBAROH', 254, 258, 12000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(45, 'Juni 2026', 'BU IS (TOL)', 2302, 2354, 132000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:05:24'),
(46, 'Juli 2026', 'HARTOYO', 18, 18, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:08:32'),
(47, 'Juli 2026', 'SUPOYO', 10, 10, 0, 2000, 0, 'Belum Bayar', '2026-07-25 22:11:59'),
(48, 'Juli 2026', 'HERU', 1, 1, 0, 2000, 0, 'Belum Bayar', '2026-07-25 22:08:36'),
(49, 'Juli 2026', 'LEGIMAN / TINAH', 150, 161, 29500, 2000, 0, 'Sudah Bayar', '2026-07-26 09:59:47'),
(50, 'Juli 2026', 'SUWARNI', 211, 211, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:12:37'),
(51, 'Juli 2026', 'TRIYANTO', 76, 76, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:12:56'),
(52, 'Juli 2026', 'BU SITI', 0, 0, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:07:09'),
(53, 'Juli 2026', 'KISWANTO', 70, 70, 9000, 2000, -7000, 'Sudah Bayar', '2026-07-26 09:59:54'),
(54, 'Juli 2026', 'MARNI SATE', 85, 85, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:09:43'),
(55, 'Juli 2026', 'ENI ( OUTLET)', 287, 295, 22000, 2000, 0, 'Sudah Bayar', '2026-07-26 09:59:58'),
(56, 'Juli 2026', 'ERTA', 12, 12, 4000, 2000, -2000, 'Sudah Bayar', '2026-07-25 21:09:51'),
(57, 'Juli 2026', 'SUTOPO', 0, 0, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:12:27'),
(58, 'Juli 2026', 'SUPRAPTI', 5, 5, 2000, 2000, 0, 'Sudah Bayar', '2026-07-27 10:34:02'),
(59, 'Juli 2026', 'SUMARNO', 85, 85, 0, 2000, 0, 'Belum Bayar', '2026-07-25 22:11:56'),
(60, 'Juli 2026', 'NUR W', 16, 16, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:10:33'),
(61, 'Juli 2026', 'ADIT', 94, 96, 7000, 2000, 0, 'Sudah Bayar', '2026-07-26 10:00:04'),
(62, 'Juli 2026', 'BOWO', 1, 1, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:06:52'),
(63, 'Juli 2026', 'SUGINO', 116, 116, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:11:34'),
(64, 'Juli 2026', 'MADRIM', 558, 585, 69500, 2000, 0, 'Sudah Bayar', '2026-07-27 09:12:40'),
(65, 'Juli 2026', 'GALIH (LAMPU)', 875, 883, 0, 2000, 0, 'Belum Bayar', '2026-07-25 22:08:19'),
(66, 'Juli 2026', 'GAGAN (BENGKEL)', 154, 171, 44500, 2000, 0, 'Sudah Bayar', '2026-07-27 09:14:38'),
(67, 'Juli 2026', 'SUCI ( PIJAT)', 455, 460, 14500, 2000, 42500, 'Sudah Bayar', '2026-07-25 22:18:14'),
(68, 'Juli 2026', 'ATIK', 22, 22, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:13:12'),
(69, 'Juli 2026', 'RINI', 682, 693, 29500, 2000, 0, 'Sudah Bayar', '2026-07-26 10:00:10'),
(70, 'Juli 2026', 'SUKINO', 100, 100, 0, 2000, 0, 'Belum Bayar', '2026-07-25 22:11:52'),
(71, 'Juli 2026', 'AGUS', 57, 57, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:02:48'),
(72, 'Juli 2026', 'TRI W', 715, 720, 14500, 2000, 0, 'Sudah Bayar', '2026-07-28 11:34:39'),
(73, 'Juli 2026', 'DAKIMAH', 780, 789, 24500, 2000, 0, 'Sudah Bayar', '2026-07-28 11:33:27'),
(74, 'Juli 2026', 'NARTI', 196, 214, 5000, 2000, 0, 'Sudah Bayar', '2026-08-03 10:11:49'),
(75, 'Juli 2026', 'WAGIYO', 25, 25, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:13:01'),
(76, 'Juli 2026', 'KATIYO', 484, 495, 29500, 2000, 0, 'Sudah Bayar', '2026-07-28 11:27:53'),
(77, 'Juli 2026', 'ANI', 584, 598, 37000, 2000, 0, 'Sudah Bayar', '2026-08-03 09:29:51'),
(78, 'Juli 2026', 'ABDULLAH', 46, 46, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 21:56:35'),
(79, 'Juli 2026', 'ISKAK KISNO', 275, 278, 9500, 2000, 0, 'Sudah Bayar', '2026-07-26 10:00:17'),
(80, 'Juli 2026', 'BAMBANG PARLIS', 171, 175, 12000, 2000, 0, 'Sudah Bayar', '2026-07-27 09:20:24'),
(81, 'Juli 2026', 'MULYONO', 148, 150, 7000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:21:33'),
(82, 'Juli 2026', 'SUTARNO', 378, 385, 19500, 2000, 0, 'Sudah Bayar', '2026-07-26 10:00:20'),
(83, 'Juli 2026', 'SARPAN', 277, 282, 14500, 2000, 0, 'Sudah Bayar', '2026-07-27 09:23:33'),
(84, 'Juli 2026', 'SUHAR (ADEK)', 1827, 1883, 142000, 2000, 0, 'Sudah Bayar', '2026-07-27 09:26:48'),
(85, 'Juli 2026', 'SUPRIYONO (TAMBAL BAN)', 170, 170, 2000, 2000, 0, 'Sudah Bayar', '2026-07-28 11:34:50'),
(86, 'Juli 2026', 'GITO', 255, 255, 2000, 2000, 0, 'Sudah Bayar', '2026-07-25 22:08:24'),
(87, 'Juli 2026', 'NGATIRAN', 92, 96, 0, 2000, 0, 'Belum Bayar', '2026-07-25 22:10:26'),
(88, 'Juli 2026', 'CANDRA', 1313, 1326, 34500, 2000, 0, 'Sudah Bayar', '2026-07-27 09:16:26'),
(89, 'Juli 2026', 'ISBAROH', 258, 261, 9500, 2000, 0, 'Sudah Bayar', '2026-07-27 09:35:30'),
(90, 'Juli 2026', 'BU IS (TOL)', 2354, 2395, 104500, 2000, 0, 'Sudah Bayar', '2026-08-03 10:18:23'),
(91, 'Agustus 2026', 'HARTOYO', 18, 18, 2000, 2000, 0, 'Sudah Bayar', '2026-08-26 11:24:11'),
(92, 'Agustus 2026', 'SUPOYO', 10, 10, 0, 2000, -2000, 'Belum Bayar', '2026-08-27 11:35:50'),
(93, 'Agustus 2026', 'HERU', 1, 1, 0, 2000, -2000, 'Belum Bayar', '2026-08-26 11:24:25'),
(94, 'Agustus 2026', 'LEGIMAN / TINAH', 161, 187, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:36:51'),
(95, 'Agustus 2026', 'SUWARNI', 211, 215, 12000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:47:44'),
(96, 'Agustus 2026', 'TRIYANTO', 76, 76, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:37:11'),
(97, 'Agustus 2026', 'BU SITI', 1, 1, 2000, 2000, 0, 'Sudah Bayar', '2026-08-27 11:37:31'),
(98, 'Agustus 2026', 'KISWANTO', 70, 71, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:38:03'),
(99, 'Agustus 2026', 'MARNI SATE', 85, 85, 2000, 2000, 0, 'Sudah Bayar', '2026-08-27 11:38:12'),
(100, 'Agustus 2026', 'ENI ( OUTLET)', 295, 306, 29500, 2000, 0, 'Sudah Bayar', '2026-08-28 12:00:08'),
(101, 'Agustus 2026', 'ERTA', 12, 12, 2000, 2000, 0, 'Sudah Bayar', '2026-08-28 12:02:35'),
(102, 'Agustus 2026', 'SUTOPO', 0, 0, 2000, 2000, 0, 'Sudah Bayar', '2026-08-27 11:38:55'),
(103, 'Agustus 2026', 'SUPRAPTI', 5, 5, 2000, 2000, 0, 'Sudah Bayar', '2026-08-27 11:38:41'),
(104, 'Agustus 2026', 'SUMARNO', 85, 87, 0, 2000, -2000, 'Belum Bayar', '2026-08-27 11:35:13'),
(105, 'Agustus 2026', 'NUR W', 16, 16, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:34:13'),
(106, 'Agustus 2026', 'ADIT', 96, 98, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:34:08'),
(107, 'Agustus 2026', 'BOWO', 1, 1, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:33:40'),
(108, 'Agustus 2026', 'SUGINO', 116, 116, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:33:35'),
(109, 'Agustus 2026', 'MADRIM', 585, 627, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:33:03'),
(110, 'Agustus 2026', 'GALIH (LAMPU)', 883, 883, 0, 2000, -22000, 'Belum Bayar', '2026-08-28 05:55:24'),
(111, 'Agustus 2026', 'GAGAN (BENGKEL)', 171, 192, 54500, 2000, 0, 'Sudah Bayar', '2026-08-28 12:04:30'),
(112, 'Agustus 2026', 'SUCI ( PIJAT)', 460, 465, 0, 2000, 42500, 'Sudah Bayar', '2026-08-27 11:28:20'),
(113, 'Agustus 2026', 'ATIK', 22, 22, 2000, 2000, 0, 'Sudah Bayar', '2026-08-27 11:28:00'),
(114, 'Agustus 2026', 'RINI', 693, 706, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:27:21'),
(115, 'Agustus 2026', 'SUKINO', 100, 101, 0, 2000, -2000, 'Belum Bayar', '2026-08-27 11:53:38'),
(116, 'Agustus 2026', 'AGUS', 57, 57, 2000, 2000, 0, 'Sudah Bayar', '2026-08-27 11:52:51'),
(117, 'Agustus 2026', 'TRI W', 720, 729, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:52:22'),
(118, 'Agustus 2026', 'DAKIMAH', 789, 802, 34500, 2000, 0, 'Sudah Bayar', '2026-08-28 12:08:14'),
(119, 'Agustus 2026', 'NARTI', 214, 234, 94000, 2000, -42000, 'Sudah Bayar', '2026-08-28 12:12:20'),
(120, 'Agustus 2026', 'WAGIYO', 25, 25, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:50:15'),
(121, 'Agustus 2026', 'KATIYO', 495, 513, 47000, 2000, 0, 'Sudah Bayar', '2026-08-28 12:50:28'),
(122, 'Agustus 2026', 'ANI', 598, 617, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:48:56'),
(123, 'Agustus 2026', 'ABDULLAH', 46, 46, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:46:24'),
(124, 'Agustus 2026', 'ISKAK KISNO', 278, 284, 17000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:50:08'),
(125, 'Agustus 2026', 'BAMBANG PARLIS', 175, 181, 17000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:46:18'),
(126, 'Agustus 2026', 'MULYONO', 150, 157, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:42:24'),
(127, 'Agustus 2026', 'SUTARNO', 385, 397, 32000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:54:31'),
(128, 'Agustus 2026', 'SARPAN', 282, 288, 17000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:44:11'),
(129, 'Agustus 2026', 'SUHAR (ADEK)', 1883, 1925, 107000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:56:33'),
(130, 'Agustus 2026', 'SUPRIYONO (TAMBAL BAN)', 170, 170, 2000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:40:22'),
(131, 'Agustus 2026', 'GITO', 255, 277, 0, 2000, 0, 'Belum Bayar', '2026-08-27 11:44:09'),
(132, 'Agustus 2026', 'NGATIRAN', 96, 100, 0, 2000, -12000, 'Belum Bayar', '2026-08-27 11:44:52'),
(133, 'Agustus 2026', 'CANDRA', 1326, 1350, 62000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:33:07'),
(134, 'Agustus 2026', 'ISBAROH', 261, 265, 12000, 2000, 0, 'Sudah Bayar', '2026-08-28 11:38:20'),
(135, 'Agustus 2026', 'BU IS (TOL)', 2395, 2454, 149500, 2000, 0, 'Sudah Bayar', '2026-08-28 12:13:13');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `transaksi_pelanggan`
--
ALTER TABLE `transaksi_pelanggan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pelanggan_periode` (`periode`,`nama`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `transaksi_pelanggan`
--
ALTER TABLE `transaksi_pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

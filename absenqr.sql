-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 16 Jun 2025 pada 23.23
-- Versi server: 10.4.24-MariaDB
-- Versi PHP: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absenqr`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('hadir','izin','sakit','alpha') DEFAULT NULL,
  `status_absen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `user_id`, `tanggal`, `jam_masuk`, `jam_keluar`, `status`, `status_absen`) VALUES
(3, 3, '2025-06-16', '14:54:38', NULL, 'hadir', 'terlambat');

-- --------------------------------------------------------

--
-- Struktur dari tabel `informasi`
--

CREATE TABLE `informasi` (
  `id` int(11) NOT NULL,
  `isi_info` text NOT NULL,
  `tanggal_input` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `informasi`
--

INSERT INTO `informasi` (`id`, `isi_info`, `tanggal_input`) VALUES
(1, 'Selamat datang di sistem absensi QR dinamis!', '2025-06-15 04:41:50'),
(2, 'Harap scan QR sebelum jam 08.00 WIB.', '2025-06-15 04:41:50'),
(3, 'Pastikan jaringan internet stabil saat scan QR.', '2025-06-15 04:41:50'),
(4, 'Gunakan browser terbaru untuk hasil terbaik.', '2025-06-15 04:41:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `izin_cuti`
--

CREATE TABLE `izin_cuti` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jenis` enum('izin','cuti') NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keterangan` text NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `tanggal_pengajuan` datetime NOT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `izin_cuti`
--

INSERT INTO `izin_cuti` (`id`, `id_user`, `jenis`, `tanggal_mulai`, `tanggal_selesai`, `keterangan`, `bukti`, `tanggal_pengajuan`, `status`) VALUES
(1, 3, 'izin', '2025-06-17', '2025-06-17', 'Acara Keluarga', 'bukti_684fd5a81c56c.jpg', '2025-06-16 15:28:24', 'Disetujui');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL,
  `nama_jabatan` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `jabatan`
--

INSERT INTO `jabatan` (`id`, `nama_jabatan`, `keterangan`) VALUES
(1, 'Ketua Umum', 'Pimpinan tertinggi yang menentukan arah kebijakan organisasi.'),
(2, 'Wakil Ketua Umum', 'Mendampingi Ketua dan mengambil alih saat Ketua berhalangan.'),
(3, 'Sekretaris Umum', 'Mengelola surat, arsip, dan dokumentasi resmi organisasi.'),
(4, 'Bendahara', 'Mengatur keuangan dan membuat laporan keuangan rutin.'),
(5, 'Kepala Bidang Kompetisi', 'Mengelola semua kegiatan kompetisi di wilayah PSSI.'),
(6, 'Kepala Bidang Pembinaan Usia Dini', 'Membina pemain muda dan akademi sepak bola.'),
(7, 'Kepala Bidang Wasit', 'Membina dan menugaskan wasit lokal.'),
(8, 'Kepala Bidang Pelatih', 'Melatih dan meningkatkan kualitas pelatih lokal.'),
(9, 'Koordinator Lapangan', 'Mengatur kesiapan lapangan dan teknis pertandingan.'),
(10, 'Koordinator Acara', 'Merancang dan mengelola event resmi dan turnamen.'),
(11, 'Staf Administrasi', 'Membantu tugas surat-menyurat dan arsip.'),
(12, 'Staf Keuangan', 'Mendampingi bendahara dalam mengelola keuangan.'),
(13, 'Staf Logistik', 'Mengelola perlengkapan dan kebutuhan barang kantor.'),
(14, 'Petugas Keamanan', 'Menjaga keamanan lingkungan kantor.'),
(15, 'Petugas Kebersihan', 'Menjaga kebersihan lingkungan kantor dan fasilitas.'),
(16, 'Petugas OB (Office Boy)', 'Menyiapkan kebutuhan harian seperti air minum, ruang rapat.'),
(17, 'Sopir Operasional', 'Mengantar pegawai atau dokumen resmi.'),
(18, 'Teknisi IT', 'Menangani masalah teknis komputer dan jaringan.'),
(19, 'Desain Grafis', 'Membuat materi publikasi dan desain kegiatan PSSI Daerah.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jam_kerja`
--

CREATE TABLE `jam_kerja` (
  `hari` varchar(10) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `shift` varchar(20) DEFAULT 'Pagi',
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `jam_kerja`
--

INSERT INTO `jam_kerja` (`hari`, `jam_masuk`, `jam_pulang`, `shift`, `id`) VALUES
('Senin', '08:00:00', '12:00:00', 'Pagi', 8),
('Senin', '13:00:00', '17:00:00', 'Siang', 9),
('Senin', '18:00:00', '22:00:00', 'Malam', 10),
('Selasa', '08:00:00', '12:00:00', 'Pagi', 11),
('Selasa', '13:00:00', '17:00:00', 'Siang', 12),
('Selasa', '18:00:00', '22:00:00', 'Malam', 13),
('Rabu', '08:00:00', '12:00:00', 'Pagi', 14),
('Rabu', '13:00:00', '17:00:00', 'Siang', 15),
('Rabu', '18:00:00', '22:00:00', 'Malam', 16),
('Kamis', '08:00:00', '12:00:00', 'Pagi', 17),
('Kamis', '13:00:00', '17:00:00', 'Siang', 18),
('Kamis', '18:00:00', '22:00:00', 'Malam', 19),
('Jumat', '08:00:00', '11:30:00', 'Pagi', 20),
('Jumat', '13:00:00', '16:30:00', 'Siang', 21),
('Jumat', '18:00:00', '21:30:00', 'Malam', 22),
('Sabtu', '08:00:00', '12:00:00', 'Pagi', 23),
('Sabtu', '13:00:00', '17:00:00', 'Siang', 24),
('Sabtu', '18:00:00', '22:00:00', 'Malam', 25),
('Minggu', '08:00:00', '12:00:00', 'Pagi', 26),
('Minggu', '13:00:00', '17:00:00', 'Siang', 27),
('Minggu', '18:00:00', '22:00:00', 'Malam', 28);

-- --------------------------------------------------------

--
-- Struktur dari tabel `qr_token`
--

CREATE TABLE `qr_token` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `waktu_dibuat` datetime NOT NULL,
  `waktu_kadaluarsa` datetime NOT NULL,
  `status` enum('aktif','kadaluarsa') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `nama_aplikasi` varchar(255) DEFAULT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `refresh_time` int(11) DEFAULT NULL,
  `informasi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `setting`
--

INSERT INTO `setting` (`id`, `nama_aplikasi`, `nama_perusahaan`, `logo`, `refresh_time`, `informasi`) VALUES
(1, 'Sistem Absensi Dinamis', 'PT Contoh Institusi', '', 30, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `setting_absen`
--

CREATE TABLE `setting_absen` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `jam_kerja_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `setting_absen`
--

INSERT INTO `setting_absen` (`id`, `user_id`, `jam_kerja_id`) VALUES
(116, 3, 9),
(117, 3, 13),
(118, 3, 15),
(119, 3, 17),
(120, 3, 21),
(121, 3, 23),
(122, 3, 27),
(123, 5, 10),
(124, 5, 12),
(125, 5, 15),
(126, 5, 18),
(127, 5, 20),
(128, 5, 25),
(129, 5, 28),
(130, 6, 9),
(131, 6, 11),
(132, 6, 15),
(133, 6, 19),
(134, 6, 21),
(135, 6, 25),
(136, 6, 28),
(137, 8, 8),
(138, 8, 11),
(139, 8, 15),
(140, 8, 18),
(141, 8, 21),
(142, 8, 24),
(143, 8, 27),
(144, 9, 9),
(145, 9, 13),
(146, 9, 16),
(147, 9, 17),
(148, 9, 22),
(149, 9, 25),
(150, 9, 28),
(151, 11, 10),
(152, 11, 11),
(153, 11, 14),
(154, 11, 19),
(155, 11, 20),
(156, 11, 24),
(157, 11, 27),
(158, 12, 9),
(159, 12, 12),
(160, 12, 16),
(161, 12, 19),
(162, 12, 20),
(163, 12, 23),
(164, 12, 28),
(165, 13, 10),
(166, 13, 13),
(167, 13, 16),
(168, 13, 17),
(169, 13, 21),
(170, 13, 23),
(171, 13, 27),
(172, 14, 8),
(173, 14, 12),
(174, 14, 14),
(175, 14, 18),
(176, 14, 20),
(177, 14, 25),
(178, 14, 27),
(179, 15, 8),
(180, 15, 13),
(181, 15, 15),
(182, 15, 19),
(183, 15, 21),
(184, 15, 25),
(185, 15, 26),
(186, 16, 10),
(187, 16, 12),
(188, 16, 16),
(189, 16, 17),
(190, 16, 21),
(191, 16, 25),
(192, 16, 26),
(193, 18, 9),
(194, 18, 12),
(195, 18, 15),
(196, 18, 19),
(197, 18, 21),
(198, 18, 23),
(199, 18, 27),
(200, 19, 8),
(201, 19, 12),
(202, 19, 15),
(203, 19, 19),
(204, 19, 20),
(205, 19, 23),
(206, 19, 27),
(207, 20, 10),
(208, 20, 11),
(209, 20, 15),
(210, 20, 17),
(211, 20, 21),
(212, 20, 24),
(213, 20, 27),
(214, 21, 9),
(215, 21, 12),
(216, 21, 16),
(217, 21, 19),
(218, 21, 21),
(219, 21, 23),
(220, 21, 27),
(229, 22, 10),
(230, 22, 11),
(231, 22, 16),
(232, 22, 18),
(233, 22, 20),
(234, 22, 25),
(235, 22, 27);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `jabatan_id` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `nama_lengkap`, `jabatan_id`, `foto`, `password`, `role`, `status`) VALUES
(2, 'rayhan', 'Rayhan', NULL, NULL, '$2y$10$pnXZIsCMhJVhHfAuThOCE.XUcliOGqb45Rb/FNrYpz8OU/SQ3.Dia', 'admin', 'aktif'),
(3, 'roba', 'Roba', NULL, NULL, '$2y$10$g2wvMa9eEmHGVhSoOfuSdOelXFowYEXCGe1W8TsoM2nqH/GGHAglC', 'user', 'aktif'),
(4, 'admin01', 'Ahmad Fauzi', 13, 'foto1.jpg', '$2y$10$mTZuLgKlY/ZPB3C.cZoKru2Hew5dHPiJm.N.yGMIu5tlfm4nNmFRm', 'user', 'aktif'),
(5, 'user01', 'Siti Aminah', 2, 'foto2.jpg', '$2y$10$/YGgnC8tr8Nia0N6O7rafe4Wwsj8lpMO1n18z5php9GoDfQlnga5C', 'user', 'aktif'),
(6, 'user02', 'Budi Santoso', 2, 'foto3.jpg', '$2y$10$Nz3wwX5cCrusXdPwhGm4x.M04m7I0YY3aJmd0O.bOvT3jWzmXmcay', 'user', 'aktif'),
(7, 'admin02', 'Rina Kartika', 1, 'foto4.jpg', '$2y$10$h9wk3Jcf7GbKqdJyDQzeUei2AWxOIsR2OmjrmdooK6N/mQQkWzJnC', 'user', 'aktif'),
(8, 'user03', 'Joko Purnomo', 3, 'foto5.jpg', '$2y$10$oakmFSLEfGHQgkTMHRbvYeaYUOV4OZHVNZNG5hcWqdHnYOqcZydhG', 'user', 'aktif'),
(9, 'user04', 'Dewi Lestari', 2, 'foto6.jpg', '$2y$10$UTic3h0roENeTWFJbHn5iuZ4hz3YwDuw47n69fkTXNN.m2ZxhZCcK', 'user', 'aktif'),
(10, 'admin03', 'Faisal Hakim', 1, 'foto7.jpg', '$2y$10$k0Qgnw7bMW9Kl/vft/XPvOrNFFbnZZvGFlOkb.9HZ8dxvC3UAOg3e', 'user', 'aktif'),
(11, 'user05', 'Nina Herlina', 4, 'foto8.jpg', '$2y$10$oyuxPZrRDP9dUiBZcJkr6Og4Ptvfn4rOtJcWHDzi6Ux5KQzVlvkA.', 'user', 'aktif'),
(12, 'user06', 'Rizky Hidayat', 3, 'foto9.jpg', '$2y$10$xvDSgcOO1m6CJ6udA09jwOETjbY3RLupoXja1MrfRrdaXI1nXEHJW', 'user', 'aktif'),
(13, 'user07', 'Tina Melati', 4, 'foto10.jpg', '$2y$10$jky47FHd77bReoYLwHMhHe0wsnivkqdOhhHRTZZHsiyok8GQ9hPNK', 'user', 'aktif'),
(14, 'user08', 'Dedi Supriadi', 3, 'foto11.jpg', '$2y$10$dJfNpg4Lnie6bLdqBoLqbuZpElVRuNVyhJaqX4GxpPkvfIjmunXU.', 'user', 'aktif'),
(15, 'user09', 'Yuniarti Sari', 2, 'foto12.jpg', '$2y$10$8MoujlJwp1c7PMwiu2xIhePNUhrO22oNiH8O02AxAGknrrrjOXFmC', 'user', 'aktif'),
(16, 'user10', 'Gilang Ramadhan', 4, 'foto13.jpg', '$2y$10$sIOz/pdkqx.gshYycktnpOPjqBLB4o3HSA7UgROG5tp3Qcx2xcc3y', 'user', 'aktif'),
(17, 'admin04', 'Lilis Suryani', 1, 'foto14.jpg', '$2y$10$jwUxDo9OBA/Bfsra5I3vdeDfwRhxfJSCpDDJXExPmaU7KOLpMRY76', 'user', 'aktif'),
(18, 'user11', 'Fajar Nugroho', 2, 'foto15.jpg', '$2y$10$5zwQL8xKdQv6j/1o841o4OvSRxX7ugiKv7x64MBOi5A7pe3kk8aYi', 'user', 'aktif'),
(19, 'user12', 'Mega Indah', 4, 'foto16.jpg', '$2y$10$78KZJ99PSyIxtlN7B2c1f.fLhBXMMqFWB147BrwD6vLxNOE5r/mL6', 'user', 'aktif'),
(20, 'user13', 'Hendra Saputra', 3, 'foto17.jpg', '$2y$10$2Y2t4U8NlruS.fgiwjSS5ezHSIefQ912XZSrR2imGvxp7qJWKdv8W', 'user', 'aktif'),
(21, 'user14', 'Ratna Wulandari', 2, 'foto18.jpg', '$2y$10$GdqI0cWCeieNplIYYsfzDO3KbYMia0Ghv4NpjZTjj3qsAFELc1eYu', 'user', 'nonaktif'),
(22, 'user15', 'Bayu Prasetyo', 4, 'foto19.jpg', '$2y$10$789uKri2MsCLuYfZZgXm9u/DpJSdKXJAMVqiORG6Uvi9fazvFvbBu', 'user', 'aktif'),
(23, 'admin05', 'Samsul Arifin', 1, 'foto20.jpg', '$2y$10$EBTQrxztWf6Rh8/3uqe5TeNga0R9iAjmT.mijoLndfaWsKS.2zkQW', 'user', 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `informasi`
--
ALTER TABLE `informasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `izin_cuti`
--
ALTER TABLE `izin_cuti`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_jabatan` (`nama_jabatan`);

--
-- Indeks untuk tabel `jam_kerja`
--
ALTER TABLE `jam_kerja`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `qr_token`
--
ALTER TABLE `qr_token`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `setting_absen`
--
ALTER TABLE `setting_absen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `jam_kerja_id` (`jam_kerja_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `informasi`
--
ALTER TABLE `informasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `izin_cuti`
--
ALTER TABLE `izin_cuti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `jam_kerja`
--
ALTER TABLE `jam_kerja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `qr_token`
--
ALTER TABLE `qr_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `setting_absen`
--
ALTER TABLE `setting_absen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `setting_absen`
--
ALTER TABLE `setting_absen`
  ADD CONSTRAINT `setting_absen_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `setting_absen_ibfk_2` FOREIGN KEY (`jam_kerja_id`) REFERENCES `jam_kerja` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

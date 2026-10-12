<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Anda belum login.";
    exit;
}

$id_user = $_SESSION['user_id'];
$jenis = $_POST['jenis'] ?? '';
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
$keterangan = $_POST['keterangan'] ?? '';
$nama_file = null;

// Validasi input
if (!$jenis || !$tanggal_mulai || !$tanggal_selesai || !$keterangan) {
    http_response_code(400);
    echo "Semua data wajib diisi.";
    exit;
}

// Validasi upload
if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
    $uploadDir = '../uploads/izincuti/';
    $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        echo "Format file tidak didukung. Hanya JPG, PNG, dan PDF yang diperbolehkan.";
        exit;
    }

    // Simpan file dengan nama unik
    $nama_file = uniqid('bukti_') . '.' . $ext;
    $path = $uploadDir . $nama_file;

    if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $path)) {
        http_response_code(500);
        echo "Gagal mengunggah file.";
        exit;
    }
} else {
    http_response_code(400);
    echo "Bukti wajib diunggah.";
    exit;
}

// Simpan ke database
$stmt = $koneksi->prepare("INSERT INTO izin_cuti (id_user, jenis, tanggal_mulai, tanggal_selesai, keterangan, bukti, tanggal_pengajuan, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Menunggu')");
$stmt->bind_param("ssssss", $id_user, $jenis, $tanggal_mulai, $tanggal_selesai, $keterangan, $nama_file);

if ($stmt->execute()) {
    echo "Pengajuan $jenis berhasil dikirim!";
} else {
    http_response_code(500);
    echo "Gagal menyimpan ke database.";
}

$stmt->close();
$koneksi->close();

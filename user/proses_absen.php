<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    exit('Akses ditolak.');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Session user_id tidak ditemukan.');
}

include '../db.php'; // Koneksi database

date_default_timezone_set('Asia/Jakarta');

$user_id = mysqli_real_escape_string($koneksi, $_SESSION['user_id']);
$tanggal = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$hari = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis',
    'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
][date('l')];

// Validasi token QR
$secret = 'absen-qr-secret';
$token = $_POST['token'] ?? '';

$valid = false;
$currentInterval = floor(time() / 30);
for ($i = -1; $i <= 1; $i++) {
    if (hash('sha256', $secret . ($currentInterval + $i)) === $token) {
        $valid = true;
        break;
    }
}

if (!$valid) {
    exit('Token tidak valid atau sudah kadaluarsa.');
}

// Ambil jam kerja user dari setting_absen
$jamKerjaQuery = mysqli_query($koneksi, "
    SELECT jk.jam_masuk, jk.jam_pulang 
    FROM setting_absen sa
    JOIN jam_kerja jk ON sa.jam_kerja_id = jk.id
    WHERE sa.user_id = '$user_id' AND jk.hari = '$hari'
");

if (!$jamKerjaQuery || mysqli_num_rows($jamKerjaQuery) === 0) {
    exit("Jadwal absensi Anda tidak ditemukan.");
}

$jamKerja = mysqli_fetch_assoc($jamKerjaQuery);
$jam_masuk = $jamKerja['jam_masuk'];
$jam_pulang = $jamKerja['jam_pulang'];

// Fungsi ubah jam ke menit
function toMinutes($time) {
    list($h, $m) = explode(':', substr($time, 0, 5));
    return ($h * 60) + $m;
}

$menit_sekarang = toMinutes($jam_sekarang);
$menit_masuk = toMinutes($jam_masuk);
$menit_pulang = toMinutes($jam_pulang);

// Cek apakah user sudah absen hari ini
$cekAbsen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal'");
if (mysqli_num_rows($cekAbsen) > 0) {
    $absen = mysqli_fetch_assoc($cekAbsen);

    if ($absen['jam_keluar']) {
        echo "Anda sudah absen masuk dan pulang hari ini.";
        exit;
    }

    // Jika belum waktunya pulang
    if ($menit_sekarang < ($menit_pulang - 10)) {
        echo "Belum waktunya absen pulang. Silakan absen nanti setelah jam " . $jam_pulang;
        exit;
    }

    // Hitung status fuzzy pulang
    $selisih_pulang = $menit_sekarang - $menit_pulang;
    if ($selisih_pulang < -15) {
        $status_absen = 'pulang cepat';
    } elseif ($selisih_pulang <= 15) {
        $status_absen = 'tepat waktu';
    } else {
        $status_absen = 'lembur';
    }

    // Simpan absen pulang
    $update = mysqli_query($koneksi, "
        UPDATE absensi 
        SET jam_keluar = '$jam_sekarang', status_absen_pulang = '$status_absen'
        WHERE id = {$absen['id']}
    ");

    if ($update) {
        echo "Absen pulang berhasil.<br>Waktu: <strong>$jam_sekarang</strong><br>Status: <strong>$status_absen</strong>";
    } else {
        echo "Gagal menyimpan absen pulang.";
    }

} else {
    // Hitung status fuzzy masuk
    $selisih_masuk = $menit_sekarang - $menit_masuk;
    if ($selisih_masuk >= -15 && $selisih_masuk <= 15) {
        $status = 'hadir';
        $status_absen = 'tepat waktu';
    } elseif ($selisih_masuk > 15 && $selisih_masuk <= 240) {
        $status = 'hadir';
        $status_absen = 'terlambat';
    } elseif ($selisih_masuk < -15) {
        $status = 'hadir';
        $status_absen = 'terlalu cepat';
    } else {
        $status = 'alpha';
        $status_absen = 'tidak valid';
    }

    // Simpan absen masuk
    $insert = mysqli_query($koneksi, "
        INSERT INTO absensi (user_id, tanggal, jam_masuk, status, status_absen)
        VALUES ('$user_id', '$tanggal', '$jam_sekarang', '$status', '$status_absen')
    ");

    if ($insert) {
        echo "Absen masuk berhasil.<br>Waktu: <strong>$jam_sekarang</strong><br>Status: <strong>$status_absen</strong>";
    } else {
        echo "Gagal menyimpan absen masuk.";
    }
}

$cekIzinCuti = mysqli_query($koneksi, "
    SELECT * FROM izin_cuti 
    WHERE user_id = '$user_id'
      AND status = 'disetujui'
      AND '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai
");

if (mysqli_num_rows($cekIzinCuti) > 0) {
    $data = mysqli_fetch_assoc($cekIzinCuti);
    echo "Tidak bisa absen karena sedang <strong>" . strtoupper($data['jenis']) . "</strong>.<br><em>(" . $data['keterangan'] . ")</em>";
    exit;
}
?>

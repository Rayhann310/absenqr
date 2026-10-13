<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

include_once __DIR__ . '/db.php';
require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/config_ip.php';

date_default_timezone_set('Asia/Jakarta');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'absen');

// -------------------------------------------------------------------------
// Helper: Ubah string jam (HH:MM) ke total menit
// -------------------------------------------------------------------------
function toMinutes($time) {
    if (empty($time)) return 0;
    list($h, $m) = explode(':', substr($time, 0, 5));
    return ((int)$h * 60) + (int)$m;
}

// -------------------------------------------------------------------------
// Action 1: Cek status kehadiran hari ini untuk user tertentu
// -------------------------------------------------------------------------
if ($action === 'check_status') {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'User ID tidak valid']);
        exit;
    }

    $userQuery = mysqli_query($koneksi, "
        SELECT u.id, u.username, u.nama_lengkap, u.foto, j.nama_jabatan 
        FROM users u 
        LEFT JOIN jabatan j ON u.jabatan_id = j.id 
        WHERE u.id = '$user_id' AND u.status = 'aktif'
        LIMIT 1
    ");
    $user = $userQuery ? mysqli_fetch_assoc($userQuery) : null;
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan atau nonaktif']);
        exit;
    }

    $tanggal = date('Y-m-d');
    $hariNames = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis',
        'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $hari = $hariNames[date('l')];

    // Ambil jadwal jam kerja
    $jamKerjaQuery = mysqli_query($koneksi, "
        SELECT jk.jam_masuk, jk.jam_pulang, jk.shift 
        FROM setting_absen sa
        JOIN jam_kerja jk ON sa.jam_kerja_id = jk.id
        WHERE sa.user_id = '$user_id' AND jk.hari = '$hari'
        LIMIT 1
    ");
    $jamKerja = $jamKerjaQuery ? mysqli_fetch_assoc($jamKerjaQuery) : null;

    // Ambil catatan absensi hari ini
    $cekAbsen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal' LIMIT 1");
    $absen = $cekAbsen ? mysqli_fetch_assoc($cekAbsen) : null;

    // Cek izin / cuti
    $cekIzin = mysqli_query($koneksi, "
        SELECT * FROM izin_cuti 
        WHERE (id_user = '$user_id' OR user_id = '$user_id')
          AND (LOWER(status) = 'disetujui')
          AND '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai
        LIMIT 1
    ");
    $izin = $cekIzin ? mysqli_fetch_assoc($cekIzin) : null;

    $nextAction = 'masuk';
    $statusText = 'Belum Absen Masuk';
    $canAbsen = true;

    if ($izin) {
        $canAbsen = false;
        $statusText = 'Sedang ' . strtoupper($izin['jenis']) . ' (' . htmlspecialchars($izin['keterangan']) . ')';
    } elseif ($absen) {
        if (!empty($absen['jam_keluar'])) {
            $nextAction = 'selesai';
            $canAbsen = false;
            $statusText = 'Sudah Absen Masuk (' . substr($absen['jam_masuk'], 0, 5) . ') & Pulang (' . substr($absen['jam_keluar'], 0, 5) . ')';
        } else {
            $nextAction = 'pulang';
            $statusText = 'Sudah Absen Masuk (' . substr($absen['jam_masuk'], 0, 5) . ') - Siap Absen Pulang';
        }
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nama_lengkap' => $user['nama_lengkap'],
            'username' => $user['username'],
            'jabatan' => $user['nama_jabatan'] ?? '-',
            'foto' => (!empty($user['foto']) && file_exists(__DIR__ . '/foto/' . $user['foto'])) ? 'foto/' . $user['foto'] : ''
        ],
        'jam_kerja' => $jamKerja ? [
            'hari' => $hari,
            'jam_masuk' => substr($jamKerja['jam_masuk'], 0, 5),
            'jam_pulang' => substr($jamKerja['jam_pulang'], 0, 5),
            'shift' => $jamKerja['shift'] ?? 'Normal'
        ] : null,
        'absen_hari_ini' => $absen ? [
            'jam_masuk' => $absen['jam_masuk'],
            'status_masuk' => $absen['status_absen'],
            'jam_keluar' => $absen['jam_keluar'],
            'status_pulang' => $absen['status_absen_pulang']
        ] : null,
        'next_action' => $nextAction,
        'status_text' => $statusText,
        'can_absen' => $canAbsen
    ]);
    exit;
}

// -------------------------------------------------------------------------
// Action 2: Proses Catat Absensi Tanpa Login
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan']);
    exit;
}

// 1. Cek Switch Status QR dari qr_status.json
$statusFile = __DIR__ . '/qr_status.json';
if (file_exists($statusFile)) {
    $statusData = json_decode(file_get_contents($statusFile), true);
    if (isset($statusData['status_absen']) && $statusData['status_absen'] !== 'aktif') {
        echo json_encode([
            'success' => false,
            'message' => 'QR Absensi saat ini sedang DINONAKTIFKAN oleh Admin.'
        ]);
        exit;
    }
}

// 2. Validasi Pembatasan Jaringan Kantor (Wi-Fi) jika aktif
if (!isClientAllowed()) {
    $client_ip = getClientIP();
    echo json_encode([
        'success' => false,
        'message' => 'Absensi ditolak: Anda harus terhubung ke jaringan Wi-Fi kantor untuk melakukan absensi.',
        'detail' => "IP perangkat Anda: $client_ip"
    ]);
    exit;
}

// 3. Validasi Token QR Dinamis
$token = trim($_POST['token'] ?? '');
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token QR tidak ditemukan. Pastikan Anda men-scan QR terbaru dari layar monitor.']);
    exit;
}

$secret = env('QR_SECRET', 'absen-qr-secret');
$refreshSeconds = (int) env('QR_REFRESH_INTERVAL', 30);
if ($refreshSeconds <= 0) $refreshSeconds = 30;

$validToken = false;
$currentInterval = floor(time() / $refreshSeconds);
// Berikan toleransi rentang waktu interval sebelumnya, sekarang, dan selanjutnya (+/- 1)
for ($i = -1; $i <= 1; $i++) {
    if (hash('sha256', $secret . ($currentInterval + $i)) === $token) {
        $validToken = true;
        break;
    }
}

if (!$validToken) {
    echo json_encode([
        'success' => false,
        'message' => 'Token QR sudah kedaluwarsa atau tidak valid.',
        'detail' => 'QR code di monitor kantor diperbarui secara dinamis. Silakan scan ulang QR yang tampil saat ini.'
    ]);
    exit;
}

// 4. Validasi Karyawan yang Dipilih
$user_id = (int)($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Silakan pilih nama karyawan terlebih dahulu.']);
    exit;
}

$userQuery = mysqli_query($koneksi, "SELECT id, username, nama_lengkap, role, status FROM users WHERE id = '$user_id' LIMIT 1");
$user = $userQuery ? mysqli_fetch_assoc($userQuery) : null;
if (!$user || $user['status'] !== 'aktif') {
    echo json_encode(['success' => false, 'message' => 'Data karyawan tidak ditemukan atau sedang tidak aktif.']);
    exit;
}

$namaKaryawan = htmlspecialchars($user['nama_lengkap'] ?: $user['username']);
$tanggal = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$hariNames = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis',
    'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$hari = $hariNames[date('l')];

// 5. Cek Izin / Cuti yang Disetujui
$cekIzinCuti = mysqli_query($koneksi, "
    SELECT * FROM izin_cuti 
    WHERE (id_user = '$user_id' OR user_id = '$user_id')
      AND (LOWER(status) = 'disetujui')
      AND '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai
    LIMIT 1
");
if ($cekIzinCuti && mysqli_num_rows($cekIzinCuti) > 0) {
    $izin = mysqli_fetch_assoc($cekIzinCuti);
    echo json_encode([
        'success' => false,
        'message' => "Tidak bisa melakukan absensi karena sedang " . strtoupper($izin['jenis']),
        'detail' => "Keterangan: " . ($izin['keterangan'] ?: '-')
    ]);
    exit;
}

// 6. Cek Jadwal Jam Kerja User
$jamKerjaQuery = mysqli_query($koneksi, "
    SELECT jk.jam_masuk, jk.jam_pulang 
    FROM setting_absen sa
    JOIN jam_kerja jk ON sa.jam_kerja_id = jk.id
    WHERE sa.user_id = '$user_id' AND jk.hari = '$hari'
    LIMIT 1
");

if (!$jamKerjaQuery || mysqli_num_rows($jamKerjaQuery) === 0) {
    echo json_encode([
        'success' => false,
        'message' => "Jadwal absensi untuk hari $hari belum diatur oleh Admin untuk karyawan $namaKaryawan."
    ]);
    exit;
}

$jamKerja = mysqli_fetch_assoc($jamKerjaQuery);
$jam_masuk = $jamKerja['jam_masuk'];
$jam_pulang = $jamKerja['jam_pulang'];

$menit_sekarang = toMinutes($jam_sekarang);
$menit_masuk = toMinutes($jam_masuk);
$menit_pulang = toMinutes($jam_pulang);

// 7. Cek Record Absensi Hari Ini
$cekAbsen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal' LIMIT 1");
if ($cekAbsen && mysqli_num_rows($cekAbsen) > 0) {
    $absen = mysqli_fetch_assoc($cekAbsen);

    // Kasus A: Sudah Absen Masuk dan Sudah Absen Keluar
    if (!empty($absen['jam_keluar'])) {
        echo json_encode([
            'success' => false,
            'message' => "Halo $namaKaryawan, Anda sudah menyelesaikan Absen Masuk dan Absen Pulang untuk hari ini.",
            'detail' => "Masuk: " . substr($absen['jam_masuk'], 0, 5) . " | Pulang: " . substr($absen['jam_keluar'], 0, 5)
        ]);
        exit;
    }

    // Kasus B: Sudah Absen Masuk, Sekarang Waktunya Absen Pulang
    // Syarat: minimal 10 menit sebelum jam pulang kerja
    if ($menit_sekarang < ($menit_pulang - 10)) {
        $jamPulangFormat = substr($jam_pulang, 0, 5);
        echo json_encode([
            'success' => false,
            'message' => "Belum waktunya absen pulang.",
            'detail' => "Jadwal pulang Anda adalah jam $jamPulangFormat WIB. Silakan absen kembali setelah mendekati jam tersebut."
        ]);
        exit;
    }

    // Hitung Status Fuzzy Pulang
    $selisih_pulang = $menit_sekarang - $menit_pulang;
    if ($selisih_pulang < -15) {
        $status_pulang = 'pulang cepat';
    } elseif ($selisih_pulang <= 15) {
        $status_pulang = 'tepat waktu';
    } else {
        $status_pulang = 'lembur';
    }

    $idAbsensi = $absen['id'];
    $update = mysqli_query($koneksi, "
        UPDATE absensi 
        SET jam_keluar = '$jam_sekarang', status_absen_pulang = '$status_pulang'
        WHERE id = '$idAbsensi'
    ");

    if ($update) {
        echo json_encode([
            'success' => true,
            'type' => 'pulang',
            'user_id' => $user_id,
            'nama_karyawan' => $namaKaryawan,
            'jam' => substr($jam_sekarang, 0, 5),
            'status' => ucwords($status_pulang),
            'message' => "Absen PULANG Berhasil!",
            'detail' => "Terima kasih $namaKaryawan. Waktu Pulang: " . substr($jam_sekarang, 0, 5) . " WIB (Status: " . ucwords($status_pulang) . ")"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data absensi pulang ke database.']);
    }
    exit;

} else {
    // Kasus C: Belum Absen Masuk Hari Ini -> Lakukan Absen Masuk
    $selisih_masuk = $menit_sekarang - $menit_masuk;
    if ($selisih_masuk >= -15 && $selisih_masuk <= 15) {
        $status_kehadiran = 'hadir';
        $status_masuk = 'tepat waktu';
    } elseif ($selisih_masuk > 15 && $selisih_masuk <= 240) {
        $status_kehadiran = 'hadir';
        $status_masuk = 'terlambat';
    } elseif ($selisih_masuk < -15) {
        $status_kehadiran = 'hadir';
        $status_masuk = 'terlalu cepat';
    } else {
        $status_kehadiran = 'alpha';
        $status_masuk = 'tidak valid';
    }

    $insert = mysqli_query($koneksi, "
        INSERT INTO absensi (user_id, tanggal, jam_masuk, status, status_absen)
        VALUES ('$user_id', '$tanggal', '$jam_sekarang', '$status_kehadiran', '$status_masuk')
    ");

    if ($insert) {
        echo json_encode([
            'success' => true,
            'type' => 'masuk',
            'user_id' => $user_id,
            'nama_karyawan' => $namaKaryawan,
            'jam' => substr($jam_sekarang, 0, 5),
            'status' => ucwords($status_masuk),
            'message' => "Absen MASUK Berhasil!",
            'detail' => "Selamat bekerja $namaKaryawan! Waktu Masuk: " . substr($jam_sekarang, 0, 5) . " WIB (Status: " . ucwords($status_masuk) . ")"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absensi masuk ke database.']);
    }
    exit;
}

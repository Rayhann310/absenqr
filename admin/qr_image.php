<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../env_loader.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// Membuat token dinamis berdasarkan waktu
$timestamp = time();
$refreshSeconds = (int) env('QR_REFRESH_INTERVAL', 30);
$interval = floor($timestamp / ($refreshSeconds > 0 ? $refreshSeconds : 30));
$secret = env('QR_SECRET', 'absen-qr-secret');
$token = hash('sha256', $secret . $interval);

// URL atau data yang akan di-encode ke QR
// 1. Cek dari konfigurasi APP_URL (.env) jika diset khusus
$configuredUrl = env('APP_URL');

if (!empty($configuredUrl) && !in_array($configuredUrl, ['http://localhost', 'http://localhost/absenqr', 'http://127.0.0.1'])) {
    $baseUrl = rtrim($configuredUrl, '/');
} else {
    // 2. Deteksi host saat ini
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Jika dibuka di localhost (misal laptop/layar TV lokal), JANGAN encode 'localhost' ke QR!
    // Karena HP karyawan yang scan tidak bisa membuka 'localhost'. Gunakan domain online resmi!
    if (empty($host) || $host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost:') === 0) {
        $baseUrl = 'https://pssikabbogor.my.id';
    } else {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(dirname($scriptDir), '/\\');
        if ($baseDir === '/' || $baseDir === '\\') {
            $baseDir = '';
        }
        $baseUrl = $protocol . $host . $baseDir;
    }
}

// Target link scan QR
$data = rtrim($baseUrl, '/') . '/user/scan_qr.php?token=' . $token;

// Generate QR code
header('Content-Type: image/png');

$result = Builder::create()
    ->writer(new PngWriter())
    ->data($data)
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
    ->size(300)
    ->margin(10)
    ->build();

echo $result->getString();

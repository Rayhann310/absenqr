<?php
require '../vendor/autoload.php'; // pastikan phpqrcode atau endroid/qr-code di-install via composer
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

// URL atau data yang akan di-encode ke QR (otomatis mendeteksi domain hosting pssikabbogor.my.id maupun localhost)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'pssikabbogor.my.id';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseDir = rtrim(dirname($scriptDir), '/\\');
if ($baseDir === '/' || $baseDir === '\\') {
    $baseDir = '';
}
$data = $protocol . $host . $baseDir . '/user/scan_qr.php?token=' . $token;

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

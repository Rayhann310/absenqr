<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../env_loader.php';
require_once __DIR__ . '/../config_ip.php';

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

// Dapatkan Base URL yang valid dan dapat diakses dari smartphone (bukan localhost)
$baseUrl = getQRBaseURL();

// Target link scan QR (langsung ke halaman absensi tanpa perlu login)
$data = rtrim($baseUrl, '/') . '/scan.php?token=' . $token;

// Header anti-cache agar browser tidak menyimpan QR lama yang mengarah ke localhost
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$result = Builder::create()
    ->writer(new PngWriter())
    ->data($data)
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
    ->size(300)
    ->margin(10)
    ->build();

echo $result->getString();

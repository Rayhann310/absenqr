<?php
require '../vendor/autoload.php'; // pastikan phpqrcode atau endroid/qr-code di-install via composer

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// Membuat token dinamis berdasarkan waktu (misalnya per 30 detik)
$timestamp = time();
$interval = floor($timestamp / 30); // QR berubah setiap 30 detik
$secret = 'absen-qr-secret'; // bisa diganti dengan secret key atau ID ruangan misalnya
$token = hash('sha256', $secret . $interval);

// URL atau data yang akan di-encode ke QR
$data = 'http://localhost/absenqr/user/scan_qr.php?token=' . $token;

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

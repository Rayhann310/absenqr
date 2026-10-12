<?php
/**
 * Konfigurasi Koneksi Database MySQL
 * Mendukung otomatis mode Lokal (XAMPP) & Hosting Online (Hostinger pssikabbogor.my.id)
 */

// Nonaktifkan mode exception strict bawaan PHP 8.1+ agar tidak memicu HTTP 500 kosong
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Deteksi lingkungan: localhost / komputer lokal vs hosting live
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_local = (
    in_array($http_host, ['localhost', '127.0.0.1', '::1']) ||
    in_array($server_name, ['localhost', '127.0.0.1', '::1']) ||
    strpos($http_host, 'localhost:') === 0 ||
    strpos($http_host, '127.0.0.1:') === 0 ||
    (php_sapi_name() === 'cli' && empty($_SERVER['REMOTE_ADDR']))
);

// Daftar konfigurasi database yang akan dicoba secara berurutan
$db_candidates = [];

// Opsi 1: File custom override jika ada (db_custom.php)
if (file_exists(__DIR__ . '/db_custom.php')) {
    $custom = include __DIR__ . '/db_custom.php';
    if (is_array($custom)) {
        $db_candidates[] = $custom;
    }
}

if ($is_local) {
    // Mode Lokal (XAMPP)
    $db_candidates[] = [
        'label' => 'XAMPP Localhost',
        'host'  => 'localhost',
        'user'  => 'root',
        'pass'  => '',
        'name'  => 'absenqr'
    ];
    $db_candidates[] = [
        'label' => 'XAMPP 127.0.0.1',
        'host'  => '127.0.0.1',
        'user'  => 'root',
        'pass'  => '',
        'name'  => 'absenqr'
    ];
    // Cadangan jika lokal menggunakan database hosting
    $db_candidates[] = [
        'label' => 'Hostinger Production',
        'host'  => 'localhost',
        'user'  => 'u423457723_absenqr',
        'pass'  => 'Rayhanaja123@',
        'name'  => 'u423457723_absenqr'
    ];
} else {
    // Mode Hosting Online (Hostinger pssikabbogor.my.id)
    $db_candidates[] = [
        'label' => 'Hostinger Production (localhost)',
        'host'  => 'localhost',
        'user'  => 'u423457723_absenqr',
        'pass'  => 'Rayhanaja123@',
        'name'  => 'u423457723_absenqr'
    ];
    $db_candidates[] = [
        'label' => 'Hostinger Production (127.0.0.1)',
        'host'  => '127.0.0.1',
        'user'  => 'u423457723_absenqr',
        'pass'  => 'Rayhanaja123@',
        'name'  => 'u423457723_absenqr'
    ];
    // Cadangan
    $db_candidates[] = [
        'label' => 'Localhost Fallback',
        'host'  => 'localhost',
        'user'  => 'root',
        'pass'  => '',
        'name'  => 'absenqr'
    ];
}

$koneksi = null;
$connection_errors = [];

foreach ($db_candidates as $cfg) {
    try {
        $conn = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
        if (!$conn->connect_error) {
            $koneksi = $conn;
            $koneksi->set_charset("utf8mb4");
            break;
        } else {
            $connection_errors[] = "[{$cfg['label']}] {$conn->connect_error}";
        }
    } catch (Throwable $e) {
        $connection_errors[] = "[{$cfg['label']}] " . $e->getMessage();
    }
}

// Jika semua upaya koneksi database gagal, tampilkan halaman diagnosa yang informatif
if (!$koneksi || $koneksi->connect_error) {
    http_response_code(500);

    // Jika dipanggil via API / AJAX JSON
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Koneksi database gagal',
            'errors' => $connection_errors
        ]);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Diagnosa Database | Sistem Absensi</title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
      <style>
        body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .error-container { max-width: 650px; margin: 40px auto; padding: 15px; }
      </style>
    </head>
    <body>
      <div class="error-container">
        <div class="card card-danger card-outline shadow-sm">
          <div class="card-header">
            <h3 class="card-title font-weight-bold text-danger">
              <i class="fas fa-database mr-2"></i> Koneksi Database Gagal (500)
            </h3>
          </div>
          <div class="card-body">
            <p class="text-secondary">
              Aplikasi belum dapat terhubung ke database MySQL. Detail pesan error dari server MySQL:
            </p>
            <div class="alert alert-light border border-danger">
              <strong class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i> Detail Error:</strong>
              <ul class="mb-0 mt-2 pl-3 text-dark" style="font-size: 0.9rem;">
                <?php foreach ($connection_errors as $err): ?>
                  <li><code><?= htmlspecialchars($err) ?></code></li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="mt-4">
              <h6 class="font-weight-bold text-dark"><i class="fas fa-tools mr-1"></i> Langkah Perbaikan di Hostinger (hPanel):</h6>
              <ol class="text-muted pl-3" style="font-size: 0.95rem; line-height: 1.6;">
                <li>Buka <strong>hPanel Hostinger</strong> &rarr; menu <strong>Databases</strong> &rarr; <strong>MySQL Databases</strong>.</li>
                <li>Pastikan Database <code>u423457723_absenqr</code> dan User <code>u423457723_absenqr</code> sudah dibuat.</li>
                <li>Jika lupa password, klik tombol <strong>titik tiga (&hellip;)</strong> di sebelah user &rarr; pilih <strong>Change Password</strong> &rarr; set password yang sesuai.</li>
                <li>Pastikan user memiliki hak akses penuh (<strong>All Privileges</strong>) ke database tersebut.</li>
                <li>Pastikan tabel sudah di-import dari <code>absenqr.sql</code> melalui menu <strong>phpMyAdmin</strong>.</li>
              </ol>
            </div>
          </div>
          <div class="card-footer bg-white text-center">
            <a href="" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync-alt mr-1"></i> Refresh Halaman</a>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>

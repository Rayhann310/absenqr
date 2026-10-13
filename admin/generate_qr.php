<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Cek status QR dari file JSON
$statusFile = '../qr_status.json';
$statusData = file_exists($statusFile) ? json_decode(file_get_contents($statusFile), true) : ['status_absen' => 'nonaktif'];
$isAktif = isset($statusData['status_absen']) && $statusData['status_absen'] === 'aktif';

require_once __DIR__ . '/../config_ip.php';
$qrBaseUrl = getQRBaseURL();

include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';
?>

<div class="content-wrapper">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid">
      <h1><i class="fas fa-sync-alt mr-2"></i>QR Dinamis untuk Absensi</h1>
    </div>
  </section>

  <!-- Main Content -->
  <section class="content">
    <div class="container-fluid">

      <!-- Status Panel -->
      <div class="alert alert-<?= $isAktif ? 'success' : 'danger' ?> text-center">
        Status QR Absensi saat ini: <strong><?= strtoupper($statusData['status_absen']) ?></strong>
      </div>

      <!-- QR Panel -->
      <div class="card">
        <div class="card-body text-center">
          <?php if ($isAktif): ?>
            <div id="qr-container">
              <img src="qr_image.php?t=<?= time() ?>" alt="QR Dinamis" id="qr-image" style="max-width: 300px;">
            </div>
            <p class="mt-2 mb-1 text-muted">QR akan diperbarui setiap <strong>30 detik</strong> secara otomatis.</p>
            <div class="mb-2">
              <small class="text-muted"><i class="fas fa-link mr-1"></i> Target Scan: <span class="badge badge-light border text-dark"><?= htmlspecialchars($qrBaseUrl) ?></span></small>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mt-4">
              <h5>QR Dinamis sedang dinonaktifkan.</h5>
              Silakan aktifkan kembali melalui pengaturan jika ingin menampilkan QR untuk absensi.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<!-- Auto Refresh QR -->
<?php if ($isAktif): ?>
<script>
  setInterval(() => {
    const qrImage = document.getElementById('qr-image');
    if (qrImage) {
      qrImage.src = 'qr_image.php?t=' + new Date().getTime(); // prevent caching
    }
  }, 30000);
</script>
<?php endif; ?>
</body>
</html>

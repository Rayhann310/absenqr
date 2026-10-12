<?php
$allowed_ips = ['10.175.151.187'];
$client_ip = $_SERVER['REMOTE_ADDR'];

if (!in_array($client_ip, $allowed_ips)) {
  http_response_code(403);
  die('Akses hanya diizinkan dari jaringan kantor.<br>IP Anda saat ini: <strong>' . $client_ip . '</strong>');
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>QR Dinamis Absensi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 4 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      background: linear-gradient(to right, #1d2b64, #f8cdda);
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
    }

    .container-qr {
      margin-top: 50px;
      padding: 20px;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      max-width: 400px;
      width: 90%;
      text-align: center;
      animation: fadeIn 0.8s ease;
    }

    .qr-img {
      width: 100%;
      max-width: 300px;
      border-radius: 12px;
      border: 4px solid #dee2e6;
      margin-bottom: 20px;
      transition: transform 0.3s;
    }

    .qr-img:hover {
      transform: scale(1.05);
    }

    .title {
      color: #343a40;
      font-weight: bold;
      font-size: 1.6rem;
    }

    .status-badge {
      margin-top: 10px;
      font-size: 0.9rem;
      padding: 5px 15px;
      border-radius: 20px;
      margin-bottom: 10px;
      background-color: #17a2b8;
      color: #fff;
      display: inline-block;
    }

    .countdown-bar {
      height: 10px;
      background-color: #ced4da;
      border-radius: 20px;
      overflow: hidden;
      margin: 20px 0;
    }

    .progress-inner {
      height: 100%;
      width: 100%;
      background-color: #28a745;
      transition: width 1s linear;
    }

    .fullscreen-btn {
      margin-top: 10px;
    }

    .info-marquee {
      width: 100%;
      background-color: #343a40;
      color: white;
      padding: 10px 0;
      font-size: 0.95rem;
      position: fixed;
      bottom: 0;
      text-align: center;
    }

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>

<div class="container-qr">
  <i class="fas fa-user-check fa-3x text-primary mb-3"></i>
  <div class="title">QR Dinamis Absensi</div>

  <?php if ($qrAktif): ?>
    <span class="status-badge bg-info" id="refreshStatus">QR Aktif</span>
    <img id="qr-image" src="admin/qr_image.php" alt="QR Dinamis" class="qr-img">
    <div class="countdown-bar">
      <div class="progress-inner" id="progressBar"></div>
    </div>
    <p id="countdownText">QR akan diperbarui dalam <strong><span id="countdown">10</span></strong> detik</p>
    <button class="btn btn-outline-dark fullscreen-btn" onclick="toggleFullScreen()">
      <i class="fas fa-expand"></i> Layar Penuh
    </button>
  <?php else: ?>
    <div class="alert alert-warning mt-4">
      <strong>QR Absensi sedang nonaktif.</strong><br>
      Silakan hubungi admin.
    </div>
  <?php endif; ?>
</div>


<!-- Informasi Marquee -->
<div class="info-marquee">
  <marquee behavior="scroll" direction="left" scrollamount="4">
    <?php
      include 'db.php';
      $query = mysqli_query($koneksi, "SELECT isi_info FROM informasi ORDER BY id DESC");
      while ($row = mysqli_fetch_assoc($query)) {
        echo "📢 " . htmlspecialchars($row['isi_info']) . " &nbsp;&nbsp;&nbsp;";
      }
    ?>
  </marquee>
</div>

<!-- JS -->
<script>
  let countdown = 30;
  const countdownEl = document.getElementById('countdown');
  const qrImage = document.getElementById('qr-image');
  const statusEl = document.getElementById('refreshStatus');
  const progressBar = document.getElementById('progressBar');

  function refreshQR() {
    qrImage.src = 'admin/qr_image.php?t=' + new Date().getTime();
    statusEl.textContent = "QR Diperbarui!";
    statusEl.classList.remove("bg-danger");
    statusEl.classList.add("bg-success");
    setTimeout(() => {
      statusEl.textContent = "QR Aktif";
      statusEl.classList.remove("bg-success");
      statusEl.classList.add("bg-info");
    }, 1500);
  }

  setInterval(() => {
    countdown--;
    countdownEl.textContent = countdown;
    progressBar.style.width = (countdown * 100 / 10) + "%";
    if (countdown <= 0) {
      refreshQR();
      countdown = 10;
    }
  }, 1000);

  function toggleFullScreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(err => {
        alert("Gagal masuk layar penuh: " + err.message);
      });
    } else {
      document.exitFullscreen();
    }
  }
</script>

</body>
</html>

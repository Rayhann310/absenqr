<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit;
}

include '../db.php';
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';

// Set zona waktu ke Jakarta
date_default_timezone_set('Asia/Jakarta');
$hariIni = date('l'); // e.g. Monday
$hariIndo = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu'
];
$hariSekarang = $hariIndo[$hariIni];
$jamSekarang = date('H:i');

// Ambil ID user login
$username = $_SESSION['username'];
$userQuery = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$username'");
$userData = mysqli_fetch_assoc($userQuery);
$userId = $userData['id'];

// Cek apakah user sudah absen hari ini
$tanggalHariIni = date('Y-m-d');
$cekAbsen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$userId' AND tanggal='$tanggalHariIni'");
$sudahAbsen = mysqli_num_rows($cekAbsen) > 0;

// Ambil jadwal absen user
$jadwalQuery = mysqli_query($koneksi, "
  SELECT jk.hari, jk.jam_masuk, jk.jam_pulang
  FROM setting_absen sa
  JOIN jam_kerja jk ON sa.jam_kerja_id = jk.id
  WHERE sa.user_id = '$userId'
  ORDER BY FIELD(jk.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')
") or die("Query Error: " . mysqli_error($koneksi));
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Dashboard Pengguna</h1>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <h5>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</h5>
        <p>Hari ini: <strong><?= $hariSekarang ?></strong>, pukul <strong><?= $jamSekarang ?> WIB</strong></p>

        <?php if (!$sudahAbsen): ?>
          <div class="alert alert-warning">
            Anda <strong>belum absen</strong> hari ini. Silakan lakukan absensi.
          </div>
          <a href="scan_qr.php" class="btn btn-success mb-3">
            <i class="fas fa-qrcode"></i> Absen Sekarang
          </a>
        <?php else: ?>
          <div class="alert alert-success">
            Anda sudah melakukan absensi hari ini.
          </div>
        <?php endif; ?>

        <h5>Jadwal Absen Anda:</h5>
        <div class="row">
          <?php while ($jadwal = mysqli_fetch_assoc($jadwalQuery)) : ?>
            <div class="col-md-4">
              <div class="card bg-light mb-3 shadow">
                <div class="card-header text-primary">
                  <strong><?= $jadwal['hari'] ?></strong>
                  <?= $jadwal['hari'] == $hariSekarang ? '<span class="badge badge-info float-end">Hari Ini</span>' : '' ?>
                </div>
                <div class="card-body">
                  <p><strong>Jam Masuk:</strong> <?= $jadwal['jam_masuk'] ?> WIB</p>
                  <p><strong>Jam Keluar:</strong> <?= $jadwal['jam_pulang'] ?> WIB</p>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>
</body>
</html>

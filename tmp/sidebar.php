<?php
if (!isset($koneksi)) {
    include '../db.php'; // pastikan koneksi database dimuat
}

// Atur $hasIzinCuti jika belum didefinisikan
$hasIzinCuti = false;

if (isset($_SESSION['user_id'])) {
    $id_user = $_SESSION['user_id'];
} elseif (isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
}

if (!empty($id_user)) {
    $query = $koneksi->prepare("SELECT id FROM izin_cuti WHERE id_user = ? LIMIT 1");
    $query->bind_param("i", $id_user);
    $query->execute();
    $query->store_result();
    if ($query->num_rows > 0) {
        $hasIzinCuti = true;
    }
    $query->close();
}
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="#" class="brand-link">
    <i class="fas fa-qrcode ml-3 mr-2"></i>
    <span class="brand-text font-weight-light">Absensi QR</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- User Panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['username'] ?>&background=0D8ABC&color=fff" class="img-circle elevation-2" alt="User Image">
      </div>
      <div class="info">
        <a href="#" class="d-block"><?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)</a>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
        <!-- Dashboard -->
        <li class="nav-item">
          <a href="index.php" class="nav-link">
            <i class="nav-icon fas fa-home"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- ADMIN MENU -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <li class="nav-item">
            <a href="user.php" class="nav-link">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>Kelola Pengguna</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="absensi.php" class="nav-link">
              <i class="nav-icon fas fa-calendar-check"></i>
              <p>Rekapitulasi Absensi</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="izin_cuti.php" class="nav-link">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Izin & Cuti</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="generate_qr.php" class="nav-link">
              <i class="nav-icon fas fa-qrcode"></i>
              <p>Generator QR Absensi</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="jam_kerja.php" class="nav-link">
              <i class="nav-icon fas fa-clock"></i>
              <p>Master Jam Kerja</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="setting_absen.php" class="nav-link">
              <i class="nav-icon fas fa-sliders-h"></i>
              <p>Konfigurasi Absensi</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="setting_jabatan.php" class="nav-link">
              <i class="nav-icon fas fa-briefcase"></i>
              <p>Manajemen Jabatan</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="setting.php" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Pengaturan Sistem</p>
            </a>
          </li>

        <!-- USER MENU -->
        <?php else: ?>
          <li class="nav-item">
            <a href="scan_qr.php" class="nav-link">
              <i class="nav-icon fas fa-qrcode"></i>
              <p>Scan QR</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="riwayat.php" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>Riwayat Absensi</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="profil.php" class="nav-link">
              <i class="nav-icon fas fa-user-circle"></i>
              <p>Profil Saya</p>
            </a>
          </li>

          <?php if ($hasIzinCuti): ?>
          <li class="nav-item">
            <a href="izin_cuti.php" class="nav-link">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Izin & Cuti</p>
            </a>
          </li>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Logout -->
        <li class="nav-item">
          <a href="../logout.php" class="nav-link">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>

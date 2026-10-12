<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Dashboard Admin</h1>
        </div>
        <div class="col-sm-6 text-right">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">

      <!-- Info Boxes -->
      <div class="row">
        <div class="col-lg-3 col-6">
          <!-- small box -->
          <div class="small-box bg-info">
            <div class="inner">
              <h3>12</h3>
              <p>Total Pengguna</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="#" class="small-box-footer">Selengkapnya <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3>7</h3>
              <p>Absensi Hari Ini</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <a href="#" class="small-box-footer">Selengkapnya <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3>3</h3>
              <p>Pengajuan Izin</p>
            </div>
            <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
            <a href="#" class="small-box-footer">Lihat Pengajuan <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3>2</h3>
              <p>Absen Terlambat</p>
            </div>
            <div class="icon"><i class="fas fa-user-clock"></i></div>
            <a href="#" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <!-- Welcome Card -->
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Selamat Datang</h3>
        </div>
        <div class="card-body">
          <p>Halo, <strong><?= $_SESSION['username'] ?></strong>! Anda login sebagai <strong>Admin</strong>.</p>
          <p>Gunakan dashboard ini untuk mengelola data pengguna, absensi, dan fitur lainnya.</p>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>
</body>
</html>

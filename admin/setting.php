<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../db.php';
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';

// ============ Proses Simpan Data ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_aplikasi   = mysqli_real_escape_string($koneksi, $_POST['nama_aplikasi']);
    $nama_perusahaan = mysqli_real_escape_string($koneksi, $_POST['nama_perusahaan']);
    $refresh_time    = (int) $_POST['refresh_time'];
    $informasi       = mysqli_real_escape_string($koneksi, $_POST['informasi']);

    $logo_baru = '';
    if (!empty($_FILES['logo']['name'])) {
        $nama_file = time() . '_' . basename($_FILES['logo']['name']);
        $upload_path = '../uploads/' . $nama_file;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_baru = $nama_file;
        }
    }

    if ($logo_baru !== '') {
        // update dengan logo baru
        $sql = "UPDATE setting SET 
            nama_aplikasi='$nama_aplikasi',
            nama_perusahaan='$nama_perusahaan',
            logo='$logo_baru',
            refresh_time='$refresh_time',
            informasi='$informasi'
            WHERE id = 1";
    } else {
        // update tanpa mengubah logo
        $sql = "UPDATE setting SET 
            nama_aplikasi='$nama_aplikasi',
            nama_perusahaan='$nama_perusahaan',
            refresh_time='$refresh_time',
            informasi='$informasi'
            WHERE id = 1";
    }

    mysqli_query($koneksi, $sql);
    $pesan_sukses = "Pengaturan berhasil disimpan.";
}

// Ambil data setting
$setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM setting WHERE id = 1"));
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Pengaturan Sistem</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <?php if (isset($pesan_sukses)): ?>
        <div class="alert alert-success"><?= $pesan_sukses ?></div>
      <?php endif; ?>

      <div class="card card-info">
        <div class="card-header">
          <h3 class="card-title">Form Pengaturan</h3>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
          <div class="card-body">

            <div class="form-group">
              <label for="nama_aplikasi">Nama Aplikasi</label>
              <input type="text" name="nama_aplikasi" class="form-control"
                     value="<?= htmlspecialchars($setting['nama_aplikasi']) ?>">
            </div>

            <div class="form-group">
              <label for="nama_perusahaan">Nama Perusahaan / Institusi</label>
              <input type="text" name="nama_perusahaan" class="form-control"
                     value="<?= htmlspecialchars($setting['nama_perusahaan']) ?>">
            </div>

            <div class="form-group">
              <label for="logo">Logo (opsional)</label><br>
              <?php if (!empty($setting['logo'])): ?>
                <img src="../uploads/<?= htmlspecialchars($setting['logo']) ?>" alt="Logo" height="60"><br><br>
              <?php endif; ?>
              <input type="file" name="logo" class="form-control-file">
            </div>

            <div class="form-group">
              <label for="refresh_time">Waktu Refresh QR (detik)</label>
              <input type="number" name="refresh_time" class="form-control"
                     value="<?= (int) $setting['refresh_time'] ?>">
            </div>

            <div class="form-group">
              <label for="informasi">Informasi Berjalan</label>
              <textarea name="informasi" rows="3" class="form-control"><?= htmlspecialchars($setting['informasi']) ?></textarea>
            </div>

          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
          </div>
        </form>
      </div>

      <!-- Card untuk Aktivasi LocalToNet -->
      <div class="card card-success">
        <div class="card-header">
          <h3 class="card-title">Aktifkan Sistem via LocalToNet</h3>
        </div>
        <div class="card-body text-center">
          <p>Klik tombol di bawah untuk mengaktifkan sistem dengan token LocalToNet:</p>
          <form method="POST" action="localtonet_start.php">
            <input type="hidden" name="token" value="oGv1wpXmLYOflne5R8NCKtkzyrMAa3TF4">
            <button type="submit" class="btn btn-success"><i class="fas fa-plug"></i> Aktifkan LocalToNet</button>
          </form>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>
</body>
</html>

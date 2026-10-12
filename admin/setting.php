<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../db.php';
require_once '../config_ip.php';
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
        $sql = "UPDATE setting SET 
            nama_aplikasi='$nama_aplikasi',
            nama_perusahaan='$nama_perusahaan',
            logo='$logo_baru',
            refresh_time='$refresh_time',
            informasi='$informasi'
            WHERE id = 1";
    } else {
        $sql = "UPDATE setting SET 
            nama_aplikasi='$nama_aplikasi',
            nama_perusahaan='$nama_perusahaan',
            refresh_time='$refresh_time',
            informasi='$informasi'
            WHERE id = 1";
    }

    mysqli_query($koneksi, $sql);

    // Simpan Pengaturan Jaringan Kantor (IP Whitelist)
    $filter_ip = isset($_POST['filter_ip']) ? 1 : 0;
    $allowed_ips = $_POST['allowed_ips'] ?? '';
    saveNetworkSetting($filter_ip, $allowed_ips);

    $pesan_sukses = "Pengaturan sistem dan keamanan jaringan berhasil disimpan.";
}

// Ambil data setting aplikasi
$setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM setting WHERE id = 1"));

// Ambil data setting jaringan
$networkSetting = getNetworkSetting();
$currentClientIp = getClientIP();
$allowedIpsText = implode("\n", (array)($networkSetting['allowed_ips'] ?? []));
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
        <div class="alert alert-success alert-dismissible fade show">
          <i class="fas fa-check-circle mr-2"></i><?= $pesan_sukses ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
      <?php endif; ?>

      <form action="" method="POST" enctype="multipart/form-data">
        <div class="card card-primary card-outline">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Form Pengaturan Aplikasi</h3>
          </div>
          <div class="card-body">

            <div class="form-group">
              <label for="nama_aplikasi">Nama Aplikasi</label>
              <input type="text" name="nama_aplikasi" class="form-control"
                     value="<?= htmlspecialchars($setting['nama_aplikasi'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label for="nama_perusahaan">Nama Perusahaan / Institusi</label>
              <input type="text" name="nama_perusahaan" class="form-control"
                     value="<?= htmlspecialchars($setting['nama_perusahaan'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label for="logo">Logo (opsional)</label><br>
              <?php if (!empty($setting['logo'])): ?>
                <img src="../uploads/<?= htmlspecialchars($setting['logo']) ?>" alt="Logo" height="60" class="mb-2 rounded border p-1"><br>
              <?php endif; ?>
              <input type="file" name="logo" class="form-control-file">
            </div>

            <div class="form-group">
              <label for="refresh_time">Waktu Refresh QR Dinamis (detik)</label>
              <input type="number" name="refresh_time" class="form-control" min="5" max="300"
                     value="<?= (int) ($setting['refresh_time'] ?? 30) ?>" required>
              <small class="form-text text-muted">Durasi pergantian token QR (standar: 30 detik).</small>
            </div>

            <div class="form-group">
              <label for="informasi">Informasi Berjalan (Marquee)</label>
              <textarea name="informasi" rows="3" class="form-control"><?= htmlspecialchars($setting['informasi'] ?? '') ?></textarea>
            </div>

          </div>
        </div>

        <!-- Card Pengaturan Jaringan Kantor (Wi-Fi Absensi) -->
        <div class="card card-warning card-outline">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-wifi mr-2"></i>Keamanan Jaringan Kantor (Wi-Fi Absensi)</h3>
          </div>
          <div class="card-body">
            <div class="alert alert-light border">
              <h5><i class="fas fa-info-circle text-info mr-2"></i>Panduan Hosting di Hostinger (pssikabbogor.my.id)</h5>
              <p class="mb-1">
                Ketika website berada di internet publik, semua perangkat (HP/Laptop) yang terhubung ke <strong>Wi-Fi Kantor</strong> akan keluar ke internet menggunakan <strong>IP Publik Router Kantor</strong> yang sama.
              </p>
              <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                Jika fitur pembatasan dinonaktifkan, absensi tetap aman karena menggunakan <strong>QR Dinamis (berganti tiap 30 detik)</strong> yang hanya dapat di-scan langsung di layar kantor.
              </p>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch custom-switch-lg">
                <input type="checkbox" class="custom-control-input" id="filter_ip" name="filter_ip" value="1"
                       <?= !empty($networkSetting['filter_ip']) ? 'checked' : '' ?>>
                <label class="custom-control-label font-weight-bold" for="filter_ip">
                  Aktifkan Pembatasan: Absen HANYA diizinkan dari Jaringan / Wi-Fi Kantor
                </label>
              </div>
              <small class="form-text text-muted">
                Jika diaktifkan, karyawan yang menggunakan paket data sendiri (4G/5G di luar kantor) tidak dapat melakukan absensi.
              </small>
            </div>

            <div class="form-group">
              <label for="allowed_ips">Daftar IP Publik Kantor yang Diizinkan:</label>
              <div class="d-flex align-items-center mb-2">
                <span class="badge badge-info p-2 mr-2">
                  <i class="fas fa-network-wired"></i> IP Anda saat ini: <strong><?= htmlspecialchars($currentClientIp) ?></strong>
                </span>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="addCurrentIP('<?= htmlspecialchars($currentClientIp) ?>')">
                  <i class="fas fa-plus-circle"></i> Gunakan IP Saya Saat Ini
                </button>
              </div>
              <textarea name="allowed_ips" id="allowed_ips" rows="4" class="form-control"
                        placeholder="Contoh:&#10;180.252.12.34&#10;114.124.50.89"><?= htmlspecialchars($allowedIpsText) ?></textarea>
              <small class="form-text text-muted">
                Tulis satu IP per baris atau pisahkan dengan koma. Jika kantor memiliki lebih dari satu koneksi/ISP, masukkan semuanya di sini.
              </small>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i> Simpan Semua Pengaturan</button>
          </div>
        </div>
      </form>

      <!-- Card untuk Aktivasi LocalToNet (Opsional jika ingin tunneling lokal) -->
      <div class="card card-secondary collapsed-card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Tunneling Tambahan (LocalToNet)</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
          </div>
        </div>
        <div class="card-body text-center">
          <p>Jika ingin mengaktifkan tunnel alternatif dengan token LocalToNet:</p>
          <form method="POST" action="localtonet_start.php">
            <input type="hidden" name="token" value="oGv1wpXmLYOflne5R8NCKtkzyrMAa3TF4">
            <button type="submit" class="btn btn-outline-success"><i class="fas fa-plug"></i> Aktifkan LocalToNet</button>
          </form>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
function addCurrentIP(ip) {
  const textarea = document.getElementById('allowed_ips');
  const currentVal = textarea.value.trim();
  const ipList = currentVal ? currentVal.split(/[\r\n,]+/).map(s => s.trim()) : [];
  
  if (!ipList.includes(ip)) {
    if (currentVal.length > 0) {
      textarea.value = currentVal + "\n" + ip;
    } else {
      textarea.value = ip;
    }
    alert('IP ' + ip + ' berhasil ditambahkan ke daftar!');
  } else {
    alert('IP ' + ip + ' sudah ada di daftar.');
  }
}
</script>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>
</body>
</html>

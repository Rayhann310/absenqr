<?php
// Tampilkan error
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit;
}

include '../db.php';
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';

date_default_timezone_set('Asia/Jakarta');

// Ambil ID user dari session
$username = $_SESSION['username'];
$userQuery = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$username'");
$userData = mysqli_fetch_assoc($userQuery);
$userId = $userData['id'] ?? 0;

// Ambil data izin user
$izinQuery = mysqli_query($koneksi, "SELECT * FROM izin_cuti WHERE id_user='$userId' ORDER BY tanggal_pengajuan DESC");
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Izin & Cuti</h1>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <h5>Riwayat Pengajuan</h5>
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>Pengajuan</th>
              <th>Jenis</th>
              <th>Mulai</th>
              <th>Selesai</th>
              <th>Keterangan</th>
              <th>Bukti</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($izinQuery) === 0): ?>
              <tr><td colspan="7" class="text-center">Belum ada pengajuan.</td></tr>
            <?php else: ?>
              <?php while ($izin = mysqli_fetch_assoc($izinQuery)) : ?>
                <tr>
                  <td><?= htmlspecialchars($izin['tanggal_pengajuan']) ?></td>
                  <td><?= htmlspecialchars($izin['jenis']) ?></td>
                  <td><?= htmlspecialchars($izin['tanggal_mulai']) ?></td>
                  <td><?= htmlspecialchars($izin['tanggal_selesai']) ?></td>
                  <td><?= nl2br(htmlspecialchars($izin['keterangan'])) ?></td>
                  <td>
                    <?php if (!empty($izin['bukti'])): ?>
                      <a href="../uploads/izincuti/<?= $izin['bukti'] ?>" target="_blank">Lihat</a>
                    <?php else: ?>
                      Tidak ada
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-<?= 
                        $izin['status'] == 'Disetujui' ? 'success' : 
                        ($izin['status'] == 'Ditolak' ? 'danger' : 'secondary') ?>">
                      <?= htmlspecialchars($izin['status']) ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>
</body>
</html>

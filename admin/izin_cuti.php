<?php
// Tampilkan error
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../db.php';
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';

date_default_timezone_set('Asia/Jakarta');

// Handle aksi update status
$alert = '';
if (isset($_GET['id']) && isset($_GET['aksi'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if (in_array($aksi, ['Disetujui', 'Ditolak'])) {
        $success = mysqli_query($koneksi, "UPDATE izin_cuti SET status='$aksi' WHERE id='$id'");
        if ($success) {
            $alert = "Status berhasil diperbarui menjadi: $aksi";
        } else {
            $alert = "Gagal memperbarui status.";
        }
    }
}

// Ambil semua data izin
$izinQuery = mysqli_query($koneksi, "SELECT ic.*, u.nama_lengkap 
    FROM izin_cuti ic 
    JOIN users u ON ic.id_user = u.id 
    ORDER BY ic.tanggal_pengajuan DESC");
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Manajemen Izin & Cuti</h1>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <?php if (!empty($alert)): ?>
          <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>

        <h5>Data Pengajuan</h5>
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>Pengajuan</th>
              <th>Nama</th>
              <th>Jenis</th>
              <th>Mulai</th>
              <th>Selesai</th>
              <th>Keterangan</th>
              <th>Bukti</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($izinQuery) === 0): ?>
              <tr><td colspan="9" class="text-center">Tidak ada pengajuan.</td></tr>
            <?php else: ?>
              <?php while ($izin = mysqli_fetch_assoc($izinQuery)) : ?>
                <tr>
                  <td><?= htmlspecialchars($izin['tanggal_pengajuan']) ?></td>
                  <td><?= htmlspecialchars($izin['nama_lengkap']) ?></td>
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
                  <td>
                    <?php if ($izin['status'] == 'Menunggu'): ?>
                      <a href="?id=<?= $izin['id'] ?>&aksi=Disetujui" class="btn btn-success btn-sm" onclick="return confirm('Setujui pengajuan ini?')">Setujui</a>
                      <a href="?id=<?= $izin['id'] ?>&aksi=Ditolak" class="btn btn-danger btn-sm" onclick="return confirm('Tolak pengajuan ini?')">Tolak</a>
                    <?php else: ?>
                      <em>Terkunci</em>
                    <?php endif; ?>
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

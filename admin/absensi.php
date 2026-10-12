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

// Hapus data absensi jika ada query delete
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  mysqli_query($koneksi, "DELETE FROM absensi WHERE id='$id'");
  header("Location: absensi.php");
  exit;
}

// Ambil data absensi bergabung dengan tabel user
$data = mysqli_query($koneksi, "
  SELECT absensi.*, users.username 
  FROM absensi 
  JOIN users ON absensi.user_id = users.id 
  ORDER BY absensi.tanggal DESC
");
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Data Absensi</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow">
        <div class="card-body">

<div class="mb-3 d-flex flex-wrap gap-2">
  <a href="../absensi.php" class="btn btn-success mr-2 mb-2">
    <i class="fas fa-qrcode"></i> Lihat QR Absensi
  </a>

  <a href="absensi_export.php" class="btn btn-success mr-2 mb-2">
    <i class="fas fa-file-excel"></i> Export ke Excel
  </a>

  <a href="absensi_export_pdf.php" class="btn btn-danger mb-2">
    <i class="fas fa-file-pdf"></i> Export ke PDF
  </a>
</div>

          <!-- Tabel Absensi -->
          <div class="table-responsive">
            <table class="table table-bordered table-striped" id="absensiTable">
              <thead class="thead-dark text-center">
                <tr>
                  <th>Username</th>
                  <th>Tanggal</th>
                  <th>Jam Masuk</th>
                  <th>Jam Keluar</th>
                  <th>Status Masuk</th>
                  <th>Status Keluar</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($a = mysqli_fetch_assoc($data)): ?>
                  <tr>
                    <td><?= htmlspecialchars($a['username']) ?></td>
                    <td><?= $a['tanggal'] ?></td>
                    <td><?= $a['jam_masuk'] ?></td>
                    <td><?= $a['jam_keluar'] ?></td>

                    <!-- Status Masuk -->
                    <td>
                      <span class="badge badge-secondary">
                        <?= ucwords(htmlspecialchars($a['status_absen'])) ?>
                      </span>
                    </td>

                    <!-- Status Pulang -->
                    <td>
                      <span class="badge badge-secondary">
                        <?= ucwords(htmlspecialchars($a['status_absen_pulang'])) ?>
                      </span>
                    </td>

                    <!-- Aksi -->
                    <td class="text-center">
                      <a href="?delete=<?= $a['id'] ?>" onclick="return confirm('Yakin ingin hapus?')" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash-alt"></i> Hapus
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<!-- DataTables Init -->
<script>
  $(document).ready(function () {
    $('#absensiTable').DataTable();
  });
</script>
</body>
</html>

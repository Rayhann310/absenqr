<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

include '../db.php';
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';

$username = $_SESSION['username'];
$queryUser = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$username'");
$user = mysqli_fetch_assoc($queryUser);
$user_id = $user['id'];

// Ambil riwayat absensi user
$riwayat = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' ORDER BY tanggal DESC");
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Riwayat Absensi</h1>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <table id="riwayatTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Jam Masuk</th>
              <th>Jam Keluar</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($riwayat)) : ?>
              <tr>
                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td><?= $row['jam_masuk'] ?? '-' ?></td>
                <td><?= $row['jam_keluar'] ?? '-' ?></td>
                <td>
                <?php
                    $status = $row['status'];
                    if ($status == 'hadir') {
                        $badge = 'badge-success';
                    } elseif ($status == 'izin') {
                        $badge = 'badge-warning';
                    } elseif ($status == 'sakit') {
                        $badge = 'badge-info';
                    } elseif ($status == 'alpa') {
                        $badge = 'badge-danger';
                    } else {
                        $badge = 'badge-secondary';
                    }
                ?>
                <span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<!-- DataTables Init -->
<script>
$(document).ready(function() {
  $('#riwayatTable').DataTable({
    responsive: true,
    autoWidth: false
  });
});
</script>
</body>
</html>

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
$queryUser = mysqli_query($koneksi, "
    SELECT u.*, j.nama_jabatan 
    FROM users u 
    LEFT JOIN jabatan j ON u.jabatan_id = j.id 
    WHERE u.username = '$username'
");
$user = mysqli_fetch_assoc($queryUser);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Profil Saya</h1>
  </section>

  <section class="content">
    <div class="card card-primary">
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 text-center">
            <?php if (!empty($user['foto']) && file_exists('../foto/' . $user['foto'])): ?>
              <img src="../foto/<?= $user['foto'] ?>" alt="Foto Profil" class="img-fluid img-thumbnail mb-2">
            <?php else: ?>
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['nama_lengkap']) ?>&background=random" class="img-fluid img-thumbnail mb-2" alt="Foto Default">
            <?php endif; ?>
            <h5 class="mt-2"><?= htmlspecialchars($user['nama_lengkap']) ?></h5>
            <span class="badge badge-info"><?= htmlspecialchars($user['role']) ?></span>
          </div>
          <div class="col-md-9">
            <table class="table table-striped">
              <tr>
                <th>Username</th>
                <td><?= htmlspecialchars($user['username']) ?></td>
              </tr>
              <tr>
                <th>Nama Lengkap</th>
                <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
              </tr>
              <tr>
                <th>Jabatan</th>
                <td><?= htmlspecialchars($user['nama_jabatan'] ?? '-') ?></td>
              </tr>
              <tr>
                <th>Alamat</th>
                <td><?= htmlspecialchars($user['almat']) ?></td>
              </tr>
              <tr>
                <th>Status Akun</th>
                <td>
                  <?php if ($user['status'] === 'aktif'): ?>
                    <span class="badge badge-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Nonaktif</span>
                  <?php endif; ?>
                </td>
              </tr>
            </table>
            <!-- <a href="ubah_password.php" class="btn btn-sm btn-warning">Ubah Password</a> -->
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>
</body>
</html>

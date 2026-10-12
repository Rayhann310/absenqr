<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
  header("Location: ../index.php");
  exit;
}

include '../db.php';
include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';

// Handle Tambah/Edit
if (isset($_POST['saveUser'])) {
  $id = $_POST['id'];
  $username = $_POST['username'];
  $nama_lengkap = $_POST['nama_lengkap'];
  $jabatan_id = $_POST['jabatan_id'];
  $role = $_POST['role'];
  $status = $_POST['status'];
  $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

  // Handle file foto
  $foto = null;
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto = 'user_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $foto);
  }

  if ($id == '') {
    // Tambah
    $stmt = $koneksi->prepare("INSERT INTO users (username, nama_lengkap, jabatan_id, foto, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissss", $username, $nama_lengkap, $jabatan_id, $foto, $password, $role, $status);
    $_SESSION['message'] = "Pengguna berhasil ditambahkan.";
  } else {
    // Edit
    if ($password && $foto) {
      $stmt = $koneksi->prepare("UPDATE users SET username=?, nama_lengkap=?, jabatan_id=?, foto=?, password=?, role=?, status=? WHERE id=?");
      $stmt->bind_param("ssissssi", $username, $nama_lengkap, $jabatan_id, $foto, $password, $role, $status, $id);
    } elseif ($password) {
      $stmt = $koneksi->prepare("UPDATE users SET username=?, nama_lengkap=?, jabatan_id=?, password=?, role=?, status=? WHERE id=?");
      $stmt->bind_param("ssisssi", $username, $nama_lengkap, $jabatan_id, $password, $role, $status, $id);
    } elseif ($foto) {
      $stmt = $koneksi->prepare("UPDATE users SET username=?, nama_lengkap=?, jabatan_id=?, foto=?, role=?, status=? WHERE id=?");
      $stmt->bind_param("ssisssi", $username, $nama_lengkap, $jabatan_id, $foto, $role, $status, $id);
    } else {
      $stmt = $koneksi->prepare("UPDATE users SET username=?, nama_lengkap=?, jabatan_id=?, role=?, status=? WHERE id=?");
      $stmt->bind_param("ssissi", $username, $nama_lengkap, $jabatan_id, $role, $status, $id);
    }
    $_SESSION['message'] = "Pengguna berhasil diperbarui.";
  }

  $stmt->execute();
}

// Handle Delete
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  mysqli_query($koneksi, "DELETE FROM setting_absen WHERE user_id='$id'");
  mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");  $_SESSION['message'] = "Pengguna berhasil dihapus.";
}

// Toggle Status
if (isset($_GET['toggle'])) {
  $id = $_GET['toggle'];
  $user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM users WHERE id='$id'"));
  $newStatus = $user['status'] == 'aktif' ? 'nonaktif' : 'aktif';
  mysqli_query($koneksi, "UPDATE users SET status='$newStatus' WHERE id='$id'");
  $_SESSION['message'] = "Status pengguna berhasil diubah.";
}

// Get users and jabatan
$users = mysqli_query($koneksi, "SELECT users.*, jabatan.nama_jabatan FROM users LEFT JOIN jabatan ON users.jabatan_id = jabatan.id");
$jabatans = mysqli_query($koneksi, "SELECT * FROM jabatan");
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Manajemen Pengguna</h1>
    <button class="btn btn-primary mt-2" onclick="openModal()">+ Tambah Pengguna</button>
  </section>

  <section class="content">
    <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <table class="table table-bordered table-striped" id="userTable">
          <thead>
            <tr>
              <th>Username</th>
              <th>Nama Lengkap</th>
              <th>Jabatan</th>
              <th>Role</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
              <tr>
                <td><?= $u['username'] ?></td>
                <td><?= $u['nama_lengkap'] ?></td>
                <td><?= $u['nama_jabatan'] ?: 'Tidak Diketahui' ?></td>
                <td><?= ucfirst($u['role']) ?></td>
                <td>
                  <span class="badge badge-<?= $u['status'] == 'aktif' ? 'success' : 'secondary' ?>">
                    <?= ucfirst($u['status']) ?>
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-warning" onclick='openModal(<?= json_encode($u) ?>)'>Edit</button>
                  <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Yakin?')" class="btn btn-sm btn-danger">Hapus</a>
                  <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-<?= $u['status'] == 'aktif' ? 'secondary' : 'success' ?>">
                    <?= $u['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Form Pengguna</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="userId">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" id="userUsername" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" id="userNamaLengkap" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Jabatan</label>
          <select name="jabatan_id" id="userJabatan" class="form-control" required>
            <?php while ($j = mysqli_fetch_assoc($jabatans)): ?>
              <option value="<?= $j['id'] ?>"><?= $j['nama_jabatan'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Foto</label>
          <input type="file" name="foto" class="form-control">
        </div>
        <div class="form-group">
          <label>Password (Kosongkan jika tidak diubah)</label>
          <input type="password" name="password" class="form-control">
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="userRole" class="form-control" required>
            <option value="admin">Admin</option>
            <option value="user">User</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="userStatus" class="form-control">
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="saveUser" class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<script>
$(document).ready(function () {
  $('#userTable').DataTable();
});

function openModal(data = null) {
  $('#userId').val(data ? data.id : '');
  $('#userUsername').val(data ? data.username : '');
  $('#userNamaLengkap').val(data ? data.nama_lengkap : '');
  $('#userJabatan').val(data ? data.jabatan_id : '');
  $('#userRole').val(data ? data.role : 'user');
  $('#userStatus').val(data ? data.status : 'aktif');
  $('input[name="password"]').val('');
  $('#userModal').modal('show');
}
</script>
</body>
</html>

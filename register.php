<?php
include 'db.php';

if (isset($_POST['register'])) {
  $username = mysqli_real_escape_string($koneksi, $_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = 'user'; // default role

  // Cek apakah username sudah digunakan
  $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
  if (mysqli_num_rows($cek) > 0) {
    $error = "Username sudah digunakan.";
  } else {
    $insert = mysqli_query($koneksi, "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");
    if ($insert) {
      $success = "Pendaftaran berhasil. Silakan login.";
    } else {
      $error = "Terjadi kesalahan saat mendaftar.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register | Sistem Absensi</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">
</head>
<body class="hold-transition login-page">

<div class="login-box">
  <div class="login-logo"><b>Register</b> Akun</div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Silakan daftar akun baru</p>

      <?php if (isset($error)): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
          Swal.fire({ icon: 'error', title: 'Gagal Daftar', text: '<?= $error ?>' });
        </script>
      <?php elseif (isset($success)): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
          Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= $success ?>' }).then(() => {
            window.location.href = 'index.php';
          });
        </script>
      <?php endif; ?>

      <form method="post">
        <div class="input-group mb-3">
          <input type="text" name="username" class="form-control" placeholder="Username" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
        </div>
        <div class="row">
          <div class="col-12"><button type="submit" name="register" class="btn btn-success btn-block">Daftar</button></div>
        </div>
      </form>

      <p class="mt-3 mb-1 text-center">Sudah punya akun? <a href="index.php">Login</a></p>
    </div>
  </div>
</div>

<?php include 'tmp/script.php'; ?>
</body>
</html>

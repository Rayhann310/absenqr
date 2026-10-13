<?php
session_start();
include 'db.php';

if (!empty($_GET['token'])) {
  header("Location: scan.php?token=" . urlencode($_GET['token']));
  exit;
}

// Jika user sudah login, langsung teruskan
if (isset($_SESSION['role'])) {
  if ($_SESSION['role'] === 'admin') {
    header("Location: admin/index.php");
    exit;
  } else {
    $pendingToken = $_GET['token'] ?? ($_SESSION['pending_qr_token'] ?? '');
    if (!empty($pendingToken)) {
      header("Location: user/scan_qr.php?token=" . urlencode($pendingToken));
    } else {
      header("Location: user/index.php");
    }
    exit;
  }
}

if (isset($_POST['login'])) {
  $username = mysqli_real_escape_string($koneksi, $_POST['username']);
  $password = $_POST['password'];

  $query = "SELECT * FROM users WHERE username='$username'";
  $result = mysqli_query($koneksi, $query);

  if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
$_SESSION['user_id'] = $user['id']; // Betul, gunakan $user


      if ($user['role'] == 'admin') {
        header("Location: admin/index.php");
      } else {
        $pendingToken = $_GET['token'] ?? ($_SESSION['pending_qr_token'] ?? '');
        if (!empty($pendingToken)) {
          header("Location: user/scan_qr.php?token=" . urlencode($pendingToken));
        } else {
          header("Location: user/index.php");
        }
      }
      exit;
    } else {
      $error = "Password salah.";
    }
  } else {
    $error = "Username tidak ditemukan.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Login | Sistem Absensi</title>
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">
</head>
<body class="hold-transition login-page">

<div class="login-box">
  <div class="login-logo"><b>Sistem</b> Absensi</div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Silakan login</p>

      <?php if (isset($error)): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
          Swal.fire({ icon: 'error', title: 'Login Gagal', text: '<?= $error ?>' });
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
          <div class="col-12"><button type="submit" name="login" class="btn btn-primary btn-block">Login</button></div>
        </div>
      </form>
      <!-- Tombol untuk halaman QR Code -->
<div class="text-center mt-3">
  <a href="absensi.php" class="btn btn-success btn-block">Lihat QR Absensi</a>
</div>

      <p class="mt-3 mb-1 text-center">Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
  </div>
</div>

<?php include 'tmp/script.php'; ?>
</body>
</html>

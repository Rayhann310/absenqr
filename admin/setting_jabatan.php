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

// Tambah Jabatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['aksi'] === 'tambah') {
    $nama = trim($_POST['nama_jabatan']);
    $keterangan = trim($_POST['keterangan']);

    $cek = $koneksi->prepare("SELECT COUNT(*) FROM jabatan WHERE nama_jabatan = ?");
    $cek->bind_param("s", $nama);
    $cek->execute();
    $cek->bind_result($jumlah);
    $cek->fetch();
    $cek->close();

    if ($jumlah > 0) {
        $error = "Jabatan '$nama' sudah ada.";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO jabatan (nama_jabatan, keterangan) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama, $keterangan);
        $stmt->execute();
        $success = "Jabatan berhasil ditambahkan.";
    }
}

// Edit Jabatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['aksi'] === 'edit') {
    $id = $_POST['id'];
    $nama = $_POST['nama_jabatan'];
    $keterangan = $_POST['keterangan'];

    $stmt = $koneksi->prepare("UPDATE jabatan SET nama_jabatan = ?, keterangan = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nama, $keterangan, $id);
    $stmt->execute();
    $success = "Jabatan berhasil diperbarui.";
}

// Hapus Jabatan
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM jabatan WHERE id = $id");
    $success = "Jabatan berhasil dihapus.";
}

// Ambil Data
$jabatanList = $koneksi->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Manajemen Jabatan</h1>
  </section>

  <section class="content">
    <div class="container-fluid">

      <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
            <i class="fas fa-plus"></i> Tambah Jabatan
          </button>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered text-left">
            <thead>
              <tr>
                <th>Nama Jabatan</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jabatanList as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['keterangan'])) ?></td>
                <td>
                  <button class="btn btn-warning btn-sm btn-edit"
                    data-id="<?= $row['id'] ?>"
                    data-nama="<?= htmlspecialchars($row['nama_jabatan']) ?>"
                    data-keterangan="<?= htmlspecialchars($row['keterangan']) ?>"
                    data-toggle="modal" data-target="#modalEdit">Edit</button>
                  <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus jabatan ini?')">Hapus</a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($jabatanList)): ?>
              <tr><td colspan="3">Belum ada data jabatan</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Jabatan</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="tambah">
        <div class="form-group">
          <label>Nama Jabatan</label>
          <input type="text" name="nama_jabatan" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Tambah</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Jabatan</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group">
          <label>Nama Jabatan</label>
          <input type="text" name="nama_jabatan" id="edit-nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" id="edit-keterangan" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<script>
  $('.btn-edit').click(function() {
    $('#edit-id').val($(this).data('id'));
    $('#edit-nama').val($(this).data('nama'));
    $('#edit-keterangan').val($(this).data('keterangan'));
  });
</script>

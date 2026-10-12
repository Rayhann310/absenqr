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

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$shiftList = ['Pagi', 'Siang', 'Malam', 'Libur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['aksi'] === 'tambah') {
        $hari = $_POST['hari'];
        $shift = $_POST['shift'];
        $jam_masuk = $_POST['jam_masuk'];
        $jam_pulang = $_POST['jam_pulang'];

        $cek = $koneksi->prepare("SELECT COUNT(*) FROM jam_kerja WHERE hari = ? AND shift = ?");
        $cek->bind_param("ss", $hari, $shift);
        $cek->execute();
        $cek->bind_result($jumlah);
        $cek->fetch();
        $cek->close();

        if ($jumlah > 0) {
            $error = "Shift $shift pada hari $hari sudah ada.";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO jam_kerja (hari, shift, jam_masuk, jam_pulang) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $hari, $shift, $jam_masuk, $jam_pulang);
            $stmt->execute();
            $success = "Shift berhasil ditambahkan.";
        }
    }

    if ($_POST['aksi'] === 'edit') {
        $id = $_POST['id'];
        $hari = $_POST['hari'];
        $shift = $_POST['shift'];
        $jam_masuk = $_POST['jam_masuk'];
        $jam_pulang = $_POST['jam_pulang'];

        $cek = $koneksi->prepare("SELECT COUNT(*) FROM jam_kerja WHERE hari = ? AND shift = ? AND id != ?");
        $cek->bind_param("ssi", $hari, $shift, $id);
        $cek->execute();
        $cek->bind_result($jumlah);
        $cek->fetch();
        $cek->close();

        if ($jumlah > 0) {
            $error = "Shift sudah ada untuk hari dan jenis shift ini.";
        } else {
            $stmt = $koneksi->prepare("UPDATE jam_kerja SET hari=?, shift=?, jam_masuk=?, jam_pulang=? WHERE id=?");
            $stmt->bind_param("ssssi", $hari, $shift, $jam_masuk, $jam_pulang, $id);
            $stmt->execute();
            $success = "Shift berhasil diperbarui.";
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM jam_kerja WHERE id = $id");
    $success = "Data shift berhasil dihapus.";
}

$jamKerja = $koneksi->query("
  SELECT * FROM jam_kerja 
  ORDER BY 
    FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
    FIELD(shift, 'Pagi', 'Siang', 'Malam', 'Libur')
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Pengaturan Jam Kerja</h1>
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
            <i class="fas fa-plus"></i> Tambah Shift
          </button>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered text-center">
            <thead>
              <tr>
                <th>Hari</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jamKerja as $row): ?>
              <tr>
                <td><?= $row['hari'] ?></td>
                <td><?= $row['shift'] ?></td>
                <td><?= $row['jam_masuk'] ?></td>
                <td><?= $row['jam_pulang'] ?></td>
                <td>
                  <button class="btn btn-warning btn-sm btn-edit"
                    data-id="<?= $row['id'] ?>"
                    data-hari="<?= $row['hari'] ?>"
                    data-shift="<?= $row['shift'] ?>"
                    data-jam_masuk="<?= $row['jam_masuk'] ?>"
                    data-jam_pulang="<?= $row['jam_pulang'] ?>"
                    data-toggle="modal" data-target="#modalEdit">Edit</button>
                  <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Hapus</a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($jamKerja)): ?>
              <tr><td colspan="5">Belum ada data</td></tr>
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
        <h5 class="modal-title">Tambah Shift</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="tambah">
        <div class="form-group">
          <label>Hari</label>
          <select name="hari" class="form-control" required>
            <option value="">Pilih Hari</option>
            <?php foreach ($hariList as $hari): ?>
            <option value="<?= $hari ?>"><?= $hari ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Shift</label>
          <select name="shift" class="form-control" required>
            <option value="">Pilih Shift</option>
            <?php foreach ($shiftList as $shift): ?>
            <option value="<?= $shift ?>"><?= $shift ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Jam Masuk</label>
          <input type="time" name="jam_masuk" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Jam Pulang</label>
          <input type="time" name="jam_pulang" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Tambah</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Global -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Shift</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group">
          <label>Hari</label>
          <select name="hari" id="edit-hari" class="form-control" required></select>
        </div>
        <div class="form-group">
          <label>Shift</label>
          <select name="shift" id="edit-shift" class="form-control" required></select>
        </div>
        <div class="form-group">
          <label>Jam Masuk</label>
          <input type="time" name="jam_masuk" id="edit-jam_masuk" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Jam Pulang</label>
          <input type="time" name="jam_pulang" id="edit-jam_pulang" class="form-control" required>
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
  $(document).ready(function() {
    const hariList = <?= json_encode($hariList) ?>;
    const shiftList = <?= json_encode($shiftList) ?>;

    $('.btn-edit').click(function() {
      $('#edit-id').val($(this).data('id'));
      $('#edit-jam_masuk').val($(this).data('jam_masuk'));
      $('#edit-jam_pulang').val($(this).data('jam_pulang'));

      let hariNow = $(this).data('hari');
      let shiftNow = $(this).data('shift');

      let hariOptions = '';
      hariList.forEach(h => {
        hariOptions += `<option value="${h}" ${h === hariNow ? 'selected' : ''}>${h}</option>`;
      });
      $('#edit-hari').html(hariOptions);

      let shiftOptions = '';
      shiftList.forEach(s => {
        shiftOptions += `<option value="${s}" ${s === shiftNow ? 'selected' : ''}>${s}</option>`;
      });
      $('#edit-shift').html(shiftOptions);
    });
  });
</script>

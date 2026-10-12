<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../index.php");
    exit;
}

include '../db.php';

// === HANDLE AJAX: GENERATE OTOMATIS UNTUK SEMUA USER DENGAN 1 SHIFT PER HARI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_generate'])) {
    $users = $koneksi->query("SELECT id FROM users WHERE role = 'user'")->fetch_all(MYSQLI_ASSOC);
    $jamKerja = $koneksi->query("SELECT * FROM jam_kerja ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), shift")->fetch_all(MYSQLI_ASSOC);

    $koneksi->query("DELETE FROM setting_absen");

    $jadwalPerHari = [];
    foreach ($jamKerja as $jk) {
        $jadwalPerHari[$jk['hari']][] = $jk;
    }

    foreach ($users as $user) {
        $user_id = $user['id'];
        foreach ($jadwalPerHari as $hari => $shifts) {
            $randomShift = $shifts[array_rand($shifts)];
            $jam_kerja_id = $randomShift['id'];

            $stmt = $koneksi->prepare("INSERT INTO setting_absen (user_id, jam_kerja_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $jam_kerja_id);
            $stmt->execute();
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Jadwal otomatis berhasil digenerate dengan 1 shift per hari per user."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modal_jam_kerja_id'])) {
    $user_id = $_POST['user_id'];
    $jam_kerja_ids = $_POST['modal_jam_kerja_id'];

    $koneksi->query("DELETE FROM setting_absen WHERE user_id = $user_id");

    foreach ($jam_kerja_ids as $jam_kerja_id) {
        $stmt = $koneksi->prepare("INSERT INTO setting_absen (user_id, jam_kerja_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $jam_kerja_id);
        $stmt->execute();
    }

    $success = "Pengaturan absensi untuk user berhasil diperbarui.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $user_id = $_POST['user_id'];
    $jam_kerja_ids = $_POST['jam_kerja_id'] ?? [];

    $koneksi->query("DELETE FROM setting_absen WHERE user_id = $user_id");

    foreach ($jam_kerja_ids as $jam_kerja_id) {
        $stmt = $koneksi->prepare("INSERT INTO setting_absen (user_id, jam_kerja_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $jam_kerja_id);
        $stmt->execute();
    }

    $success = "Pengaturan absensi untuk user berhasil disimpan.";
}

$userList = $koneksi->query("SELECT id, nama_lengkap, username FROM users")->fetch_all(MYSQLI_ASSOC);
$jamKerjaList = $koneksi->query("SELECT * FROM jam_kerja ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), shift")->fetch_all(MYSQLI_ASSOC);
$settingList = $koneksi->query("SELECT sau.id, sau.jam_kerja_id, u.nama_lengkap, jk.hari, jk.shift, jk.jam_masuk, jk.jam_pulang
FROM setting_absen sau
JOIN users u ON sau.user_id = u.id
JOIN jam_kerja jk ON sau.jam_kerja_id = jk.id
ORDER BY u.nama_lengkap, FIELD(jk.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jk.shift")->fetch_all(MYSQLI_ASSOC);

include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Pengaturan Absensi Berdasarkan User</h1>
  </section>

  <section class="content">
    <div class="container-fluid">
      <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Atur Shift untuk User</h3>
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label>User</label>
              <div class="input-group">
                <select name="user_id" id="userSelect" class="form-control form-select" required>
                  <option value="">-- Pilih User --</option>
                  <?php foreach ($userList as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= $u['nama_lengkap'] ?> (<?= $u['username'] ?>)</option>
                  <?php endforeach; ?>
                </select>
                <div class="input-group-append">
                  <!-- <button type="button" class="btn btn-info" id="generateBtn">Generate Otomatis Semua User</button> -->
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Pilih Shift</label>
              <div class="row">
                <?php foreach ($jamKerjaList as $jam): ?>
                  <div class="col-md-4">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="jam_kerja_id[]" value="<?= $jam['id'] ?>" id="shift<?= $jam['id'] ?>">
                      <label class="form-check-label" for="shift<?= $jam['id'] ?>">
                        <?= $jam['hari'] ?> - <?= $jam['shift'] ?> (<?= $jam['jam_masuk'] ?> - <?= $jam['jam_pulang'] ?>)
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
          </form>
        </div>
      </div>

<div class="card mt-4">
  <div class="card-header bg-primary text-white">
    <h3 class="card-title mb-0">Jadwal Mingguan Absensi</h3>
  </div>
  <div class="card-body">
    <div class="row">
      <?php
      $hariUrut = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
      $jadwalPerUser = [];

      foreach ($settingList as $s) {
          $jadwalPerUser[$s['nama_lengkap']][$s['hari']] = $s;
      }

      if (empty($jadwalPerUser)) {
          echo '<div class="col-12"><div class="alert alert-warning">Belum ada data jadwal absensi.</div></div>';
      } else {
          foreach ($jadwalPerUser as $user => $jadwal) {
      ?>
        <div class="col-md-6 col-lg-4 mb-4">
          <div class="card shadow-sm border border-info">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= $user ?></h5>
            <button class="btn btn-sm btn-light text-dark" data-toggle="modal" data-target="#editModal" data-user="<?= $user ?>">Edit</button>
            </div>
            <div class="card-body p-2">
              <?php foreach ($hariUrut as $hari): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                  <strong><?= $hari ?></strong>
                  <?php if (isset($jadwal[$hari])): 
                    $shift = $jadwal[$hari]['shift'];
                    $jamMasuk = $jadwal[$hari]['jam_masuk'];
                    $jamPulang = $jadwal[$hari]['jam_pulang'];
                    $badgeClass = [
                      '1' => 'badge-success',
                      '2' => 'badge-warning',
                      '3' => 'badge-danger'
                    ][$shift] ?? 'badge-secondary';
                  ?>
                    <span class="badge <?= $badgeClass ?>">
                      Shift <?= $shift ?>: <?= $jamMasuk ?> - <?= $jamPulang ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php }} ?>
    </div>
  </div>
</div>

  </section>
</div>

<!-- Modal Edit Jadwal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST">
      <input type="hidden" name="user_id" id="modalUserId">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Jadwal User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <?php foreach ($jamKerjaList as $jam): ?>
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="modal_jam_kerja_id[]" value="<?= $jam['id'] ?>" id="modalShift<?= $jam['id'] ?>">
                  <label class="form-check-label" for="modalShift<?= $jam['id'] ?>">
                    <?= $jam['hari'] ?> - Shift <?= $jam['shift'] ?> (<?= $jam['jam_masuk'] ?> - <?= $jam['jam_pulang'] ?>)
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_modal" class="btn btn-success">Simpan Perubahan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<script>
const userMap = <?= json_encode(array_column($userList, 'id', 'nama_lengkap')) ?>;
const userShiftMap = <?= json_encode($settingList) ?>;

$('#editModal').on('show.bs.modal', function (event) {
  const button = $(event.relatedTarget);
  const userName = button.data('user');
  const modal = $(this);
  const userId = userMap[userName];

  modal.find('#modalUserId').val(userId);
  modal.find('input[type="checkbox"]').prop('checked', false);

  const shiftUser = userShiftMap.filter(j => j.nama_lengkap === userName);
  shiftUser.forEach(item => {
    const checkbox = modal.find('#modalShift' + item.jam_kerja_id);
    if (checkbox.length) checkbox.prop('checked', true);
  });
});

document.getElementById('generateBtn').addEventListener('click', function () {
  if (!confirm("Generate otomatis akan membagi shift ke user secara merata (1 shift per hari per user). Lanjutkan?")) return;

  fetch('setting_absen.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ajax_generate=1'
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);
    location.reload();
  })
  .catch(err => {
    alert("Gagal: " + err.message);
  });
});
</script>

<?php
session_start();

// Simpan token sementara jika discan langsung dari kamera smartphone
if (!empty($_GET['token'])) {
    $_SESSION['pending_qr_token'] = trim($_GET['token']);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    if (!empty($_GET['token'])) {
        header("Location: ../scan.php?token=" . urlencode($_GET['token']));
    } else {
        header("Location: ../index.php");
    }
    exit;
}

$directToken = $_GET['token'] ?? ($_SESSION['pending_qr_token'] ?? '');
unset($_SESSION['pending_qr_token']);

include '../tmp/header.php';
include '../tmp/navbar.php';
include '../tmp/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1><i class="fas fa-qrcode"></i> Scan QR untuk Absensi</h1>
  </section>

  <section class="content">
    <div class="card shadow border-0">
      <div class="card-body text-center">

        <!-- Loading spinner -->
        <div id="loading" class="my-4">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted">Meminta izin kamera...</p>
        </div>

        <!-- QR Camera area -->
        <div id="reader" style="width: 100%; max-width: 500px; margin: auto; display: none; border: 2px dashed #ccc; border-radius: 10px; padding: 10px;"></div>

        <!-- Result area -->
        <div id="result" class="mt-4"></div>

        <!-- Tombol Izin & Cuti -->
        <div class="mt-3">
          <button class="btn btn-warning" data-toggle="modal" data-target="#modalIzin">Ajukan Izin</button>
          <button class="btn btn-info" data-toggle="modal" data-target="#modalCuti">Ajukan Cuti</button>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Modal Izin -->
<div class="modal fade" id="modalIzin" tabindex="-1" role="dialog" aria-labelledby="modalIzinLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form class="modal-content" id="formIzin" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="modalIzinLabel">Form Pengajuan Izin</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Tanggal Mulai</label>
          <input type="date" name="tanggal_mulai" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control" required></textarea>
        </div>
        <div class="form-group">
          <label>Bukti (Foto atau PDF)</label>
          <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf" required>
          <small class="form-text text-muted">Unggah bukti berupa foto (.jpg/.png) atau PDF.</small>
        </div>
        <input type="hidden" name="jenis" value="izin">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Kirim</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Cuti -->
<div class="modal fade" id="modalCuti" tabindex="-1" role="dialog" aria-labelledby="modalCutiLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form class="modal-content" id="formCuti" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCutiLabel">Form Pengajuan Cuti</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Tanggal Mulai</label>
          <input type="date" name="tanggal_mulai" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control" required></textarea>
        </div>
        <div class="form-group">
          <label>Bukti (Foto atau PDF)</label>
          <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf" required>
          <small class="form-text text-muted">Unggah bukti berupa foto (.jpg/.png) atau PDF.</small>
        </div>
        <input type="hidden" name="jenis" value="cuti">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Kirim</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<?php include '../tmp/footer.php'; ?>
<?php include '../tmp/script.php'; ?>

<!-- QR Code Scanner -->
<script src="https://unpkg.com/html5-qrcode"></script>

<!-- Tambahan animasi -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<script>
const html5QrCode = new Html5Qrcode("reader");
const directToken = <?= json_encode($directToken) ?>;

function prosesAbsensiToken(token) {
  const loadingEl = document.getElementById("loading");
  if (loadingEl) {
    loadingEl.style.display = "block";
    loadingEl.innerHTML = `
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">Memproses absensi Anda...</p>
    `;
  }
  const readerEl = document.getElementById("reader");
  if (readerEl) readerEl.style.display = "none";

  fetch("proses_absen.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "token=" + encodeURIComponent(token)
  })
  .then(res => res.text())
  .then(response => {
    if (loadingEl) loadingEl.style.display = "none";
    Swal.fire({
      title: 'Status Absensi',
      html: response,
      icon: 'success',
      confirmButtonText: 'OK'
    }).then(() => window.location.href = 'riwayat.php');
  })
  .catch(() => {
    if (loadingEl) loadingEl.style.display = "none";
    Swal.fire('Gagal', 'Terjadi kesalahan saat mengirim data.', 'error');
  });
}

function onScanSuccess(decodedText, decodedResult) {
  html5QrCode.stop().then(() => {
    document.getElementById("result").innerHTML = `
      <div class="alert alert-success animate__animated animate__fadeIn">
        <strong>QR Terdeteksi:</strong> ${decodedText}
      </div>
    `;

    let token = '';
    try {
      const url = new URL(decodedText);
      token = url.searchParams.get("token") || '';
    } catch (e) {
      // Jika decodedText bukan URL lengkap, cari parameter token dengan regex
      const match = decodedText.match(/[?&]token=([^&]+)/);
      if (match) {
        token = match[1];
      } else if (/^[a-f0-9]{64}$/i.test(decodedText.trim())) {
        token = decodedText.trim();
      }
    }

    if (!token) {
      Swal.fire('QR Tidak Valid', 'Token QR tidak ditemukan atau format salah.', 'error');
      return;
    }

    prosesAbsensiToken(token);
  });
}

function initCamera() {
  navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => {
    stream.getTracks().forEach(track => track.stop());

    Html5Qrcode.getCameras().then(devices => {
      document.getElementById("loading").style.display = "none";
      document.getElementById("reader").style.display = "block";

      if (devices && devices.length) {
        const backCam = devices.find(d =>
          d.label.toLowerCase().includes("back") ||
          d.label.toLowerCase().includes("environment")
        ) || devices[0];

        html5QrCode.start(
          { deviceId: { exact: backCam.id } },
          { fps: 10, qrbox: 250 },
          onScanSuccess
        ).catch(err => {
          console.warn("Fallback ke facingMode:", err);
          html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            onScanSuccess
          ).catch(error => {
            Swal.fire('Gagal', 'Tidak bisa mengakses kamera.', 'error');
          });
        });
      } else {
        Swal.fire('Tidak Ada Kamera', 'Perangkat Anda tidak memiliki kamera.', 'error');
      }
    }).catch(err => {
      Swal.fire('Gagal', 'Tidak bisa memuat kamera.', 'error');
    });
  }).catch(err => {
    Swal.fire({
      icon: 'error',
      title: 'Izin Kamera Ditolak',
      text: 'Silakan izinkan akses kamera di browser Anda agar dapat melakukan scan QR.',
      footer: '<a href="#">Lihat cara mengaktifkan kamera</a>'
    });
  });
}

// Jika ada token dari scan link langsung (kamera HP), langsung proses!
if (directToken) {
  prosesAbsensiToken(directToken);
} else {
  initCamera();
}

// Fungsi submit izin/cuti
function handleForm(formId, modalId) {
  const form = document.getElementById(formId);
  form.addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(form);

    fetch("proses_izin.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
    .then(msg => {
      Swal.fire("Terkirim", msg, "success");
      $('#' + modalId).modal('hide');
      form.reset();
    })
    .catch(() => {
      Swal.fire("Gagal", "Terjadi kesalahan saat mengirim data.", "error");
    });
  });
}

handleForm("formIzin", "modalIzin");
handleForm("formCuti", "modalCuti");
</script>

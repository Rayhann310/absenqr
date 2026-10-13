<?php
session_start();
include_once __DIR__ . '/db.php';
require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/config_ip.php';

date_default_timezone_set('Asia/Jakarta');

// Ambil data pengaturan instansi
$settingQuery = mysqli_query($koneksi, "SELECT * FROM setting WHERE id = 1 LIMIT 1");
$setting = $settingQuery ? mysqli_fetch_assoc($settingQuery) : null;
$namaApp = $setting['nama_aplikasi'] ?? 'Sistem Absensi Dinamis';
$namaPerusahaan = $setting['nama_perusahaan'] ?? 'PSSI Kabupaten Bogor';

// Ambil token dari parameter URL
$token = trim($_GET['token'] ?? '');

// Cek validitas token secara real-time
$secret = env('QR_SECRET', 'absen-qr-secret');
$refreshSeconds = (int) env('QR_REFRESH_INTERVAL', 30);
if ($refreshSeconds <= 0) $refreshSeconds = 30;

$tokenValid = false;
if (!empty($token)) {
    $currentInterval = floor(time() / $refreshSeconds);
    for ($i = -1; $i <= 1; $i++) {
        if (hash('sha256', $secret . ($currentInterval + $i)) === $token) {
            $tokenValid = true;
            break;
        }
    }
}

// Cek status pembatasan jaringan Wi-Fi kantor
$isAllowed = isClientAllowed();
$client_ip = getClientIP();

// Cek status aktif QR
$statusFile = __DIR__ . '/qr_status.json';
$qrAktif = true;
if (file_exists($statusFile)) {
    $statusData = json_decode(file_get_contents($statusFile), true);
    if (isset($statusData['status_absen']) && $statusData['status_absen'] !== 'aktif') {
        $qrAktif = false;
    }
}

// Ambil daftar karyawan aktif
$usersQuery = mysqli_query($koneksi, "
    SELECT u.id, u.username, u.nama_lengkap, u.foto, j.nama_jabatan 
    FROM users u 
    LEFT JOIN jabatan j ON u.jabatan_id = j.id 
    WHERE u.status = 'aktif' AND u.role = 'user' AND (u.nama_lengkap IS NOT NULL AND u.nama_lengkap != '')
    ORDER BY u.nama_lengkap ASC
");
$karyawanList = [];
while ($row = mysqli_fetch_assoc($usersQuery)) {
    $karyawanList[] = $row;
}

// Tanggal hari ini dalam bahasa Indonesia
$hariNames = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis',
    'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$bulanNames = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$hariIni = $hariNames[date('l')];
$tglFormat = date('j') . ' ' . $bulanNames[(int)date('n')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Absensi Mandiri | <?= htmlspecialchars($namaPerusahaan) ?></title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 4.6 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome 5 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <style>
    :root {
      --primary-color: #2563eb;
      --primary-dark: #1d4ed8;
      --accent-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #06b6d4 100%);
      --card-bg: #ffffff;
      --text-main: #1e293b;
      --text-muted: #64748b;
    }

    * {
      box-sizing: border-box;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
      background: #f1f5f9;
      background-image: 
        radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.12) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.1) 0px, transparent 50%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 18px 12px;
      color: var(--text-main);
    }

    .absensi-wrapper {
      width: 100%;
      max-width: 480px;
      margin: auto;
    }

    .brand-header {
      text-align: center;
      margin-bottom: 20px;
    }

    .brand-logo-icon {
      width: 58px;
      height: 58px;
      background: var(--accent-gradient);
      color: white;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
      margin-bottom: 12px;
    }

    .brand-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 2px;
      letter-spacing: -0.5px;
    }

    .brand-subtitle {
      font-size: 0.88rem;
      color: var(--text-muted);
      font-weight: 500;
    }

    .card-main {
      background: var(--card-bg);
      border-radius: 22px;
      border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(15, 23, 42, 0.03);
      overflow: hidden;
      position: relative;
    }

    .card-top-bar {
      background: linear-gradient(to right, #eff6ff, #f0fdfa);
      padding: 14px 20px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .date-display {
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155;
    }

    .clock-display {
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--primary-color);
      font-variant-numeric: tabular-nums;
      background: #ffffff;
      padding: 4px 10px;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    .card-body-content {
      padding: 24px 22px;
    }

    .token-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 20px;
      margin-bottom: 18px;
    }

    .token-badge.valid {
      background: #ecfdf5;
      color: #059669;
      border: 1px solid #a7f3d0;
    }

    .token-badge.expired {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .form-label-custom {
      font-size: 0.88rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .select-karyawan {
      height: 48px;
      font-size: 0.95rem;
      border-radius: 12px;
      border: 1.5px solid #cbd5e1;
      padding: 8px 14px;
      color: #0f172a;
      background-color: #f8fafc;
      transition: all 0.2s ease;
    }

    .select-karyawan:focus {
      border-color: var(--primary-color);
      background-color: #ffffff;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }

    .user-preview-card {
      background: #f8fafc;
      border: 1.5px dashed #cbd5e1;
      border-radius: 16px;
      padding: 16px;
      margin-top: 18px;
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .user-avatar-circle {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--accent-gradient);
      color: white;
      font-weight: 700;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }

    .schedule-badge {
      font-size: 0.78rem;
      font-weight: 600;
      background: #ffffff;
      padding: 4px 10px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      color: #475569;
      display: inline-block;
      margin-top: 4px;
    }

    .status-pill {
      font-size: 0.82rem;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 10px;
      text-align: center;
      margin-top: 12px;
    }

    .status-pill.masuk-ready {
      background: #dbeafe;
      color: #1e40af;
      border: 1px solid #bfdbfe;
    }

    .status-pill.pulang-ready {
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
    }

    .status-pill.done {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    .btn-submit-absen {
      height: 52px;
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 700;
      background: var(--accent-gradient);
      border: none;
      color: white;
      box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      margin-top: 20px;
    }

    .btn-submit-absen:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 14px 25px -5px rgba(37, 99, 235, 0.5);
      color: white;
    }

    .btn-submit-absen:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      box-shadow: none;
      background: #94a3b8;
    }

    .remember-toggle {
      font-size: 0.82rem;
      color: #475569;
      cursor: pointer;
      user-select: none;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 14px;
    }

    .footer-links {
      text-align: center;
      margin-top: 24px;
      font-size: 0.84rem;
      color: var(--text-muted);
    }

    .footer-links a {
      color: var(--primary-color);
      font-weight: 600;
      text-decoration: none;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<div class="absensi-wrapper">

  <!-- Header Instansi -->
  <div class="brand-header">
    <div class="brand-logo-icon">
      <i class="fas fa-qrcode"></i>
    </div>
    <div class="brand-title"><?= htmlspecialchars($namaApp) ?></div>
    <div class="brand-subtitle"><?= htmlspecialchars($namaPerusahaan) ?></div>
  </div>

  <!-- Warning jika QR tidak aktif -->
  <?php if (!$qrAktif): ?>
    <div class="card card-main p-4 text-center">
      <div class="text-warning mb-3">
        <i class="fas fa-exclamation-triangle fa-3x"></i>
      </div>
      <h5 class="font-weight-bold text-dark">QR Absensi Nonaktif</h5>
      <p class="text-muted mb-0">Fitur absensi via QR saat ini sedang dinonaktifkan oleh administrator.</p>
    </div>

  <!-- Warning jika IP Dibatasi -->
  <?php elseif (!$isAllowed): ?>
    <div class="card card-main p-4">
      <div class="text-danger mb-2 text-center">
        <i class="fas fa-wifi fa-3x"></i>
      </div>
      <h5 class="font-weight-bold text-dark text-center">Akses Jaringan Dibatasi</h5>
      <p class="text-muted text-center" style="font-size: 0.9rem;">
        Absensi mandiri hanya diizinkan melalui jaringan <strong>Wi-Fi Kantor</strong>.
      </p>
      <div class="alert alert-light border text-center py-2 mb-0">
        <small class="text-muted">IP Anda saat ini: <strong><?= htmlspecialchars($client_ip) ?></strong></small>
      </div>
    </div>

  <!-- Warning jika tidak ada token sama sekali -->
  <?php elseif (empty($token)): ?>
    <div class="card card-main p-4 text-center">
      <div class="text-info mb-3">
        <i class="fas fa-camera fa-3x"></i>
      </div>
      <h5 class="font-weight-bold text-dark">Silakan Scan QR Monitor</h5>
      <p class="text-muted" style="font-size: 0.9rem;">
        Halaman absensi ini harus diakses dengan men-scan QR code dinamis yang tampil di layar monitor/komputer kantor.
      </p>
      <div class="alert alert-secondary py-2 mb-0">
        <small><i class="fas fa-info-circle mr-1"></i> Arahkan kamera smartphone Anda ke layar QR absensi.</small>
      </div>
    </div>

  <!-- Tampilan Normal Absensi Mandiri -->
  <?php else: ?>

    <div class="card card-main">
      <!-- Top Bar: Hari, Tanggal, Jam Realtime -->
      <div class="card-top-bar">
        <div class="date-display">
          <i class="far fa-calendar-alt text-primary mr-1"></i>
          <?= $hariIni ?>, <?= $tglFormat ?>
        </div>
        <div class="clock-display" id="liveClock">
          <?= date('H:i:s') ?> WIB
        </div>
      </div>

      <div class="card-body-content">

        <!-- Status Validitas Token QR -->
        <div class="text-center">
          <?php if ($tokenValid): ?>
            <div class="token-badge valid" id="tokenBadge">
              <i class="fas fa-check-circle"></i> Token QR Valid & Aktif
            </div>
          <?php else: ?>
            <div class="token-badge expired" id="tokenBadge">
              <i class="fas fa-clock"></i> Token QR Kedaluwarsa (Scan Ulang)
            </div>
          <?php endif; ?>
        </div>

        <form id="formAbsensi" onsubmit="submitAbsensi(event)">
          <input type="hidden" name="token" id="inputToken" value="<?= htmlspecialchars($token) ?>">

          <!-- Pilih Karyawan -->
          <div class="form-group mb-2">
            <label class="form-label-custom" for="selectUser">
              <i class="fas fa-user-circle text-primary"></i> Pilih Nama Karyawan:
            </label>
            <select class="form-control select-karyawan" id="selectUser" name="user_id" required onchange="onUserSelected(this.value)">
              <option value="">-- Sentuh untuk Memilih Nama Anda --</option>
              <?php foreach ($karyawanList as $k): ?>
                <option value="<?= $k['id'] ?>" data-nama="<?= htmlspecialchars($k['nama_lengkap']) ?>" data-jabatan="<?= htmlspecialchars($k['nama_jabatan'] ?? '-') ?>" data-foto="<?= htmlspecialchars($k['foto'] ?? '') ?>">
                  <?= htmlspecialchars($k['nama_lengkap']) ?> (<?= htmlspecialchars($k['nama_jabatan'] ?? 'Staff') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Preview Profil & Status Absen -->
          <div class="user-preview-card" id="userPreviewCard">
            <div class="d-flex align-items-center">
              <div class="user-avatar-circle mr-3" id="previewAvatar">
                <i class="fas fa-user"></i>
              </div>
              <div style="flex: 1; min-width: 0;">
                <div class="font-weight-bold text-truncate text-dark" id="previewNama" style="font-size: 1.05rem;">-</div>
                <div class="text-muted text-truncate" id="previewJabatan" style="font-size: 0.85rem;">-</div>
                <div class="schedule-badge" id="previewJadwal">
                  <i class="far fa-clock mr-1"></i> Memuat jadwal...
                </div>
              </div>
            </div>

            <!-- Pill Info Status -->
            <div class="status-pill masuk-ready" id="previewStatusPill">
              Memeriksa kehadiran hari ini...
            </div>
          </div>

          <!-- Opsi Ingat Saya di Perangkat Ini -->
          <label class="remember-toggle">
            <input type="checkbox" id="checkRemember" checked style="width: 17px; height: 17px; accent-color: var(--primary-color);">
            <span>Ingat nama saya di HP ini agar berikutnya otomatis</span>
          </label>

          <!-- Tombol Kirim Absen -->
          <button type="submit" class="btn btn-submit-absen" id="btnSubmit" <?= !$tokenValid ? 'disabled' : '' ?>>
            <span id="btnIcon"><i class="fas fa-fingerprint fa-lg"></i></span>
            <span id="btnText">KIRIM ABSENSI SEKARANG</span>
          </button>
        </form>

      </div>
    </div>

  <?php endif; ?>

  <!-- Footer Link Login Admin / Petugas -->
  <div class="footer-links">
    <div>Login sebagai Petugas atau Administrator?</div>
    <a href="index.php"><i class="fas fa-sign-in-alt mr-1"></i> Masuk ke Dashboard Admin</a>
  </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Live Digital Clock
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(now.getMinutes()).padStart(2, '0');
  const s = String(now.getSeconds()).padStart(2, '0');
  const clockEl = document.getElementById('liveClock');
  if (clockEl) {
    clockEl.textContent = `${h}:${m}:${s} WIB`;
  }
}
setInterval(updateClock, 1000);

// Auto restore pilihan user dari localStorage jika pernah disimpan
document.addEventListener('DOMContentLoaded', function() {
  const savedUserId = localStorage.getItem('absen_saved_user_id');
  const selectEl = document.getElementById('selectUser');
  if (savedUserId && selectEl) {
    const option = selectEl.querySelector(`option[value="${savedUserId}"]`);
    if (option) {
      selectEl.value = savedUserId;
      onUserSelected(savedUserId);
    }
  }
});

// Event saat user dipilih dari dropdown
function onUserSelected(userId) {
  const previewCard = document.getElementById('userPreviewCard');
  const btnSubmit = document.getElementById('btnSubmit');
  const selectEl = document.getElementById('selectUser');
  const selectedOption = selectEl.options[selectEl.selectedIndex];

  if (!userId) {
    if (previewCard) previewCard.style.display = 'none';
    return;
  }

  // Tampilkan data awal dari atribut option
  const nama = selectedOption.getAttribute('data-nama') || '-';
  const jabatan = selectedOption.getAttribute('data-jabatan') || '-';
  
  document.getElementById('previewNama').textContent = nama;
  document.getElementById('previewJabatan').textContent = jabatan;
  document.getElementById('previewAvatar').textContent = nama.charAt(0).toUpperCase();
  document.getElementById('userPreviewCard').style.display = 'block';

  // Cek status ke server via AJAX
  const statusPill = document.getElementById('previewStatusPill');
  statusPill.className = 'status-pill masuk-ready';
  statusPill.textContent = 'Memeriksa status kehadiran...';

  fetch(`proses_absen_publik.php?action=check_status&user_id=${encodeURIComponent(userId)}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        statusPill.className = 'status-pill pulang-ready';
        statusPill.textContent = data.message || 'Tidak dapat memuat status.';
        return;
      }

      // Jadwal jam kerja
      const jadwalEl = document.getElementById('previewJadwal');
      if (data.jam_kerja) {
        jadwalEl.innerHTML = `<i class="far fa-clock mr-1"></i> Jam: ${data.jam_kerja.jam_masuk} - ${data.jam_kerja.jam_pulang} (${data.jam_kerja.shift})`;
      } else {
        jadwalEl.innerHTML = `<i class="fas fa-calendar-times mr-1 text-warning"></i> Jadwal belum diatur`;
      }

      // Update Pill dan tombol
      const btnText = document.getElementById('btnText');
      if (data.next_action === 'masuk') {
        statusPill.className = 'status-pill masuk-ready';
        statusPill.innerHTML = `<i class="fas fa-sign-in-alt mr-1"></i> Belum Absen Masuk &bull; Siap Absen Masuk`;
        btnText.textContent = 'KIRIM ABSEN MASUK';
        btnSubmit.disabled = false;
      } else if (data.next_action === 'pulang') {
        statusPill.className = 'status-pill pulang-ready';
        statusPill.innerHTML = `<i class="fas fa-sign-out-alt mr-1"></i> ${data.status_text}`;
        btnText.textContent = 'KIRIM ABSEN PULANG';
        btnSubmit.disabled = false;
      } else {
        statusPill.className = 'status-pill done';
        statusPill.innerHTML = `<i class="fas fa-check-double mr-1"></i> ${data.status_text}`;
        btnText.textContent = 'ABSENSI LENGKAP';
        btnSubmit.disabled = true;
      }
    })
    .catch(() => {
      statusPill.textContent = 'Siap Absen';
    });
}

// Submit Absensi Mandiri
function submitAbsensi(e) {
  e.preventDefault();

  const userId = document.getElementById('selectUser').value;
  const token = document.getElementById('inputToken').value;
  const remember = document.getElementById('checkRemember').checked;
  const btnSubmit = document.getElementById('btnSubmit');
  const btnText = document.getElementById('btnText');
  const btnIcon = document.getElementById('btnIcon');

  if (!userId) {
    Swal.fire({
      icon: 'warning',
      title: 'Pilih Nama Karyawan',
      text: 'Silakan pilih nama Anda pada daftar yang tersedia terlebih dahulu.'
    });
    return;
  }

  // Simpan / Hapus ke localStorage
  if (remember) {
    localStorage.setItem('absen_saved_user_id', userId);
  } else {
    localStorage.removeItem('absen_saved_user_id');
  }

  // Tampilkan loading di tombol
  btnSubmit.disabled = true;
  btnIcon.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
  btnText.textContent = 'Memproses Absensi...';

  const formData = new FormData();
  formData.append('user_id', userId);
  formData.append('token', token);

  fetch('proses_absen_publik.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    btnSubmit.disabled = false;
    btnIcon.innerHTML = '<i class="fas fa-fingerprint fa-lg"></i>';
    btnText.textContent = 'KIRIM ABSENSI SEKARANG';

    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: data.message,
        html: `<p class="mb-2 font-weight-bold" style="font-size: 1.1rem; color: #1e293b;">${data.nama_karyawan}</p>
               <p class="text-muted mb-0">${data.detail}</p>`,
        confirmButtonColor: '#2563eb',
        confirmButtonText: '<i class="fas fa-check mr-1"></i> Selesai',
        allowOutsideClick: false
      }).then(() => {
        // Refresh preview status
        onUserSelected(userId);
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Absensi Gagal',
        text: data.message + (data.detail ? ` (${data.detail})` : ''),
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Tutup'
      });
    }
  })
  .catch(err => {
    btnSubmit.disabled = false;
    btnIcon.innerHTML = '<i class="fas fa-fingerprint fa-lg"></i>';
    btnText.textContent = 'KIRIM ABSENSI SEKARANG';

    Swal.fire({
      icon: 'error',
      title: 'Koneksi Gagal',
      text: 'Terjadi kendala jaringan saat menghubungi server. Silakan coba kembali.'
    });
  });
}
</script>

</body>
</html>

<?php
require '../vendor/autoload.php'; // pastikan path mpdf benar
include '../db.php';

// Ambil data setting
$setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM setting WHERE id = 1"));
$logo = !empty($setting['logo']) ? "../uploads/" . $setting['logo'] : "";
$nama_aplikasi = $setting['nama_aplikasi'] ?? "Sistem Absensi";

// Ambil data absensi
$data = mysqli_query($koneksi, "
  SELECT absensi.*, users.username 
  FROM absensi 
  JOIN users ON absensi.user_id = users.id 
  ORDER BY absensi.tanggal DESC
");

$html = '
<style>
  body { font-family: sans-serif; }
  table { border-collapse: collapse; width: 100%; font-size: 12px; }
  th, td { border: 1px solid #000; padding: 6px; text-align: center; }
  h2, h4 { text-align: center; margin: 5px 0; }
</style>';

if (!empty($logo) && file_exists($logo)) {
    $img = base64_encode(file_get_contents($logo));
    $src = 'data:image/png;base64,' . $img;
    $html .= "<div style='text-align:center;'><img src='$src' height='60'></div>";
}

$html .= "<h2>$nama_aplikasi</h2><h4>Data Absensi</h4>";
$html .= "<table>
<thead>
  <tr>
    <th>No</th>
    <th>Username</th>
    <th>Tanggal</th>
    <th>Jam Masuk</th>
    <th>Jam Keluar</th>
    <th>Status Masuk</th>
    <th>Status Keluar</th>
  </tr>
</thead>
<tbody>";

$no = 1;
while ($a = mysqli_fetch_assoc($data)) {
    $html .= "<tr>
      <td>$no</td>
      <td>{$a['username']}</td>
      <td>{$a['tanggal']}</td>
      <td>{$a['jam_masuk']}</td>
      <td>{$a['jam_keluar']}</td>
      <td>{$a['status_absen']}</td>
      <td>{$a['status_absen_pulang']}</td>
    </tr>";
    $no++;
}

$html .= '</tbody></table>';

// Cetak PDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output('absensi.pdf', 'I'); // I = inline (browser), D = download
?>

<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../vendor/autoload.php'; // composer autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include '../db.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Absensi');

// Header kolom
$headers = ['No', 'Username', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Status'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Ambil data
$data = mysqli_query($koneksi, "
  SELECT absensi.*, users.username 
  FROM absensi 
  JOIN users ON absensi.user_id = users.id 
  ORDER BY absensi.tanggal DESC
");

$rowNum = 2;
$no = 1;
while ($row = mysqli_fetch_assoc($data)) {
    $sheet->setCellValue('A' . $rowNum, $no++);
    $sheet->setCellValue('B' . $rowNum, $row['username']);
    $sheet->setCellValue('C' . $rowNum, $row['tanggal']);
    $sheet->setCellValue('D' . $rowNum, $row['jam_masuk']);
    $sheet->setCellValue('E' . $rowNum, $row['jam_keluar']);
    $sheet->setCellValue('F' . $rowNum, ucfirst($row['status']));
    $rowNum++;
}

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="absensi_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

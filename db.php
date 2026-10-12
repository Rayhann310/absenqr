<?php
$koneksi = new mysqli("localhost", "root", "", "absenqr");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
?>

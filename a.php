<?php
$users = [
    ['admin', 'Andry', 1, 'foto1.jpg', 'admin', 'admin', 'aktif'],
];

foreach ($users as $u) {
    $username = $u[0];
    $nama = $u[1];
    $jabatan_id = $u[2];
    $foto = $u[3];
    $password = password_hash($u[4], PASSWORD_DEFAULT);
    $role = $u[5];
    $status = $u[6];

    echo "INSERT INTO users (username, nama_lengkap, jabatan_id, foto, password, role, status) VALUES ";
    echo "('$username', '$nama', $jabatan_id, '$foto', '$password', '$role', '$status');\n";
}
?>

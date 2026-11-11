<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_bengkel";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Fungsi bantu bersama (ID otomatis, format rupiah, pesan, dll)
require_once __DIR__ . '/fungsi.php';
?>
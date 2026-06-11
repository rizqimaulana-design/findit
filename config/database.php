<?php
// Pengaturan koneksi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // username default Laragon
define('DB_PASS', '');           // password default Laragon = kosong
define('DB_NAME', 'findit_campus');

// Buat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek apakah koneksi berhasil
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set encoding ke UTF-8 agar karakter Indonesia tampil benar
$conn->set_charset("utf8");

// Token Fonnte WhatsApp
define('FONNTE_TOKEN', 'gVf7Z6o72wP2rxsFmYxF');
?>

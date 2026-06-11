<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/whatsapp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/admin/');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // Ambil data barang dulu untuk notifikasi
    $ambil = $conn->prepare("SELECT * FROM barang WHERE id = ?");
    $ambil->bind_param("i", $id);
    $ambil->execute();
    $barang = $ambil->get_result()->fetch_assoc();

    // Update status
    $stmt = $conn->prepare("UPDATE barang SET status = 'terverifikasi' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Kirim WA ke pelapor kalau ada nomor kontaknya
    if ($barang && !empty($barang['kontak'])) {
        $pesan = pesanStatusUpdate($barang['nama'], 'terverifikasi');
        kirimWA($barang['kontak'], $pesan);
    }
}

$conn->close();
ob_clean();
header('HTTP/1.1 303 See Other');
header('Location: http://localhost/findit/admin/?success=Laporan+berhasil+diverifikasi');
exit;
?>
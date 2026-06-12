<?php
ob_start();
session_start();
require_once '../config/database.php';
require_once '../config/base.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Harus login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$type      = $_POST['type']      ?? 'hilang';
$kategori  = $_POST['kategori']  ?? 'lainnya';
$nama      = trim($_POST['nama']      ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$lokasi    = trim($_POST['lokasi']    ?? '');
$tanggal   = $_POST['tanggal']        ?? date('Y-m-d');
$pelapor   = trim($_POST['pelapor']   ?? 'Anonim');
$kontak    = trim($_POST['kontak']    ?? '-');
$foto_lama = $_POST['foto_lama']      ?? '';

if ($id <= 0) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Validasi input
if (empty($nama) || empty($lokasi)) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/edit.php?id=' . $id . '&error=Nama+barang+dan+lokasi+wajib+diisi');
    exit;
}

// Pastikan barang milik user ini dan belum selesai
$cek = $conn->prepare("SELECT id, foto, status, user_id FROM barang WHERE id = ?");
$cek->bind_param("i", $id);
$cek->execute();
$existing = $cek->get_result()->fetch_assoc();
$cek->close();

if (!$existing || $existing['user_id'] != $user_id || $existing['status'] === 'selesai') {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Proses upload foto baru (kalau ada)
$nama_foto = $foto_lama ?: $existing['foto']; // default pakai foto lama

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file    = $_FILES['foto'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/edit.php?id=' . $id . '&error=Format+foto+harus+JPG+atau+PNG');
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/edit.php?id=' . $id . '&error=Ukuran+foto+maksimal+2MB');
        exit;
    }

    // Upload foto baru
    $nama_foto_baru = time() . '_' . uniqid() . '.' . $ext;
    $tujuan         = __DIR__ . '/../uploads/' . $nama_foto_baru;

    if (move_uploaded_file($file['tmp_name'], $tujuan)) {
        // Hapus foto lama kalau ada
        if (!empty($existing['foto'])) {
            $path_lama = __DIR__ . '/../uploads/' . $existing['foto'];
            if (file_exists($path_lama)) {
                unlink($path_lama);
            }
        }
        $nama_foto = $nama_foto_baru;
    } else {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/edit.php?id=' . $id . '&error=Gagal+upload+foto');
        exit;
    }
}

// Update database
$sql  = "UPDATE barang
         SET kategori=?, nama=?, deskripsi=?, foto=?, lokasi=?, tanggal=?, pelapor=?, kontak=?
         WHERE id=? AND user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssii",
    $kategori, $nama, $deskripsi, $nama_foto,
    $lokasi, $tanggal, $pelapor, $kontak,
    $id, $user_id
);

if ($stmt->execute()) {
    ob_clean();
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/detail.php?id=' . $id . '&updated=1');
} else {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/edit.php?id=' . $id . '&error=Gagal+menyimpan+perubahan');
}

$stmt->close();
$conn->close();
exit;
?>
<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'Method tidak valid']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'error'=>'Belum login']);
    exit;
}

$sender_id   = (int)$_SESSION['user_id'];
$barang_id   = isset($_POST['barang_id'])   ? (int)$_POST['barang_id']   : 0;
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$isi         = trim($_POST['isi'] ?? '');

// Validasi
if ($barang_id <= 0 || $receiver_id <= 0 || empty($isi)) {
    echo json_encode(['ok'=>false,'error'=>'Data tidak lengkap']);
    exit;
}

if ($sender_id === $receiver_id) {
    echo json_encode(['ok'=>false,'error'=>'Tidak bisa kirim pesan ke diri sendiri']);
    exit;
}

if (mb_strlen($isi) > 1000) {
    echo json_encode(['ok'=>false,'error'=>'Pesan terlalu panjang (max 1000 karakter)']);
    exit;
}

// Pastikan barang ada dan salah satu pihak adalah pemilik
$cek = $conn->prepare("SELECT user_id FROM barang WHERE id = ?");
$cek->bind_param("i", $barang_id);
$cek->execute();
$barang = $cek->get_result()->fetch_assoc();
$cek->close();

if (!$barang) {
    echo json_encode(['ok'=>false,'error'=>'Laporan tidak ditemukan']);
    exit;
}

if ($barang['user_id'] != $sender_id && $barang['user_id'] != $receiver_id) {
    echo json_encode(['ok'=>false,'error'=>'Tidak diizinkan']);
    exit;
}

// Pastikan receiver ada
$cek2 = $conn->prepare("SELECT id FROM users WHERE id = ?");
$cek2->bind_param("i", $receiver_id);
$cek2->execute();
$receiver = $cek2->get_result()->fetch_assoc();
$cek2->close();

if (!$receiver) {
    echo json_encode(['ok'=>false,'error'=>'Penerima tidak ditemukan']);
    exit;
}

// Simpan pesan
$stmt = $conn->prepare("INSERT INTO pesan (barang_id, sender_id, receiver_id, isi) VALUES (?,?,?,?)");
$stmt->bind_param("iiis", $barang_id, $sender_id, $receiver_id, $isi);

if ($stmt->execute()) {
    $id_baru   = $conn->insert_id;
    $timestamp = date('Y-m-d H:i:s');
    echo json_encode([
        'ok'    => true,
        'pesan' => [
            'id'          => $id_baru,
            'barang_id'   => $barang_id,
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'isi'         => $isi,
            'dibaca'      => 0,
            'created_at'  => $timestamp,
        ]
    ]);
} else {
    echo json_encode(['ok'=>false,'error'=>'Gagal menyimpan pesan']);
}

$stmt->close();
$conn->close();
?>
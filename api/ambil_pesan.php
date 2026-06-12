<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'pesan'=>[]]);
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$barang_id = isset($_GET['barang_id']) ? (int)$_GET['barang_id'] : 0;
$lawan_id  = isset($_GET['dengan'])    ? (int)$_GET['dengan']    : 0;
$offset    = isset($_GET['offset'])    ? (int)$_GET['offset']    : 0;

if ($barang_id <= 0 || $lawan_id <= 0) {
    echo json_encode(['ok'=>false,'pesan'=>[]]);
    exit;
}

// Tandai pesan baru dari lawan sebagai dibaca
$upd = $conn->prepare("UPDATE pesan SET dibaca=1
                        WHERE barang_id=? AND sender_id=? AND receiver_id=? AND dibaca=0");
$upd->bind_param("iii", $barang_id, $lawan_id, $user_id);
$upd->execute();
$upd->close();

// Ambil pesan mulai dari offset
$stmt = $conn->prepare("
    SELECT p.id, p.barang_id, p.sender_id, p.receiver_id,
           p.isi, p.dibaca, p.created_at, u.nama AS sender_nama
    FROM pesan p
    JOIN users u ON u.id = p.sender_id
    WHERE p.barang_id = ?
      AND (
        (p.sender_id = ? AND p.receiver_id = ?)
        OR
        (p.sender_id = ? AND p.receiver_id = ?)
      )
    ORDER BY p.created_at ASC
    LIMIT 100 OFFSET ?
");
$stmt->bind_param("iiiiii", $barang_id, $user_id, $lawan_id, $lawan_id, $user_id, $offset);
$stmt->execute();
$pesan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode(['ok'=>true,'pesan'=>$pesan_list]);
?>
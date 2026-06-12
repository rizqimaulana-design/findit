<?php
session_start();
require_once 'config/database.php';
require_once 'config/base.php';
require_once 'config/whatsapp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Ambil data barang lengkap + kontak pemilik
$stmt = $conn->prepare("
    SELECT b.*, u.nama as user_nama
    FROM barang b
    LEFT JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item || $item['user_id'] != $user_id || $item['status'] === 'selesai') {
    header('Location: ' . BASE_URL . '/detail.php?id=' . $id);
    exit;
}

// Update status jadi selesai
$upd = $conn->prepare("UPDATE barang SET status = 'selesai' WHERE id = ? AND user_id = ?");
$upd->bind_param("ii", $id, $user_id);
$upd->execute();
$upd->close();

// ── NOTIFIKASI WA ──────────────────────────────────────────

$nama_barang = $item['nama'];
$lokasi      = $item['lokasi'];

// 1. Notif WA ke pemilik (konfirmasi penutupan laporan)
if (!empty($item['kontak'])) {
    $pesan_pemilik = "✅ *FindIt Campus*\n\n"
        . "Laporan kamu telah ditutup!\n\n"
        . "📦 Barang: *{$nama_barang}*\n"
        . "📍 Lokasi: {$lokasi}\n"
        . "📋 Status: *Selesai / Dikembalikan*\n\n"
        . "Senang bisa membantu! Semoga barangmu kembali dengan selamat. 🙌\n\n"
        . "— Tim FindIt Campus";
    kirimWA($item['kontak'], $pesan_pemilik);
}

// 2. Notif WA ke semua penemu yang pernah chat di laporan ini
$sq = $conn->prepare("
    SELECT DISTINCT u.id, u.nama, u.no_wa
    FROM pesan p
    JOIN users u ON u.id = p.sender_id
    WHERE p.barang_id = ?
      AND p.sender_id != ?
");
$sq->bind_param("ii", $id, $user_id);
$sq->execute();
$penemu_list = $sq->get_result()->fetch_all(MYSQLI_ASSOC);
$sq->close();

foreach ($penemu_list as $penemu) {
    if (!empty($penemu['no_wa'])) {
        $pesan_penemu = "🎉 *FindIt Campus*\n\n"
            . "Kabar baik! Barang *{$nama_barang}* sudah berhasil dikembalikan ke pemiliknya.\n\n"
            . "Terima kasih, *{$penemu['nama']}*, sudah membantu menemukan barang ini. "
            . "Kebaikanmu sangat berarti bagi sesama! 💙\n\n"
            . "— Tim FindIt Campus";
        kirimWA($penemu['no_wa'], $pesan_penemu);
    }
}

$conn->close();

header('Location: ' . BASE_URL . '/detail.php?id=' . $id . '&selesai=1');
exit;
?>
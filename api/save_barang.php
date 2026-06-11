<?php
ob_start();
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/lapor.php');
    exit;
}

$type      = $_POST['type']      ?? 'hilang';
$kategori  = $_POST['kategori']  ?? 'lainnya';
$nama      = trim($_POST['nama']      ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$lokasi    = trim($_POST['lokasi']    ?? '');
$tanggal   = $_POST['tanggal']   ?? date('Y-m-d');
$pelapor   = trim($_POST['pelapor']   ?? 'Anonim');
$kontak    = trim($_POST['kontak']    ?? '-');

if (empty($nama) || empty($lokasi)) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/lapor.php?error=Nama+barang+dan+lokasi+wajib+diisi&type=' . $type);
    exit;
}

// Proses upload foto
$nama_foto = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['foto'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/lapor.php?error=Format+foto+harus+JPG+atau+PNG&type=' . $type);
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/lapor.php?error=Ukuran+foto+maksimal+2MB&type=' . $type);
        exit;
    }

    // Buat nama file unik agar tidak bentrok
    $nama_foto = time() . '_' . uniqid() . '.' . $ext;
    $tujuan    = __DIR__ . '/../uploads/' . $nama_foto;

    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/lapor.php?error=Gagal+upload+foto&type=' . $type);
        exit;
    }
}

// Simpan ke database
$sql  = "INSERT INTO barang
         (type, kategori, nama, deskripsi, foto, lokasi, tanggal, pelapor, kontak)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssss",
    $type, $kategori, $nama, $deskripsi,
    $nama_foto, $lokasi, $tanggal, $pelapor, $kontak
);

if ($stmt->execute()) {
    $id_baru = $conn->insert_id;

    // Kirim notifikasi WA ke admin
    require_once '../config/whatsapp.php';
    $no_admin = '0895323162288'; // Ganti dengan nomor WA admin
    $pesan    = pesanLaporanBaru($nama, $type, $lokasi, $pelapor);
    kirimWA($no_admin, $pesan);

    // Kirim notifikasi WA ke pelapor kalau ada nomornya
    if (!empty($kontak) && is_numeric(preg_replace('/[^0-9]/', '', $kontak))) {
        $pesan_pelapor = "🔔 *FindIt Campus*\n\n"
            . "Laporan kamu berhasil dikirim!\n\n"
            . "📦 Barang: {$nama}\n"
            . "📍 Lokasi: {$lokasi}\n"
            . "📋 Status: Menunggu verifikasi\n\n"
            . "Kami akan segera memproses laporanmu.";
        kirimWA($kontak, $pesan_pelapor);
    }

    ob_clean();
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/detail.php?id=' . $id_baru . '&success=1');
} else {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/lapor.php?error=Gagal+menyimpan+data&type=' . $type);
}

$stmt->close();
$conn->close();
exit;
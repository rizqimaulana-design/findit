<?php
require_once 'config/database.php';
require_once 'config/base.php';
require_once 'includes/header.php';

// Ambil ID dari URL: detail.php?id=5
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']);

// Ambil data barang dari database
$stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

// Kalau ID tidak ditemukan, redirect ke beranda
if (!$item) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function getEmoji($kat) {
    $map = ['elektronik'=>'📱','dompet'=>'👝','tas'=>'🎒','kunci'=>'🔑','lainnya'=>'📦'];
    return $map[$kat] ?? '📦';
}

function formatTanggal($tgl) {
    if (!$tgl) return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $parts = explode('-', $tgl);
    return $parts[2] . ' ' . $bulan[(int)$parts[1]] . ' ' . $parts[0];
}
?>

<div class="content">
  <div class="detail-wrap">

    <!-- Pesan sukses setelah lapor -->
    <?php if ($success): ?>
      <div class="alert alert-success">
        ✅ Laporan berhasil dikirim! Terima kasih.
      </div>
    <?php endif; ?>

    <!-- Judul -->
    <div style="font-size:16px;font-weight:500;margin-bottom:1.25rem;">
      <?= $item['type']==='hilang' ? '🔴 Barang Hilang' : '🟢 Barang Ditemukan' ?> — Detail
    </div>

    <!-- Foto atau emoji -->
    <?php if (!empty($item['foto'])): ?>
         <div style="text-align:center;margin-bottom:1rem">
            <img src="http://localhost/findit/uploads/<?= htmlspecialchars($item['foto']) ?>"
         alt="Foto barang"
         style="max-width:100%;max-height:280px;
                border-radius:12px;object-fit:contain;
                border:1px solid #E5E5E5">
        </div>
    <?php else: ?>
        <div class="big-emoji"><?= getEmoji($item['kategori']) ?></div>
    <?php endif; ?>

    <!-- Data detail -->
    <div style="margin-bottom:1rem;">
      <div class="detail-row">
        <span class="detail-key">Nama barang</span>
        <span class="detail-val"><?= htmlspecialchars($item['nama']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Kategori</span>
        <span class="detail-val"><?= htmlspecialchars($item['kategori']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Lokasi</span>
        <span class="detail-val"><?= htmlspecialchars($item['lokasi']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Tanggal</span>
        <span class="detail-val"><?= formatTanggal($item['tanggal']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Deskripsi</span>
        <span class="detail-val"><?= htmlspecialchars($item['deskripsi'] ?: '-') ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Pelapor</span>
        <span class="detail-val"><?= htmlspecialchars($item['pelapor']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Kontak</span>
        <span class="detail-val"><?= htmlspecialchars($item['kontak']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Status</span>
        <span class="detail-val"><?= ucfirst($item['status']) ?></span>
      </div>
    </div>

    <!-- Tombol aksi -->
    <div style="display:flex;gap:8px;margin-top:1.25rem;">
      <a href="<?= BASE_URL ?>/index.php"
         class="btn-secondary"
         style="flex:1;text-align:center;font-size:13px">
        ← Kembali
      </a>
    </div>

  </div>
</div>

<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>
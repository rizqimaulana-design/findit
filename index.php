<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

if ($filter === 'semua') {
    $sql  = "SELECT * FROM barang ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
} elseif ($filter === 'hilang' || $filter === 'temuan') {
    $sql  = "SELECT * FROM barang WHERE type = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filter);
} else {
    $sql  = "SELECT * FROM barang WHERE kategori = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filter);
}

$stmt->execute();
$barang_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stat_hilang  = $conn->query("SELECT COUNT(*) as total FROM barang WHERE type='hilang'")->fetch_assoc()['total'];
$stat_temuan  = $conn->query("SELECT COUNT(*) as total FROM barang WHERE type='temuan'")->fetch_assoc()['total'];
$stat_selesai = $conn->query("SELECT COUNT(*) as total FROM barang WHERE status='selesai'")->fetch_assoc()['total'];

function getEmoji($kat) {
    $map = [
        'elektronik' => '📱',
        'dompet'     => '👝',
        'tas'        => '🎒',
        'kunci'      => '🔑',
        'lainnya'    => '📦',
    ];
    return $map[$kat] ?? '📦';
}

function formatTanggal($tgl) {
    if (!$tgl) return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $parts = explode('-', $tgl);
    return $parts[2] . ' ' . $bulan[(int)$parts[1]] . ' ' . $parts[0];
}
?>

<!-- HERO SECTION -->
<div class="hero">
  <div style="font-size:40px;margin-bottom:12px">🔍</div>
  <h1>Temukan barang hilang di kampus</h1>
  <p>Platform terpadu untuk melaporkan dan menemukan kembali barang hilang.</p>
  <div class="hero-actions">
    <a href="http://localhost/findit/lapor.php?type=hilang" class="btn-primary">
      <i class="ti ti-alert-circle"></i> Saya kehilangan barang
    </a>
    <a href="http://localhost/findit/lapor.php?type=temuan" class="btn-secondary">
      <i class="ti ti-hand-stop"></i> Saya menemukan barang
    </a>
  </div>
</div>

<!-- STATISTIK -->
<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-num"><?= $stat_hilang ?></div>
    <div class="stat-label">Barang hilang</div>
  </div>
  <div class="stat-item">
    <div class="stat-num"><?= $stat_temuan ?></div>
    <div class="stat-label">Barang ditemukan</div>
  </div>
  <div class="stat-item">
    <div class="stat-num"><?= $stat_selesai ?></div>
    <div class="stat-label">Berhasil dikembalikan</div>
  </div>
</div>

<!-- FILTER + DAFTAR BARANG -->
<div class="content">

  <div class="filter-bar">
    <a href="?filter=semua"      class="filter-chip <?= $filter==='semua'      ? 'active':'' ?>">Semua</a>
    <a href="?filter=hilang"     class="filter-chip <?= $filter==='hilang'     ? 'active':'' ?>">Hilang</a>
    <a href="?filter=temuan"     class="filter-chip <?= $filter==='temuan'     ? 'active':'' ?>">Ditemukan</a>
    <a href="?filter=elektronik" class="filter-chip <?= $filter==='elektronik' ? 'active':'' ?>">Elektronik</a>
    <a href="?filter=dompet"     class="filter-chip <?= $filter==='dompet'     ? 'active':'' ?>">Dompet/Kartu</a>
    <a href="?filter=tas"        class="filter-chip <?= $filter==='tas'        ? 'active':'' ?>">Tas/Buku</a>
    <a href="?filter=kunci"      class="filter-chip <?= $filter==='kunci'      ? 'active':'' ?>">Kunci</a>
  </div>

  <div class="items-grid">
    <?php if (empty($barang_list)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <p>📭 Belum ada laporan untuk filter ini.</p>
      </div>
    <?php else: ?>
      <?php foreach ($barang_list as $item): ?>
        <a href="http://localhost/findit/detail.php?id=<?= $item['id'] ?>"
           class="item-card">

          <div class="item-card-header">
            <span class="item-type-badge <?= $item['type']==='hilang' ? 'badge-lost':'badge-found' ?>">
              <?= $item['type']==='hilang' ? 'Hilang':'Ditemukan' ?>
            </span>
            <span style="font-size:11px;color:#AAA">
              <?= formatTanggal($item['tanggal']) ?>
            </span>
          </div>

          <!-- Foto atau emoji -->
          <?php if (!empty($item['foto'])): ?>
            <div style="margin-bottom:8px">
              <img src="http://localhost/findit/uploads/<?= htmlspecialchars($item['foto']) ?>"
                   alt="Foto barang"
                   style="width:100%;height:140px;object-fit:cover;
                          border-radius:8px;border:1px solid #E5E5E5">
            </div>
          <?php else: ?>
            <div class="item-emoji"><?= getEmoji($item['kategori']) ?></div>
          <?php endif; ?>

          <div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
          <div class="item-desc">
            <?= htmlspecialchars(substr($item['deskripsi'] ?? '', 0, 80)) ?>...
          </div>
          <div class="item-meta">
            <span>
              <i class="ti ti-map-pin"></i>
              <?= htmlspecialchars($item['lokasi']) ?>
            </span>
          </div>

        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>
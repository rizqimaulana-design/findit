<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/base.php';
require_once 'includes/header.php';

$sudah_login = isset($_SESSION['user_id']);

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
    <a href="<?= BASE_URL ?>/lapor.php?type=hilang" class="btn-primary">
      <i class="ti ti-alert-circle"></i> Saya kehilangan barang
    </a>
    <a href="<?= BASE_URL ?>/lapor.php?type=temuan" class="btn-secondary">
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

  <?php if (!$sudah_login): ?>
  <!-- Banner info login -->
  <div class="login-notice">
    <i class="ti ti-lock" style="font-size:20px;color:#185FA5"></i>
    <div>
      <div style="font-weight:600;color:#1a1a1a;font-size:14px">Masuk untuk melihat detail barang</div>
      <div style="font-size:12px;color:#666;margin-top:2px">Daftar atau masuk agar bisa melihat & menghubungi pelapor.</div>
    </div>
    <a href="<?= BASE_URL ?>/auth/login.php" class="btn-primary" style="font-size:13px;padding:8px 16px;white-space:nowrap">
      <i class="ti ti-login"></i> Masuk
    </a>
  </div>
  <?php endif; ?>

  <div class="items-grid">
    <?php if (empty($barang_list)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <p>📭 Belum ada laporan untuk filter ini.</p>
      </div>
    <?php else: ?>
      <?php foreach ($barang_list as $item): ?>

        <?php if ($sudah_login): ?>
        <!-- USER LOGIN: kartu bisa diklik normal -->
        <a href="<?= BASE_URL ?>/detail.php?id=<?= $item['id'] ?>" class="item-card">
        <?php else: ?>
        <!-- GUEST: kartu blur, klik buka modal login -->
        <div class="item-card item-card-locked" onclick="document.getElementById('modalLogin').style.display='flex'" title="Masuk untuk melihat detail">
        <?php endif; ?>

          <div class="item-card-header">
            <span class="item-type-badge <?= $item['type']==='hilang' ? 'badge-lost':'badge-found' ?>">
              <?= $item['type']==='hilang' ? 'Hilang':'Ditemukan' ?>
            </span>
            <span style="font-size:11px;color:#AAA">
              <?= formatTanggal($item['tanggal']) ?>
            </span>
          </div>

          <?php if (!empty($item['foto'])): ?>
            <div style="margin-bottom:8px">
              <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($item['foto']) ?>"
                   alt="Foto barang"
                   style="width:100%;height:140px;object-fit:cover;border-radius:8px;border:1px solid #E5E5E5">
            </div>
          <?php else: ?>
            <div class="item-emoji"><?= getEmoji($item['kategori']) ?></div>
          <?php endif; ?>

          <div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
          <div class="item-desc">
            <?= htmlspecialchars(substr($item['deskripsi'] ?? '', 0, 80)) ?>...
          </div>
          <div class="item-meta">
            <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($item['lokasi']) ?></span>
          </div>

          <?php if (!$sudah_login): ?>
          <div class="card-lock-overlay">
            <i class="ti ti-lock" style="font-size:20px;color:#185FA5"></i>
            <span>Masuk untuk melihat</span>
          </div>
          <?php endif; ?>

        <?php echo $sudah_login ? '</a>' : '</div>'; ?>

      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<?php if (!$sudah_login): ?>
<!-- MODAL LOGIN -->
<div id="modalLogin" onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);
            backdrop-filter:blur(4px);z-index:9999;
            align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:2rem 1.75rem;
              width:100%;max-width:380px;text-align:center;
              box-shadow:0 24px 64px rgba(0,0,0,0.18);
              animation:popIn .2s cubic-bezier(.34,1.56,.64,1)">
    <div style="width:56px;height:56px;background:#EFF6FF;border-radius:50%;
                display:flex;align-items:center;justify-content:center;
                margin:0 auto 1rem;font-size:24px;">🔒</div>
    <div style="font-size:18px;font-weight:700;margin-bottom:6px">Perlu masuk dulu</div>
    <div style="font-size:13px;color:#666;margin-bottom:1.5rem;line-height:1.6">
      Masuk ke akun FindIt Campus untuk melihat detail barang dan menghubungi pelapor.
    </div>
    <a href="<?= BASE_URL ?>/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
       class="btn-primary" style="width:100%;justify-content:center;padding:11px;font-size:15px;border-radius:10px;display:flex;">
      <i class="ti ti-login"></i> Masuk Sekarang
    </a>
    <a href="<?= BASE_URL ?>/auth/register.php"
       style="display:block;margin-top:10px;font-size:13px;color:#185FA5;font-weight:500;">
      Belum punya akun? Daftar gratis
    </a>
    <button onclick="document.getElementById('modalLogin').style.display='none'"
            style="margin-top:12px;background:none;border:none;font-size:12px;
                   color:#aaa;cursor:pointer;">Tutup</button>
  </div>
</div>
<style>
@keyframes popIn {
  from { transform: scale(.82); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}
.login-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #EFF6FF;
  border: 1px solid #BFDBFE;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 1rem;
}
.item-card-locked {
  position: relative;
  cursor: pointer;
  filter: blur(1.5px);
  user-select: none;
  transition: filter .15s;
}
.item-card-locked:hover { filter: blur(0); }
.item-card-locked:hover .card-lock-overlay { opacity: 1; }
.card-lock-overlay {
  position: absolute;
  inset: 0;
  border-radius: 12px;
  background: rgba(255,255,255,0.82);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #185FA5;
  opacity: 0;
  transition: opacity .15s;
}
.item-card { position: relative; }
</style>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>
<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

// Kalau belum login, redirect ke login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];

// Ambil semua laporan milik user yang login berdasarkan nama pelapor
$stmt = $conn->prepare("SELECT * FROM barang WHERE pelapor = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user_nama);
$stmt->execute();
$laporan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

<div class="content" style="margin-top:1.5rem">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div class="section-title">📋 Laporan Saya</div>
    <a href="http://localhost/findit/lapor.php" class="btn-primary"
       style="font-size:13px;padding:8px 14px">
      <i class="ti ti-plus"></i> Lapor Baru
    </a>
  </div>

  <?php if (empty($laporan_list)): ?>
    <div class="empty-state">
      <div style="font-size:48px;margin-bottom:12px">📭</div>
      <p style="font-size:15px;margin-bottom:8px">Belum ada laporan</p>
      <p style="font-size:13px;color:#AAA;margin-bottom:1.25rem">
        Kamu belum pernah membuat laporan barang hilang atau temuan.
      </p>
      <a href="http://localhost/findit/lapor.php" class="btn-primary">
        Buat laporan pertama
      </a>
    </div>

  <?php else: ?>

    <div class="items-grid">
      <?php foreach ($laporan_list as $item): ?>
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
            <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($item['lokasi']) ?></span>
          </div>

          <!-- Badge status -->
          <?php
          $badge_style = match($item['status']) {
              'aktif'         => 'background:#FAEEDA;color:#633806',
              'terverifikasi' => 'background:#EAF3DE;color:#3B6D11',
              'selesai'       => 'background:#E6F1FB;color:#185FA5',
              default         => ''
          };
          ?>
          <div style="margin-top:8px">
            <span style="padding:3px 8px;border-radius:12px;
                         font-size:11px;<?= $badge_style ?>">
              <?= ucfirst($item['status']) ?>
            </span>
          </div>

        </a>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>
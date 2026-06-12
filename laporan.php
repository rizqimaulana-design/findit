<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];

// Query pakai user_id (lebih aman)
$stmt = $conn->prepare("SELECT * FROM barang WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$laporan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung statistik
$total    = count($laporan_list);
$aktif    = count(array_filter($laporan_list, fn($x) => $x['status'] === 'aktif'));
$verified = count(array_filter($laporan_list, fn($x) => $x['status'] === 'terverifikasi'));
$selesai  = count(array_filter($laporan_list, fn($x) => $x['status'] === 'selesai'));

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

function badgeStatus($status) {
    return match($status) {
        'aktif'         => ['bg'=>'#FAEEDA', 'color'=>'#633806', 'label'=>'🟡 Aktif',         'desc'=>'Sedang dicari'],
        'terverifikasi' => ['bg'=>'#EAF3DE', 'color'=>'#3B6D11', 'label'=>'🔵 Terverifikasi', 'desc'=>'Sudah diverifikasi admin'],
        'selesai'       => ['bg'=>'#D1FAE5', 'color'=>'#065F46', 'label'=>'✅ Selesai',        'desc'=>'Berhasil dikembalikan'],
        default         => ['bg'=>'#F3F4F6', 'color'=>'#6B7280', 'label'=>ucfirst($status),   'desc'=>''],
    };
}
?>

<div class="content" style="margin-top:1.5rem">

  <!-- Header -->
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

    <!-- Ringkasan statistik -->
    <div style="
      display:grid;
      grid-template-columns:repeat(4,1fr);
      gap:10px;
      margin-bottom:1.5rem;
    ">
      <!-- Total -->
      <div style="
        background:#F8FAFC;border:1.5px solid #E5E7EB;
        border-radius:14px;padding:14px 12px;text-align:center;
      ">
        <div style="font-size:22px;font-weight:700;color:#1a1a1a"><?= $total ?></div>
        <div style="font-size:11px;color:#888;margin-top:2px">Total</div>
      </div>
      <!-- Aktif -->
      <div style="
        background:#FFFBF5;border:1.5px solid #FDE68A;
        border-radius:14px;padding:14px 12px;text-align:center;
      ">
        <div style="font-size:22px;font-weight:700;color:#92400E"><?= $aktif ?></div>
        <div style="font-size:11px;color:#B45309;margin-top:2px">🟡 Aktif</div>
      </div>
      <!-- Terverifikasi -->
      <div style="
        background:#F0FDF4;border:1.5px solid #BBF7D0;
        border-radius:14px;padding:14px 12px;text-align:center;
      ">
        <div style="font-size:22px;font-weight:700;color:#166534"><?= $verified ?></div>
        <div style="font-size:11px;color:#15803D;margin-top:2px">🔵 Verified</div>
      </div>
      <!-- Selesai -->
      <div style="
        background:#ECFDF5;border:1.5px solid #6EE7B7;
        border-radius:14px;padding:14px 12px;text-align:center;
      ">
        <div style="font-size:22px;font-weight:700;color:#065F46"><?= $selesai ?></div>
        <div style="font-size:11px;color:#047857;margin-top:2px">✅ Selesai</div>
      </div>
    </div>

    <!-- Daftar laporan -->
    <div class="items-grid">
      <?php foreach ($laporan_list as $item):
        $badge = badgeStatus($item['status']);
      ?>
        <a href="http://localhost/findit/detail.php?id=<?= $item['id'] ?>"
           class="item-card"
           style="position:relative;text-decoration:none">

          <!-- Ribbon selesai -->
          <?php if ($item['status'] === 'selesai'): ?>
            <div style="
              position:absolute;top:10px;right:10px;
              background:#059669;color:#fff;
              font-size:10px;font-weight:700;
              padding:3px 8px;border-radius:20px;
              letter-spacing:.4px;
            ">SELESAI</div>
          <?php endif; ?>

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
                          border-radius:8px;border:1px solid #E5E5E5;
                          <?= $item['status']==='selesai' ? 'filter:grayscale(30%)' : '' ?>">
            </div>
          <?php else: ?>
            <div class="item-emoji"
                 style="<?= $item['status']==='selesai' ? 'opacity:.6' : '' ?>">
              <?= getEmoji($item['kategori']) ?>
            </div>
          <?php endif; ?>

          <div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
          <div class="item-desc">
            <?= htmlspecialchars(substr($item['deskripsi'] ?? '', 0, 80)) ?>...
          </div>

          <div class="item-meta">
            <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($item['lokasi']) ?></span>
          </div>

          <!-- Badge status dengan deskripsi -->
          <div style="
            margin-top:10px;
            padding:6px 10px;
            border-radius:10px;
            background:<?= $badge['bg'] ?>;
            display:flex;align-items:center;justify-content:space-between;
          ">
            <span style="font-size:12px;font-weight:600;color:<?= $badge['color'] ?>">
              <?= $badge['label'] ?>
            </span>
            <span style="font-size:10px;color:<?= $badge['color'] ?>;opacity:.75">
              <?= $badge['desc'] ?>
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
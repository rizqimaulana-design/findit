<?php
ob_start();
session_start();

// Cek apakah sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

require_once '../config/database.php';

$success = isset($_GET['success']) ? $_GET['success'] : '';
$cari    = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if ($cari !== '') {
    $sql  = "SELECT * FROM barang
             WHERE nama LIKE ? OR lokasi LIKE ? OR pelapor LIKE ?
             ORDER BY created_at DESC";
    $like = '%' . $cari . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $sql  = "SELECT * FROM barang ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$semua = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total    = count($semua);
$pending  = $conn->query("SELECT COUNT(*) as n FROM barang WHERE status='aktif'")->fetch_assoc()['n'];
$verified = $conn->query("SELECT COUNT(*) as n FROM barang WHERE status='terverifikasi'")->fetch_assoc()['n'];
$selesai  = $conn->query("SELECT COUNT(*) as n FROM barang WHERE status='selesai'")->fetch_assoc()['n'];

function getEmoji($kat) {
    $map = ['elektronik'=>'📱','dompet'=>'👝','tas'=>'🎒','kunci'=>'🔑','lainnya'=>'📦'];
    return $map[$kat] ?? '📦';
}

require_once '../includes/header.php';
?>

<div class="content" style="margin-top:1.5rem">

  <div class="section-title" style="margin-bottom:1.25rem">🛠️ Panel Admin</div>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Statistik -->
  <div class="admin-grid">
    <div class="admin-stat">
      <div class="admin-stat-num"><?= $total ?></div>
      <div class="admin-stat-label">Total laporan</div>
    </div>
    <div class="admin-stat">
      <div class="admin-stat-num"><?= $pending ?></div>
      <div class="admin-stat-label">Menunggu verifikasi</div>
    </div>
    <div class="admin-stat">
      <div class="admin-stat-num"><?= $verified ?></div>
      <div class="admin-stat-label">Terverifikasi</div>
    </div>
    <div class="admin-stat">
      <div class="admin-stat-num"><?= $selesai ?></div>
      <div class="admin-stat-label">Selesai</div>
    </div>
  </div>

  <!-- Form pencarian -->
  <form method="GET" action=""
        style="display:flex;gap:8px;margin-bottom:1rem;">
    <input type="text"
           name="cari"
           class="form-input"
           placeholder="🔍 Cari nama barang, lokasi, pelapor..."
           value="<?= htmlspecialchars($cari) ?>"
           style="max-width:400px">
    <button type="submit" class="btn-primary"
            style="font-size:13px;padding:8px 16px">
      Cari
    </button>
    <?php if ($cari): ?>
      <a href="http://localhost/findit/admin/"
         class="btn-secondary"
         style="font-size:13px;padding:8px 16px">
        Reset
      </a>
    <?php endif; ?>
  </form>

  <!-- Tabel -->
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:5%">#</th>
          <th style="width:25%">Barang</th>
          <th style="width:10%">Jenis</th>
          <th style="width:15%">Pelapor</th>
          <th style="width:18%">Lokasi</th>
          <th style="width:12%">Status</th>
          <th style="width:15%">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($semua)): ?>
          <tr>
            <td colspan="7"
                style="text-align:center;color:#AAA;padding:2rem">
              <?= $cari
                ? 'Tidak ada hasil untuk "' . htmlspecialchars($cari) . '"'
                : 'Belum ada laporan.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($semua as $i => $item): ?>
            <tr>
              <td style="color:#AAA"><?= $i + 1 ?></td>

              <td>
                <span style="font-size:15px">
                  <?= getEmoji($item['kategori']) ?>
                </span>
                <a href="http://localhost/findit/detail.php?id=<?= $item['id'] ?>"
                   style="color:#185FA5;text-decoration:none;font-size:13px">
                  <?= htmlspecialchars($item['nama']) ?>
                </a>
              </td>

              <td>
                <span class="item-type-badge <?= $item['type']==='hilang' ? 'badge-lost':'badge-found' ?>">
                  <?= $item['type']==='hilang' ? 'Hilang':'Temuan' ?>
                </span>
              </td>

              <td><?= htmlspecialchars($item['pelapor']) ?></td>

              <td style="color:#888;font-size:12px">
                <?= htmlspecialchars($item['lokasi']) ?>
              </td>

              <td>
                <?php
                $badge_style = match($item['status']) {
                    'aktif'         => 'background:#FAEEDA;color:#633806',
                    'terverifikasi' => 'background:#EAF3DE;color:#3B6D11',
                    'selesai'       => 'background:#E6F1FB;color:#185FA5',
                    default         => ''
                };
                ?>
                <span style="padding:3px 8px;border-radius:12px;
                             font-size:11px;<?= $badge_style ?>">
                  <?= ucfirst($item['status']) ?>
                </span>
              </td>

              <td style="display:flex;gap:4px;flex-wrap:wrap">
                <?php if ($item['status'] === 'aktif'): ?>
                  <form method="POST" action="verify.php"
                        style="display:inline">
                    <input type="hidden" name="id"
                           value="<?= $item['id'] ?>">
                    <button type="submit" class="action-btn success">
                      ✅ Verifikasi
                    </button>
                  </form>
                <?php endif; ?>

                <form method="POST" action="delete.php"
                      style="display:inline"
                      onsubmit="return confirm('Yakin hapus laporan ini?')">
                  <input type="hidden" name="id"
                         value="<?= $item['id'] ?>">
                  <button type="submit" class="action-btn danger">
                    🗑️ Hapus
                  </button>
                </form>
              </td>

            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php
$stmt->close();
$conn->close();
require_once '../includes/footer.php';
?>
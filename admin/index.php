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

                <!-- Tombol hapus → buka modal custom -->
                <button type="button"
                        class="action-btn danger"
                        onclick="bukaModalHapus(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama'])) ?>')">
                  🗑️ Hapus
                </button>
              </td>

            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- ===== MODAL KONFIRMASI HAPUS ===== -->
<div id="modalHapus"
     onclick="if(event.target===this)this.style.display='none'">
  <div class="modal-hapus-box">
    <div class="modal-hapus-icon">🗑️</div>
    <div class="modal-hapus-title">Hapus Laporan?</div>
    <div class="modal-hapus-sub">
      Laporan <strong id="modalNamaBarang"></strong> akan dihapus permanen dan tidak bisa dikembalikan.
    </div>
    <div class="modal-hapus-btns">
      <button type="button"
              class="modal-btn-batal"
              onclick="document.getElementById('modalHapus').style.display='none'">
        Batal
      </button>
      <form method="POST" action="delete.php" style="flex:1">
        <input type="hidden" name="id" id="modalHapusId">
        <button type="submit" class="modal-btn-hapus" style="width:100%">
          🗑️ Ya, Hapus
        </button>
      </form>
    </div>
  </div>
</div>

<style>
#modalHapus {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  align-items: center;
  justify-content: center;
  z-index: 99999;
}
#modalHapus[style*="flex"],
#modalHapus[style*="display:flex"],
#modalHapus[style*="display: flex"] {
  display: flex !important;
}

.modal-hapus-box {
  background: #fff;
  border-radius: 20px;
  padding: 36px 32px 28px;
  text-align: center;
  box-shadow: 0 24px 64px rgba(0,0,0,0.18);
  width: 340px;
  max-width: 90vw;
  animation: hapus-pop .22s cubic-bezier(.34,1.56,.64,1);
}

@keyframes hapus-pop {
  from { transform: scale(.82); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

.modal-hapus-icon {
  width: 64px; height: 64px;
  background: #FEF2F2;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  margin: 0 auto 16px;
}

.modal-hapus-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.modal-hapus-sub {
  font-size: 13px;
  color: #777;
  margin-bottom: 24px;
  line-height: 1.55;
}

.modal-hapus-btns {
  display: flex;
  gap: 10px;
}

.modal-btn-batal {
  flex: 1;
  padding: 10px 0;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  background: #fff;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s, border-color .15s;
}
.modal-btn-batal:hover {
  background: #F9FAFB;
  border-color: #D1D5DB;
}

.modal-btn-hapus {
  flex: 1;
  padding: 10px 0;
  border: none;
  border-radius: 10px;
  background: #DC2626;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s;
}
.modal-btn-hapus:hover { background: #B91C1C; }
</style>

<script>
function bukaModalHapus(id, nama) {
  document.getElementById('modalHapusId').value = id;
  document.getElementById('modalNamaBarang').textContent = nama;
  const modal = document.getElementById('modalHapus');
  modal.style.display = 'flex';
}
</script>

<?php
$stmt->close();
$conn->close();
require_once '../includes/footer.php';
?>
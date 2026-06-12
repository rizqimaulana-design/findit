<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php';
require_once 'config/base.php';
require_once 'includes/header.php';

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']);
$selesai = isset($_GET['selesai']);
$updated = isset($_GET['updated']);

$stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user_id_login = $_SESSION['user_id'] ?? null;

// Cek apakah user yang login adalah pemilik laporan
$is_pemilik    = $user_id_login && $user_id_login == $item['user_id'];
$sudah_selesai = $item['status'] === 'selesai';

// Ambil daftar user yang sudah chat dengan pemilik di laporan ini
$pengirim_list = [];
$unread_total  = 0;
if ($is_pemilik) {
    $uq = $conn->prepare("
        SELECT u.id, u.nama,
               COUNT(CASE WHEN p.receiver_id=? AND p.dibaca=0 THEN 1 END) as unread
        FROM pesan p
        JOIN users u ON u.id = p.sender_id
        WHERE p.barang_id = ?
          AND p.sender_id != ?
        GROUP BY u.id, u.nama
    ");
    $uq->bind_param("iii", $user_id_login, $id, $user_id_login);
    $uq->execute();
    $pengirim_list = $uq->get_result()->fetch_all(MYSQLI_ASSOC);
    $uq->close();
    $unread_total = array_sum(array_column($pengirim_list, 'unread'));
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

    <!-- Pesan sukses setelah edit -->
    <?php if ($updated): ?>
      <div style="
        background:#EFF6FF;border:1.5px solid #BFDBFE;
        border-radius:12px;padding:12px 16px;margin-bottom:1.25rem;
        display:flex;align-items:center;gap:10px;
        font-size:13px;color:#1E40AF;
      ">
        <span style="font-size:18px">✏️</span>
        <span><strong>Laporan berhasil diperbarui!</strong></span>
      </div>
    <?php endif; ?>

    <!-- Banner selesai -->
    <?php if ($selesai || $sudah_selesai): ?>
      <div style="
        background: linear-gradient(135deg,#ECFDF5,#D1FAE5);
        border: 1.5px solid #6EE7B7;
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 12px;
      ">
        <span style="font-size:28px">🎉</span>
        <div>
          <div style="font-weight:700;color:#065F46;font-size:15px">
            Barang sudah berhasil ditemukan!
          </div>
          <div style="font-size:13px;color:#047857;margin-top:2px">
            Laporan ini telah ditandai selesai. Terima kasih sudah menggunakan FindIt Campus.
          </div>
        </div>
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
        <span class="detail-val">
          <?php
          $status_style = match($item['status']) {
              'aktif'         => 'background:#FAEEDA;color:#633806',
              'terverifikasi' => 'background:#EAF3DE;color:#3B6D11',
              'selesai'       => 'background:#D1FAE5;color:#065F46',
              default         => ''
          };
          $status_label = match($item['status']) {
              'aktif'         => '🟡 Aktif',
              'terverifikasi' => '🔵 Terverifikasi',
              'selesai'       => '✅ Selesai',
              default         => ucfirst($item['status'])
          };
          ?>
          <span style="padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;<?= $status_style ?>">
            <?= $status_label ?>
          </span>
        </span>
      </div>
    </div>

    <!-- Tombol aksi -->
    <div style="display:flex;gap:8px;margin-top:1.25rem;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/index.php"
         class="btn-secondary"
         style="flex:1;text-align:center;font-size:13px">
        ← Kembali
      </a>

      <?php if ($is_pemilik && !$sudah_selesai): ?>
        <!-- Tombol Edit -->
        <a href="<?= BASE_URL ?>/edit.php?id=<?= $item['id'] ?>"
           style="
             flex:1;
             padding:10px 16px;
             background:#F3F4F6;
             color:#374151;
             border:1.5px solid #E5E7EB;
             border-radius:10px;
             font-size:13px;
             font-weight:600;
             cursor:pointer;
             display:flex;
             align-items:center;
             justify-content:center;
             gap:6px;
             text-decoration:none;
             transition:background .15s;
           "
           onmouseover="this.style.background='#E5E7EB'"
           onmouseout="this.style.background='#F3F4F6'"
        >
          ✏️ Edit
        </a>
        <!-- Tombol Sudah Ditemukan — hanya muncul untuk pemilik laporan -->
        <button type="button"
                onclick="document.getElementById('modalSelesai').style.display='flex'"
                style="
                  flex:1;
                  padding:10px 16px;
                  background:linear-gradient(135deg,#059669,#10B981);
                  color:#fff;
                  border:none;
                  border-radius:10px;
                  font-size:13px;
                  font-weight:600;
                  cursor:pointer;
                  display:flex;
                  align-items:center;
                  justify-content:center;
                  gap:6px;
                  transition:opacity .15s;
                "
                onmouseover="this.style.opacity='.88'"
                onmouseout="this.style.opacity='1'"
        >
          ✅ Barang Sudah Ditemukan
        </button>
      <?php endif; ?>

      <?php if ($user_id_login && !$is_pemilik && $item['user_id']): ?>
        <!-- Tombol Hubungi Pelapor — untuk user lain yang mau kontak pemilik -->
        <a href="<?= BASE_URL ?>/chat.php?barang_id=<?= $item['id'] ?>&dengan=<?= $item['user_id'] ?>"
           style="
             flex:1;
             padding:10px 16px;
             background:linear-gradient(135deg,#2563EB,#1D4ED8);
             color:#fff;
             border:none;
             border-radius:10px;
             font-size:13px;
             font-weight:600;
             cursor:pointer;
             display:flex;
             align-items:center;
             justify-content:center;
             gap:6px;
             text-decoration:none;
             transition:opacity .15s;
           "
           onmouseover="this.style.opacity='.88'"
           onmouseout="this.style.opacity='1'"
        >
          💬 Hubungi Pelapor
        </a>
      <?php endif; ?>

      <?php if ($is_pemilik && !empty($pengirim_list)): ?>
        <!-- Tombol pesan masuk — tampil per pengirim -->
        <?php foreach ($pengirim_list as $pg): ?>
          <a href="<?= BASE_URL ?>/chat.php?barang_id=<?= $item['id'] ?>&dengan=<?= $pg['id'] ?>"
             style="
               flex:1;
               padding:10px 16px;
               background:#EFF6FF;
               color:#1D4ED8;
               border:1.5px solid #BFDBFE;
               border-radius:10px;
               font-size:13px;
               font-weight:600;
               cursor:pointer;
               display:flex;
               align-items:center;
               justify-content:center;
               gap:6px;
               text-decoration:none;
               transition:background .15s;
             "
             onmouseover="this.style.background='#DBEAFE'"
             onmouseout="this.style.background='#EFF6FF'"
          >
            💬 <?= htmlspecialchars($pg['nama']) ?>
            <?php if ($pg['unread'] > 0): ?>
              <span style="
                background:#2563EB;color:#fff;
                border-radius:20px;font-size:10px;
                padding:1px 7px;font-weight:700;
              "><?= $pg['unread'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php if ($is_pemilik && !$sudah_selesai): ?>
<!-- ===== MODAL KONFIRMASI SELESAI ===== -->
<div id="modalSelesai"
     onclick="if(event.target===this)this.style.display='none'">
  <div class="modal-selesai-box">

    <div class="modal-selesai-icon">🎉</div>

    <div style="font-size:18px;font-weight:700;color:#1a1a1a;margin-bottom:8px">
      Barang sudah ditemukan?
    </div>

    <div style="font-size:13px;color:#777;margin-bottom:8px;line-height:1.55">
      Tandai laporan <strong><?= htmlspecialchars($item['nama']) ?></strong> sebagai <strong>selesai</strong>.
    </div>
    <div style="font-size:12px;color:#aaa;margin-bottom:24px;line-height:1.5">
      Laporan akan ditutup dan tidak muncul lagi di daftar pencarian.
    </div>

    <div style="display:flex;gap:10px">
      <button type="button"
              onclick="document.getElementById('modalSelesai').style.display='none'"
              class="modal-btn-batal-selesai">
        Belum
      </button>

      <form method="POST" action="<?= BASE_URL ?>/selesai.php" style="flex:1">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        <button type="submit" class="modal-btn-selesai" style="width:100%">
          ✅ Ya, Tandai Selesai
        </button>
      </form>
    </div>

  </div>
</div>

<style>
#modalSelesai {
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

.modal-selesai-box {
  background: #fff;
  border-radius: 20px;
  padding: 36px 32px 28px;
  text-align: center;
  box-shadow: 0 24px 64px rgba(0,0,0,0.18);
  width: 340px;
  max-width: 90vw;
  animation: selesai-pop .22s cubic-bezier(.34,1.56,.64,1);
}

@keyframes selesai-pop {
  from { transform: scale(.82); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

.modal-selesai-icon {
  width: 64px; height: 64px;
  background: #ECFDF5;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  margin: 0 auto 16px;
}

.modal-btn-batal-selesai {
  flex: 1;
  padding: 10px 20px;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  background: #fff;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s, border-color .15s;
}
.modal-btn-batal-selesai:hover {
  background: #F9FAFB;
  border-color: #D1D5DB;
}

.modal-btn-selesai {
  padding: 10px 0;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg,#059669,#10B981);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s;
}
.modal-btn-selesai:hover { opacity: .88; }
</style>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>
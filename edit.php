<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/base.php';
require_once 'includes/header.php';

// Harus login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error   = isset($_GET['error']) ? $_GET['error'] : '';
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Ambil data barang
$stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Validasi: barang ada, milik user ini, dan belum selesai
if (!$item) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}
if ($item['user_id'] != $user_id) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}
if ($item['status'] === 'selesai') {
    header('Location: http://localhost/findit/detail.php?id=' . $id);
    exit;
}
?>

<div class="content">
  <div class="form-wrap">

    <div class="form-title">
      ✏️ Edit Laporan
    </div>

    <!-- Info tipe (tidak bisa diubah) -->
    <div style="
      display:inline-flex;align-items:center;gap:6px;
      background:<?= $item['type']==='hilang' ? '#FEF2F2' : '#F0FDF4' ?>;
      color:<?= $item['type']==='hilang' ? '#991B1B' : '#166534' ?>;
      border:1.5px solid <?= $item['type']==='hilang' ? '#FECACA' : '#BBF7D0' ?>;
      border-radius:20px;padding:5px 14px;font-size:13px;font-weight:600;
      margin-bottom:1.25rem;
    ">
      <?= $item['type']==='hilang' ? '🔴 Laporan Barang Hilang' : '🟢 Laporan Barang Temuan' ?>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="http://localhost/findit/api/update_barang.php"
          method="POST"
          enctype="multipart/form-data">

      <input type="hidden" name="id"   value="<?= $item['id'] ?>">
      <input type="hidden" name="type" value="<?= $item['type'] ?>">

      <div class="form-group">
        <label class="form-label">Nama barang *</label>
        <input type="text" name="nama" class="form-input"
               placeholder="Contoh: Dompet hitam kulit"
               value="<?= htmlspecialchars($item['nama']) ?>"
               required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select name="kategori" class="form-select">
            <?php
            $kategori_list = ['elektronik'=>'📱 Elektronik','dompet'=>'👝 Dompet/Kartu','tas'=>'🎒 Tas/Buku','kunci'=>'🔑 Kunci','lainnya'=>'📦 Lainnya'];
            foreach ($kategori_list as $val => $label):
            ?>
              <option value="<?= $val ?>" <?= $item['kategori']===$val ? 'selected':'' ?>>
                <?= $label ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">
            <?= $item['type']==='hilang' ? 'Tanggal hilang' : 'Tanggal ditemukan' ?>
          </label>
          <input type="date" name="tanggal" class="form-input"
                 value="<?= htmlspecialchars($item['tanggal']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <?= $item['type']==='hilang' ? 'Lokasi terakhir *' : 'Lokasi ditemukan *' ?>
        </label>
        <input type="text" name="lokasi" class="form-input"
               placeholder="Contoh: Kantin Gedung B, lantai 2"
               value="<?= htmlspecialchars($item['lokasi']) ?>"
               required>
      </div>

      <div class="form-group">
        <label class="form-label">Deskripsi ciri-ciri</label>
        <textarea name="deskripsi" class="form-input form-textarea"
                  rows="3"
                  placeholder="Warna, merk, kondisi khusus..."><?= htmlspecialchars($item['deskripsi'] ?? '') ?></textarea>
      </div>

      <!-- Upload foto -->
      <div class="form-group">
        <label class="form-label">Foto barang (opsional)</label>

        <?php if (!empty($item['foto'])): ?>
          <!-- Foto saat ini -->
          <div style="
            background:#F8FAFC;border:1.5px solid #E5E7EB;
            border-radius:10px;padding:12px;margin-bottom:10px;
            display:flex;align-items:center;gap:12px;
          ">
            <img src="http://localhost/findit/uploads/<?= htmlspecialchars($item['foto']) ?>"
                 style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #E5E5E5"
                 alt="Foto saat ini">
            <div>
              <div style="font-size:12px;font-weight:600;color:#374151">Foto saat ini</div>
              <div style="font-size:11px;color:#9CA3AF;margin-top:2px">
                Upload foto baru untuk mengganti
              </div>
            </div>
          </div>
          <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($item['foto']) ?>">
        <?php endif; ?>

        <div class="upload-area" id="upload-area"
             onclick="document.getElementById('foto-input').click()">
          <div id="upload-placeholder">
            <div style="font-size:32px;margin-bottom:8px">📷</div>
            <div style="font-size:13px;color:#888">
              <?= !empty($item['foto']) ? 'Klik untuk ganti foto' : 'Klik untuk pilih foto' ?>
            </div>
            <div style="font-size:11px;color:#AAA;margin-top:4px">JPG, PNG, max 2MB</div>
          </div>
          <img id="foto-preview" src="" alt="preview"
               style="display:none;max-width:100%;max-height:200px;
                      border-radius:8px;object-fit:contain">
        </div>
        <input type="file" id="foto-input" name="foto"
               accept="image/jpeg,image/png,image/jpg"
               style="display:none"
               onchange="previewFoto(this)">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama pelapor</label>
          <input type="text" name="pelapor" class="form-input"
                 value="<?= htmlspecialchars($item['pelapor']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Kontak (WA/email)</label>
          <input type="text" name="kontak" class="form-input"
                 value="<?= htmlspecialchars($item['kontak']) ?>">
        </div>
      </div>

      <div class="form-actions">
        <a href="http://localhost/findit/detail.php?id=<?= $item['id'] ?>"
           class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary">
          <i class="ti ti-device-floppy"></i> Simpan Perubahan
        </button>
      </div>

    </form>
  </div>
</div>

<style>
.upload-area {
  border: 2px dashed #DDD;
  border-radius: 10px;
  padding: 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.15s;
  background: #FAFAFA;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.upload-area:hover { border-color: #185FA5; background: #F0F7FF; }
.upload-area.has-file { border-color: #185FA5; background: #F0F7FF; padding: 1rem; }
</style>

<script>
function previewFoto(input) {
  const area  = document.getElementById('upload-area');
  const prev  = document.getElementById('foto-preview');
  const place = document.getElementById('upload-placeholder');

  if (input.files && input.files[0]) {
    const file = input.files[0];
    if (file.size > 2 * 1024 * 1024) {
      alert('Ukuran foto maksimal 2MB!');
      input.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
      prev.src = e.target.result;
      prev.style.display = 'block';
      place.style.display = 'none';
      area.classList.add('has-file');
    };
    reader.readAsDataURL(file);
  }
}
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
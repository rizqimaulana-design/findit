<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

$type  = isset($_GET['type']) && $_GET['type'] === 'temuan' ? 'temuan' : 'hilang';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="content">
  <div class="form-wrap">

    <div class="form-title">
      <?= $type === 'hilang' ? '🔴 Lapor Barang Hilang' : '🟢 Lapor Barang Temuan' ?>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Toggle -->
    <div style="display:flex;gap:8px;margin-bottom:1.25rem;">
      <a href="?type=hilang"
         class="btn-<?= $type==='hilang' ? 'primary' : 'secondary' ?>"
         style="flex:1;text-align:center;font-size:13px;padding:8px">
         🔴 Saya kehilangan
      </a>
      <a href="?type=temuan"
         class="btn-<?= $type==='temuan' ? 'primary' : 'secondary' ?>"
         style="flex:1;text-align:center;font-size:13px;padding:8px">
         🟢 Saya menemukan
      </a>
    </div>

    <!-- enctype multipart wajib untuk upload file -->
    <form action="http://localhost/findit/api/save_barang.php"
          method="POST"
          enctype="multipart/form-data">

      <input type="hidden" name="type" value="<?= $type ?>">

      <div class="form-group">
        <label class="form-label">Nama barang *</label>
        <input type="text" name="nama" class="form-input"
               placeholder="Contoh: Dompet hitam kulit" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select name="kategori" class="form-select">
            <option value="elektronik">📱 Elektronik</option>
            <option value="dompet">👝 Dompet/Kartu</option>
            <option value="tas">🎒 Tas/Buku</option>
            <option value="kunci">🔑 Kunci</option>
            <option value="lainnya">📦 Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">
            <?= $type === 'hilang' ? 'Tanggal hilang' : 'Tanggal ditemukan' ?>
          </label>
          <input type="date" name="tanggal" class="form-input"
                 value="<?= date('Y-m-d') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <?= $type === 'hilang' ? 'Lokasi terakhir *' : 'Lokasi ditemukan *' ?>
        </label>
        <input type="text" name="lokasi" class="form-input"
               placeholder="Contoh: Kantin Gedung B, lantai 2" required>
      </div>

      <div class="form-group">
        <label class="form-label">Deskripsi ciri-ciri</label>
        <textarea name="deskripsi" class="form-input form-textarea"
                  rows="3"
                  placeholder="Warna, merk, kondisi khusus..."></textarea>
      </div>

      <!-- Upload foto -->
      <div class="form-group">
        <label class="form-label">Foto barang (opsional)</label>
        <div class="upload-area" id="upload-area"
             onclick="document.getElementById('foto-input').click()">
          <div id="upload-placeholder">
            <div style="font-size:32px;margin-bottom:8px">📷</div>
            <div style="font-size:13px;color:#888">
              Klik untuk pilih foto
            </div>
            <div style="font-size:11px;color:#AAA;margin-top:4px">
              JPG, PNG, max 2MB
            </div>
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
                 placeholder="Nama lengkap"
                 value="<?= htmlspecialchars($_SESSION['user_nama'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Kontak (WA/email)</label>
          <input type="text" name="kontak" class="form-input"
                 placeholder="08xxxxxxxxxx"
                 value="<?= htmlspecialchars($_SESSION['user_wa'] ?? '') ?>">
        </div>
      </div>

      <div class="form-actions">
        <a href="http://localhost/findit/index.php"
           class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary">
          <i class="ti ti-send"></i>
          <?= $type === 'hilang' ? 'Kirim Laporan Hilang' : 'Kirim Laporan Temuan' ?>
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
.upload-area:hover {
  border-color: #185FA5;
  background: #F0F7FF;
}
.upload-area.has-file {
  border-color: #185FA5;
  background: #F0F7FF;
  padding: 1rem;
}
</style>

<script>
function previewFoto(input) {
  const area  = document.getElementById('upload-area');
  const prev  = document.getElementById('foto-preview');
  const place = document.getElementById('upload-placeholder');

  if (input.files && input.files[0]) {
    const file = input.files[0];

    // Cek ukuran max 2MB
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
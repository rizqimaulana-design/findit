<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$halaman_aktif = basename($_SERVER['PHP_SELF'], '.php');
$di_admin      = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$sudah_login   = isset($_SESSION['user_id']);
$nama_user     = $_SESSION['user_nama'] ?? '';
$role_user     = $_SESSION['user_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FindIt Campus</title>
  <link rel="stylesheet" href="http://localhost/findit/assets/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>

<nav class="navbar">

  <!-- Logo -->
  <a href="http://localhost/findit/index.php" class="logo">
    <div class="logo-icon">
      <i class="ti ti-map-search"></i>
    </div>
    FindIt Campus
  </a>

  <!-- Menu tengah -->
  <div class="nav-links">
    <?php if (!$di_admin): ?>
      <a href="http://localhost/findit/index.php"
         class="nav-btn <?= $halaman_aktif === 'index' ? 'active' : '' ?>">
        Beranda
      </a>
      <?php if ($sudah_login): ?>
      <a href="http://localhost/findit/laporan.php"
         class="nav-btn <?= $halaman_aktif === 'laporan' ? 'active' : '' ?>">
        Laporan Saya
      </a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($role_user === 'admin'): ?>
    <a href="http://localhost/findit/admin/"
       class="nav-btn <?= $di_admin ? 'active' : '' ?>">
      Admin
    </a>
    <?php endif; ?>
  </div>

  <!-- Kanan -->
  <div style="display:flex;align-items:center;gap:8px">
    <?php if ($sudah_login): ?>
      <span style="font-size:13px;color:#555;max-width:120px;
                   overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        👋 <?= htmlspecialchars($nama_user) ?>
      </span>
      <?php if (!$di_admin): ?>
        <a href="http://localhost/findit/lapor.php" class="nav-cta">
          <i class="ti ti-plus"></i> Lapor
        </a>
      <?php endif; ?>
      <!-- Tombol Keluar → buka modal custom (bukan confirm bawaan browser) -->
      <button type="button"
              class="nav-btn" style="color:#A32D2D;background:none;border:none;cursor:pointer;font-size:inherit;font-family:inherit;padding:6px 10px"
              onclick="document.getElementById('modalKeluar').style.display='flex'">
        <i class="ti ti-logout"></i> Keluar
      </button>
    <?php else: ?>
      <a href="http://localhost/findit/auth/login.php" class="nav-btn">
        <i class="ti ti-login"></i> Masuk
      </a>
      <a href="http://localhost/findit/lapor.php" class="nav-cta">
        <i class="ti ti-plus"></i> Lapor
      </a>
    <?php endif; ?>
  </div>

</nav>

<?php if ($sudah_login): ?>
<!-- ===== MODAL KONFIRMASI KELUAR ===== -->
<div id="modalKeluar" style="display:none"
     onclick="if(event.target===this)this.style.display='none'"
     style="
       position:fixed;inset:0;
       background:rgba(0,0,0,0.45);
       backdrop-filter:blur(6px);
       -webkit-backdrop-filter:blur(6px);
       display:flex;
       align-items:center;
       justify-content:center;
       z-index:99999;
     ">
  <div style="
    background:#fff;
    border-radius:20px;
    padding:36px 32px 28px;
    text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,0.18);
    width:320px;
    max-width:90vw;
    animation:findit-pop .22s cubic-bezier(.34,1.56,.64,1);
  ">
    <!-- Ikon -->
    <div style="
      width:64px;height:64px;
      background:#FEF2F2;
      border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      margin:0 auto 16px;
      font-size:28px;
    ">🚪</div>

    <!-- Judul -->
    <div style="font-size:18px;font-weight:700;color:#1a1a1a;margin-bottom:6px">
      Yakin mau keluar?
    </div>

    <!-- Sub-teks -->
    <div style="font-size:13px;color:#777;margin-bottom:24px;line-height:1.5">
      Kamu akan logout dari <strong>FindIt Campus</strong>.<br>
      Sesi kamu akan diakhiri.
    </div>

    <!-- Tombol -->
    <div style="display:flex;gap:10px;justify-content:center">
      <button
        type="button"
        onclick="document.getElementById('modalKeluar').style.display='none'"
        style="
          flex:1;padding:10px 0;
          border:2px solid #E5E7EB;
          border-radius:10px;
          background:#fff;
          color:#374151;
          font-size:14px;font-weight:600;
          cursor:pointer;
          transition:background .15s,border-color .15s;
        "
        onmouseover="this.style.background='#F9FAFB';this.style.borderColor='#D1D5DB'"
        onmouseout="this.style.background='#fff';this.style.borderColor='#E5E7EB'"
      >
        Batal
      </button>
      <a href="http://localhost/findit/auth/logout.php"
         style="
           flex:1;padding:10px 0;
           border:none;
           border-radius:10px;
           background:#DC2626;
           color:#fff;
           font-size:14px;font-weight:600;
           cursor:pointer;
           text-decoration:none;
           display:flex;align-items:center;justify-content:center;gap:6px;
           transition:background .15s;
         "
         onmouseover="this.style.background='#B91C1C'"
         onmouseout="this.style.background='#DC2626'"
      >
        <i class="ti ti-logout" style="font-size:15px"></i> Ya, Keluar
      </a>
    </div>
  </div>
</div>

<style>
@keyframes findit-pop {
  from { transform:scale(.82); opacity:0; }
  to   { transform:scale(1);   opacity:1; }
}
#modalKeluar {
  position: fixed !important;
  inset: 0 !important;
  background: rgba(0,0,0,0.45) !important;
  backdrop-filter: blur(6px) !important;
  -webkit-backdrop-filter: blur(6px) !important;
  display: none;
  align-items: center !important;
  justify-content: center !important;
  z-index: 99999 !important;
}
</style>
<?php endif; ?>
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
      <a href="http://localhost/findit/auth/logout.php"
         class="nav-btn" style="color:#A32D2D"
         onclick="return confirm('Yakin mau keluar?')">
        <i class="ti ti-logout"></i> Keluar
      </a>
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
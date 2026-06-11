<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/base.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']      ?? '');
    $nim      = trim($_POST['nim']       ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = $_POST['password']       ?? '';
    $konfirm  = $_POST['konfirmasi']     ?? '';
    $no_wa    = trim($_POST['no_wa']     ?? '');

    if (empty($nama) || empty($nim) || empty($email) || empty($password)) {
        $error = 'Nama, NIM, email, dan password wajib diisi.';
    } elseif (!is_numeric($nim)) {
        $error = 'NIM harus berupa angka.';
    } elseif ($password !== $konfirm) {
        $error = 'Password dan konfirmasi tidak sama.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek_nim = $conn->prepare("SELECT id FROM users WHERE nim = ?");
        $cek_nim->bind_param("s", $nim);
        $cek_nim->execute();
        $cek_nim->store_result();

        $cek_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek_email->bind_param("s", $email);
        $cek_email->execute();
        $cek_email->store_result();

        if ($cek_nim->num_rows > 0) {
            $error = 'NIM sudah terdaftar.';
        } elseif ($cek_email->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare(
                "INSERT INTO users (nama, nim, email, password, no_wa) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssss", $nama, $nim, $email, $hash, $no_wa);

            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! Silakan login.';
            } else {
                $error = 'Gagal membuat akun, coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar — FindIt Campus</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .auth-wrap {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      background: #F0F4F8; padding: 1rem;
    }
    .auth-card {
      background: white; border-radius: 16px;
      border: 1px solid #E5E5E5; padding: 2rem;
      width: 100%; max-width: 420px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    }
    .auth-logo {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 1.5rem; justify-content: center;
    }
    .auth-logo-icon {
      width: 40px; height: 40px; background: #185FA5;
      border-radius: 10px; display: flex; align-items: center;
      justify-content: center; color: white; font-size: 20px;
    }
    .auth-logo-text { font-size: 20px; font-weight: 500; color: #1A1A1A; }
    .auth-title { font-size: 22px; font-weight: 500; color: #1A1A1A; margin-bottom: 4px; }
    .auth-sub { font-size: 14px; color: #888; margin-bottom: 1.5rem; }
    .input-wrap { position: relative; margin-bottom: 1rem; }
    .input-wrap i {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%); color: #AAA; font-size: 18px;
    }
    .input-wrap input {
      width: 100%; padding: 11px 12px 11px 40px;
      border: 1px solid #DDD; border-radius: 10px;
      font-size: 14px; font-family: inherit;
      color: #1A1A1A; background: #FAFAFA; transition: all 0.15s;
    }
    .input-wrap input:focus { outline: none; border-color: #185FA5; background: white; }
    .btn-register {
      width: 100%; padding: 12px; background: #185FA5;
      color: white; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 500; cursor: pointer;
      transition: background 0.15s; margin-top: 0.25rem;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-register:hover { background: #0C447C; }
    .auth-footer { text-align: center; margin-top: 1.25rem; font-size: 13px; color: #888; }
    .auth-footer a { color: #185FA5; text-decoration: none; }
    .error-box {
      background: #FCEBEB; border: 1px solid #F7C1C1;
      color: #A32D2D; padding: 10px 14px; border-radius: 10px;
      font-size: 13px; margin-bottom: 1rem;
      display: flex; align-items: center; gap: 8px;
    }
    .success-box {
      background: #EAF3DE; border: 1px solid #C0DD97;
      color: #3B6D11; padding: 10px 14px; border-radius: 10px;
      font-size: 13px; margin-bottom: 1rem;
      display: flex; align-items: center; gap: 8px;
    }
    .form-hint {
      font-size: 12px; color: #AAA;
      margin-top: -8px; margin-bottom: 1rem; padding-left: 2px;
    }
  </style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-icon"><i class="ti ti-map-search"></i></div>
      <div class="auth-logo-text">FindIt Campus</div>
    </div>

    <div class="auth-title">Buat akun baru</div>
    <div class="auth-sub">Daftar menggunakan NIM kampus kamu</div>

    <?php if ($error): ?>
      <div class="error-box">
        <i class="ti ti-alert-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-box">
        <i class="ti ti-check"></i>
        <?= htmlspecialchars($success) ?>
        <a href="<?= BASE_URL ?>/auth/login.php"
           style="margin-left:auto;color:#3B6D11;font-weight:500">
          Login →
        </a>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">

      <div class="input-wrap">
        <i class="ti ti-user" aria-hidden="true"></i>
        <input type="text" name="nama"
               placeholder="Nama lengkap"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
               required>
      </div>

      <div class="input-wrap">
        <i class="ti ti-id-badge" aria-hidden="true"></i>
        <input type="text" name="nim"
               placeholder="NIM (contoh: 2021010001)"
               value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>"
               required>
      </div>

      <div class="input-wrap">
        <i class="ti ti-mail" aria-hidden="true"></i>
        <input type="email" name="email"
               placeholder="Email kampus kamu"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required>
      </div>

      <div class="input-wrap">
        <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>
        <input type="text" name="no_wa"
               placeholder="No. WhatsApp (opsional)"
               value="<?= htmlspecialchars($_POST['no_wa'] ?? '') ?>">
      </div>
      <p class="form-hint">Untuk notifikasi kalau barangmu ditemukan</p>

      <div class="input-wrap">
        <i class="ti ti-lock" aria-hidden="true"></i>
        <input type="password" name="password"
               placeholder="Password (min. 6 karakter)"
               required>
      </div>

      <div class="input-wrap">
        <i class="ti ti-lock-check" aria-hidden="true"></i>
        <input type="password" name="konfirmasi"
               placeholder="Ulangi password"
               required>
      </div>

      <button type="submit" class="btn-register">
        Buat akun <i class="ti ti-arrow-right"></i>
      </button>

    </form>
    <?php endif; ?>

    <div class="auth-footer">
      Sudah punya akun?
      <a href="<?= BASE_URL ?>/auth/login.php">Masuk di sini</a>
    </div>

  </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
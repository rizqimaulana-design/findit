<?php
// auth/login.php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../config/base.php';

// Sudah login → langsung ke index
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$error    = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : BASE_URL . '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? BASE_URL . '/index.php');

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';

            header("Location: " . $redirect);
            exit();
        } else {
            $error = 'Email atau password salah.';
        }
    } else {
        $error = 'Email dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk — FindIt Campus</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    body { background: #F5F5F5; }
    .login-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
    }
    .login-box {
      background: white;
      border: 1px solid #E5E5E5;
      border-radius: 16px;
      padding: 2rem 1.75rem;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    }
    .login-brand {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .login-brand .logo-icon {
      width: 48px; height: 48px;
      background: #185FA5;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 22px;
      margin: 0 auto 12px;
    }
    .login-brand h1 { font-size: 20px; font-weight: 600; margin-bottom: 4px; }
    .login-brand p  { font-size: 13px; color: #888; }
    .divider { border: none; border-top: 1px solid #F0F0F0; margin: 1.25rem 0; }
    .input-wrap { position: relative; }
    .input-wrap .ti {
      position: absolute; left: 11px; top: 50%;
      transform: translateY(-50%);
      color: #B0B0B0; font-size: 16px;
      pointer-events: none;
    }
    .input-wrap input { padding-left: 34px !important; }
    .btn-login {
      width: 100%; display: flex; align-items: center; justify-content: center;
      gap: 7px; padding: 11px; font-size: 15px; border-radius: 10px;
      margin-top: 0.25rem;
    }
    .login-footer {
      text-align: center; font-size: 13px; color: #888; margin-top: 1.25rem;
    }
    .login-footer a { color: #185FA5; font-weight: 500; }
  </style>
</head>
<body>

<div class="login-page">
  <div class="login-box">

    <div class="login-brand">
      <div class="logo-icon">
        <i class="ti ti-search"></i>
      </div>
      <h1>FindIt Campus</h1>
      <p>Masuk untuk melihat laporan barang hilang</p>
    </div>

    <hr class="divider">

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;display:flex;align-items:center;gap:8px;">
      <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <div class="input-wrap">
          <i class="ti ti-mail"></i>
          <input id="email" name="email" type="email" class="form-input"
                 placeholder="nama@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <i class="ti ti-lock"></i>
          <input id="password" name="password" type="password" class="form-input"
                 placeholder="••••••••"
                 required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn-primary btn-login">
        <i class="ti ti-login"></i> Masuk
      </button>
    </form>

    <div class="login-footer">
      Belum punya akun?
      <a href="<?= BASE_URL ?>/auth/register.php">Daftar di sini</a>
    </div>

  </div>
</div>

</body>
</html>
<?php ob_end_flush(); ?>
<?php
ob_start();
session_start();

require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/admin/');
    } else {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'NIM/Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE nim = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Hapus session lama
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_nim']  = $user['nim'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_wa']   = $user['no_wa'];

            ob_clean();

            if ($user['role'] === 'admin') {
                header('HTTP/1.1 303 See Other');
                header('Location: http://localhost/findit/admin/');
            } else {
                header('HTTP/1.1 303 See Other');
                header('Location: http://localhost/findit/index.php');
            }
            exit;
        } else {
            $error = 'NIM/Email atau password salah.';
        }
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — FindIt Campus</title>
  <link rel="stylesheet" href="http://localhost/findit/assets/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #F0F4F8;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 1rem;
    }
    .auth-card {
      background: white; border-radius: 16px;
      border: 1px solid #E5E5E5; padding: 2rem;
      width: 100%; max-width: 400px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    }
    .auth-logo {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 1.75rem; justify-content: center;
    }
    .auth-logo-icon {
      width: 40px; height: 40px; background: #185FA5;
      border-radius: 10px; display: flex; align-items: center;
      justify-content: center; color: white; font-size: 20px;
    }
    .auth-logo-text { font-size: 20px; font-weight: 500; color: #1A1A1A; }
    .auth-title { font-size: 22px; font-weight: 500; color: #1A1A1A; margin-bottom: 4px; }
    .auth-sub { font-size: 14px; color: #888; margin-bottom: 1.75rem; }
    .input-wrap { position: relative; margin-bottom: 1rem; }
    .input-wrap i {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%); color: #AAA; font-size: 18px;
      pointer-events: none;
    }
    .input-wrap input {
      width: 100%; padding: 11px 12px 11px 40px;
      border: 1px solid #DDD; border-radius: 10px;
      font-size: 14px; font-family: inherit;
      color: #1A1A1A; background: #FAFAFA; transition: all 0.15s;
    }
    .input-wrap input:focus {
      outline: none; border-color: #185FA5; background: white;
    }
    .btn-login {
      width: 100%; padding: 12px; background: #185FA5;
      color: white; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 500; cursor: pointer;
      transition: background 0.15s; margin-top: 0.5rem;
      display: flex; align-items: center;
      justify-content: center; gap: 8px;
    }
    .btn-login:hover { background: #0C447C; }
    .auth-footer {
      text-align: center; margin-top: 1.25rem;
      font-size: 13px; color: #888;
    }
    .auth-footer a { color: #185FA5; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }
    .error-box {
      background: #FCEBEB; border: 1px solid #F7C1C1;
      color: #A32D2D; padding: 10px 14px; border-radius: 10px;
      font-size: 13px; margin-bottom: 1rem;
      display: flex; align-items: center; gap: 8px;
    }
    .info-box {
      background: #E6F1FB; border: 1px solid #B5D4F4;
      color: #0C447C; padding: 10px 14px; border-radius: 10px;
      font-size: 12px; margin-bottom: 1.25rem;
      display: flex; align-items: flex-start; gap: 8px;
    }
    .divider {
      display: flex; align-items: center; gap: 12px;
      margin: 1.25rem 0; color: #CCC; font-size: 12px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: #EEE;
    }
  </style>
</head>
<body>
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-icon">
        <i class="ti ti-map-search"></i>
      </div>
      <div class="auth-logo-text">FindIt Campus</div>
    </div>

    <div class="auth-title">Selamat datang</div>
    <div class="auth-sub">Masuk menggunakan NIM atau email kampus</div>

    <div class="info-box">
      <i class="ti ti-info-circle" style="margin-top:1px;flex-shrink:0"></i>
      <span>Mahasiswa login dengan <strong>NIM</strong>,
            admin dengan <strong>email</strong>.</span>
    </div>

    <?php if ($error): ?>
      <div class="error-box">
        <i class="ti ti-alert-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
      <div class="input-wrap">
        <i class="ti ti-id-badge" aria-hidden="true"></i>
        <input type="text"
               name="login"
               placeholder="NIM atau Email"
               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
               required autofocus>
      </div>
      <div class="input-wrap">
        <i class="ti ti-lock" aria-hidden="true"></i>
        <input type="password"
               name="password"
               placeholder="Password"
               required>
      </div>
      <button type="submit" class="btn-login">
        Masuk <i class="ti ti-arrow-right"></i>
      </button>
    </form>

    <div class="divider">atau</div>

    <div class="auth-footer">
      Belum punya akun?
      <a href="http://localhost/findit/auth/register.php">
        Daftar dengan NIM
      </a>
    </div>

  </div>
</body>
</html>
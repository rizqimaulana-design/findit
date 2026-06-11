<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/auth/login.php');
        exit;
    }
}

function cekAdmin() {
    cekLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('HTTP/1.1 303 See Other');
        header('Location: http://localhost/findit/index.php');
        exit;
    }
}

function userLogin() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'nama' => $_SESSION['user_nama'] ?? 'Tamu',
        'role' => $_SESSION['user_role'] ?? 'user',
        'wa'   => $_SESSION['user_wa']   ?? null,
    ];
}
?>
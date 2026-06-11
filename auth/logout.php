<?php
ob_start();
session_start();
session_unset();
session_destroy();
ob_clean();
header('HTTP/1.1 303 See Other');
header('Location: http://localhost/findit/auth/login.php');
exit;
?>
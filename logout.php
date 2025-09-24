<?php
// logout.php
require_once 'config/db.php';
require_once 'config/auth.php';

$auth = new Auth($pdo);
$auth->logout();

// Redirect to login page with success message
header('Location: login.php?logged_out=1');
exit();
?>
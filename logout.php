<?php
require_once 'includes/functions.php';

$auth = new Auth($pdo);
$auth->logout();

header('Location: login.php?logged_out=1');
exit();
?>
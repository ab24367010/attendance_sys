<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Login - AttendFT';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/login.css'); ?>">
</head>
<body>

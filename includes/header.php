<?php
if (!isset($pageTitle)) {
    $pageTitle = 'AttendFT';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>" type="image/x-icon">
    <link id="themeStylesheet" rel="stylesheet" href="<?php echo baseUrl('assets/css/light.css'); ?>">
    <?php if (isset($useDashboardCSS) && $useDashboardCSS): ?>
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/dashboard.css'); ?>">
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="logo">
            <h1>ATTENDFT</h1>
        </div>
        <div class="header-actions">
            <button id="themeToggleBtn">Dark Theme</button>
            <?php if (isset($currentUser) && $currentUser): ?>
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</span>
                <?php if ($currentUser['role'] === 'teacher'): ?>
                    <a href="<?php echo baseUrl('teacher/dashboard.php'); ?>" class="header-btn">Teacher Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo baseUrl('student/dashboard.php'); ?>" class="header-btn">Student Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo baseUrl('logout.php'); ?>" class="header-btn logout">Logout</a>
            <?php else: ?>
                <a href="<?php echo baseUrl('login.php'); ?>" class="header-btn">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!isset($hideNavbar) || !$hideNavbar): ?>
    <nav>
        <a href="<?php echo baseUrl('index.php'); ?>#attendance-list">Attendance</a>
        <a href="<?php echo baseUrl('index.php'); ?>#student-information">Students</a>
        <a href="<?php echo baseUrl('index.php'); ?>#exportSection">Export Data</a>
    </nav>
    <?php endif; ?>

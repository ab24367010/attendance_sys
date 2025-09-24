<?php
// dashboard/student.php
require_once '../config/db.php';
require_once '../config/auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

// Ensure only students can access this page
if (!$auth->isStudent()) {
    header('Location: teacher.php');
    exit();
}

$currentUser = $auth->getCurrentUser();
$studentId = $currentUser['student_id'];

// Get student's attendance records
$attendanceStmt = $pdo->prepare("
    SELECT
        attendance.id,
        attendance.entry_time,
        attendance.exit_time,
        attendance.card_id,
        attendance.created_at,
        TIMESTAMPDIFF(MINUTE, attendance.entry_time, COALESCE(attendance.exit_time, NOW())) as duration_minutes
    FROM attendance
    WHERE attendance.student_id = :student_id
    ORDER BY attendance.entry_time DESC
    LIMIT 50
");
$attendanceStmt->bindParam(':student_id', $studentId);
$attendanceStmt->execute();
$attendances = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_visits,
        COUNT(CASE WHEN DATE(entry_time) = CURDATE() THEN 1 END) as today_visits,
        COUNT(CASE WHEN exit_time IS NULL THEN 1 END) as currently_inside,
        COUNT(CASE WHEN DATE(entry_time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as this_week_visits,
        COUNT(CASE WHEN DATE(entry_time) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as this_month_visits,
        AVG(CASE WHEN exit_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, entry_time, exit_time) END) as avg_duration_minutes
    FROM attendance 
    WHERE student_id = :student_id
");
$statsStmt->bindParam(':student_id', $studentId);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get student details
$studentStmt = $pdo->prepare("SELECT * FROM students WHERE student_id = :student_id");
$studentStmt->bindParam(':student_id', $studentId);
$studentStmt->execute();
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

// Format average duration
$avgDuration = '';
if ($stats['avg_duration_minutes']) {
    $hours = floor($stats['avg_duration_minutes'] / 60);
    $minutes = $stats['avg_duration_minutes'] % 60;
    $avgDuration = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
}

// Function to format duration
function formatDuration($minutes) {
    if (!$minutes) return 'N/A';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - AttendFT</title>
    <link rel="icon" href="../public/images/favicon.ico" type="image/x-icon">
    <link id="themeStylesheet" rel="stylesheet" href="../public/css/style-light.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .student-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 5px solid #2ecc71;
        }
        
        .student-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .detail-item label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .detail-item span {
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #2ecc71;
        }
        
        .stat-card p {
            color: #666;
            font-weight: 500;
            margin: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-
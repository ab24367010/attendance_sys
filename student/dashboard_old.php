<?php
require_once '../includes/functions.php';

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
            text-align: center;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        
        .attendance-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        /* Dark theme support */
        body.dark-theme .student-card {
            background: #2d2d2d;
            border-left-color: #2ecc71;
        }
        
        body.dark-theme .detail-item {
            background: #404040;
        }
        
        body.dark-theme .detail-item label {
            color: #ccc;
        }
        
        body.dark-theme .detail-item span {
            color: #e0e0e0;
        }
        
        body.dark-theme .stat-card {
            background: #2d2d2d;
        }
        
        body.dark-theme .attendance-table {
            background: #2d2d2d;
        }
        
        @media (max-width: 768px) {
            .user-info {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .student-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="user-info">
            <div>
                <h1>Student Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?></p>
            </div>
            <div>
                <a href="../index.php" class="btn btn-primary">Public View</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Student Information Card -->
    <div class="student-card">
        <h2>Your Information</h2>
        <div class="student-details">
            <div class="detail-item">
                <label>Student ID</label>
                <span><?php echo htmlspecialchars($student['student_id']); ?></span>
            </div>
            <div class="detail-item">
                <label>Full Name</label>
                <span><?php echo htmlspecialchars($student['full_name']); ?></span>
            </div>
            <div class="detail-item">
                <label>Card ID</label>
                <span><?php echo htmlspecialchars($student['card_id']); ?></span>
            </div>
            <div class="detail-item">
                <label>Registered Since</label>
                <span><?php echo date('M d, Y', strtotime($student['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <section>
        <h2>Your Attendance Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_visits']; ?></h3>
                <p>Total Visits</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['today_visits']; ?></h3>
                <p>Today's Visits</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['this_week_visits']; ?></h3>
                <p>This Week</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['this_month_visits']; ?></h3>
                <p>This Month</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $avgDuration ?: 'N/A'; ?></h3>
                <p>Avg Duration</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['currently_inside']; ?></h3>
                <p>Currently Inside</p>
            </div>
        </div>
    </section>

    <!-- Recent Attendance Records -->
    <section>
        <h2>Your Recent Attendance</h2>
        <div class="attendance-table">
            <div class="table-responsive">
                <?php if (count($attendances) > 0): ?>
                    <table border="1">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendances as $attendance): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($attendance['entry_time'])); ?></td>
                                    <td><?php echo date('H:i:s', strtotime($attendance['entry_time'])); ?></td>
                                    <td>
                                        <?php 
                                        echo $attendance['exit_time'] 
                                            ? date('H:i:s', strtotime($attendance['exit_time'])) 
                                            : 'Still inside'; 
                                        ?>
                                    </td>
                                    <td><?php echo formatDuration($attendance['duration_minutes']); ?></td>
                                    <td>
                                        <?php if ($attendance['exit_time']): ?>
                                            <span class="status-badge status-completed">Completed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-in-progress">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <h3>No attendance records found</h3>
                        <p>Your attendance records will appear here after you start checking in.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Export Personal Data -->
    <section style="text-align: center; margin: 40px 0;">
        <h3>Export Your Data</h3>
        <p>Download your personal attendance records as a CSV file.</p>
        <a href="export_student.php" class="btn btn-success">Download My Records</a>
    </section>

    <script>
        // Auto-refresh every 30 seconds to show real-time updates
        setInterval(function() {
            location.reload();
        }, 30000);

        // Add visual feedback for currently inside status
        document.addEventListener('DOMContentLoaded', function() {
            const currentlyInside = <?php echo $stats['currently_inside']; ?>;
            if (currentlyInside > 0) {
                const statusCards = document.querySelectorAll('.stat-card');
                statusCards[5].style.backgroundColor = '#d4edda';
                statusCards[5].style.borderLeft = '4px solid #27ae60';
            }
        });
    </script>
</body>
</html>
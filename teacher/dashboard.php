<?php
$pageTitle = 'Teacher Dashboard - AttendFT';
$hideNavbar = false;  // Show navbar like index.php

require_once '../includes/functions.php';

$auth = new Auth($pdo);
$auth->requireTeacher();

$currentUser = $auth->getCurrentUser();

// Get statistics
$statsStmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM students) as total_students,
        (SELECT COUNT(*) FROM attendance WHERE DATE(entry_time) = CURDATE()) as today_entries,
        (SELECT COUNT(*) FROM attendance WHERE exit_time IS NULL) as currently_inside,
        (SELECT COUNT(*) FROM attendance WHERE DATE(entry_time) = CURDATE() AND exit_time IS NOT NULL) as completed_today
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent attendance
$attendanceStmt = $pdo->prepare("
    SELECT attendance.*, students.full_name, students.student_id
    FROM attendance
    LEFT JOIN students ON attendance.student_id = students.student_id
    ORDER BY attendance.entry_time DESC
    LIMIT 20
");
$attendanceStmt->execute();
$attendances = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students
$studentStmt = $pdo->prepare("SELECT * FROM students ORDER BY full_name ASC");
$studentStmt->execute();
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

    <!-- Welcome Section -->
    <section style="text-align: center; padding: 30px 20px;">
        <h1>Teacher Dashboard</h1>
        <p style="font-size: 1.1rem; color: #666;">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
    </section>

    <!-- Statistics -->
    <section>
        <h2 style="text-align: center;">Statistics</h2>
        <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total_students']; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['today_entries']; ?></div>
            <div class="stat-label">Today's Entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['currently_inside']; ?></div>
            <div class="stat-label">Currently Inside</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['completed_today']; ?></div>
            <div class="stat-label">Completed Today</div>
        </div>
        </div>
    </section>

    <!-- Recent Attendance -->
    <section>
        <h2>Recent Attendance (Last 20)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Student #</th>
                    <th>Entry Time</th>
                    <th>Exit Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendances as $att): ?>
                <tr>
                    <td><?php echo htmlspecialchars($att['id']); ?></td>
                    <td><?php echo htmlspecialchars($att['full_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($att['student_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($att['entry_time']); ?></td>
                    <td><?php echo htmlspecialchars($att['exit_time'] ?? 'Still inside'); ?></td>
                    <td>
                        <?php if ($att['exit_time']): ?>
                            <span class="status-completed">Completed</span>
                        <?php else: ?>
                            <span class="status-in-progress">In Progress</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Students List -->
    <section>
        <h2>All Students (<?php echo count($students); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Student #</th>
                    <th>Card ID</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['card_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Export Button -->
    <section style="text-align: center; margin: 30px 0;">
        <a href="export.php" class="btn">Download CSV Report</a>
        <a href="../index.php" class="btn" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">View Public Page</a>
    </section>

<?php require_once '../includes/footer.php'; ?>

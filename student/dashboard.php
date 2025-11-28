<?php
$pageTitle = 'Student Dashboard - AttendFT';
$hideNavbar = false;  // Show navbar like index.php

require_once '../includes/functions.php';

$auth = new Auth($pdo);
$auth->requireStudent();

$currentUser = $auth->getCurrentUser();

// Get student's own attendance records
$attendanceStmt = $pdo->prepare("
    SELECT * FROM attendance
    WHERE student_id = :student_id
    ORDER BY entry_time DESC
    LIMIT 50
");
$attendanceStmt->execute([':student_id' => $currentUser['student_id']]);
$attendances = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_entries,
        SUM(CASE WHEN DATE(entry_time) = CURDATE() THEN 1 ELSE 0 END) as today_entries,
        SUM(CASE WHEN exit_time IS NULL THEN 1 ELSE 0 END) as incomplete_entries,
        SUM(CASE WHEN exit_time IS NOT NULL THEN 1 ELSE 0 END) as completed_entries
    FROM attendance
    WHERE student_id = :student_id
");
$statsStmt->execute([':student_id' => $currentUser['student_id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

    <!-- Welcome Section -->
    <section style="text-align: center; padding: 30px 20px;">
        <h1>Student Dashboard</h1>
        <p style="font-size: 1.1rem; color: #666;">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
        <p style="color: #666;">Student ID: <?php echo htmlspecialchars($currentUser['student_id']); ?></p>
    </section>

    <!-- Statistics -->
    <section>
        <h2 style="text-align: center;">My Statistics</h2>
        <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total_entries']; ?></div>
            <div class="stat-label">Total Entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['today_entries']; ?></div>
            <div class="stat-label">Today's Entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['completed_entries']; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['incomplete_entries']; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        </div>
    </section>

    <!-- My Attendance History -->
    <section>
        <h2>My Attendance History</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Entry Time</th>
                    <th>Exit Time</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($attendances) > 0): ?>
                    <?php foreach ($attendances as $att): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($att['id']); ?></td>
                        <td><?php echo htmlspecialchars($att['entry_time']); ?></td>
                        <td><?php echo htmlspecialchars($att['exit_time'] ?? 'Still inside'); ?></td>
                        <td>
                            <?php 
                            if ($att['exit_time']) {
                                $entry = new DateTime($att['entry_time']);
                                $exit = new DateTime($att['exit_time']);
                                $duration = $entry->diff($exit);
                                echo $duration->format('%h hours %i minutes');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($att['exit_time']): ?>
                                <span class="status-completed">Completed</span>
                            <?php else: ?>
                                <span class="status-in-progress">In Progress</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="loading">No attendance records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- Actions -->
    <section style="text-align: center; margin: 30px 0;">
        <a href="export.php" class="btn">Download My Attendance Report</a>
        <a href="../index.php" class="btn" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">View Public Page</a>
    </section>

<?php require_once '../includes/footer.php'; ?>

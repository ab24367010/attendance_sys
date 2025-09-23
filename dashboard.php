<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
// dashboard.php
require_once 'auth/auth_functions.php';
requireLogin();

$user = getCurrentUser();
$isTeacher = hasRole('teacher');
$isStudent = hasRole('student');

// Get search term
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

// Build attendance query based on role
if ($isStudent) {
    // Students can only see their own attendance
    $sql = "
        SELECT
            attendance.id,
            attendance.entry_time,
            attendance.exit_time,
            students.full_name,
            students.student_id,
            attendance.card_id
        FROM attendance
        LEFT JOIN students ON attendance.student_id = students.student_id
        WHERE attendance.student_id = :user_student_id
    ";
    
    if ($searchTerm) {
        $sql .= " AND (attendance.card_id LIKE :searchTerm OR DATE(attendance.entry_time) LIKE :searchTerm)";
    }
    $sql .= " ORDER BY attendance.entry_time DESC";
} else {
    // Teachers can see all attendance
    $sql = "
        SELECT
            attendance.id,
            attendance.entry_time,
            attendance.exit_time,
            students.full_name,
            students.student_id,
            attendance.card_id
        FROM attendance
        LEFT JOIN students ON attendance.student_id = students.student_id
    ";
    
    if ($searchTerm) {
        $sql .= " WHERE attendance.card_id LIKE :searchTerm 
                  OR students.full_name LIKE :searchTerm 
                  OR students.student_id LIKE :searchTerm";
    }
    $sql .= " ORDER BY attendance.entry_time DESC";
}

$stmt = $pdo->prepare($sql);

if ($isStudent) {
    $stmt->bindValue(':user_student_id', $user['student_id']);
}

if ($searchTerm) {
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}

$stmt->execute();
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students (only for teachers)
$students = [];
if ($isTeacher) {
    $studentSql = "SELECT * FROM students ORDER BY full_name ASC";
    if ($searchTerm) {
        $studentSql = "SELECT * FROM students 
                       WHERE full_name LIKE :searchTerm 
                          OR student_id LIKE :searchTerm 
                          OR card_id LIKE :searchTerm
                       ORDER BY full_name ASC";
    }

    $studentStmt = $pdo->prepare($studentSql);
    if ($searchTerm) {
        $studentStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
    $studentStmt->execute();
    $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendFT - Dashboard</title>
    <link rel="icon" href="public/images/favicon.ico" type="image/x-icon">
    <link id="themeStylesheet" rel="stylesheet" href="public/css/style-light.css">
    <link id="navStylesheet" rel="stylesheet" href="public/css/nav-style.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>
    <header>
        <div class="logo">
            <h1>ATTENDFT</h1>
        </div>
        <div class="user-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> (<?php echo ucfirst($user['role']); ?>)</span>
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <a href="#attendance-list">Attendance</a>
        <?php if ($isTeacher): ?>
            <a href="#student-information">Students</a>
            <a href="#edit-attendance">Edit Attendance</a>
            <a href="#exportSection">Export Data</a>
        <?php endif; ?>
        <a href="#profile">Profile</a>
    </nav>

    <button id="themeToggleBtn">Dark Theme</button>
    
    <!-- Search Bar -->
    <div style="text-align: center; margin: 20px 0;">
        <form method="POST" action="" id="searchForm" style="display: inline-block;">
            <input type="text" name="search" id="searchInput" 
                   placeholder="<?php echo $isStudent ? 'Search your records...' : 'Name, Card ID or student number'; ?>" 
                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                   style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
            <button type="submit" style="padding: 10px 20px; margin-left: 10px;">Search</button>
            <?php if ($searchTerm): ?>
                <a href="dashboard.php" style="margin-left: 10px; color: #3498db; text-decoration: none;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <section id="attendance-list">
        <h1><?php echo $isStudent ? 'Your Attendance Records' : 'Attendance List'; ?></h1>
        <?php if (count($attendances) > 0): ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($isTeacher): ?>
                            <th>Student Name</th>
                            <th>Student Number</th>
                        <?php endif; ?>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Card ID</th>
                        <th>Status</th>
                        <?php if ($isTeacher): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <?php foreach ($attendances as $attendance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attendance['id']); ?></td>
                            <?php if ($isTeacher): ?>
                                <td><?php echo htmlspecialchars($attendance['full_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($attendance['student_id'] ?? 'N/A'); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($attendance['entry_time']); ?></td>
                            <td><?php echo htmlspecialchars($attendance['exit_time'] ?? 'Still inside'); ?></td>
                            <td><?php echo htmlspecialchars($attendance['card_id']); ?></td>
                            <td>
                                <?php if ($attendance['exit_time']): ?>
                                    <span class="status-completed">Completed</span>
                                <?php else: ?>
                                    <span class="status-in-progress">In Progress</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isTeacher): ?>
                                <td>
                                    <button onclick="openEditModal(<?php echo $attendance['id']; ?>, '<?php echo $attendance['entry_time']; ?>', '<?php echo $attendance['exit_time'] ?? ''; ?>')" 
                                            class="edit-btn">Edit</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; font-style: italic;">No attendance records found.</p>
        <?php endif; ?>
    </section>

    <?php if ($isTeacher): ?>
        <section id="student-information">
            <h2>Student Information</h2>
            <?php if (count($students) > 0): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Card ID</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
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
            <?php else: ?>
                <p style="text-align: center; font-style: italic;">No students found.</p>
            <?php endif; ?>
        </section>

        <!-- Export Section -->
        <section id="exportSection">
            <div style="text-align: center; margin: 30px 0;">
                <a href="dashboard/export.php" class="btn">Download CSV file</a>
            </div>
        </section>
    <?php endif; ?>
    
    <?php if ($isTeacher): ?>
        <!-- Edit Attendance Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Attendance Record</h2>
                <form id="editAttendanceForm">
                    <input type="hidden" id="editAttendanceId" name="attendanceId">
                    
                    <div class="form-group">
                        <label for="editEntryTime">Entry Time:</label>
                        <input type="datetime-local" id="editEntryTime" name="entryTime" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editExitTime">Exit Time:</label>
                        <input type="datetime-local" id="editExitTime" name="exitTime">
                    </div>
                    
                    <div class="form-group">
                        <label for="editReason">Reason for Edit:</label>
                        <textarea id="editReason" name="reason" rows="3" placeholder="Explain why this record is being modified..." required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeEditModal()">Cancel</button>
                        <button type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script src="public/js/theme.js" defer></script>
    <script src="public/js/dashboard.js" defer></script>
    
    <footer>
        <p>&copy; 2025 Attendft. All rights reserved.</p>
        <p>Contact us at: <a href="mailto:ab24367010@ga.ttc.ac.jp">ab24367010@ga.ttc.ac.jp</a></p>
    </footer>
</body>
</html>
<?php
$pageTitle = 'Home - Attendance System';
$includeRealTime = true;

require_once 'includes/functions.php';

$auth = new Auth($pdo);
$currentUser = $auth->getCurrentUser();
$searchTerm = isset($_POST['search']) ? sanitize($_POST['search']) : '';

// Get attendance records
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

$stmt = $pdo->prepare($sql);

if ($searchTerm) {
    // Хайлтын утгыг аюулгүй оруулах
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}

$stmt->execute();
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Оюутны мэдээллийг авах
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
?>

<?php require_once 'includes/header.php'; ?>

    <!-- Хайлтын талбар -->
    <div style="text-align: center; margin: 20px 0;">
        <form method="POST" action="" id="searchForm">
            <input type="text" name="search" id="searchInput"
                   placeholder="Name, Card ID or student number"
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Search</button>
            <?php if ($searchTerm): ?>
                <a href="index.php" class="btn-link">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <section id="attendance-list">
        <h1>Attendance List</h1>
        <?php if (count($attendances) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Student Number</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Card ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <?php foreach ($attendances as $attendance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attendance['id']); ?></td>
                            <td><?php echo htmlspecialchars($attendance['full_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($attendance['student_id'] ?? 'N/A'); ?></td>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="loading">No attendance records found.</p>
        <?php endif; ?>
    </section>

    <section id="student-information">
        <h2>Student Information</h2>
        <?php if (count($students) > 0): ?>
            <table>
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
            <p class="loading">No students found.</p>
        <?php endif; ?>
    </section>

    <!-- Export Section -->
    <section id="exportSection" style="text-align: center; margin: 30px 0;">
        <a href="teacher/export.php" class="btn">Download CSV file</a>
    </section>

<?php require_once 'includes/footer.php'; ?>
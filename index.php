<?php
// DB холболтыг хийх
require_once 'config/db.php';

// Хайлтын талбарын утга авах
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

// Ирц бүртгэлийг авах (JOIN ашиглан оюутны нэрийг харуулах)
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
    ORDER BY attendance.entry_time DESC
";

if ($searchTerm) {
    // Хайлт хийх, карт ID эсвэл оюутны нэр эсвэл оюутны ID-ээр фильтер хийх
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
        WHERE attendance.card_id LIKE :searchTerm 
           OR students.full_name LIKE :searchTerm 
           OR students.student_id LIKE :searchTerm
        ORDER BY attendance.entry_time DESC
    ";
}

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendFT</title>
    <link rel="icon" href="public/images/favicon.ico" type="image/x-icon">
    <link id="themeStylesheet" rel="stylesheet" href="public/css/style-light.css"> <!-- Default light theme -->
    <link id="navStylesheet" rel="stylesheet" href="public/css/nav-style.css">
</head>

<body>
    <header>
        <div class="logo">
            <h1>ATTENDFT</h1>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <a href="#attendence-list">Attendance</a>
        <a href="#student-information">Students</a>
        <a href="#exportSection">Export Data</a>
    </nav>

    <button id="themeToggleBtn">Dark Theme</button>
    
    <!-- Хайлтын талбар -->
    <div style="text-align: center; margin: 20px 0;">
        <form method="POST" action="" id="searchForm" style="display: inline-block;">
            <input type="text" name="search" id="searchInput" 
                   placeholder="Name, Card ID or student number" 
                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                   style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
            <button type="submit" style="padding: 10px 20px; margin-left: 10px;">Search</button>
            <?php if ($searchTerm): ?>
                <a href="index.php" style="margin-left: 10px; color: #3498db; text-decoration: none;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <section id="attendence-list">
        <h1>Attendance List</h1>
        <?php if (count($attendances) > 0): ?>
            <table border="1">
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
                                    <span style="color: green; font-weight: bold;">Completed</span>
                                <?php else: ?>
                                    <span style="color: orange; font-weight: bold;">In Progress</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; font-style: italic;">No attendance records found.</p>
        <?php endif; ?>
    </section>

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

    <!-- Excel файл татах -->
    <section id="exportSection">
        <div style="text-align: center; margin: 30px 0;">
            <a href="dashboard/export.php" class="btn">Download CSV file</a>
        </div>
    </section>
    
    <script src="public/js/theme.js" defer></script>
    <script src="public/js/real-time.js" defer></script>
    
    <footer>
        <p>&copy; 2025 Attendft. All rights reserved.</p>
        <p>Contact us at: <a href="mailto:ab24367010@ga.ttc.ac.jp">ab24367010@ga.ttc.ac.jp</a></p>
    </footer>
</body>

</html>
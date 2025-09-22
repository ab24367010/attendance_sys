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
        students.card_id
    FROM attendance
    LEFT JOIN students ON attendance.student_id = students.student_id
";


if ($searchTerm) {
    // Хайлт хийх, карт ID эсвэл оюутны нэр эсвэл оюутны ID-ээр фильтр хийх
    $sql .= " WHERE attendance.card_id LIKE :searchTerm OR students.full_name LIKE :searchTerm OR students.student_id LIKE :searchTerm";
}

$stmt = $pdo->prepare($sql);

if ($searchTerm) {
    // Хайлтын утгыг аюулгүй оруулах
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}

$stmt->execute();
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Оюутны мэдээллийг авах
$studentStmt = $pdo->prepare("SELECT * FROM students");
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
    <form method="POST" action="" id="searchForm">
        <input type="text" name="search" id="searchInput" placeholder="Name, Card ID or student number" value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">Search</button>
    </form>

    <section id="attendence-list">
        <h1>Attendance List</h1>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Entry Time</th>
                    <th>Exit Time</th>
                    <th>Card ID</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                <?php foreach ($attendances as $attendance): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($attendance['id']); ?></td>
                        <td><?php echo htmlspecialchars($attendance['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($attendance['entry_time']); ?></td>
                        <td><?php echo htmlspecialchars($attendance['exit_time']); ?></td>
                        <td><?php echo htmlspecialchars($attendance['card_id']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section id="student-information">
        <h2>Student Information</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Student Number</th>
                    <th>Card ID</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['card_id']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Excel файл татах -->
    <section id="exportSection">
        <br>
        <a href="dashboard/export.php" class="btn">Download Excel file</a>
    </section>
    <script src="public/js/theme.js" defer></script>
    <script src="public/js/real-time.js" defer></script>
    <footer>
        <p>&copy; 2025 Attendft. All rights reserved.</p>
        <p>Contact us at: <a href="mailto:ab24367010@ga.ttc.ac.jp">ab24367010@ga.ttc.ac.jp</a></p>
    </footer>
</body>

</html>

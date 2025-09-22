<?php
require_once 'config/db.php';

// Хайлт хийх утга авах
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';

// SQL запрос - Ирцийн мэдээллийг авах
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

try {
    $stmt = $pdo->prepare($sql);

    if ($searchTerm) {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }

    $stmt->execute();
    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add status field for each record
    foreach ($attendances as &$attendance) {
        $attendance['status'] = $attendance['exit_time'] ? 'Completed' : 'In Progress';
        $attendance['full_name'] = $attendance['full_name'] ?? 'Unknown';
        $attendance['student_id'] = $attendance['student_id'] ?? 'N/A';
        $attendance['exit_time'] = $attendance['exit_time'] ?? 'Still inside';
    }

    // JSON хэлбэрээр ирцийн мэдээллийг буцаах
    header('Content-Type: application/json');
    echo json_encode($attendances);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
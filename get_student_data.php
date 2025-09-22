<?php
require_once 'config/db.php';

// Хайлт хийх утга авах
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';

// SQL запрос - Хайлт хийх
$sql = "SELECT * FROM students";

if ($searchTerm) {
    $sql .= " WHERE full_name LIKE :searchTerm 
              OR student_id LIKE :searchTerm 
              OR card_id LIKE :searchTerm";
}

$sql .= " ORDER BY full_name ASC";

try {
    $stmt = $pdo->prepare($sql);

    if ($searchTerm) {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // JSON хэлбэрээр өгөгдлийг буцаах
    header('Content-Type: application/json');
    echo json_encode($students);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
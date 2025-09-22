<?php
require_once 'config/db.php';

// Хайлт хийх утга авах
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

// SQL запрос - Ирцийн мэдээллийг авах
$sql = "SELECT * FROM attendance";

if ($searchTerm) {
    $sql .= " WHERE attendance.card_id LIKE :searchTerm OR students.full_name LIKE :searchTerm OR students.student_id LIKE :searchTerm";
}

$stmt = $pdo->prepare($sql);

if ($searchTerm) {
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}

$stmt->execute();
$attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON хэлбэрээр ирцийн мэдээллийг буцаах
echo json_encode($attendances);

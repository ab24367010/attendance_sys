<?php
require_once 'config/db.php';

// Хайлт хийх утга авах
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

// SQL запрос - Хайлт хийх
$sql = "SELECT * FROM students";

if ($searchTerm) {
    $sql .= " WHERE full_name LIKE :searchTerm OR student_id LIKE :searchTerm OR card_id LIKE :searchTerm";
}

$stmt = $pdo->prepare($sql);

if ($searchTerm) {
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}

$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON хэлбэрээр өгөгдлийг буцаах
echo json_encode($students);

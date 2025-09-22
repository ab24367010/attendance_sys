<?php
require_once 'config/db.php';

$cardID = isset($_POST['cardID']) ? $_POST['cardID'] : '';

if (empty($cardID)) {
    http_response_code(400);
    echo "Missing card ID.";
    exit();
}

// 1. card_logs хүснэгтэд бүртгэх
$logStmt = $pdo->prepare("INSERT INTO card_logs (card_id, scan_time) VALUES (:cardID, NOW())");
$logStmt->bindParam(':cardID', $cardID);
$logStmt->execute();

// 2. Карт ID-г student_id-тай холбох
$studentStmt = $pdo->prepare("SELECT student_id FROM students WHERE card_id = :cardID");
$studentStmt->bindParam(':cardID', $cardID);
$studentStmt->execute();
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Card ID not registered to a student.";
    exit();
}

$student_id = $student['student_id'];

// 3. Хамгийн сүүлд бүртгэгдсэн exit_time хоосон мөр байна уу шалгах
$checkStmt = $pdo->prepare("
    SELECT id FROM attendance
    WHERE student_id = :student_id AND exit_time IS NULL
    ORDER BY entry_time DESC LIMIT 1
");
$checkStmt->bindParam(':student_id', $student_id);
$checkStmt->execute();
$openAttendance = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($openAttendance) {
    // exit_time хоосон байгаа -> одоо гарах гэж байна
    $updateStmt = $pdo->prepare("UPDATE attendance SET exit_time = NOW() WHERE id = :id");
    $updateStmt->bindParam(':id', $openAttendance['id']);
    $updateStmt->execute();
    echo "Exit time recorded!";
} else {
    // exit_time бөглөгдсөн -> одоо орж ирж байна
    $insertStmt = $pdo->prepare("INSERT INTO attendance (student_id, entry_time) VALUES (:student_id, NOW())");
    $insertStmt->bindParam(':student_id', $student_id);
    $insertStmt->execute();
    echo "Entry time recorded!";
}
?>

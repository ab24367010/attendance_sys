<?php
require_once '../config/db.php';

try {
    // Ирц болон оюутны мэдээллийг авах
    $stmt = $pdo->prepare("SELECT * FROM attendance");
    $stmt->execute();
    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV файл үүсгэх
    $filename = 'attendance_data.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Хүснэгт толгойн бичвэрийг нэмэх
    fputcsv($output, ['ID', 'Card ID', 'Entry Time', 'Exit Time', 'Student Number']);

    // Мэдээллийг бичих
    foreach ($attendances as $attendance) {
        fputcsv($output, $attendance);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

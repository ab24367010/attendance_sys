<?php
require_once '../includes/functions.php';

try {
    // Ирц болон оюутны мэдээллийг авах
    $stmt = $pdo->prepare("
        SELECT
            attendance.id,
            students.full_name,
            students.student_id,
            attendance.card_id,
            attendance.entry_time,
            attendance.exit_time,
            CASE 
                WHEN attendance.exit_time IS NOT NULL THEN 'Completed'
                ELSE 'In Progress'
            END as status,
            attendance.created_at
        FROM attendance
        LEFT JOIN students ON attendance.student_id = students.student_id
        ORDER BY attendance.entry_time DESC
    ");
    $stmt->execute();
    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV файл үүсгэх
    $filename = 'attendance_data_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    // UTF-8 BOM нэмэх (Excel-д зөв харагдахын тулд)
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Хүснэгт толгойн бичвэрийг нэмэх
    fputcsv($output, [
        'ID', 
        'Student Name', 
        'Student Number', 
        'Card ID', 
        'Entry Time', 
        'Exit Time', 
        'Status',
        'Record Created'
    ]);

    // Мэдээллийг бичих
    foreach ($attendances as $attendance) {
        fputcsv($output, [
            $attendance['id'],
            $attendance['full_name'] ?? 'Unknown',
            $attendance['student_id'] ?? 'N/A',
            $attendance['card_id'],
            $attendance['entry_time'],
            $attendance['exit_time'] ?? 'Still inside',
            $attendance['status'],
            $attendance['created_at']
        ]);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<?php
require_once '../includes/functions.php';

$auth = new Auth($pdo);
$auth->requireStudent();

$currentUser = $auth->getCurrentUser();
$studentId = $currentUser['student_id'];

try {
    // Get student's attendance records
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
            CASE 
                WHEN attendance.exit_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, attendance.entry_time, attendance.exit_time)
                ELSE TIMESTAMPDIFF(MINUTE, attendance.entry_time, NOW())
            END as duration_minutes,
            attendance.created_at
        FROM attendance
        LEFT JOIN students ON attendance.student_id = students.student_id
        WHERE attendance.student_id = :student_id
        ORDER BY attendance.entry_time DESC
    ");
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV file name
    $filename = 'my_attendance_' . $studentId . '_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    // UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'ID', 
        'Student Name', 
        'Student Number', 
        'Card ID', 
        'Entry Time', 
        'Exit Time', 
        'Duration (minutes)',
        'Status',
        'Record Created'
    ]);

    // Write data
    foreach ($attendances as $attendance) {
        fputcsv($output, [
            $attendance['id'],
            $attendance['full_name'],
            $attendance['student_id'],
            $attendance['card_id'],
            $attendance['entry_time'],
            $attendance['exit_time'] ?? 'Still inside',
            $attendance['duration_minutes'],
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
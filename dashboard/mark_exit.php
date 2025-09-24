<?php
// dashboard/mark_exit.php
require_once '../config/db.php';
require_once '../config/auth.php';

$auth = new Auth($pdo);
$auth->requireTeacher();

// Set JSON response header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$attendanceId = filter_input(INPUT_POST, 'attendance_id', FILTER_VALIDATE_INT);

if (!$attendanceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid attendance ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check if record exists and doesn't have exit time
    $checkStmt = $pdo->prepare("
        SELECT id, student_id, entry_time 
        FROM attendance 
        WHERE id = :id AND exit_time IS NULL
    ");
    $checkStmt->bindParam(':id', $attendanceId);
    $checkStmt->execute();
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Record not found or already has exit time']);
        exit;
    }
    
    // Update exit time
    $updateStmt = $pdo->prepare("UPDATE attendance SET exit_time = NOW() WHERE id = :id");
    $updateStmt->bindParam(':id', $attendanceId);
    $updateStmt->execute();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Exit time marked successfully',
        'exit_time' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Mark exit error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
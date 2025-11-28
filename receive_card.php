<?php
require_once 'includes/functions.php';

// Get card ID from POST request
$cardID = isset($_POST['cardID']) ? sanitize($_POST['cardID']) : '';

if (empty($cardID)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing card ID']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. Log all card scans
    $logStmt = $pdo->prepare("INSERT INTO card_logs (card_id, scan_time) VALUES (:cardID, NOW())");
    $logStmt->bindParam(':cardID', $cardID);
    $logStmt->execute();

    // 2. Find student by card ID
    $studentStmt = $pdo->prepare("SELECT student_id, full_name FROM students WHERE card_id = :cardID");
    $studentStmt->bindParam(':cardID', $cardID);
    $studentStmt->execute();
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $pdo->rollBack();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Card ID not registered to any student',
            'card_id' => $cardID
        ]);
        exit();
    }

    $student_id = $student['student_id'];
    $student_name = $student['full_name'];

    // 3. Check for most recent attendance record without exit_time
    $checkStmt = $pdo->prepare("
        SELECT id, entry_time FROM attendance
        WHERE student_id = :student_id AND exit_time IS NULL
        ORDER BY entry_time DESC LIMIT 1
    ");
    $checkStmt->bindParam(':student_id', $student_id);
    $checkStmt->execute();
    $openAttendance = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($openAttendance) {
        // Student is checking out - update exit_time
        $updateStmt = $pdo->prepare("UPDATE attendance SET exit_time = NOW() WHERE id = :id");
        $updateStmt->bindParam(':id', $openAttendance['id']);
        $updateStmt->execute();
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'action' => 'checkout',
            'message' => 'Exit time recorded successfully!',
            'student_name' => $student_name,
            'student_id' => $student_id,
            'card_id' => $cardID,
            'entry_time' => $openAttendance['entry_time'],
            'exit_time' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Student is checking in - create new attendance record
        $insertStmt = $pdo->prepare("
            INSERT INTO attendance (student_id, card_id, entry_time) 
            VALUES (:student_id, :card_id, NOW())
        ");
        $insertStmt->bindParam(':student_id', $student_id);
        $insertStmt->bindParam(':card_id', $cardID);
        $insertStmt->execute();
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'action' => 'checkin',
            'message' => 'Entry time recorded successfully!',
            'student_name' => $student_name,
            'student_id' => $student_id,
            'card_id' => $cardID,
            'entry_time' => date('Y-m-d H:i:s')
        ]);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in receive_card.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred',
        'debug' => $e->getMessage()
    ]);
}
?>
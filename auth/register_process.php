<?php
// auth/register_process.php
header('Content-Type: application/json');

require_once 'auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$role = $_POST['role'] ?? '';
$fullName = trim($_POST['fullName'] ?? '');
$studentId = trim($_POST['studentId'] ?? '') ?: null;

// Validation
$errors = [];

if (empty($username)) {
    $errors[] = 'Username is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters long';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
}

if (!in_array($role, ['teacher', 'student'])) {
    $errors[] = 'Invalid role selected';
}

if (empty($fullName)) {
    $errors[] = 'Full name is required';
}

if ($role === 'student' && empty($studentId)) {
    $errors[] = 'Student ID is required for student accounts';
}

// If student role, validate that student ID exists in students table
if ($role === 'student' && !empty($studentId)) {
    try {
        $checkStmt = $pdo->prepare("SELECT id FROM students WHERE student_id = :student_id");
        $checkStmt->bindParam(':student_id', $studentId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            $errors[] = 'Student ID not found in the system. Please contact administration.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error occurred during validation';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Register user
$result = registerUser($username, $email, $password, $role, $fullName, $studentId);

echo json_encode($result);
?>
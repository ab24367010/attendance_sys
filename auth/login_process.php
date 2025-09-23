<?php
// auth/login_process.php
header('Content-Type: application/json');

require_once 'auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username/email and password are required']);
    exit();
}

if (loginUser($identifier, $password)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'user' => getCurrentUser()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
?>
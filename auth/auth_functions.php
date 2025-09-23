<?php
// auth/auth_functions.php
require_once '../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Hash a password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against its hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a secure random session token
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Login user with username/email and password
 */
function loginUser($identifier, $password) {
    global $pdo;
    
    try {
        // Check if identifier is email or username
        $sql = "SELECT id, username, email, password_hash, role, full_name, student_id, is_active 
                FROM users 
                WHERE (username = :identifier OR email = :identifier) AND is_active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            return false;
        }
        
        // Generate session token
        $sessionToken = generateSessionToken();
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        // Save session to database
        $sessionStmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, expires_at) 
            VALUES (:user_id, :token, :expires_at)
        ");
        $sessionStmt->bindParam(':user_id', $user['id']);
        $sessionStmt->bindParam(':token', $sessionToken);
        $sessionStmt->bindParam(':expires_at', $expiresAt);
        $sessionStmt->execute();
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['session_token'] = $sessionToken;
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Check if user is teacher
 */
function isTeacher() {
    return hasRole('teacher');
}

/**
 * Check if user is student
 */
function isStudent() {
    return hasRole('student');
}

/**
 * Get current user information
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'],
        'student_id' => $_SESSION['student_id']
    ];
}

/**
 * Validate session token
 */
function validateSession() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM user_sessions 
            WHERE user_id = :user_id 
            AND session_token = :token 
            AND expires_at > NOW()
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':token', $_SESSION['session_token']);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    global $pdo;
    
    if (isLoggedIn()) {
        try {
            // Delete session from database
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = :token");
            $stmt->bindParam(':token', $_SESSION['session_token']);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    // Clear session
    session_unset();
    session_destroy();
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn() || !validateSession()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require teacher role
 */
function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header('Location: unauthorized.php');
        exit();
    }
}

/**
 * Register new user
 */
function registerUser($username, $email, $password, $role, $fullName, $studentId = null) {
    global $pdo;
    
    try {
        // Check if username or email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $passwordHash = hashPassword($password);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role, full_name, student_id) 
            VALUES (:username, :email, :password_hash, :role, :full_name, :student_id)
        ");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':student_id', $studentId);
        
        $stmt->execute();
        
        return ['success' => true, 'message' => 'User registered successfully'];
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

/**
 * Log attendance edit
 */
function logAttendanceEdit($attendanceId, $oldEntryTime, $newEntryTime, $oldExitTime, $newExitTime, $reason) {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO attendance_edit_logs 
            (attendance_id, edited_by, old_entry_time, new_entry_time, old_exit_time, new_exit_time, edit_reason) 
            VALUES (:attendance_id, :edited_by, :old_entry, :new_entry, :old_exit, :new_exit, :reason)
        ");
        
        $stmt->bindParam(':attendance_id', $attendanceId);
        $stmt->bindParam(':edited_by', $_SESSION['user_id']);
        $stmt->bindParam(':old_entry', $oldEntryTime);
        $stmt->bindParam(':new_entry', $newEntryTime);
        $stmt->bindParam(':old_exit', $oldExitTime);
        $stmt->bindParam(':new_exit', $newExitTime);
        $stmt->bindParam(':reason', $reason);
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Edit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired sessions
 */
function cleanupExpiredSessions() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Session cleanup error: " . $e->getMessage());
    }
}

// Clean up expired sessions on each request (with 1% probability to avoid overhead)
if (rand(1, 100) === 1) {
    cleanupExpiredSessions();
}
?>
<?php
// config/auth.php

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Start secure session
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Login user with username/email and password
     */
    public function login($identifier, $password) {
        try {
            // Check if identifier is email or username
            $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password_hash, role, student_id, full_name, is_active 
                FROM users 
                WHERE {$field} = :identifier AND is_active = 1
            ");
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Create session
                self::startSession();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Update last login
                $updateStmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Create session token for additional security
                $this->createSessionToken($user['id']);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Login successful'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login system error'
            ];
        }
    }
    
    /**
     * Create secure session token
     */
    private function createSessionToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        try {
            // Clean old sessions for this user
            $cleanStmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = :user_id OR expires_at < NOW()");
            $cleanStmt->bindParam(':user_id', $userId);
            $cleanStmt->execute();
            
            // Insert new session
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
                VALUES (:user_id, :token, :expires_at, :ip, :user_agent)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':expires_at' => $expiresAt,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $_SESSION['session_token'] = $token;
            
        } catch (PDOException $e) {
            error_log("Session token creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user is logged in and session is valid
     */
    public function isLoggedIn() {
        self::startSession();
        
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        // Check session timeout (2 hours)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
            $this->logout();
            return false;
        }
        
        // Verify session token if exists
        if (isset($_SESSION['session_token']) && isset($_SESSION['user_id'])) {
            return $this->validateSessionToken($_SESSION['user_id'], $_SESSION['session_token']);
        }
        
        return true;
    }
    
    /**
     * Validate session token
     */
    private function validateSessionToken($userId, $token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM user_sessions 
                WHERE user_id = :user_id AND session_token = :token AND expires_at > NOW()
            ");
            $stmt->execute([':user_id' => $userId, ':token' => $token]);
            
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
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
     * Check if current user has specific role
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if current user is teacher
     */
    public function isTeacher() {
        return $this->hasRole('teacher');
    }
    
    /**
     * Check if current user is student
     */
    public function isStudent() {
        return $this->hasRole('student');
    }
    
    /**
     * Require login - redirect to login page if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('Location: unauthorized.php');
            exit();
        }
    }
    
    /**
     * Require teacher role
     */
    public function requireTeacher() {
        $this->requireRole('teacher');
    }
    
    /**
     * Logout user
     */
    public function logout() {
        self::startSession();
        
        // Clean session token from database
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = :user_id AND session_token = :token");
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':token' => $_SESSION['session_token']
                ]);
            } catch (PDOException $e) {
                error_log("Logout cleanup error: " . $e->getMessage());
            }
        }
        
        // Clear session
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Register new user (for teachers to add students)
     */
    public function register($username, $email, $password, $role, $fullName, $studentId = null) {
        try {
            // Validate input
            if (strlen($password) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if username or email already exists
            $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $checkStmt->execute([':username' => $username, ':email' => $email]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, full_name, student_id) 
                VALUES (:username, :email, :password_hash, :role, :full_name, :student_id)
            ");
            
            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':role' => $role,
                ':full_name' => $fullName,
                ':student_id' => $studentId
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user_id' => $this->pdo->lastInsertId()
                ];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration system error'];
        }
    }
    
    /**
     * Clean expired sessions (call this periodically)
     */
    public function cleanExpiredSessions() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Session cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
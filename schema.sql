-- Create database
CREATE DATABASE IF NOT EXISTS irts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE irts;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL,
    student_id VARCHAR(20) NULL, -- Only for students, links to students table
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_student_id (student_id)
);

-- Students table (updated)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    card_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NULL, -- Link to users table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_card_id (card_id),
    INDEX idx_full_name (full_name),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Attendance table (updated)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    card_id VARCHAR(50) NOT NULL,
    entry_time TIMESTAMP NULL,
    exit_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES students(card_id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_card_id (card_id),
    INDEX idx_entry_time (entry_time),
    INDEX idx_exit_time (exit_time),
    INDEX idx_created_at (created_at)
);

-- Card logs table (for tracking all card scans)
CREATE TABLE card_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(50) NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_scan_time (scan_time)
);

-- Sessions table for secure session management
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Insert sample users
-- Password for all sample users is: "password123"
-- This is the hash of "password123" using PHP's password_hash() function
INSERT INTO users (username, email, password_hash, role, student_id, full_name) VALUES
('teacher1', 'teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, 'Teacher Admin'),
('john_smith', 'john.smith@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024001', 'John Smith'),
('jane_doe', 'jane.doe@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024002', 'Jane Doe'),
('mike_johnson', 'mike.johnson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024003', 'Mike Johnson'),
('sarah_wilson', 'sarah.wilson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024004', 'Sarah Wilson');

-- Insert sample students (updated with user links)
INSERT INTO students (student_id, full_name, card_id, user_id) VALUES
('2024001', 'John Smith', 'abc123def456', 2),
('2024002', 'Jane Doe', 'xyz789uvw012', 3),
('2024003', 'Mike Johnson', 'mno345pqr678', 4),
('2024004', 'Sarah Wilson', 'stu901vwx234', 5);

-- Update users table with student_id references
UPDATE users SET student_id = '2024001' WHERE id = 2;
UPDATE users SET student_id = '2024002' WHERE id = 3;
UPDATE users SET student_id = '2024003' WHERE id = 4;
UPDATE users SET student_id = '2024004' WHERE id = 5;

-- Insert sample attendance data
INSERT INTO attendance (student_id, card_id, entry_time, exit_time) VALUES
('2024001', 'abc123def456', '2025-01-20 08:30:00', '2025-01-20 17:00:00'),
('2024002', 'xyz789uvw012', '2025-01-20 09:00:00', '2025-01-20 18:30:00'),
('2024003', 'mno345pqr678', '2025-01-21 08:45:00', NULL),
('2024004', 'stu901vwx234', '2025-01-21 09:15:00', '2025-01-21 17:45:00');

-- Create a view for easy user data retrieval
CREATE VIEW user_details AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.role,
    u.full_name,
    u.student_id,
    u.is_active,
    u.created_at,
    u.last_login,
    s.card_id
FROM users u
LEFT JOIN students s ON u.student_id = s.student_id;
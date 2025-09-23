-- Create database
CREATE DATABASE IF NOT EXISTS irts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE irts;

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    card_id VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_card_id (card_id),
    INDEX idx_full_name (full_name)
);

-- Attendance table
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
    INDEX idx_exit_time (exit_time)
);

-- Card logs table (for tracking all card scans)
CREATE TABLE card_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(50) NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_scan_time (scan_time)
);

-- Insert sample data
INSERT INTO students (student_id, full_name, card_id) VALUES
('2024001', 'John Smith', 'abc123def456'),
('2024002', 'Jane Doe', 'xyz789uvw012'),
('2024003', 'Mike Johnson', 'mno345pqr678'),
('2024004', 'Sarah Wilson', 'stu901vwx234');

-- Insert sample attendance data
INSERT INTO attendance (student_id, card_id, entry_time, exit_time) VALUES
('2024001', 'abc123def456', '2025-01-20 08:30:00', '2025-01-20 17:00:00'),
('2024002', 'xyz789uvw012', '2025-01-20 09:00:00', '2025-01-20 18:30:00'),
('2024003', 'mno345pqr678', '2025-01-21 08:45:00', NULL),
('2024004', 'stu901vwx234', '2025-01-21 09:15:00', '2025-01-21 17:45:00');
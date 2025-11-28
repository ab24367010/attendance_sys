<?php
require_once '../includes/functions.php';

$auth = new Auth($pdo);
$auth->requireTeacher();

$currentUser = $auth->getCurrentUser();
$searchTerm = isset($_POST['search']) ? sanitize($_POST['search']) : '';

// Handle student management actions
$action_message = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action_message = 'Invalid security token. Please try again.';
        $action_type = 'error';
    } else {
        // Handle different actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_student':
                    $result = addStudent($pdo, $_POST);
                    $action_message = $result['message'];
                    $action_type = $result['success'] ? 'success' : 'error';
                    break;
                case 'delete_student':
                    $result = deleteStudent($pdo, $_POST['student_id']);
                    $action_message = $result['message'];
                    $action_type = $result['success'] ? 'success' : 'error';
                    break;
            }
        }
    }
}

// Get attendance data with search
$attendanceSql = "
    SELECT
        attendance.id,
        attendance.entry_time,
        attendance.exit_time,
        students.full_name,
        students.student_id,
        attendance.card_id,
        attendance.created_at
    FROM attendance
    LEFT JOIN students ON attendance.student_id = students.student_id
";

if ($searchTerm) {
    $attendanceSql .= " WHERE attendance.card_id LIKE :searchTerm 
                       OR students.full_name LIKE :searchTerm 
                       OR students.student_id LIKE :searchTerm";
}

$attendanceSql .= " ORDER BY attendance.entry_time DESC LIMIT 100";

$attendanceStmt = $pdo->prepare($attendanceSql);
if ($searchTerm) {
    $attendanceStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}
$attendanceStmt->execute();
$attendances = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Get student data with search
$studentSql = "SELECT * FROM students";
if ($searchTerm) {
    $studentSql .= " WHERE full_name LIKE :searchTerm 
                    OR student_id LIKE :searchTerm 
                    OR card_id LIKE :searchTerm";
}
$studentSql .= " ORDER BY full_name ASC";

$studentStmt = $pdo->prepare($studentSql);
if ($searchTerm) {
    $studentStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}
$studentStmt->execute();
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsStmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM students) as total_students,
        (SELECT COUNT(*) FROM attendance WHERE DATE(entry_time) = CURDATE()) as today_entries,
        (SELECT COUNT(*) FROM attendance WHERE exit_time IS NULL) as currently_inside,
        (SELECT COUNT(*) FROM attendance WHERE DATE(entry_time) = CURDATE() AND exit_time IS NOT NULL) as completed_today
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$csrfToken = Auth::generateCSRFToken();

// Helper functions
function addStudent($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Validate input
        $required_fields = ['student_id', 'full_name', 'card_id', 'username', 'email', 'password'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Check if student ID or card ID already exists
        $checkStmt = $pdo->prepare("SELECT id FROM students WHERE student_id = :student_id OR card_id = :card_id");
        $checkStmt->execute([
            ':student_id' => $data['student_id'],
            ':card_id' => $data['card_id']
        ]);
        if ($checkStmt->fetch()) {
            throw new Exception("Student ID or Card ID already exists");
        }
        
        // Create user account first
        $auth = new Auth($pdo);
        $userResult = $auth->register(
            $data['username'],
            $data['email'],
            $data['password'],
            'student',
            $data['full_name'],
            $data['student_id']
        );
        
        if (!$userResult['success']) {
            throw new Exception($userResult['message']);
        }
        
        // Insert student
        $studentStmt = $pdo->prepare("
            INSERT INTO students (student_id, full_name, card_id, user_id) 
            VALUES (:student_id, :full_name, :card_id, :user_id)
        ");
        $studentStmt->execute([
            ':student_id' => $data['student_id'],
            ':full_name' => $data['full_name'],
            ':card_id' => $data['card_id'],
            ':user_id' => $userResult['user_id']
        ]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Student added successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Add student error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function deleteStudent($pdo, $studentId) {
    try {
        $pdo->beginTransaction();
        
        // Get user_id first
        $getUserStmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = :student_id");
        $getUserStmt->execute([':student_id' => $studentId]);
        $student = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        // Delete from students table (this will cascade to attendance due to foreign key)
        $deleteStudentStmt = $pdo->prepare("DELETE FROM students WHERE student_id = :student_id");
        $deleteStudentStmt->execute([':student_id' => $studentId]);
        
        // Delete user account if exists
        if ($student['user_id']) {
            $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
            $deleteUserStmt->execute([':user_id' => $student['user_id']]);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Student deleted successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete student error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - AttendFT</title>
    <link rel="icon" href="../public/images/favicon.ico" type="image/x-icon">
    <link id="themeStylesheet" rel="stylesheet" href="../public/css/style-light.css">
    <link id="navStylesheet" rel="stylesheet" href="../public/css/nav-style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .stat-card p {
            color: #666;
            font-weight: 500;
        }
        
        .action-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80%;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .search-section {
            text-align: center;
            margin: 20px 0;
        }
        
        .search-form {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="user-info">
            <div>
                <h1>Teacher Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?></p>
            </div>
            <div>
                <a href="../index.php" class="btn btn-primary">Public View</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav>
        <a href="#stats">Statistics</a>
        <a href="#attendance-list">Attendance</a>
        <a href="#student-management">Students</a>
        <a href="#export">Export Data</a>
    </nav>

    <?php if ($action_message): ?>
        <div class="alert alert-<?php echo $action_type; ?>">
            <?php echo htmlspecialchars($action_message); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Section -->
    <section id="stats">
        <h2>Today's Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_students']; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['today_entries']; ?></h3>
                <p>Today's Entries</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['currently_inside']; ?></h3>
                <p>Currently Inside</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['completed_today']; ?></h3>
                <p>Completed Today</p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <div class="search-section">
        <form method="POST" class="search-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="text" name="search" id="searchInput" 
                   placeholder="Search by name, student ID, or card ID" 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($searchTerm): ?>
                <a href="teacher.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Attendance List Section -->
    <section id="attendance-list">
        <h2>Recent Attendance Records</h2>
        <div class="table-responsive">
            <?php if (count($attendances) > 0): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Entry Time</th>
                            <th>Exit Time</th>
                            <th>Card ID</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendances as $attendance): ?>
                            <tr id="attendance-row-<?php echo $attendance['id']; ?>">
                                <td><?php echo htmlspecialchars($attendance['id']); ?></td>
                                <td><?php echo htmlspecialchars($attendance['full_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($attendance['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($attendance['entry_time']); ?></td>
                                <td class="exit-time-<?php echo $attendance['id']; ?>">
                                    <?php echo htmlspecialchars($attendance['exit_time'] ?? 'Still inside'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($attendance['card_id']); ?></td>
                                <td class="status-<?php echo $attendance['id']; ?>">
                                    <?php if ($attendance['exit_time']): ?>
                                        <span style="color: green; font-weight: bold;">Completed</span>
                                    <?php else: ?>
                                        <span style="color: orange; font-weight: bold;">In Progress</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$attendance['exit_time']): ?>
                                        <button onclick="markExit(<?php echo $attendance['id']; ?>)" 
                                                class="btn btn-warning btn-sm mark-exit-btn-<?php echo $attendance['id']; ?>">Mark Exit</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; font-style: italic;">No attendance records found.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Student Management Section -->
    <section id="student-management">
        <h2>Student Management</h2>
        <div class="action-buttons">
            <button onclick="openAddStudentModal()" class="btn btn-success">Add New Student</button>
        </div>
        
        <div class="table-responsive">
            <?php if (count($students) > 0): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Card ID</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['id']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['card_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['created_at']); ?></td>
                                <td>
                                    <button onclick="deleteStudent('<?php echo htmlspecialchars($student['student_id']); ?>', '<?php echo htmlspecialchars($student['full_name']); ?>')" 
                                            class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; font-style: italic;">No students found.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Export Section -->
    <section id="export">
        <h2>Export Data</h2>
        <div class="action-buttons">
            <a href="../dashboard/export.php" class="btn btn-success">Download CSV File</a>
        </div>
    </section>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddStudentModal()">&times;</span>
            <h2>Add New Student</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="add_student">
                
                <div class="form-group">
                    <label for="student_id">Student Number:</label>
                    <input type="text" id="student_id" name="student_id" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="card_id">Card ID:</label>
                    <input type="text" id="card_id" name="card_id" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <button type="submit" class="btn btn-success">Add Student</button>
            </form>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loading" class="loading">Processing...</div>

    <script>
        // Modal functions
        function openAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
        }
        
        function closeAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addStudentModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Delete student function
        function deleteStudent(studentId, studentName) {
            if (confirm(`Are you sure you want to delete student "${studentName}"? This will also delete all their attendance records and user account.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete_student">
                    <input type="hidden" name="student_id" value="${studentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Mark exit function with AJAX
        function markExit(attendanceId) {
            if (confirm('Mark this attendance record as completed (add exit time)?')) {
                const loading = document.getElementById('loading');
                const button = document.querySelector(`.mark-exit-btn-${attendanceId}`);
                
                loading.style.display = 'block';
                button.disabled = true;
                button.textContent = 'Processing...';
                
                const formData = new FormData();
                formData.append('attendance_id', attendanceId);
                formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken); ?>');
                
                fetch('mark_exit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';
                    
                    if (data.success) {
                        // Update the UI
                        document.querySelector(`.exit-time-${attendanceId}`).textContent = data.exit_time;
                        document.querySelector(`.status-${attendanceId}`).innerHTML = '<span style="color: green; font-weight: bold;">Completed</span>';
                        button.remove();
                        
                        // Show success message
                        showNotification('Exit time marked successfully!', 'success');
                        
                        // Update statistics
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        button.disabled = false;
                        button.textContent = 'Mark Exit';
                        showNotification(data.message || 'Failed to mark exit', 'error');
                    }
                })
                .catch(error => {
                    loading.style.display = 'none';
                    button.disabled = false;
                    button.textContent = 'Mark Exit';
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }
        
        // Auto-generate username from full name
        document.getElementById('full_name').addEventListener('input', function() {
            const fullName = this.value.toLowerCase().replace(/\s+/g, '_');
            document.getElementById('username').value = fullName;
        });
        
        // Show notification function
        function showNotification(message, type = 'info') {
            let notification = document.getElementById('notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 25px;
                    border-radius: 8px;
                    color: white;
                    font-weight: bold;
                    z-index: 10000;
                    display: none;
                    max-width: 300px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                `;
                document.body.appendChild(notification);
            }

            const colors = {
                'info': '#3498db',
                'success': '#27ae60',
                'warning': '#f39c12',
                'error': '#e74c3c'
            };
            
            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 4000);
        }
        
        // Auto-refresh attendance data every 30 seconds
        setInterval(() => {
            if (!document.querySelector('.modal[style*="block"]')) {
                location.reload();
            }
        }, 30000);
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#addStudentModal form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = ['student_id', 'full_name', 'card_id', 'username', 'email', 'password'];
                    let valid = true;
                    
                    requiredFields.forEach(field => {
                        const input = document.getElementById(field);
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = '#e74c3c';
                        } else {
                            input.style.borderColor = '#e1e5e9';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        showNotification('Please fill in all required fields', 'error');
                    }
                });
            }
        });
    </script>
</body>
</html>
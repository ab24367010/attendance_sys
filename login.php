<?php
$pageTitle = 'Login - AttendFT';
require_once 'includes/functions.php';

$auth = new Auth($pdo);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    if ($user['role'] === 'teacher') {
        redirect('teacher/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$error = '';
$success = '';

// Check for logout success message
if (isset($_GET['logged_out'])) {
    $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = $auth->login($identifier, $password);

            if ($result['success']) {
                if ($result['user']['role'] === 'teacher') {
                    redirect('teacher/dashboard.php');
                } else {
                    redirect('student/dashboard.php');
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = Auth::generateCSRFToken();

require_once 'includes/header-login.php';
?>

<main>
    <div class="login-container">
        <div class="logo">
            <h1>ATTENDFT</h1>
            <p>Attendance Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier"
                       value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                       required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword()">Show</button>
                </div>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="demo-credentials">
            <h4>Demo Credentials (Click to auto-fill)</h4>
            <p onclick="fillCredentials('teacher1', 'password123')"><strong>Teacher:</strong> teacher1 / password123</p>
            <p onclick="fillCredentials('john_smith', 'password123')"><strong>Student:</strong> john_smith / password123</p>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Public View</a>
        </div>
    </div>
</main>

<?php require_once 'includes/footer-login.php'; ?>

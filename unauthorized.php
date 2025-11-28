<?php
require_once 'includes/functions.php';

$auth = new Auth($pdo);
$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - AttendFT</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .unauthorized-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .error-icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .error-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        @media (max-width: 480px) {
            .unauthorized-container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="unauthorized-container">
        <div class="error-icon">🚫</div>
        <h1>Access Denied</h1>
        <div class="error-message">
            You don't have permission to access this page. Please contact your administrator if you believe this is an error.
        </div>

        <?php if ($currentUser): ?>
            <div class="user-info">
                <strong>Current User:</strong> <?php echo htmlspecialchars($currentUser['full_name']); ?><br>
                <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($currentUser['role'])); ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <?php if ($currentUser): ?>
                <?php if ($currentUser['role'] === 'teacher'): ?>
                    <a href="teacher/dashboard.php" class="btn btn-primary">Go to Teacher Dashboard</a>
                <?php else: ?>
                    <a href="student/dashboard.php" class="btn btn-primary">Go to Student Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Login</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Public View</a>
        </div>
    </div>
</body>
</html>
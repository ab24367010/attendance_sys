<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendFT - Login</title>
    <link rel="icon" href="public/images/favicon.ico" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .demo-users {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
            font-size: 0.9rem;
        }

        .demo-users h3 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }

        .demo-users .user-group {
            margin-bottom: 15px;
        }

        .demo-users .user-group h4 {
            color: #667eea;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .demo-users .user-item {
            background: white;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid #667eea;
        }

        .demo-users .password {
            color: #666;
            font-style: italic;
            margin-top: 10px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .logo h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>ATTENDFT</h1>
            <p>Attendance Management System</p>
        </div>

        <div id="alert-container"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php" class="register-link">Register here</a></p>

        <div class="demo-users">
            <h3>Demo Users</h3>
            
            <div class="user-group">
                <h4>Teachers:</h4>
                <div class="user-item">teacher1 / teacher1@school.edu</div>
                <div class="user-item">teacher2 / teacher2@school.edu</div>
            </div>
            
            <div class="user-group">
                <h4>Students:</h4>
                <div class="user-item">john.smith / john.smith@student.edu</div>
                <div class="user-item">jane.doe / jane.doe@student.edu</div>
                <div class="user-item">mike.johnson / mike.johnson@student.edu</div>
                <div class="user-item">sarah.wilson / sarah.wilson@student.edu</div>
            </div>
            
            <div class="password">All passwords: <strong>password123</strong></div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const alertContainer = document.getElementById('alert-container');
            
            try {
                const response = await fetch('auth/login_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Login failed. Please try again.', 'error');
                console.error('Login error:', error);
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
        
        // Quick login function for demo
        function quickLogin(username) {
            document.getElementById('identifier').value = username;
            document.getElementById('password').value = 'password123';
        }
        
        // Add click handlers to demo users
        document.querySelectorAll('.user-item').forEach(item => {
            item.style.cursor = 'pointer';
            item.addEventListener('click', function() {
                const username = this.textContent.split(' / ')[0];
                quickLogin(username);
            });
        });
    </script>
</body>
</html>
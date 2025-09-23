<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendFT - Register</title>
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

        .register-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
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

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .register-btn {
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

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .login-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link:hover {
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

        #studentIdGroup {
            display: none;
        }

        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 0.9rem;
            color: #1565c0;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }

            .logo h1 {
                font-size: 2rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>ATTENDFT</h1>
            <p>Create Account</p>
        </div>

        <div class="info-box">
            <strong>Registration Information:</strong><br>
            • Teachers can manage attendance and view all student records<br>
            • Students can only view their own attendance records<br>
            • Student ID is required for student accounts only
        </div>

        <div id="alert-container"></div>

        <form id="registerForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="studentIdGroup">
                <label for="studentId">Student ID</label>
                <input type="text" id="studentId" name="studentId">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
            </div>

            <button type="submit" class="register-btn">Register</button>
        </form>

        <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
    </div>

    <script>
        const roleSelect = document.getElementById('role');
        const studentIdGroup = document.getElementById('studentIdGroup');
        const studentIdInput = document.getElementById('studentId');

        roleSelect.addEventListener('change', function() {
            if (this.value === 'student') {
                studentIdGroup.style.display = 'block';
                studentIdInput.required = true;
            } else {
                studentIdGroup.style.display = 'none';
                studentIdInput.required = false;
                studentIdInput.value = '';
            }
        });

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }

            if (password.length < 6) {
                showAlert('Password must be at least 6 characters long', 'error');
                return;
            }
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('auth/register_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Registration successful! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Registration failed. Please try again.', 'error');
                console.error('Registration error:', error);
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
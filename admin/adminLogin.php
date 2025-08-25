<?php

session_name('admin_session');
session_start();


if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'admin') {
    header('Location: adminDashboard.php');
    exit();
}

$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']); // Changed back to username
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // Check if admin exists in admin_login table using username
            $stmt = $pdo->prepare("SELECT * FROM admin_login WHERE username = ? AND role = 'admin'");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['username'];
                $_SESSION['user_type'] = $admin['role'];
                
                // Update last login (make sure this column exists in your table)
                try {
                    $update_stmt = $pdo->prepare("UPDATE admin_login SET last_login = NOW() WHERE id = ?");
                    $update_stmt->execute([$admin['id']]);
                } catch(PDOException $e) {
                    // Continue even if last_login update fails (column might not exist)
                }
                
                $success = 'Login successful! Redirecting to dashboard...';
                
                // Add a small delay before redirect to show success message
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'adminDashboard.php';
                    }, 2000);
                </script>";
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } catch(PDOException $e) {
            $error = 'Login failed. Please try again later. Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MavetCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Background decorative elements */
        .bg-decoration {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .paw-print {
            position: absolute;
            width: 30px;
            height: 30px;
            background: rgba(139, 195, 74, 0.1);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .paw-1 { top: 15%; left: 10%; animation-delay: 0s; }
        .paw-2 { top: 25%; right: 15%; animation-delay: 1.5s; }
        .paw-3 { bottom: 35%; left: 20%; animation-delay: 3s; }
        .paw-4 { bottom: 25%; right: 10%; animation-delay: 4.5s; }
        .paw-5 { top: 65%; left: 8%; animation-delay: 6s; }
        .paw-6 { top: 75%; right: 25%; animation-delay: 7.5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 0.1; }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem 2rem;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(139, 195, 74, 0.1);
            margin: 20px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }

        .logo i {
            background: #8BC34A;
            color: white;
            padding: 8px;
            border-radius: 50%;
            margin-right: 8px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(139, 195, 74, 0.3);
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            background: #8BC34A;
            color: white;
            padding: 6px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(139, 195, 74, 0.3);
        }

        .admin-badge i {
            margin-right: 6px;
            font-size: 0.85rem;
        }

        .login-title {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .login-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-weight: 500;
            color: #333;
        }

        .form-input::placeholder {
            color: #999;
            font-weight: 400;
        }

        .form-input:focus {
            outline: none;
            border-color: #8BC34A;
            box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.15);
            background: white;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1rem;
            pointer-events: none;
            z-index: 5;
        }

        .form-group.has-label .input-icon {
            top: calc(50% + 12px);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            font-size: 1rem;
            z-index: 5;
            transition: color 0.3s;
        }

        .form-group.has-label .password-toggle {
            top: calc(50% + 12px);
        }

        .password-toggle:hover {
            color: #8BC34A;
            transform: translateY(-50%) scale(1.1);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .remember-me input[type="checkbox"] {
            margin-right: 12px;
            accent-color: #8BC34A;
            transform: scale(1.2);
        }

        .forgot-link {
            color: #8BC34A;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 15px;
        }

        .forgot-link:hover {
            color: #7CB342;
            background: rgba(139, 195, 74, 0.1);
            transform: translateY(-2px);
        }

        .login-btn {
            width: 100%;
            background: #8BC34A;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(139, 195, 74, 0.3);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(139, 195, 74, 0.4);
            background: #7CB342;
        }

        .login-btn:active {
            transform: translateY(-2px) scale(1.01);
        }

        .alert {
            padding: 20px 25px;
            border-radius: 20px;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.4s ease;
            font-weight: 500;
        }

        .alert i {
            margin-right: 15px;
            font-size: 1.3rem;
        }

        .alert-error {
            background: #ffe6e6;
            color: #cc0000;
            border: 2px solid #ffcccc;
        }

        .alert-success {
            background: #e6f7e6;
            color: #008000;
            border: 2px solid #ccffcc;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-home a {
            color: #666;
            text-decoration: none;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 15px;
        }

        .back-home a:hover {
            color: #8BC34A;
            background: rgba(139, 195, 74, 0.1);
            transform: translateY(-2px);
        }

        .back-home a i {
            margin-right: 10px;
        }

        .security-note {
            background: rgba(139, 195, 74, 0.1);
            border: 2px solid rgba(139, 195, 74, 0.3);
            border-radius: 15px;
            padding: 15px;
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
        }

        .security-note i {
            color: #8BC34A;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .login-container {
                max-width: 90%;
                padding: 2rem 1.5rem;
            }

            .login-title {
                font-size: 1.3rem;
            }

            .logo {
                font-size: 1.3rem;
            }

            .logo i {
                padding: 7px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 15px;
                padding: 1.5rem 1.2rem;
                max-width: 95%;
            }

            .logo {
                font-size: 1.2rem;
            }

            .login-title {
                font-size: 1.2rem;
            }

            .form-input {
                padding: 12px 16px 12px 40px;
                font-size: 0.85rem;
            }

            .input-icon {
                left: 14px;
                font-size: 0.9rem;
            }

            .password-toggle {
                right: 14px;
                font-size: 0.9rem;
            }

            .remember-forgot {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .login-btn {
                padding: 12px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 320px) {
            .login-container {
                margin: 10px;
                padding: 1.2rem 1rem;
            }
        }

        /* Loading state */
        .login-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .login-btn.loading::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            border: 3px solid transparent;
            border-top: 3px solid white;
            border-radius: 50%;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }

        /* Focus states for better accessibility */
        .form-input.focused {
            border-color: #8BC34A;
            box-shadow: 0 0 0 6px rgba(139, 195, 74, 0.15);
        }
    </style>
</head>
<body>
    <div class="bg-decoration">
        <div class="paw-print paw-1"></div>
        <div class="paw-print paw-2"></div>
        <div class="paw-print paw-3"></div>
        <div class="paw-print paw-4"></div>
        <div class="paw-print paw-5"></div>
        <div class="paw-print paw-6"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-paw"></i>
                <span>MavetCare</span>
            </div>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i>
                Admin Access
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to access the admin dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group has-label">
                <label class="form-label" for="username">Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                </div>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    placeholder="admin"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group has-label">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="••••••••••••"
                    required
                    autocomplete="current-password"
                >
                <div class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" id="passwordToggleIcon"></i>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn" id="loginButton">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                Sign In
            </button>
        </form>

        <div class="security-note">
            <i class="fas fa-lock"></i>
            This is a secure admin area. All login attempts are monitored and logged.
        </div>

        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginButton = document.getElementById('loginButton');
            loginButton.classList.add('loading');
            loginButton.innerHTML = '<i class="fas fa-sign-in-alt" style="margin-right: 10px;"></i>Signing In...';
        });

        // Auto-hide alerts after 6 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-15px)';
                setTimeout(function() {
                    alert.remove();
                }, 400);
            });
        }, 6000);

        // Add input focus effects
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
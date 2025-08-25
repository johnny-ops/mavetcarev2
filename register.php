<?php

// config.php - Database connection (same as login.php)
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

session_name('user_session');
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Read flash success (PRG)
if (!empty($_SESSION['reg_success'])) {
    $success_message = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = 'client'; // Default user type
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = 'An account with this email already exists.';
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, user_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password, $user_type])) {
                    // Set flash and redirect to avoid resubmission on refresh
                    $_SESSION['reg_success'] = 'Account created successfully! You can now log in.';
                    header('Location: register.php');
                    exit();
                } else {
                    $error_message = 'Registration failed. Please try again.';
                }
            }
        } catch(PDOException $e) {
            $error_message = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MavetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/register.css">
</head>
<body>
    <div class="register-container">
       
        <div class="register-form-section">
            <div class="register-form-wrapper">
                <div class="logo-section">
                    <i class="fas fa-paw"></i>
                    <h1>MavetCare</h1>
                    <p>Professional Pet Care Management</p>
                </div>

                <div class="form-container">
                    <div class="form-header">
                        <h2>Create Your Account</h2>
                        <p>Join our community of pet care professionals</p>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                        </div>

                        <div class="form-group full-width">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                        </div>

                        <button type="submit" class="register-btn">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>

                    <div class="divider">
                        <span>Already have an account?</span>
                    </div>

                    <div class="form-footer">
                        <a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Sign In Here
                        </a>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="pet-section">
            <div class="pet-illustration">
                <div class="main-pet">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="welcome-text">
                    <h3>Welcome to MavetCare</h3>
                    <p>Your trusted partner in professional pet care management. Join thousands of pet care professionals who trust us.</p>
                </div>
                <div class="floating-elements">
                    <i class="fas fa-paw floating-paw"></i>
                    <i class="fas fa-paw floating-paw"></i>
                    <i class="fas fa-paw floating-paw"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling;
            
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

 
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });

    
        <?php if (!empty($success_message)): ?>
            setTimeout(function() {
                const successMsg = document.querySelector('.success-message');
                if (successMsg) {
                    successMsg.style.opacity = '0';
                    setTimeout(() => successMsg.remove(), 300);
                }
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
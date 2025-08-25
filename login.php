<?php
// config.php - Database connection


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

    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please fill in all fields.';
        } else {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, user_type FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Set success message and redirect to index.php for all users after successful login
                    $_SESSION['flash_success'] = 'Welcome back, ' . $user['first_name'] . '! You have successfully logged in.';
                    header("Location: index.php");
                    exit();
                } else {
                    $error_message = 'Invalid email or password.';
                }
            } catch(PDOException $e) {
                $error_message = 'Login failed. Please try again.';
            }
        }
    }



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MavetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Back to Home Button -->
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>

        <!-- Left Side - Login Form -->
        <div class="login-form-section">
            <div class="login-form-wrapper">
                <div class="logo-section">
                    <i class="fas fa-paw"></i>
                    <h1>MavetCare</h1>
                    <p>Welcome back to your pet care portal</p>
                </div>

                <div class="form-container">
                    <div class="form-header">
                        <h2>Sign In</h2>
                        <p>Enter your credentials to access your account</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i class="fas fa-envelope"></i>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                        </div>

                        <button type="submit" class="login-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </button>
                    </form>

                    <div class="divider">
                        <span>Don't have an account?</span>
                    </div>

                    <div class="form-footer">
                        <a href="register.php">Create New Account</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Pet Illustration -->
        <div class="pet-section">
            <div class="floating-paws">
                <div class="paw"></div>
                <div class="paw"></div>
                <div class="paw"></div>
                <div class="paw"></div>
                <div class="paw"></div>
            </div>
            
            <div class="pet-illustration">
                <div class="main-pet"></div>
                <div class="pet-accessory"></div>
                <div class="medical-tools"></div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
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
    </script>
</body>
</html>
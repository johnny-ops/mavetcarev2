<?php
session_name('user_session');
session_start();

// Database connection
$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

$pdo = null;
$db_error = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $db_error = true;
    $error_message = "Database connection failed: " . $e->getMessage();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$db_error) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = 'First name, last name, and email are required.';
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error_message = 'Email already exists.';
            } else {
                // Update basic info
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $user_id]);
                
                // Update session data
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
                
                // Handle password change if provided
                if (!empty($current_password) && !empty($new_password)) {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($current_password, $user['password'])) {
                        if ($new_password === $confirm_password) {
                            if (strlen($new_password) >= 6) {
                                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                                $stmt->execute([$hashed_password, $user_id]);
                                $success_message = 'Profile and password updated successfully!';
                            } else {
                                $error_message = 'New password must be at least 6 characters long.';
                            }
                        } else {
                            $error_message = 'New passwords do not match.';
                        }
                    } else {
                        $error_message = 'Current password is incorrect.';
                    }
                } else {
                    $success_message = 'Profile updated successfully!';
                }
            }
        } catch(PDOException $e) {
            $error_message = 'Update failed. Please try again.';
        }
    }
}

// Get current user data
$user = null;
if (!$db_error) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone, user_type, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error_message = 'Failed to load user data.';
    }
}

// If no user data found, use session data as fallback
if (!$user) {
    $user = [
        'first_name' => $_SESSION['user_name'] ? explode(' ', $_SESSION['user_name'])[0] : 'User',
        'last_name' => $_SESSION['user_name'] ? (explode(' ', $_SESSION['user_name'])[1] ?? '') : '',
        'email' => $_SESSION['user_email'] ?? 'email@example.com',
        'phone' => '',
        'user_type' => $_SESSION['user_type'] ?? 'client',
        'created_at' => date('Y-m-d H:i:s')
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MavetCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/profile.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>

        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <?php if ($db_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-database"></i>
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                <br><small>Please check if XAMPP is running and the database exists.</small>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message && !$db_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <h2 class="card-title">Profile Information</h2>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <div class="password-section">
                    <h3 style="margin-bottom: 20px; color: #333;">Change Password (Optional)</h3>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <div class="profile-card">
            <h2 class="card-title">Account Information</h2>
            
            <div class="info-row">
                <span class="info-label">Account Type:</span>
                <span class="info-value"><?php echo ucfirst($user['user_type']); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Member Since:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Account ID:</span>
                <span class="info-value">#<?php echo $user_id; ?></span>
            </div>
        </div>
    </div>
</body>
</html>

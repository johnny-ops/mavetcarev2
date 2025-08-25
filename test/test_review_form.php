<?php
session_name('user_session');
session_start();

// Database connection
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

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Handle review submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    $rating = $_POST['rating'] ?? 0;
    $review_text = trim($_POST['review_text'] ?? '');
    $pet_name = trim($_POST['pet_name'] ?? '');
    $pet_type = $_POST['pet_type'] ?? '';
    $service_received = trim($_POST['service_received'] ?? '');
    
    if ($rating >= 1 && $rating <= 5 && !empty($review_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO testimonials (client_name, pet_name, pet_type, rating, review_text, service_received, is_featured, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$userName, $pet_name, $pet_type, $rating, $review_text, $service_received]);
            $success_message = "Thank you for your review! It has been submitted successfully.";
        } catch(PDOException $e) {
            $error_message = "Error submitting review: " . $e->getMessage();
        }
    } else {
        $error_message = "Please provide a valid rating (1-5) and review text.";
    }
}

// Fetch recent reviews
try {
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 5");
    $reviews = $stmt->fetchAll();
} catch(PDOException $e) {
    $reviews = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Form Test - MavetCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="responsive.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
        }
        
        .status {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .review-form {
            margin-bottom: 3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8BC34A;
        }
        
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            gap: 0.25rem;
        }
        
        .rating-input input[type="radio"] {
            display: none;
        }
        
        .star-label {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd;
            transition: color 0.3s;
        }
        
        .star-label:hover,
        .star-label:hover ~ .star-label,
        .rating-input input[type="radio"]:checked ~ .star-label {
            color: #FFD700;
        }
        
        .char-count {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .submit-review-btn {
            background: #8BC34A;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 auto;
        }
        
        .submit-review-btn:hover {
            background: #7CB342;
        }
        
        .reviews-list {
            margin-top: 2rem;
        }
        
        .review-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .reviewer-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .review-service {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-rating {
            color: #FFD700;
        }
        
        .review-text {
            color: #333;
            line-height: 1.6;
        }
        
        .login-prompt {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .login-link {
            background: #8BC34A;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
            display: inline-block;
            margin-top: 1rem;
        }
        
        .login-link:hover {
            background: #7CB342;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Review Form Test</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="status success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="status error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="review-form">
            <h2>Submit a Review</h2>
            
            <?php if ($isLoggedIn): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="rating">Your Rating *</label>
                        <div class="rating-input">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5" class="star-label"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4" class="star-label"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3" class="star-label"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2" class="star-label"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1" class="star-label"><i class="fas fa-star"></i></label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pet_name">Pet's Name</label>
                            <input type="text" id="pet_name" name="pet_name" placeholder="Enter your pet's name">
                        </div>
                        
                        <div class="form-group">
                            <label for="pet_type">Pet Type</label>
                            <select id="pet_type" name="pet_type">
                                <option value="">Select pet type</option>
                                <option value="Dog">Dog</option>
                                <option value="Cat">Cat</option>
                                <option value="Bird">Bird</option>
                                <option value="Rabbit">Rabbit</option>
                                <option value="Hamster">Hamster</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_received">Service Received</label>
                        <input type="text" id="service_received" name="service_received" placeholder="e.g., General Checkup, Vaccination, Surgery, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="review_text">Your Review *</label>
                        <textarea id="review_text" name="review_text" rows="5" placeholder="Share your experience with MavetCare..." required></textarea>
                        <div class="char-count">
                            <span id="char-count">0</span> / 500 characters
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-review-btn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Review
                    </button>
                </form>
            <?php else: ?>
                <div class="login-prompt">
                    <h3>Login Required</h3>
                    <p>You must be logged in to submit a review.</p>
                    <a href="login.php" class="login-link">
                        <i class="fas fa-sign-in-alt"></i>
                        Log In to Review
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="reviews-list">
            <h2>Recent Reviews</h2>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <h4><?php echo htmlspecialchars($review['client_name']); ?></h4>
                                <div class="review-service">
                                    <?php echo htmlspecialchars($review['service_received']); ?>
                                    <?php if (!empty($review['pet_name'])): ?>
                                        - <?php echo htmlspecialchars($review['pet_name']); ?> (<?php echo htmlspecialchars($review['pet_type']); ?>)
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $review['rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No reviews yet. Be the first to leave a review!</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="reviews.php" style="color: #8BC34A; text-decoration: none;">
                <i class="fas fa-arrow-left"></i>
                Back to Reviews Page
            </a>
        </div>
    </div>
    
    <script>
        // Character counter for review text
        const reviewText = document.getElementById('review_text');
        const charCount = document.getElementById('char-count');
        
        if (reviewText && charCount) {
            reviewText.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                
                if (length > 450) {
                    charCount.style.color = '#dc3545';
                } else if (length > 400) {
                    charCount.style.color = '#ffc107';
                } else {
                    charCount.style.color = '#666';
                }
            });
        }
        
        // Form validation
        const reviewForm = document.querySelector('form');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                const rating = document.querySelector('input[name="rating"]:checked');
                const reviewText = document.getElementById('review_text');
                
                if (!rating) {
                    e.preventDefault();
                    alert('Please select a rating.');
                    return false;
                }
                
                if (!reviewText.value.trim()) {
                    e.preventDefault();
                    alert('Please write your review.');
                    return false;
                }
                
                if (reviewText.value.length > 500) {
                    e.preventDefault();
                    alert('Review text must be 500 characters or less.');
                    return false;
                }
            });
        }
    </script>
</body>
</html>


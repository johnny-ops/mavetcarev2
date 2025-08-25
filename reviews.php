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

// Fetch reviews from database
try {
    $stmt = $pdo->query("
        SELECT * FROM testimonials 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $reviews = $stmt->fetchAll();
} catch(PDOException $e) {
    // Fallback to sample data if database error
$reviews = [
    [
            'client_name' => 'Sarah M.',
        'rating' => 5,
        'review_text' => 'Amazing service! The staff is incredibly caring and professional. My dog was nervous but they made him feel so comfortable.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
            'service_received' => 'General Checkup',
            'pet_name' => 'Buddy',
            'pet_type' => 'Dog'
        ],
        [
            'client_name' => 'John D.',
        'rating' => 5,
        'review_text' => 'They take care of my pets so well. The place is good!',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
            'service_received' => 'Vaccination',
            'pet_name' => 'Whiskers',
            'pet_type' => 'Cat'
        ],
        [
            'client_name' => 'Maria L.',
        'rating' => 4,
        'review_text' => 'They take care of my pets so well',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
            'service_received' => 'Pet Grooming',
            'pet_name' => 'Max',
            'pet_type' => 'Dog'
        ],
        [
            'client_name' => 'Alex R.',
        'rating' => 5,
        'review_text' => 'I think the place is good!',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'service_received' => 'Emergency Care',
            'pet_name' => 'Luna',
            'pet_type' => 'Cat'
        ]
    ];
}

// Calculate average rating
$totalRating = array_sum(array_column($reviews, 'rating'));
$averageRating = count($reviews) > 0 ? $totalRating / count($reviews) : 0;

// Count ratings distribution
$ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $review) {
    $ratingCounts[$review['rating']]++;
}

// Helper function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        return 'Today';
    } elseif ($diff->days == 1) {
        return 'Yesterday';
    } elseif ($diff->days < 7) {
        return $diff->days . ' days ago';
    } elseif ($diff->days < 30) {
        $weeks = floor($diff->days / 7);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        $months = floor($diff->days / 30);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAW-SITIVE REVIEWS - MavetCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="responsive.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/reviews.css">
</head>
<body>
    
    <header>
        <nav class="container">
            <div class="logo">
                <i class="fas fa-paw"></i>
                <span>MavetCare</span>
            </div>
            <button class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-links" id="navLinks">
                 <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="reviews.php" class="active">Reviews</a></li>
                <li>
                    <?php if ($isLoggedIn): ?>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-name" onclick="toggleUserDropdown()">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($userName); ?>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="dropdown-menu">
                                <?php if ($userType == 'admin'): ?>
                                    <a href="admin_dashboard.php" class="dropdown-item">
                                        <i class="fas fa-tachometer-alt"></i>
                                        Admin Dashboard
                                    </a>
                                <?php else: ?>
                                    <a href="dashboard.php" class="dropdown-item">
                                        <i class="fas fa-tachometer-alt"></i>
                                        Dashboard
                                    </a>
                                <?php endif; ?>
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user-cog"></i>
                                    Profile
                                </a>
                                                            <a href="myAppointments.php" class="dropdown-item">
                                <i class="fas fa-calendar-alt"></i>
                                My Appointments
                            </a>
                                <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                                <button onclick="logout()" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="login-btn">Log in</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <h1>PAW-SITIVE REVIEWS</h1>
                    <p class="header-subtitle">From loving pet parents</p>
                    <a href="#write-review" class="read-more-btn">Read More</a>
                </div>
                <div class="header-image"></div>
            </div>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <!-- Ratings Overview -->
                <div class="ratings-overview">
                    <div class="rating-header">
                        <div class="admin-badge">A</div>
                        <div class="rating-summary">
                            <h3>What do you think?</h3>
                            <div class="average-rating">
                                <span class="rating-number"><?php echo number_format($averageRating, 1); ?></span>
                                <div class="stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= floor($averageRating)) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $averageRating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <p class="rating-subtitle">Please log in to write a review</p>
                        </div>
                    </div>

                    <div class="rating-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php 
                            $count = $ratingCounts[$i];
                            $percentage = count($reviews) > 0 ? ($count / count($reviews)) * 100 : 0;
                            ?>
                            <div class="rating-bar">
                                <span class="star-label"><?php echo $i; ?> stars</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="percentage"><?php echo round($percentage); ?>%</span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="reviews-list">
                    <div class="reviews-list-header">
                        <h3>Ratings and Reviews</h3>
                    </div>
                    
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['client_name']); ?></div>
                                    <div class="review-service">
                                        <?php echo htmlspecialchars($review['service_received']); ?>
                                        <?php if (!empty($review['pet_name'])): ?>
                                            - <?php echo htmlspecialchars($review['pet_name']); ?> (<?php echo htmlspecialchars($review['pet_type']); ?>)
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
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
                                    <div class="review-date">Posted <?php echo formatDate($review['created_at']); ?></div>
                                </div>
                            </div>
                            <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Write a Review Section -->
            <div class="write-review" id="write-review">
                <h3>Write a Review</h3>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                        <button class="close-message" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                        <button class="close-message" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if ($isLoggedIn): ?>
                    <p>Share your experience with MavetCare and help other pet parents make informed decisions.</p>
                    
                    <form class="review-form" method="POST" action="#write-review">
                        <div class="form-group">
                            <label for="rating">Your Rating *</label>
                            <div class="rating-input">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" class="star-label"><i class="far fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" class="star-label"><i class="far fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" class="star-label"><i class="far fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" class="star-label"><i class="far fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" class="star-label"><i class="far fa-star"></i></label>
                    </div>
                            <div class="rating-text">Click to rate</div>
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
                        <div class="login-prompt-content">
                            <i class="fas fa-user-lock"></i>
                            <h4>Login Required</h4>
                            <p>You must be logged in to submit a review.</p>
                            <a href="login.php" class="login-link">
                                <i class="fas fa-sign-in-alt"></i>
                                Log In to Review
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3 class="section-title">Quick Links</h3>
                <ul class="quick-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="reviews.php">Reviews</a></li>
                </ul>
            </div>

            <div class="divider"></div>

            <div class="footer-section center">
                <div class="logo-section">
                    <div class="footer-logo">
                        <div class="paw-icon"></div>
                        <span class="brand-name">MaVetCare</span>
                    </div>
                    <p class="tagline">Leave your pets in safe hands.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="footer-section right">
                <h3 class="section-title">Get in Touch!</h3>
                <div class="contact-section">
                    <div class="email-display">
                        <span>mavetcare@email.com</span>
                        <div class="paw-btn"></div>
                    </div>
                    <div class="phone-info">
                        <div class="phone-icon">
                            <i class="fa-solid fa-mobile"></i>
                        </div>
                        <span>+63 123 456 7890</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pet-images-footer">
            <div class="cat-image"></div>
            <div class="dog-image"></div>
        </div>

        <div class="copyright">
            All Rights Reserved to <strong>Mavetcare 2025</strong>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.querySelector('.menu-toggle i');
            
            navLinks.classList.toggle('active');
            
            if (navLinks.classList.contains('active')) {
                menuToggle.classList.remove('fa-bars');
                menuToggle.classList.add('fa-times');
            } else {
                menuToggle.classList.remove('fa-times');
                menuToggle.classList.add('fa-bars');
            }
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                const navLinks = document.getElementById('navLinks');
                const menuToggle = document.querySelector('.menu-toggle i');
                
                navLinks.classList.remove('active');
                menuToggle.classList.remove('fa-times');
                menuToggle.classList.add('fa-bars');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            const nav = document.querySelector('nav');
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.querySelector('.menu-toggle i');
            const userDropdown = document.getElementById('userDropdown');
            
            // Close mobile menu when clicking outside
            if (!nav.contains(e.target)) {
                navLinks.classList.remove('active');
                menuToggle.classList.remove('fa-times');
                menuToggle.classList.add('fa-bars');
            }
            
            // Close user dropdown when clicking outside
            if (userDropdown && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate rating bars on scroll
        function animateRatingBars() {
            const bars = document.querySelectorAll('.bar-fill');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const bar = entry.target;
                        const width = bar.style.width;
                        bar.style.width = '0%';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 200);
                        observer.unobserve(bar);
                    }
                });
            }, { threshold: 0.5 });

            bars.forEach(bar => observer.observe(bar));
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateRatingBars();
            
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
            
            // Enhanced star rating system
            const ratingInputs = document.querySelectorAll('input[name="rating"]');
            const ratingText = document.querySelector('.rating-text');
            const ratingLabels = document.querySelectorAll('.star-label');
            
            if (ratingInputs.length > 0 && ratingText) {
                const ratingDescriptions = {
                    1: 'Poor - Not satisfied',
                    2: 'Fair - Could be better',
                    3: 'Good - Satisfied',
                    4: 'Very Good - Happy with service',
                    5: 'Excellent - Outstanding experience'
                };
                
                ratingInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        const rating = this.value;
                        ratingText.textContent = ratingDescriptions[rating] || 'Click to rate';
                        
                        // Update star colors
                        ratingLabels.forEach((label, index) => {
                            if (index < rating) {
                                label.querySelector('i').className = 'fas fa-star';
                            } else {
                                label.querySelector('i').className = 'far fa-star';
                            }
                        });
                    });
                });
                
                // Hover effects for stars
                ratingLabels.forEach((label, index) => {
                    label.addEventListener('mouseenter', function() {
                        const hoverRating = index + 1;
                        ratingText.textContent = ratingDescriptions[hoverRating] || 'Click to rate';
                        
                        ratingLabels.forEach((starLabel, starIndex) => {
                            if (starIndex < hoverRating) {
                                starLabel.querySelector('i').className = 'fas fa-star';
                            } else {
                                starLabel.querySelector('i').className = 'far fa-star';
                            }
                        });
                    });
                    
                    label.addEventListener('mouseleave', function() {
                        const selectedRating = document.querySelector('input[name="rating"]:checked');
                        if (selectedRating) {
                            const rating = selectedRating.value;
                            ratingText.textContent = ratingDescriptions[rating] || 'Click to rate';
                            
                            ratingLabels.forEach((starLabel, starIndex) => {
                                if (starIndex < rating) {
                                    starLabel.querySelector('i').className = 'fas fa-star';
                                } else {
                                    starLabel.querySelector('i').className = 'far fa-star';
                                }
                            });
                        } else {
                            ratingText.textContent = 'Click to rate';
                            ratingLabels.forEach(starLabel => {
                                starLabel.querySelector('i').className = 'far fa-star';
                            });
                        }
                    });
                });
            }
            
            // Form validation with better UX
            const reviewForm = document.querySelector('.review-form');
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    const rating = document.querySelector('input[name="rating"]:checked');
                    const reviewText = document.getElementById('review_text');
                    
                    if (!rating) {
                        e.preventDefault();
                        showValidationMessage('Please select a rating.', 'error');
                        return false;
                    }
                    
                    if (!reviewText.value.trim()) {
                        e.preventDefault();
                        showValidationMessage('Please write your review.', 'error');
                        return false;
                    }
                    
                    if (reviewText.value.length > 500) {
                        e.preventDefault();
                        showValidationMessage('Review text must be 500 characters or less.', 'error');
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = this.querySelector('.submit-review-btn');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                    submitBtn.disabled = true;
                    
                    // Re-enable after a short delay (in case of validation failure)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 2000);
                });
            }
            
            // Auto-hide success/error messages after 5 seconds
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(message => {
                setTimeout(() => {
                    if (message.style.display !== 'none') {
                        message.style.opacity = '0';
                        setTimeout(() => {
                            message.style.display = 'none';
                        }, 300);
                    }
                }, 5000);
            });
        });
        
        // Function to show validation messages
        function showValidationMessage(message, type) {
            const existingMessage = document.querySelector('.validation-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `validation-message ${type}-message`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                ${message}
                <button class="close-message" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            const form = document.querySelector('.review-form');
            if (form) {
                form.insertBefore(messageDiv, form.firstChild);
                
                // Auto-remove after 3 seconds
                setTimeout(() => {
                    if (messageDiv.parentElement) {
                        messageDiv.style.opacity = '0';
                        setTimeout(() => {
                            messageDiv.remove();
                        }, 300);
                    }
                }, 3000);
            }
        }
    </script>
</body>
</html>
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MavetCare - Veterinary Medical Clinic</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="responsive.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-success container">
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>
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
                <li><a href="#" class="active">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="reviews.php">Reviews</a></li>
                <li>
                    <?php if ($isLoggedIn): ?>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-name" onclick="toggleUserDropdown()">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($userName); ?>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="dropdown-menu">
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user-cog"></i>
                                    Profile
                                </a>
                                                            <a href="myAppointments.php" class="dropdown-item">
                                <i class="fas fa-calendar-alt"></i>
                                My Appointments
                            </a>
                            <?php if ($userType === 'admin'): ?>
                                <a href="dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                            <?php endif; ?>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Welcome to <span class="highlight">MavetCare</span></h1>
                    <div class="subtitle">Compassionate Care for Your Beloved Pets</div>
                    <p>Professional veterinary services with a personal touch. We're here to keep your furry family members healthy and happy.</p>
                    <div class="cta-buttons">
                        <?php if ($isLoggedIn): ?>
                            <a href="myAppointments.php" class="btn-primary">
                                <i class="fas fa-calendar-plus"></i> Book Appointment
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn-primary">
                                <i class="fas fa-user-plus"></i> Get Started
                            </a>
                            <a href="login.php" class="btn-secondary">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="pet-images">
                        <div class="pet-main"></div>
                        <div class="pet-cat"></div>
                        <div class="stethoscope"></div>
                        <div class="decorative-elements">
                            <div class="paw-print paw-1"></div>
                            <div class="paw-print paw-2"></div>
                            <div class="paw-print paw-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Section -->
    <section class="info-section">
        <div class="container">
            <div class="info-header">
                <h2>Get to Know Us</h2>
            </div>
            <div class="info-grid">
                <div class="info-card explore-card">
                    <h3>Find Us</h3>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2719.1230993689783!2d123.91259010851019!3d10.313797789765662!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a9991428526863%3A0x19e6a6c42181f51!2sFC%20Mabolo%20Animal%20Clinic!5e1!3m2!1sfil!2sph!4v1755680443666!5m2!1sfil!2sph" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                <div class="info-card address-card">
                    <h3>Visit Us</h3>
                    <div class="address-text">
                        <strong>Mabolo Veterinary Clinic</strong><br>
                        123 Pet Care Street<br>
                        Mabolo, Cebu City
                    </div>
                    <div class="contact-info">
                        <div><i class="fas fa-phone"></i> +63 123 456 7890</div>
                        <div><i class="fas fa-envelope"></i> info@mavetcare.com</div>
                    </div>
                </div>
                <div class="info-card hours-card">
                    <h3>Hours</h3>
                    <div class="hours-list">
                        <div><span>Mon - Fri:</span><span>9AM - 6PM</span></div>
                        <div><span>Saturday:</span><span>9AM - 3PM</span></div>
                        <div><span>Sunday:</span><span>Closed</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Footer -->
    <footer class="footer">
        <div class="footer-content">
                            <div class="footer-section">
                    <h3 class="section-title">Quick Links</h3>
                    <ul class="quick-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="services.php">Services</a></li>
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
    </script>
</body>
</html>
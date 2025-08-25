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

// Fetch services from admin services table
try {
    $stmt = $pdo->query('SELECT * FROM services ORDER BY service_name');
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no services found, show empty array
    if (!$services) {
        $services = [];
    }
} catch (PDOException $e) {
    // If table doesn't exist or error, use empty array
    $services = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - MavetCare Veterinary Clinic</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/services.css">
</head>
<body>
    <!-- Header -->
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
                <li><a href="services.php" class="active">Services</a></li>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Look for the services that you need.</h1>
                    <p>Whether it's a check-up or a specialized service, we've got your furry friend covered.</p>
                </div>
                <div class="hero-image">
                   <img src="images/download__1_-removebg-preview.png" alt="" srcset="">
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section">
        <div class="container">
            <?php if (empty($services)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #666;">
                    <i class="fas fa-cogs" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                    <h3>No Services Available</h3>
                    <p>Services will be added by our admin team soon.</p>
                </div>
            <?php else: ?>
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-image <?php echo strtolower(str_replace(' ', '-', $service['category'] ?? 'general')); ?>">
                                <div class="service-price">₱<?php echo number_format($service['price'] ?? 0, 2); ?></div>
                            </div>
                            <div class="service-content">
                                <h3><?php echo htmlspecialchars($service['service_name'] ?? 'Service'); ?></h3>
                                <p><?php echo htmlspecialchars($service['description'] ?? 'Professional veterinary service'); ?></p>
                                <?php if (!empty($service['duration'])): ?>
                                    <div class="service-duration">
                                        <i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($service['duration']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="quick-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="#products">Products</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <div class="footer-logo">
                        <div class="paw-icon"></div>
                        <span class="brand-name">MaVetCare</span>
                    </div>
                    <p style="color: #333; margin-bottom: 1rem;">Leave your pets in safe hands.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Get in Touch!</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>mavetcare@email.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+63 123 456 7890</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Mabolo, Cebu City</span>
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
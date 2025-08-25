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
        <title>About Us - MavetCare Veterinary Medical Clinic</title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="responsive.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/about.css">
    </head>
    <body>
        <main>
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
                <li><a href="about.php" class="active">About</a></li>
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

            <!-- About Hero Section -->
            <section class="about-hero">
                <div class="container">
                    <h1>About <span class="highlight">MavetCare</span></h1>
                    <p>We are a team of dedicated veterinary professionals committed to providing exceptional care for your beloved pets. With years of experience and a passion for animal welfare, we strive to ensure your furry family members receive the best possible treatment in a comfortable, caring environment.</p>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section class="company-info">
                <div class="container">
                    <div class="section-header">
                        <h2>What Our Clients Say</h2>
                        <p>Real testimonials from pet owners who trust us with their beloved companions.</p>
                    </div>
                    <div class="testimonials-grid">
                        <?php
                        // Fetch featured testimonials from database
                        try {
                            $stmt = $pdo->query("
                                SELECT * FROM testimonials 
                                WHERE is_featured = 1 
                                ORDER BY rating DESC, created_at DESC 
                                LIMIT 4
                            ");
                            $testimonials = $stmt->fetchAll();
                        } catch(PDOException $e) {
                            $testimonials = [];
                        }
                        
                        if (empty($testimonials)) {
                            // Fallback testimonials if database is empty
                            $testimonials = [
                                [
                                    'client_name' => 'Sarah M.',
                                    'pet_name' => 'Buddy',
                                    'pet_type' => 'Dog',
                                    'rating' => 5,
                                    'review_text' => 'Amazing service! The staff is incredibly caring and professional. My dog was nervous but they made him feel so comfortable.',
                                    'service_received' => 'General Checkup'
                                ],
                                [
                                    'client_name' => 'John D.',
                                    'pet_name' => 'Whiskers',
                                    'pet_type' => 'Cat',
                                    'rating' => 5,
                                    'review_text' => 'They take care of my pets so well. The place is clean, the staff is friendly, and the doctors are very knowledgeable.',
                                    'service_received' => 'Vaccination'
                                ],
                                [
                                    'client_name' => 'Maria L.',
                                    'pet_name' => 'Max',
                                    'pet_type' => 'Dog',
                                    'rating' => 4,
                                    'review_text' => 'Great experience with the grooming service. My dog looks and smells amazing! The staff was patient and gentle.',
                                    'service_received' => 'Pet Grooming'
                                ],
                                [
                                    'client_name' => 'Alex R.',
                                    'pet_name' => 'Luna',
                                    'pet_type' => 'Cat',
                                    'rating' => 5,
                                    'review_text' => 'Emergency care was outstanding. They took care of my cat immediately and kept me informed throughout the process.',
                                    'service_received' => 'Emergency Care'
                                ]
                            ];
                        }
                        
                        foreach ($testimonials as $testimonial): ?>
                            <div class="testimonial-card">
                                <div class="testimonial-header">
                                    <div class="client-info">
                                        <h4><?php echo htmlspecialchars($testimonial['client_name']); ?></h4>
                                        <p class="pet-info"><?php echo htmlspecialchars($testimonial['pet_name']); ?> (<?php echo htmlspecialchars($testimonial['pet_type']); ?>)</p>
                                        <p class="service-info"><?php echo htmlspecialchars($testimonial['service_received']); ?></p>
                                    </div>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="testimonial-content">
                                    <p>"<?php echo htmlspecialchars($testimonial['review_text']); ?>"</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section class="services-section">
                <div class="container">
                    <div class="section-header">
                        <h2>What We Offer</h2>
                        <p>Comprehensive veterinary services to keep your pets healthy and happy throughout their lives.</p>
                    </div>
                    <div class="services-grid">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <h3>Wellness Exams</h3>
                            <p>Regular check-ups and preventive care to maintain your pet's optimal health and catch potential issues early.</p>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-syringe"></i>
                            </div>
                            <h3>Vaccinations</h3>
                            <p>Complete vaccination programs to protect your pets from common diseases and ensure they stay healthy.</p>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-cut"></i>
                            </div>
                            <h3>Surgery</h3>
                            <p>Advanced surgical procedures performed in our modern operating room with the highest safety standards.</p>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-tooth"></i>
                            </div>
                            <h3>Dental Care</h3>
                            <p>Professional dental cleaning, treatment, and oral health maintenance for your pet's overall wellbeing.</p>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-x-ray"></i>
                            </div>
                            <h3>Diagnostics</h3>
                            <p>State-of-the-art diagnostic equipment including X-rays, ultrasound, and laboratory testing services.</p>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-ambulance"></i>
                            </div>
                            <h3>Emergency Care</h3>
                            <p>24/7 emergency services for urgent medical situations when your pet needs immediate attention.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Products Section -->
            <section class="products-section">
                <div class="container">
                    <div class="section-header">
                        <h2>Featured Products</h2>
                        <p>High-quality pet care products from our inventory, recommended by our veterinary professionals.</p>
                    </div>
                    <div class="products-grid">
                        <?php
                        // Fetch featured products from database
                        try {
                            $stmt = $pdo->query("
                                SELECT * FROM inventory 
                                WHERE quantity > 0 
                                ORDER BY price DESC, created_at DESC 
                                LIMIT 4
                            ");
                            $products = $stmt->fetchAll();
                        } catch(PDOException $e) {
                            $products = [];
                        }
                        
                        if (empty($products)) {
                            // Fallback products if database is empty
                            $products = [
                                [
                                    'name' => 'Premium Dog Food',
                                    'category' => 'Food',
                                    'price' => 450.00,
                                    'product_image' => 'premium dog food.png'
                                ],
                                [
                                    'name' => 'Anti-Parasitic Medicine',
                                    'category' => 'Medicine',
                                    'price' => 250.00,
                                    'product_image' => 'anti parasitic.png'
                                ],
                                [
                                    'name' => 'Vaccine Vial',
                                    'category' => 'Medicine',
                                    'price' => 150.00,
                                    'product_image' => 'vaccine.png'
                                ],
                                [
                                    'name' => 'Pet Shampoo',
                                    'category' => 'Supplies',
                                    'price' => 120.00,
                                    'product_image' => 'pet shampoo.png'
                                ]
                            ];
                        }
                        
                        foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image" style="background-image: url('images/products/<?php echo htmlspecialchars($product['product_image'] ?? 'default-product.png'); ?>');">
                                    <?php if (empty($product['product_image'])): ?>
                                        <i class="fas fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($product['category']); ?> - High-quality product for your pet's health and wellbeing.</p>
                                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Meet Our Veterinarians Section -->
            <section class="team-section">
                <div class="container">
                    <div class="section-header">
                        <h2>Meet Our Veterinarians & Staff</h2>
                        <p>Our experienced team of veterinary professionals dedicated to providing exceptional care for your pets.</p>
                    </div>
                    <div class="team-grid">
                        <?php
                        // Fetch staff members from database
                        try {
                            $stmt = $pdo->query("
                                SELECT * FROM staff 
                                WHERE status = 'on-duty' 
                                ORDER BY position, name ASC 
                                LIMIT 6
                            ");
                            $staffMembers = $stmt->fetchAll();
                        } catch(PDOException $e) {
                            $staffMembers = [];
                        }
                        
                        if (empty($staffMembers)) {
                            // Fallback staff if database is empty
                            $staffMembers = [
                                [
                                    'name' => 'Dr. Maria Santos',
                                    'position' => 'Doctor',
                                    'schedule' => 'Mon-Fri 9AM-5PM',
                                    'email' => 'maria.santos@mavetcare.com',
                                    'phone' => '09123456789'
                                ],
                                [
                                    'name' => 'Dr. Juan Dela Cruz',
                                    'position' => 'Senior Doctor',
                                    'schedule' => 'Mon-Sat 8AM-6PM',
                                    'email' => 'juan.delacruz@mavetcare.com',
                                    'phone' => '09187654321'
                                ],
                                [
                                    'name' => 'Ana Reyes',
                                    'position' => 'Receptionist',
                                    'schedule' => 'Mon-Sat 8AM-6PM',
                                    'email' => 'ana.reyes@mavetcare.com',
                                    'phone' => '09234567890'
                                ],
                                [
                                    'name' => 'Pedro Martinez',
                                    'position' => 'Veterinary Assistant',
                                    'schedule' => 'Mon-Fri 8AM-5PM',
                                    'email' => 'pedro.martinez@mavetcare.com',
                                    'phone' => '09345678901'
                                ]
                            ];
                        }
                        
                        foreach ($staffMembers as $staff): ?>
                            <div class="team-card">
                                <div class="team-photo">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="team-info">
                                    <h3><?php echo htmlspecialchars($staff['name']); ?></h3>
                                    <div class="team-role"><?php echo htmlspecialchars($staff['position']); ?></div>
                                    <div class="team-schedule">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($staff['schedule']); ?></span>
                                    </div>
                                    <div class="team-contact">
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($staff['email']); ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($staff['phone']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section -->
            <section class="why-choose-section">
                <div class="container">
                    <div class="section-header">
                        <h2>Why Choose MavetCare?</h2>
                        <p>We're committed to providing the highest quality veterinary care with a personal touch.</p>
                    </div>
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3>Compassionate Care</h3>
                            <p>We treat every pet with love and respect, understanding that they're cherished family members who deserve the best care possible.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-microscope"></i>
                            </div>
                            <h3>Advanced Technology</h3>
                            <p>Our clinic is equipped with state-of-the-art medical equipment and diagnostic tools to provide accurate diagnoses and effective treatments.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3>Experienced Team</h3>
                            <p>Our veterinarians have decades of combined experience and participate in continuing education to stay current with the latest veterinary practices.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>Flexible Hours</h3>
                            <p>We offer extended hours and emergency services to accommodate your busy schedule and provide care when your pet needs it most.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h3>Affordable Pricing</h3>
                            <p>Quality veterinary care shouldn't break the bank. We offer competitive pricing and payment plans to make pet healthcare accessible.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <h3>Comfort-First Environment</h3>
                            <p>Our clinic is designed to reduce stress for both pets and owners, with separate areas for cats and dogs and calming amenities.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Hours & Contact Section -->
            <section class="hours-section">
                <div class="container">
                    <div class="section-header">
                        <h2>Our Hours & Contact</h2>
                        <p>We're here when you need us most. Visit us during our regular hours or call for emergency services.</p>
                    </div>
                    <div class="hours-content">
                        <div class="hours-info">
                            <h3>Clinic Hours</h3>
                            <div class="hours-list">
                                <div class="hours-item">
                                    <span>Monday</span>
                                    <span>9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span>Tuesday</span>
                                    <span>9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="hours-item today">
                                    <span>Wednesday</span>
                                    <span>9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span>Thursday</span>
                                    <span>9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span>Friday</span>
                                    <span>9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span>Saturday</span>
                                    <span>9:00 AM - 3:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span>Sunday</span>
                                    <span>Emergency Only</span>
                                </div>
                            </div>
                        </div>
                        <div class="contact-info-hours">
                            <h3>Get in Touch</h3>
                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Phone</h4>
                                    <p>+63 123 456 7890</p>
                                </div>
                            </div>
                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Email</h4>
                                    <p>info@mavetcare.com</p>
                                </div>
                            </div>
                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Address</h4>
                                    <p>123 Pet Care Street<br>Mabolo, Cebu City</p>
                                </div>
                            </div>
                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-ambulance"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Emergency</h4>
                                    <p>24/7 Emergency Line<br>+63 987 654 3210</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="section-title">Quick Links</h3>
                    <ul class="quick-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#products">Products</a></li>
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
                            <a href="https://facebook.com/mavetcare" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://instagram.com/mavetcare" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                            <a href="https://youtube.com/mavetcare" class="social-link" target="_blank"><i class="fab fa-youtube"></i></a>
                            <a href="https://twitter.com/mavetcare" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
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

            // Close mobile menu when clicking outside
            document.addEventListener('click', (e) => {
                const nav = document.querySelector('nav');
                const navLinks = document.getElementById('navLinks');
                const menuToggle = document.querySelector('.menu-toggle i');
                
                if (!nav.contains(e.target)) {
                    navLinks.classList.remove('active');
                    menuToggle.classList.remove('fa-times');
                    menuToggle.classList.add('fa-bars');
                }
            });

            // User dropdown functionality
            function toggleUserDropdown() {
                const dropdown = document.getElementById('userDropdown');
                dropdown.classList.toggle('active');
            }

            // Close user dropdown when clicking outside
            document.addEventListener('click', (e) => {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Logout function
            function logout() {
                window.location.href = 'logout.php';
            }

        
            const today = new Date().getDay();
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const todayName = days[today];
            
            document.querySelectorAll('.hours-item').forEach((item, index) => {
                const dayName = item.querySelector('span').textContent;
                if (dayName === todayName) {
                    item.classList.add('today');
                } else {
                    item.classList.remove('today');
                }
            });
        </script>
    </body>
    </html>
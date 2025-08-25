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

// Fetch products from admin inventory table
try {
    $stmt = $pdo->query('SELECT * FROM inventory ORDER BY name');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no products found, show empty array
    if (!$products) {
        $products = [];
    }
} catch (PDOException $e) {
    // If table doesn't exist or error, use empty array
    $products = [];
}

// Filter products by category if specified
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'all';
$filteredProducts = $selectedCategory === 'all' ? $products : array_filter($products, function($product) use ($selectedCategory) {
    return $product['category'] === $selectedCategory;
});

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($searchTerm)) {
    $filteredProducts = array_filter($filteredProducts, function($product) use ($searchTerm) {
        return stripos($product['name'], $searchTerm) !== false || stripos($product['category'], $searchTerm) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - MavetCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/products.css">
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
                <li><a href="services.php">Services</a></li>
                <li><a href="products.php" class="active">Products</a></li>
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

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Welcome to Our Products</h1>
                <p>We offer high-quality vet-approved products to support your pet's well-being</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search products..." id="searchInput" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <?php if (!empty($searchTerm)): ?>
                            <button type="button" id="clearSearch" class="clear-search-btn" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="category-filters">
                    <a href="?category=all<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                       class="filter-btn <?php echo $selectedCategory === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> All Products
                    </a>
                    <a href="?category=Shampoo<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                       class="filter-btn <?php echo $selectedCategory === 'Shampoo' ? 'active' : ''; ?>">
                        <i class="fas fa-soap"></i> Shampoo
                    </a>
                    <a href="?category=Food & Accessories<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                       class="filter-btn <?php echo $selectedCategory === 'Food & Accessories' ? 'active' : ''; ?>">
                        <i class="fas fa-bone"></i> Food & Accessories
                    </a>
                    <a href="?category=Vitamins<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                       class="filter-btn <?php echo $selectedCategory === 'Vitamins' ? 'active' : ''; ?>">
                        <i class="fas fa-pills"></i> Vitamins
                    </a>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($filteredProducts)): ?>
                <div class="no-products">
                    <i class="fas fa-search"></i>
                    <h3>No Products Found</h3>
                    <p>Try adjusting your search or filter criteria.</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($filteredProducts as $product): ?>
                        <div class="product-card" onclick="viewProduct(<?php echo $product['id']; ?>)">
                            <div class="product-image">
                                <?php if ($product['product_image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-img"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div class="product-img-placeholder" style="display: none;">
                                        <i class="fas fa-image"></i>
                                        <span>No Image</span>
                                    </div>
                                <?php else: ?>
                                    <div class="product-img-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>No Image</span>
                                    </div>
                                <?php endif; ?>
                                <div class="product-id">ID: <?php echo $product['id']; ?></div>
                                <?php if (($product['quantity'] ?? 0) > 0): ?>
                                    <div class="product-badge">In Stock</div>
                                <?php else: ?>
                                    <div class="product-badge" style="background: #dc3545;">Out of Stock</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['category']); ?></p>
                                <div class="product-footer">
                                    <span class="product-price">₱<?php echo number_format($product['price'] ?? 0, 2); ?></span>
                                    <span class="product-stock"><?php echo $product['quantity'] ?? 0; ?> available</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

        function viewProduct(productId) {
            // Clicking on card area - you can customize this behavior
            console.log('Card clicked for product ID: ' + productId);
        }

        function viewProductDetails(productId) {
            // View Details button functionality
            alert('Viewing details for Product ID: ' + productId + '\nThis would normally open a detailed product page or modal.');
            // You can redirect to product detail page:
            // window.location.href = 'product_detail.php?id=' + productId;
            
            // Or open a modal with product details:
            // showProductModal(productId);
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value;
                const currentCategory = new URLSearchParams(window.location.search).get('category') || 'all';
                let url = 'products.php?category=' + currentCategory;
                if (searchTerm.trim() !== '') {
                    url += '&search=' + encodeURIComponent(searchTerm);
                }
                window.location.href = url;
            }
        });

        // Clear search when input is cleared
        document.getElementById('searchInput').addEventListener('input', function(e) {
            if (this.value.trim() === '') {
                const currentCategory = new URLSearchParams(window.location.search).get('category') || 'all';
                let url = 'products.php?category=' + currentCategory;
                window.location.href = url;
            }
        });

        // Clear search button functionality
        const clearSearchBtn = document.getElementById('clearSearch');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                const currentCategory = new URLSearchParams(window.location.search).get('category') || 'all';
                let url = 'products.php?category=' + currentCategory;
                window.location.href = url;
            });
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

        // Add smooth scroll effect for better UX
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>
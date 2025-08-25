<?php
session_name('admin_session');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: adminLogin.php');
    exit();
}

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

// Get services statistics
try {
    // Total services
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM services");
    $totalServices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Average service price
    $stmt = $pdo->query("SELECT AVG(price) as average FROM services");
    $averagePrice = $stmt->fetch(PDO::FETCH_ASSOC)['average'] ?? 0;
    
    // Most expensive service
    $stmt = $pdo->query("SELECT MAX(price) as max_price FROM services");
    $maxPrice = $stmt->fetch(PDO::FETCH_ASSOC)['max_price'] ?? 0;
    
    // Services by category
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM services GROUP BY category");
    $servicesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all services
    $stmt = $pdo->query("SELECT * FROM services ORDER BY category, service_name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - MavetCare</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: #1e293b;
            border-right: 1px solid #334155;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #334155;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: #8BC34A;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .admin-info {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(139, 195, 74, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(139, 195, 74, 0.2);
        }
        
        .admin-name {
            color: #8BC34A;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .admin-role {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        .sidebar-nav {
            padding: 1.5rem 0;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-title {
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 1.5rem;
            margin-bottom: 1rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: #334155;
            color: white;
            border-left-color: #8BC34A;
        }
        
        .nav-item.active {
            background: #334155;
            color: white;
            border-left-color: #8BC34A;
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #8BC34A;
            color: white;
        }
        
        .btn-primary:hover {
            background: #7CB342;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-title {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-icon.services { background: #3b82f6; }
        .stat-icon.price { background: #8BC34A; }
        .stat-icon.max-price { background: #f59e0b; }
        .stat-icon.categories { background: #ef4444; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .content-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .section-action {
            color: #8BC34A;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .section-action:hover {
            text-decoration: underline;
        }
        
        .section-content {
            padding: 1.5rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .data-table tr:hover {
            background: #f8fafc;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .category-general { background: #dbeafe; color: #1e40af; }
        .category-surgery { background: #fee2e2; color: #991b1b; }
        .category-vaccination { background: #d1fae5; color: #065f46; }
        .category-grooming { background: #fef3c7; color: #92400e; }
        .category-emergency { background: #fecaca; color: #dc2626; }
        .category-consultation { background: #e0e7ff; color: #3730a3; }
        
        .price {
            font-weight: 600;
            color: #059669;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .font-weight-600 { font-weight: 600; }
        .text-sm { font-size: 0.875rem; }
        .text-gray-500 { color: #6b7280; }
        .text-center { text-align: center; }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <span class="logo-text">MavetCare</span>
                </a>
                <div class="admin-info">
                    <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                    <div class="admin-role">Administrator</div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">Dashboard</div>
                    <a href="adminDashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Overview
                    </a>
                    <a href="analytics.php" class="nav-item">
                        <i class="fas fa-chart-line"></i>
                        Analytics
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Management</div>
                    <a href="patients.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        Patients
                    </a>
                    <a href="appointments.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i>
                        Appointments
                    </a>
                    <a href="sales.php" class="nav-item">
                        <i class="fas fa-shopping-cart"></i>
                        Sales
                    </a>
                    <a href="inventory.php" class="nav-item">
                        <i class="fas fa-boxes"></i>
                        Inventory
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Staff</div>
                    <a href="staff.php" class="nav-item">
                        <i class="fas fa-user-md"></i>
                        Doctors
                    </a>
                    <a href="staff.php" class="nav-item">
                        <i class="fas fa-user-nurse"></i>
                        Staff
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Services</div>
                    <a href="services.php" class="nav-item active">
                        <i class="fas fa-stethoscope"></i>
                        Services
                    </a>
                    <a href="products.php" class="nav-item">
                        <i class="fas fa-tags"></i>
                        Products
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">System</div>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <a href="../logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Services Management</h1>
                <div class="header-actions">
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Service
                    </a>
                    <a href="#" class="btn btn-secondary">
                        <i class="fas fa-download"></i>
                        Export Services
                    </a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Services</span>
                        <div class="stat-icon services">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalServices); ?></div>
                    <div class="stat-change">Available services</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Average Price</span>
                        <div class="stat-icon price">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($averagePrice, 2); ?></div>
                    <div class="stat-change">Per service</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Highest Price</span>
                        <div class="stat-icon max-price">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($maxPrice, 2); ?></div>
                    <div class="stat-change">Most expensive</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Categories</span>
                        <div class="stat-icon categories">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($servicesByCategory); ?></div>
                    <div class="stat-change">Service categories</div>
                </div>
            </div>
            
            <!-- Services List -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Services Directory</h2>
                    <a href="#" class="section-action">View All</a>
                </div>
                <div class="section-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-600"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                            </td>
                                            <td>
                                                <span class="category-badge category-<?php echo strtolower($service['category']); ?>">
                                                    <?php echo htmlspecialchars($service['category']); ?>
                                                </span>
                                            </td>
                                            <td class="text-sm">
                                                <?php 
                                                $description = htmlspecialchars($service['description']);
                                                echo strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                                ?>
                                            </td>
                                            <td class="price">₱<?php echo number_format($service['price'], 2); ?></td>
                                            <td class="text-sm"><?php echo htmlspecialchars($service['duration']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <button class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></button>
                                                    <button class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-500">No services found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Add mobile menu button for smaller screens
        if (window.innerWidth <= 768) {
            const header = document.querySelector('.page-header');
            const toggleBtn = document.createElement('button');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.className = 'btn btn-secondary';
            toggleBtn.onclick = toggleSidebar;
            header.insertBefore(toggleBtn, header.firstChild);
        }
    </script>
</body>
</html>

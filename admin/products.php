<?php
/**
 * Products Management - MavetCare Admin
 * Clean, well-structured product inventory management
 */

// ============================================================================
// INITIALIZATION & SECURITY
// ============================================================================

session_name('admin_session');
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: adminLogin.php');
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function handleFileUpload($file, $upload_dir = '../images/products/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_info = pathinfo($file['name']);
    $file_extension = strtolower($file_info['extension']);
    
    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File size too large. Maximum size is 5MB.");
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return 'images/products/' . $filename;
    } else {
        throw new Exception("Failed to upload file.");
    }
}

function getProductStats($pdo) {
    $stats = [];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total inventory value
    $stmt = $pdo->query("SELECT SUM(quantity * price) as total_value FROM inventory");
    $stats['total_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
    // Low stock items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory WHERE quantity <= minimum_stock");
    $stats['low_stock_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Products by category
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM inventory GROUP BY category");
    $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

function getAllProducts($pdo) {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY category, name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================================
// DATA FETCHING
// ============================================================================

try {
    $stats = getProductStats($pdo);
    $products = getAllProducts($pdo);
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// ============================================================================
// FORM PROCESSING
// ============================================================================

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            try {
                // Handle file upload
                $product_image = handleFileUpload($_FILES['product_image'] ?? null);
                
                // Insert product
                $stmt = $pdo->prepare("
                    INSERT INTO inventory (name, category, quantity, price, product_image, expiry_date, minimum_stock, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category'],
                    $_POST['quantity'],
                    $_POST['price'],
                    $product_image,
                    $_POST['expiry_date'] ?? null,
                    $_POST['minimum_stock'] ?? 10
                ]);
                
                $success_message = "Product added successfully!";
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
                
            } catch(Exception $e) {
                $error_message = "Error adding product: " . $e->getMessage();
            }
            break;
            
        case 'delete_product':
            try {
                $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                
                $success_message = "Product deleted successfully!";
                
            } catch(Exception $e) {
                $error_message = "Error deleting product: " . $e->getMessage();
            }
            break;
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Product added successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - MavetCare</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================================
           RESET & BASE STYLES
           ============================================================================ */
        
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
        }
        
        /* ============================================================================
           LAYOUT COMPONENTS
           ============================================================================ */
        
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
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        /* ============================================================================
           SIDEBAR STYLES
           ============================================================================ */
        
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
        
        /* ============================================================================
           HEADER & CONTENT STYLES
           ============================================================================ */
        
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
        
        /* ============================================================================
           BUTTON STYLES
           ============================================================================ */
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #8BC34A;
            color: white;
        }
        
        .btn-primary:hover {
            background: #7CB342;
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        /* ============================================================================
           STATISTICS CARDS
           ============================================================================ */
        
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
        
        .stat-icon.products { background: #3b82f6; }
        .stat-icon.value { background: #8BC34A; }
        .stat-icon.low-stock { background: #f59e0b; }
        .stat-icon.categories { background: #ef4444; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        /* ============================================================================
           CONTENT SECTIONS
           ============================================================================ */
        
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
        
        /* ============================================================================
           TABLE STYLES
           ============================================================================ */
        
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
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        /* ============================================================================
           MODAL STYLES
           ============================================================================ */
        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .close {
            color: #64748b;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .close:hover {
            color: #1e293b;
        }
        
        .modal-body {
            margin-bottom: 1.5rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        /* ============================================================================
           FORM STYLES
           ============================================================================ */
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #8BC34A;
            box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        /* ============================================================================
           FILE UPLOAD STYLES - CLEAN VERSION
           ============================================================================ */
        
        .file-upload-container {
            position: relative;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
            top: 0;
            left: 0;
        }
        
        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f9fafb;
            color: #6b7280;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #8BC34A;
            background: #f0f9ff;
        }
        
        .upload-area.dragover {
            border-color: #8BC34A;
            background: #ecfdf5;
        }
        
        .upload-area i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #8BC34A;
            display: block;
        }
        
        .upload-area p {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .upload-area small {
            font-size: 0.875rem;
            color: #9ca3af;
        }
        
        .image-preview-container {
            position: relative;
            margin-top: 1rem;
            text-align: center;
        }
        
        .image-preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: #ef4444;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
        }
        
        .remove-image:hover {
            background: #dc2626;
        }
        
        /* ============================================================================
           UTILITY CLASSES
           ============================================================================ */
        
        .text-center { text-align: center; }
        .text-gray-500 { color: #6b7280; }
        .font-weight-600 { font-weight: 600; }
        .text-sm { font-size: 0.875rem; }
        
        .stock-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .stock-ok { background: #d1fae5; color: #065f46; }
        .stock-low { background: #fef3c7; color: #92400e; }
        .stock-out { background: #fee2e2; color: #991b1b; }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* ============================================================================
           RESPONSIVE DESIGN
           ============================================================================ */
        
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
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- ============================================================================
         SIDEBAR NAVIGATION
         ============================================================================ -->
    
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
            <a href="adminDashboard.php" class="logo">
                <div class="logo-icon">M</div>
                <div class="logo-text">MavetCare</div>
                </a>
                <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
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
                    <a href="services.php" class="nav-item">
                        <i class="fas fa-stethoscope"></i>
                        Services
                    </a>
                    <a href="products.php" class="nav-item active">
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
        
    <!-- ============================================================================
         MAIN CONTENT
         ============================================================================ -->
    
        <div class="main-content">
        <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Products Management</h1>
                <div class="header-actions">
                <a href="#" class="btn btn-primary" onclick="openModal('addProductModal')">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </a>
                </div>
            </div>
            
        <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
            <div id="successMessage" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #fca5a5;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Products</span>
                        <div class="stat-icon products">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                <div class="stat-value"><?php echo number_format($stats['total_products'] ?? 0); ?></div>
                    <div class="stat-change">In inventory</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Value</span>
                        <div class="stat-icon value">
                        <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                <div class="stat-value">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                    <div class="stat-change">Inventory worth</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                    <span class="stat-title">Low Stock Items</span>
                        <div class="stat-icon low-stock">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                <div class="stat-value"><?php echo number_format($stats['low_stock_items'] ?? 0); ?></div>
                    <div class="stat-change">Need restocking</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Categories</span>
                        <div class="stat-icon categories">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                <div class="stat-value"><?php echo count($stats['by_category'] ?? []); ?></div>
                    <div class="stat-change">Product categories</div>
                </div>
            </div>
            
        <!-- Products Table -->
            <div class="content-section">
                <div class="section-header">
                <h2 class="section-title">Product Inventory</h2>
                <a href="#" class="section-action" onclick="openModal('addProductModal')">Add New Product</a>
                </div>
                <div class="section-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                <th>Product</th>
                                    <th>Category</th>
                                <th>Quantity</th>
                                    <th>Price</th>
                                <th>Stock Status</th>
                                    <th>Expiry Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($products)): ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <?php if ($product['product_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 40px; height: 40px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                <div class="font-weight-600"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?php echo $product['id']; ?></div>
                                                </div>
                                            </div>
                                            </td>
                                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td class="font-weight-600"><?php echo number_format($product['quantity']); ?></td>
                                        <td class="font-weight-600">₱<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <?php
                                            $stockStatus = 'In Stock';
                                            $stockClass = 'stock-ok';
                                            
                                            if ($product['quantity'] <= 0) {
                                                    $stockStatus = 'Out of Stock';
                                                    $stockClass = 'stock-out';
                                                } elseif ($product['quantity'] <= $product['minimum_stock']) {
                                                    $stockStatus = 'Low Stock';
                                                    $stockClass = 'stock-low';
                                                }
                                                ?>
                                                <span class="stock-status <?php echo $stockClass; ?>">
                                                    <?php echo $stockStatus; ?>
                                                </span>
                                            </td>
                                            <td class="text-sm">
                                                <?php 
                                                if ($product['expiry_date']) {
                                                    $expiryDate = new DateTime($product['expiry_date']);
                                                    echo $expiryDate->format('M d, Y');
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                <button class="btn btn-secondary btn-sm" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-primary btn-sm" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                    <td colspan="7" class="text-center text-gray-500">No products found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    
    <!-- ============================================================================
         MODALS
         ============================================================================ -->
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Product</h2>
                <span class="close" onclick="closeModal('addProductModal')">&times;</span>
    </div>
            <form id="addProductForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="product_name" class="form-label">Product Name *</label>
                        <input type="text" id="product_name" name="name" class="form-input" required placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label for="product_category" class="form-label">Category *</label>
                        <select id="product_category" name="category" class="form-input" required>
                            <option value="">Select category</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Food">Food</option>
                            <option value="Supplies">Supplies</option>
                            <option value="Toys">Toys</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_quantity" class="form-label">Quantity *</label>
                            <input type="number" id="product_quantity" name="quantity" class="form-input" min="0" required placeholder="0">
                        </div>

                        <div class="form-group">
                            <label for="product_price" class="form-label">Price (₱) *</label>
                            <input type="number" id="product_price" name="price" class="form-input" min="0.01" step="0.01" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_minimum_stock" class="form-label">Minimum Stock</label>
                            <input type="number" id="product_minimum_stock" name="minimum_stock" class="form-input" min="0" value="10" placeholder="10">
                        </div>

                        <div class="form-group">
                            <label for="product_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" id="product_expiry_date" name="expiry_date" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="product_image" class="form-label">Product Image</label>
                        <div class="file-upload-container">
                            <input type="file" id="product_image" name="product_image" accept="image/*" class="file-input">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to choose a file or drag it here</p>
                                <small>Supports: JPG, PNG, GIF (Max 5MB)</small>
                            </div>
                            <div class="image-preview-container" id="imagePreviewContainer" style="display: none;">
                                <img id="imagePreview" src="" alt="">
                                <button type="button" class="remove-image" onclick="removeImage()">×</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitProductForm()">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================================================
         JAVASCRIPT
         ============================================================================ -->
    
    <script>
        // ============================================================================
        // MODAL FUNCTIONS
        // ============================================================================
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        }

        // ============================================================================
        // FORM VALIDATION
        // ============================================================================
        
        function validateProductForm() {
            const name = document.getElementById('product_name').value.trim();
            const category = document.getElementById('product_category').value;
            const quantity = document.getElementById('product_quantity').value;
            const price = document.getElementById('product_price').value;
            const minimumStock = document.getElementById('product_minimum_stock').value;

            if (!name) {
                alert('Please enter a product name');
                return false;
            }
            if (!category) {
                alert('Please select a category');
                return false;
            }
            if (quantity < 0) {
                alert('Quantity cannot be negative');
                return false;
            }
            if (price <= 0) {
                alert('Price must be greater than 0');
                return false;
            }
            if (minimumStock < 0) {
                alert('Minimum stock cannot be negative');
                return false;
            }

            return true;
        }

        function submitProductForm() {
            if (validateProductForm()) {
                document.getElementById('addProductForm').submit();
            }
        }

        // ============================================================================
        // FILE UPLOAD FUNCTIONALITY - CLEAN VERSION
        // ============================================================================
        
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('product_image');
            const uploadArea = document.getElementById('uploadArea');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');

            if (fileInput && uploadArea) {
                // Handle file selection
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validate file type
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Please select a valid image file (JPG, PNG, or GIF)');
                            fileInput.value = '';
                            return;
                        }

                        // Validate file size (5MB)
                        if (file.size > 5 * 1024 * 1024) {
                            alert('File size must be less than 5MB');
                            fileInput.value = '';
                            return;
                        }

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            uploadArea.style.display = 'none';
                            imagePreviewContainer.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Drag and drop
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });

                // Click to upload
                uploadArea.addEventListener('click', function() {
                    fileInput.click();
                });
            }
        });
        
        function removeImage() {
            const fileInput = document.getElementById('product_image');
            const uploadArea = document.getElementById('uploadArea');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            
            fileInput.value = '';
            uploadArea.style.display = 'block';
            imagePreviewContainer.style.display = 'none';
        }
        
        // ============================================================================
        // PRODUCT ACTIONS
        // ============================================================================
        
        function viewProduct(productId) {
            // Implement view product functionality
            alert('View product ' + productId);
        }
        
        function editProduct(productId) {
            // Implement edit product functionality
            alert('Edit product ' + productId);
        }
        
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================
        
        // Auto-hide success message
        const successMessage = document.getElementById('successMessage');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 5000);
        }
        
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

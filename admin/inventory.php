<?php
session_name('admin_session');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: adminLogin.php');
    exit();
}

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

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_inventory':
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO inventory (name, category, quantity, price, product_image, expiry_date, minimum_stock, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category'],
                    $_POST['quantity'],
                    $_POST['price'],
                    $_POST['product_image'] ?? null,
                    $_POST['expiry_date'] ?? null,
                    $_POST['minimum_stock']
                ]);
                $success_message = "Inventory item added successfully!";
            } catch(PDOException $e) {
                $error_message = "Error adding inventory item: " . $e->getMessage();
            }
            break;
            
        case 'update_stock':
            try {
                $stmt = $pdo->prepare("UPDATE inventory SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['quantity'], $_POST['inventory_id']]);
                $success_message = "Stock updated successfully!";
            } catch(PDOException $e) {
                $error_message = "Error updating stock: " . $e->getMessage();
            }
            break;
    }
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'inventory') {
    try {
        $stmt = $pdo->query("SELECT * FROM inventory ORDER BY category, name ASC");
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Category', 'Quantity', 'Price', 'Minimum Stock', 'Expiry Date', 'Created At']);
        
        foreach ($inventory as $item) {
            fputcsv($output, $item);
        }
        
        fclose($output);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error exporting data: " . $e->getMessage();
    }
}

// Get inventory data
try {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY category, name ASC");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= minimum_stock");
    $lowStockCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT SUM(quantity * price) as total_value FROM inventory");
    $totalValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - MavetCare Admin</title>
    
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
        
        .stat-icon.inventory { background: #ef4444; }
        .stat-icon.value { background: #8BC34A; }
        .stat-icon.low-stock { background: #f59e0b; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .stat-change {
            font-size: 0.875rem;
            color: #10b981;
            font-weight: 500;
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
        
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .inventory-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .inventory-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .inventory-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #8BC34A;
            margin-bottom: 0.5rem;
        }
        
        .inventory-stock {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1rem;
        }
        
        .inventory-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .message { 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 1rem; 
        }
        .success-message { 
            background: #d1fae5; 
            color: #065f46; 
            border: 1px solid #a7f3d0; 
        }
        .error-message { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1e293b;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group input[type="url"],
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            color: #1e293b;
        }

        .form-group input[type="number"]::-webkit-inner-spin-button,
        .form-group input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .form-actions button {
            padding: 0.75rem 1.5rem;
        }
        
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
                <a href="adminDashboard.php" class="logo">
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
                    <a href="inventory.php" class="nav-item active">
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
                <h1 class="page-title">Inventory Management</h1>
                <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('addInventoryModal')">
                    <i class="fas fa-plus"></i>
                    Add Item
                </button>
                <a href="?export=inventory" class="btn btn-secondary">
                    <i class="fas fa-download"></i>
                    Export Inventory
                </a>
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="message success-message" id="successMessage">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Items</span>
                        <div class="stat-icon inventory">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                <div class="stat-value"><?php echo count($inventory); ?></div>
                    <div class="stat-change">In inventory</div>
            </div>
            
            <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Value</span>
                        <div class="stat-icon value">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                    </div>
                <div class="stat-value">₱<?php echo number_format($totalValue, 2); ?></div>
                    <div class="stat-change">Inventory worth</div>
            </div>
            
            <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Low Stock Items</span>
                        <div class="stat-icon low-stock">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                <div class="stat-value"><?php echo $lowStockCount; ?></div>
                    <div class="stat-change">Need restocking</div>
            </div>
        </div>
        
            <!-- Inventory List -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Inventory Directory</h2>
                    <a href="#" class="section-action">View All</a>
                </div>
                <div class="section-content">
        <div class="inventory-grid">
            <?php if (!empty($inventory)): ?>
                <?php foreach ($inventory as $item): ?>
                    <div class="inventory-card">
                        <div class="inventory-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="inventory-price">₱<?php echo number_format($item['price'], 2); ?></div>
                        <div class="inventory-stock">
                            Stock: <?php echo $item['quantity']; ?> units<br>
                            Category: <?php echo htmlspecialchars($item['category']); ?>
                        </div>
                        <div class="inventory-actions">
                            <button class="btn btn-primary btn-sm" onclick="updateStock(<?php echo $item['id']; ?>, <?php echo $item['quantity']; ?>)">
                                <i class="fas fa-boxes"></i> Update Stock
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                            <div style="text-align: center; color: #6b7280; grid-column: 1 / -1; padding: 2rem;">No inventory items found</div>
            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateStock(inventoryId, currentQuantity) {
            document.getElementById('update_inventory_id').value = inventoryId;
            document.getElementById('update_quantity').value = currentQuantity;
            openModal('updateStockModal');
        }
        
        // Auto-hide success message
        const successMessage = document.getElementById('successMessage');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 5000);
        }

        // Modal functions
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

        // Form validation
        function validateForm() {
            const name = document.getElementById('name').value.trim();
            const category = document.getElementById('category').value;
            const quantity = document.getElementById('quantity').value;
            const price = document.getElementById('price').value;
            const minimumStock = document.getElementById('minimum_stock').value;

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

        function submitForm() {
            if (validateForm()) {
                document.getElementById('addInventoryForm').submit();
            }
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

    <!-- Add Inventory Modal -->
    <div id="addInventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Inventory Item</h2>
                <span class="close" onclick="closeModal('addInventoryModal')">&times;</span>
            </div>
            <form id="addInventoryForm" method="POST" action="">
                <input type="hidden" name="action" value="add_inventory">
                
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required placeholder="Enter product name">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
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
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="0" required placeholder="0">
                    </div>

                    <div class="form-group">
                        <label for="price">Price (₱) *</label>
                        <input type="number" id="price" name="price" min="0.01" step="0.01" required placeholder="0.00">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="minimum_stock">Minimum Stock</label>
                        <input type="number" id="minimum_stock" name="minimum_stock" min="0" value="10" placeholder="10">
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date">
                    </div>
                </div>

                <div class="form-group">
                    <label for="product_image">Product Image URL</label>
                    <input type="url" id="product_image" name="product_image" placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addInventoryModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm()">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div id="updateStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Stock</h2>
                <span class="close" onclick="closeModal('updateStockModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" id="update_inventory_id" name="inventory_id">
                
                <div class="form-group">
                    <label for="update_quantity">New Quantity</label>
                    <input type="number" id="update_quantity" name="quantity" min="0" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('updateStockModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

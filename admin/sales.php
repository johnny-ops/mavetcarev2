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
        case 'add_sale':
            try {
                // Start transaction for data consistency
                $pdo->beginTransaction();
                
                // Insert sale record
                $stmt = $pdo->prepare("
                    INSERT INTO sales (patient_id, items, total_amount, payment_method, pet_type, sale_date) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_POST['patient_id'] ?? null,
                    $_POST['items'],
                    $_POST['total_amount'],
                    $_POST['payment_method'],
                    $_POST['pet_type'] ?? 'N/A'
                ]);
                
                // Get the sale ID
                $saleId = $pdo->lastInsertId();
                
                // Process items and reduce stock for products
                $items = json_decode($_POST['items'], true);
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if ($item['type'] === 'product') {
                            // Reduce stock for products
                            $stmt = $pdo->prepare("
                                UPDATE inventory 
                                SET quantity = quantity - ? 
                                WHERE id = ? AND quantity >= ?
                            ");
                            $result = $stmt->execute([
                                $item['quantity'],
                                $item['id'],
                                $item['quantity']
                            ]);
                            
                            if ($stmt->rowCount() === 0) {
                                throw new Exception("Insufficient stock for product: " . $item['name']);
                            }
                        }
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                $success_message = "Sale recorded successfully! Stock updated.";
                
                // Redirect to refresh the page
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
                
            } catch(Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                $error_message = "Error recording sale: " . $e->getMessage();
            }
            break;
            
        case 'update_sale':
            try {
                $stmt = $pdo->prepare("
                    UPDATE sales SET 
                    patient_id = ?, items = ?, total_amount = ?, payment_method = ?, pet_type = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['patient_id'] ?? null,
                    $_POST['items'],
                    $_POST['total_amount'],
                    $_POST['payment_method'],
                    $_POST['pet_type'] ?? 'N/A',
                    $_POST['sale_id']
                ]);
                $success_message = "Sale updated successfully!";
            } catch(PDOException $e) {
                $error_message = "Error updating sale: " . $e->getMessage();
            }
            break;
            
        case 'delete_sale':
            try {
                $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
                $stmt->execute([$_POST['sale_id']]);
                $success_message = "Sale deleted successfully!";
            } catch(PDOException $e) {
                $error_message = "Error deleting sale: " . $e->getMessage();
            }
            break;
    }
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'sales') {
    try {
        $stmt = $pdo->query("
            SELECT s.*, p.pet_name, p.client_name 
            FROM sales s 
            LEFT JOIN patients p ON s.patient_id = p.id 
            ORDER BY s.sale_date DESC
        ");
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Client', 'Pet', 'Items', 'Amount', 'Payment Method', 'Date']);
        
        foreach ($sales as $sale) {
            fputcsv($output, [
                $sale['id'],
                $sale['client_name'] ?? 'N/A',
                $sale['pet_name'] ?? 'N/A',
                $sale['items'],
                $sale['total_amount'],
                $sale['payment_method'],
                $sale['sale_date']
            ]);
        }
        
        fclose($output);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error exporting data: " . $e->getMessage();
    }
}

// Get sales data
try {
    // All sales with patient info
    $stmt = $pdo->query("
        SELECT s.*, p.pet_name, p.client_name 
        FROM sales s 
        LEFT JOIN patients p ON s.patient_id = p.id 
        ORDER BY s.sale_date DESC
    ");
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly revenue
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(sale_date, '%Y-%m') as month,
            SUM(total_amount) as revenue,
            COUNT(*) as transactions
        FROM sales 
        WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month DESC
    ");
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment method breakdown
    $stmt = $pdo->query("
        SELECT 
            payment_method,
            SUM(total_amount) as total_revenue,
            COUNT(*) as transaction_count
        FROM sales 
        GROUP BY payment_method
        ORDER BY total_revenue DESC
    ");
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Today's sales
    $stmt = $pdo->query("
        SELECT s.*, p.pet_name, p.client_name 
        FROM sales s 
        LEFT JOIN patients p ON s.patient_id = p.id 
        WHERE DATE(s.sale_date) = CURDATE()
        ORDER BY s.sale_date DESC
    ");
    $todaySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get patients for dropdown
    $stmt = $pdo->query("SELECT id, pet_name, client_name FROM patients ORDER BY pet_name");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get products for POS
    $stmt = $pdo->query("SELECT id, name, price, quantity, category FROM inventory WHERE quantity > 0 ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get services for POS
    $stmt = $pdo->query("SELECT id, service_name, price, category FROM services ORDER BY service_name");
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
    <title>Sales - MavetCare Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        /* POS System Specific Styles */
        .pos-cart-item {
            transition: all 0.2s ease;
        }
        
        .pos-cart-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pos-summary-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .pos-customer-section {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
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
        
        .stat-icon.revenue { background: #10b981; }
        .stat-icon.transactions { background: #3b82f6; }
        .stat-icon.today { background: #8BC34A; }
        .stat-icon.average { background: #f59e0b; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        
        .data-table tr:hover {
            background: #f8fafc;
        }
        
        .sale-info {
            display: flex;
            flex-direction: column;
        }
        
        .client-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .pet-name {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .amount {
            font-weight: 600;
            color: #10b981;
        }
        
        .payment-method {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            background: #e2e8f0;
            color: #64748b;
        }
        
        .payment-cash { background: #d1fae5; color: #065f46; }
        .payment-card { background: #dbeafe; color: #1e40af; }
        .payment-gcash { background: #fef3c7; color: #92400e; }
        .payment-paymaya { background: #fce7f3; color: #be185d; }
        .payment-bank { background: #f3e8ff; color: #7c3aed; }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .revenue-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        
        .revenue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .revenue-month {
            font-weight: 600;
            color: #1e293b;
        }
        
        .revenue-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .revenue-details {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-left: 0;
            }
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
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
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #8BC34A;
            box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.1);
        }
        
        /* Success/Error Messages */
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
        
        /* Custom Scrollbar Styling */
        .modal-body::-webkit-scrollbar,
        #posCartItems::-webkit-scrollbar,
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-body::-webkit-scrollbar-track,
        #posCartItems::-webkit-scrollbar-track,
        .sidebar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .modal-body::-webkit-scrollbar-thumb,
        #posCartItems::-webkit-scrollbar-thumb,
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .modal-body::-webkit-scrollbar-thumb:hover,
        #posCartItems::-webkit-scrollbar-thumb:hover,
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Firefox scrollbar styling */
        .modal-body,
        #posCartItems,
        .sidebar {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        
        /* POS Modal specific scrollbar */
        .pos-modal-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .pos-modal-content::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        
        .pos-modal-content::-webkit-scrollbar-thumb {
            background: #8BC34A;
            border-radius: 3px;
        }
        
        .pos-modal-content::-webkit-scrollbar-thumb:hover {
            background: #7CB342;
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
                    <a href="sales.php" class="nav-item active">
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
                <h1 class="page-title">Sales Management</h1>
                <div class="header-actions">
                    <button class="btn btn-success" onclick="openModal('posModal')">
                        <i class="fas fa-cash-register"></i>
                        POS System
                    </button>
                    <button class="btn btn-primary" onclick="openModal('addSaleModal')">
                        <i class="fas fa-plus"></i>
                        New Sale
                    </button>
                    <a href="?export=sales" class="btn btn-warning">
                        <i class="fas fa-file-export"></i>
                        Export Report
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
                        <span class="stat-title">Total Revenue</span>
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format(array_sum(array_column($sales, 'total_amount')), 2); ?></div>
                    <div style="font-size: 0.875rem; color: #10b981;">All time</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Transactions</span>
                        <div class="stat-icon transactions">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($sales); ?></div>
                    <div style="font-size: 0.875rem; color: #3b82f6;">All time</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Today's Sales</span>
                        <div class="stat-icon today">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format(array_sum(array_column($todaySales, 'total_amount')), 2); ?></div>
                    <div style="font-size: 0.875rem; color: #8BC34A;"><?php echo count($todaySales); ?> transactions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Average Sale</span>
                        <div class="stat-icon average">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php 
                        $totalRevenue = array_sum(array_column($sales, 'total_amount'));
                        $totalTransactions = count($sales);
                        echo $totalTransactions > 0 ? number_format($totalRevenue / $totalTransactions, 2) : '0.00';
                    ?></div>
                    <div style="font-size: 0.875rem; color: #f59e0b;">Per transaction</div>
                </div>
            </div>
            
            <div class="content-grid">
                <!-- Sales Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Sales</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sales)): ?>
                                <?php foreach ($sales as $sale): ?>
                                            <tr data-sale-id="<?php echo $sale['id']; ?>">
                                        <td>
                                            <div class="sale-info">
                                                <div class="client-name"><?php echo htmlspecialchars($sale['client_name'] ?? 'N/A'); ?></div>
                                                <div class="pet-name"><?php echo htmlspecialchars($sale['pet_name'] ?? 'N/A'); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $items = json_decode($sale['items'], true);
                                            if (is_array($items)) {
                                                foreach ($items as $item) {
                                                    echo htmlspecialchars($item['name']) . ' (x' . $item['quantity'] . ')<br>';
                                                }
                                            } else {
                                                echo htmlspecialchars($sale['items']);
                                            }
                                            ?>
                                        </td>
                                        <td class="amount">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="payment-method payment-<?php echo strtolower($sale['payment_method']); ?>">
                                                <?php echo htmlspecialchars($sale['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $saleDate = new DateTime($sale['sale_date']);
                                            echo $saleDate->format('M d, Y g:i A');
                                            ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button class="btn btn-secondary btn-sm" onclick="viewSale(<?php echo $sale['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-primary btn-sm" onclick="printReceipt(<?php echo $sale['id']; ?>)">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #6b7280;">No sales found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Revenue Summary -->
                <div>
                    <!-- Monthly Revenue -->
                    <div class="table-container">
                        <div class="table-header">
                            <h2 class="table-title">Monthly Revenue</h2>
                        </div>
                        
                        <div style="padding: 1.5rem;">
                            <?php if (!empty($monthlyRevenue)): ?>
                                <?php foreach ($monthlyRevenue as $revenue): ?>
                                    <div class="revenue-card">
                                        <div class="revenue-header">
                                            <div class="revenue-month">
                                                <?php 
                                                $date = new DateTime($revenue['month'] . '-01');
                                                echo $date->format('M Y');
                                                ?>
                                            </div>
                                            <div class="revenue-amount">₱<?php echo number_format($revenue['revenue'], 2); ?></div>
                                        </div>
                                        <div class="revenue-details">
                                            <?php echo $revenue['transactions']; ?> transactions
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; color: #6b7280; padding: 2rem;">
                                    <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <div>No revenue data available</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="table-container" style="margin-top: 1.5rem;">
                        <div class="table-header">
                            <h2 class="table-title">Payment Methods</h2>
                        </div>
                        
                        <div style="padding: 1.5rem;">
                            <?php if (!empty($paymentMethods)): ?>
                                <?php foreach ($paymentMethods as $method): ?>
                                    <div class="revenue-card">
                                        <div class="revenue-header">
                                            <div class="revenue-month">
                                                <?php echo htmlspecialchars($method['payment_method']); ?>
                                            </div>
                                            <div class="revenue-amount">₱<?php echo number_format($method['total_revenue'], 2); ?></div>
                                        </div>
                                        <div class="revenue-details">
                                            <?php echo $method['transaction_count']; ?> transactions
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; color: #6b7280; padding: 1rem;">
                                    No payment method data
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="table-container" style="margin-top: 1.5rem;">
                        <div class="table-header">
                            <h2 class="table-title">Quick Actions</h2>
                        </div>
                        
                        <div style="padding: 1.5rem;">
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <button class="btn btn-primary" onclick="openModal('addSaleModal')">
                                    <i class="fas fa-plus"></i>
                                    Record New Sale
                                </button>
                                <a href="?export=sales" class="btn btn-warning">
                                    <i class="fas fa-file-export"></i>
                                    Export Sales Report
                                </a>
                                <button class="btn btn-success" onclick="viewAnalytics()">
                                    <i class="fas fa-chart-pie"></i>
                                    View Analytics
                                </button>
                                <button class="btn btn-secondary" onclick="openSettings()">
                                    <i class="fas fa-cog"></i>
                                    Sales Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Sale Modal -->
    <div id="addSaleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Record New Sale</h2>
                <span class="close" onclick="closeModal('addSaleModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_sale">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Patient (Optional)</label>
                        <select name="patient_id" class="form-input">
                            <option value="">Select Patient (Optional)</option>
                            <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['pet_name'] . ' (' . $patient['client_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Items/Services</label>
                        <textarea name="items" class="form-input" rows="3" required placeholder="Describe items or services sold"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Amount</label>
                        <input type="number" name="total_amount" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-input" required>
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pet Type</label>
                        <input type="text" name="pet_type" class="form-input" placeholder="e.g., Dog, Cat, Bird">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSaleModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Sale</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Sale Modal -->
    <div id="viewSaleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Sale Details</h2>
                <span class="close" onclick="closeModal('viewSaleModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="saleDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewSaleModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- POS System Modal -->
    <div id="posModal" class="modal">
        <div class="modal-content" style="max-width: 1000px; width: 95%; max-height: 90vh; overflow: hidden;">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-cash-register"></i>
                    Point of Sale System
                </h2>
                <span class="close" onclick="closeModal('posModal')">&times;</span>
            </div>
            <div class="modal-body" style="padding: 0; max-height: calc(90vh - 80px); overflow-y: auto;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0; min-height: 600px;">
                    <!-- Left Side - Product/Service Selection -->
                    <div style="padding: 1.5rem; border-right: 1px solid #e2e8f0; overflow-y: auto;">
                        <!-- Customer Info -->
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Customer Information</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <select id="posPatientId" class="form-input" onchange="updateCustomerInfo()">
                                    <option value="">Select Customer (Optional)</option>
                                    <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" data-pet="<?php echo htmlspecialchars($patient['pet_name']); ?>" data-client="<?php echo htmlspecialchars($patient['client_name']); ?>">
                                        <?php echo htmlspecialchars($patient['pet_name'] . ' (' . $patient['client_name'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="posCustomerName" class="form-input" placeholder="Customer Name (if not in system)">
                            </div>
                        </div>

                        <!-- Product Selection -->
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Add Products</label>
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end;">
                                <select id="posProductSelect" class="form-input" onchange="updateProductPrice()">
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-stock="<?php echo $product['quantity']; ?>">
                                        <?php echo htmlspecialchars($product['name'] . ' - ₱' . number_format($product['price'], 2) . ' (Stock: ' . $product['quantity'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" id="posProductQty" class="form-input" placeholder="Qty" min="1" value="1" style="width: 80px;">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addProductToCart()" style="margin-top: 0.5rem;">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>

                        <!-- Service Selection -->
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Add Services</label>
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end;">
                                <select id="posServiceSelect" class="form-input" onchange="updateServicePrice()">
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>" data-name="<?php echo htmlspecialchars($service['service_name']); ?>" data-category="<?php echo htmlspecialchars($service['category']); ?>">
                                        <?php echo htmlspecialchars($service['service_name'] . ' - ₱' . number_format($service['price'], 2)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" id="posServiceQty" class="form-input" placeholder="Qty" min="1" value="1" style="width: 80px;">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addServiceToCart()" style="margin-top: 0.5rem;">
                                <i class="fas fa-plus"></i> Add Service
                            </button>
                        </div>

                        <!-- Cart Items -->
                        <div class="form-group">
                            <label class="form-label">
                                Cart Items 
                                <span style="font-size: 0.8rem; color: #64748b; font-weight: normal;">
                                    (Use +/- buttons to adjust quantities)
                                </span>
                            </label>
                            <div id="posCartItems" style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0; background: #f8fafc;">
                                <div style="text-align: center; color: #6b7280; padding: 2rem;">
                                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: #8BC34A;"></i>
                                    <div style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #64748b;">Cart is empty</div>
                                    <div style="font-size: 0.9rem; color: #94a3b8;">Add products or services to get started</div>
                                </div>
                            </div>
                            <!-- Keyboard Shortcuts Help -->
                            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
                                <strong>Shortcuts:</strong> Ctrl+Enter (Complete Sale) | Ctrl+D (Clear Cart) | Ctrl+R (Reset) | Esc (Close)
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Payment & Receipt -->
                    <div style="padding: 1.5rem; background: #f8fafc; overflow-y: auto;">
                        <!-- Payment Summary -->
                        <div style="margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem; color: #1e293b;">Payment Summary</h3>
                            <div style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span>Subtotal:</span>
                                    <span id="posSubtotal">₱0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span>Tax (12%):</span>
                                    <span id="posTax">₱0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-weight: 600; font-size: 1.1rem; color: #10b981;">
                                    <span>Total:</span>
                                    <span id="posTotal">₱0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div style="margin-bottom: 2rem;">
                            <label class="form-label">Payment Method</label>
                            <select id="posPaymentMethod" class="form-input">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="GCash">GCash</option>
                                <option value="PayMaya">PayMaya</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <!-- Receipt Options -->
                        <div style="margin-bottom: 2rem;">
                            <label class="form-label">Receipt Options</label>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" id="posPrintReceipt" checked>
                                    <span>Print Receipt</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" id="posEmailReceipt">
                                    <span>Email Receipt</span>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <button type="button" class="btn btn-success" onclick="processPOSSale()" style="width: 100%; padding: 1rem;">
                                <i class="fas fa-credit-card"></i>
                                Complete Sale
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearPOSCart()" style="width: 100%;">
                                <i class="fas fa-trash"></i>
                                Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // POS System Variables
        let posCart = [];
        let posCustomerInfo = {
            patientId: '',
            customerName: '',
            petName: '',
            petType: ''
        };

        // Add keyboard shortcuts for POS
        document.addEventListener('keydown', function(event) {
            // Only apply shortcuts when POS modal is open
            if (document.getElementById('posModal').style.display === 'block') {
                // Ctrl/Cmd + Enter to complete sale
                if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                    event.preventDefault();
                    processPOSSale();
                }
                
                // Ctrl/Cmd + D to clear cart
                if ((event.ctrlKey || event.metaKey) && event.key === 'd') {
                    event.preventDefault();
                    clearPOSCart();
                }
                
                // Ctrl/Cmd + R to reset system
                if ((event.ctrlKey || event.metaKey) && event.key === 'r') {
                    event.preventDefault();
                    resetPOSSystem();
                }
                
                // Escape to close modal
                if (event.key === 'Escape') {
                    closeModal('posModal');
                }
            }
        });

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            if (modalId === 'posModal') {
                resetPOSSystem();
                // Focus on customer name input
                setTimeout(() => {
                    document.getElementById('posCustomerName').focus();
                }, 100);
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function viewSale(saleId) {
            // In a real application, you would fetch sale data via AJAX
            document.getElementById('saleDetails').innerHTML = '<p>Loading sale details...</p>';
            openModal('viewSaleModal');
        }
        
        function printReceipt(saleId) {
            // Generate and print receipt
            generateReceipt(saleId);
        }

        function generateReceipt(saleId) {
            // Find the sale data
            const saleRow = document.querySelector(`tr[data-sale-id="${saleId}"]`);
            if (!saleRow) {
                alert('Sale data not found');
                return;
            }

            // Extract sale information
            const clientName = saleRow.querySelector('.client-name').textContent;
            const petName = saleRow.querySelector('.pet-name').textContent;
            const items = saleRow.querySelector('td:nth-child(2)').textContent;
            const amount = saleRow.querySelector('.amount').textContent;
            const paymentMethod = saleRow.querySelector('.payment-method').textContent;
            const date = saleRow.querySelector('td:nth-child(5)').textContent;

            // Create receipt content with improved design
            const receiptContent = `
                <div style="font-family: 'Inter', Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; background: white; color: #1e293b;">
                    <!-- Header Section -->
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h1 style="margin: 0; color: #8BC34A; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">Mabolo Veterinary Clinic</h1>
                        <p style="margin: 8px 0 4px 0; color: #64748b; font-size: 14px;">Juan Luna Ave, Mabolo, Cebu City, Philippines</p>
                        <p style="margin: 4px 0; color: #64748b; font-size: 14px;">Phone: 233-20-30</p>
                        <p style="margin: 4px 0; color: #64748b; font-size: 14px;">VAT Reg TIN: 649-058-316-00000</p>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                    
                    <!-- Receipt Details -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px;">
                        <span style="color: #64748b; font-weight: 500;">Receipt #: ${saleId}</span>
                        <span style="color: #64748b; font-weight: 500;">Date: ${date}</span>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                    
                    <!-- Customer Info -->
                    <div style="margin-bottom: 20px; font-size: 14px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Customer:</span>
                            <span style="color: #1e293b; font-weight: 500;">${clientName}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Pet:</span>
                            <span style="color: #1e293b; font-weight: 500;">${petName}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Payment:</span>
                            <span style="color: #1e293b; font-weight: 500;">${paymentMethod}</span>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <th style="text-align: left; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Item</th>
                                    <th style="text-align: center; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Qty</th>
                                    <th style="text-align: right; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Price</th>
                                    <th style="text-align: right; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.split('<br>').map(item => {
                                    // Parse item details (assuming format: "Item Name x1 ₱100.00")
                                    const itemMatch = item.match(/(.+) x(\d+) ₱([\d.]+)/);
                                    if (itemMatch) {
                                        const [, itemName, qty, price] = itemMatch;
                                        const amount = (parseFloat(price) * parseInt(qty)).toFixed(2);
                                        return `
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="text-align: left; padding: 8px 0; color: #1e293b;">${itemName}</td>
                                                <td style="text-align: center; padding: 8px 0; color: #1e293b;">${qty}</td>
                                                <td style="text-align: right; padding: 8px 0; color: #1e293b;">₱${price}</td>
                                                <td style="text-align: right; padding: 8px 0; color: #1e293b;">₱${amount}</td>
                                            </tr>
                                        `;
                                    } else {
                                        return `
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="text-align: left; padding: 8px 0; color: #1e293b;" colspan="4">${item}</td>
                                            </tr>
                                        `;
                                    }
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Total Section -->
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #1e293b; font-weight: 600; font-size: 16px;">Total:</span>
                            <span style="color: #8BC34A; font-weight: 700; font-size: 18px;">${amount}</span>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 5px 0; color: #64748b; font-size: 14px; font-weight: 500;">Thank you for your purchase!</p>
                        <p style="margin: 5px 0; color: #64748b; font-size: 14px; font-weight: 500;">Come again!</p>
                    </div>
                </div>
            `;

            // Open print window with improved styling
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Receipt - Mabolo Veterinary Clinic</title>
                        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                        <style>
                            body { 
                                margin: 0; 
                                padding: 20px; 
                                background: #f8fafc; 
                                font-family: 'Inter', Arial, sans-serif;
                            }
                            @media print {
                                body { 
                                    margin: 0; 
                                    padding: 0; 
                                    background: white; 
                                }
                                .no-print { display: none; }
                                .receipt-container {
                                    box-shadow: none !important;
                                    border: none !important;
                                }
                            }
                            .receipt-container {
                                background: white;
                                border-radius: 12px;
                                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                max-width: 400px;
                                margin: 0 auto;
                                overflow: hidden;
                            }
                            .print-btn {
                                background: #8BC34A;
                                color: white;
                                border: none;
                                border-radius: 8px;
                                padding: 12px 24px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: background 0.2s;
                                font-family: 'Inter', sans-serif;
                            }
                            .print-btn:hover {
                                background: #7CB342;
                            }
                            .close-btn {
                                background: #64748b;
                                color: white;
                                border: none;
                                border-radius: 8px;
                                padding: 12px 24px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: background 0.2s;
                                font-family: 'Inter', sans-serif;
                                margin-left: 10px;
                            }
                            .close-btn:hover {
                                background: #475569;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="receipt-container">
                            ${receiptContent}
                        </div>
                        <div class="no-print" style="text-align: center; margin-top: 20px;">
                            <button onclick="window.print()" class="print-btn">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <button onclick="window.close()" class="close-btn">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        function viewAnalytics() {
            alert('Analytics functionality would be implemented here. This could show charts and detailed reports.');
        }
        
        function openSettings() {
            alert('Settings functionality would be implemented here. This could configure sales preferences.');
        }

        // POS System Functions
        function resetPOSSystem() {
            posCart = [];
            posCustomerInfo = {
                patientId: '',
                customerName: '',
                petName: '',
                petType: ''
            };
            updatePOSDisplay();
            clearPOSForm();
        }

        function clearPOSForm() {
            document.getElementById('posPatientId').value = '';
            document.getElementById('posCustomerName').value = '';
            document.getElementById('posProductSelect').value = '';
            document.getElementById('posProductQty').value = '1';
            document.getElementById('posServiceSelect').value = '';
            document.getElementById('posServiceQty').value = '1';
        }

        function updateCustomerInfo() {
            const patientSelect = document.getElementById('posPatientId');
            const customerNameInput = document.getElementById('posCustomerName');
            
            if (patientSelect.value) {
                const selectedOption = patientSelect.options[patientSelect.selectedIndex];
                posCustomerInfo.patientId = patientSelect.value;
                posCustomerInfo.customerName = selectedOption.getAttribute('data-client');
                posCustomerInfo.petName = selectedOption.getAttribute('data-pet');
                customerNameInput.value = posCustomerInfo.customerName;
                customerNameInput.disabled = true;
            } else {
                posCustomerInfo.patientId = '';
                posCustomerInfo.customerName = '';
                posCustomerInfo.petName = '';
                customerNameInput.value = '';
                customerNameInput.disabled = false;
            }
        }

        function updateProductPrice() {
            const productSelect = document.getElementById('posProductSelect');
            if (productSelect.value) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = parseFloat(selectedOption.getAttribute('data-price'));
                // You can add price display logic here if needed
            }
        }

        function updateServicePrice() {
            const serviceSelect = document.getElementById('posServiceSelect');
            if (serviceSelect.value) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const price = parseFloat(selectedOption.getAttribute('data-price'));
                // You can add price display logic here if needed
            }
        }

        function addProductToCart() {
            const productSelect = document.getElementById('posProductSelect');
            const qtyInput = document.getElementById('posProductQty');
            
            if (!productSelect.value) {
                alert('Please select a product');
                return;
            }
            
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productId = productSelect.value;
            const productName = selectedOption.getAttribute('data-name');
            const productPrice = parseFloat(selectedOption.getAttribute('data-price'));
            const productCategory = selectedOption.getAttribute('data-category');
            const quantity = parseInt(qtyInput.value);
            const availableStock = parseInt(selectedOption.getAttribute('data-stock'));
            
            // Check stock availability
            if (quantity > availableStock) {
                alert(`Insufficient stock! Only ${availableStock} available for ${productName}`);
                return;
            }
            
            // Warn if stock is low (less than 5 items)
            if (availableStock <= 5) {
                alert(`Warning: Low stock alert! Only ${availableStock} ${productName} remaining.`);
            }
            
            // Check if product already in cart
            const existingIndex = posCart.findIndex(item => item.id === productId && item.type === 'product');
            
            if (existingIndex >= 0) {
                // Check if adding more would exceed available stock
                const newTotalQuantity = posCart[existingIndex].quantity + quantity;
                if (newTotalQuantity > availableStock) {
                    alert(`Cannot add more! Total quantity ${newTotalQuantity} would exceed available stock ${availableStock} for ${productName}`);
                    return;
                }
                posCart[existingIndex].quantity += quantity;
            } else {
                posCart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    category: productCategory,
                    quantity: quantity,
                    type: 'product',
                    stock: availableStock
                });
            }
            
            updatePOSDisplay();
            clearPOSForm();
        }

        function addServiceToCart() {
            const serviceSelect = document.getElementById('posServiceSelect');
            const qtyInput = document.getElementById('posServiceQty');
            
            if (!serviceSelect.value) {
                alert('Please select a service');
                return;
            }
            
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const serviceId = serviceSelect.value;
            const serviceName = selectedOption.getAttribute('data-name');
            const servicePrice = parseFloat(selectedOption.getAttribute('data-price'));
            const serviceCategory = selectedOption.getAttribute('data-category');
            const quantity = parseInt(qtyInput.value);
            
            // Check if service already in cart
            const existingIndex = posCart.findIndex(item => item.id === serviceId && item.type === 'service');
            
            if (existingIndex >= 0) {
                posCart[existingIndex].quantity += quantity;
            } else {
                posCart.push({
                    id: serviceId,
                    name: serviceName,
                    price: servicePrice,
                    category: serviceCategory,
                    quantity: quantity,
                    type: 'service'
                });
            }
            
            updatePOSDisplay();
            clearPOSForm();
        }

        function removeFromCart(index) {
            if (confirm('Are you sure you want to remove this item from the cart?')) {
                posCart.splice(index, 1);
                updatePOSDisplay();
            }
        }

        function updatePOSDisplay() {
            const cartContainer = document.getElementById('posCartItems');
            const subtotalElement = document.getElementById('posSubtotal');
            const taxElement = document.getElementById('posTax');
            const totalElement = document.getElementById('posTotal');
            
            if (posCart.length === 0) {
                cartContainer.innerHTML = `
                    <div style="text-align: center; color: #6b7280; padding: 2rem;">
                        <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: #8BC34A;"></i>
                        <div style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #64748b;">Cart is empty</div>
                        <div style="font-size: 0.9rem; color: #94a3b8;">Add products or services to get started</div>
                    </div>
                `;
                subtotalElement.textContent = '₱0.00';
                taxElement.textContent = '₱0.00';
                totalElement.textContent = '₱0.00';
                return;
            }
            
            let cartHTML = `
                <div style="padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #8BC34A; color: white; border-radius: 8px; font-weight: 600; margin-bottom: 1rem;">
                        <span>Cart Items (${posCart.length})</span>
                        <span>Total: ₱${posCart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2)}</span>
                    </div>
                </div>
            `;
            
            let subtotal = 0;
            
            posCart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                // Determine item type icon and color
                const itemIcon = item.type === 'product' ? 'fas fa-box' : 'fas fa-stethoscope';
                const itemColor = item.type === 'product' ? '#3b82f6' : '#f59e0b';
                
                cartHTML += `
                    <div style="margin: 0 1rem 1rem 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                        <!-- Item Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 0;">
                                <i class="${itemIcon}" style="color: ${itemColor}; font-size: 1rem; flex-shrink: 0;"></i>
                                <span style="font-weight: 600; color: #1e293b; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.name}</span>
                                <span style="background: ${itemColor}; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 500; text-transform: uppercase; flex-shrink: 0;">${item.type}</span>
                            </div>
                            <button type="button" onclick="removeFromCart(${index})" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 0.25rem 0.5rem; cursor: pointer; font-size: 0.8rem; transition: background 0.2s; flex-shrink: 0; margin-left: 0.5rem;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        
                        <!-- Item Details -->
                        <div style="padding: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                <span style="color: #64748b; font-size: 0.85rem;">Category: ${item.category}</span>
                                <span style="color: #64748b; font-size: 0.85rem;">₱${item.price.toFixed(2)} each</span>
                            </div>
                            
                            <!-- Quantity Controls -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <button type="button" onclick="updateItemQuantity(${index}, -1)" style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; border-radius: 4px; width: 28px; height: 28px; cursor: pointer; font-weight: bold; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">-</button>
                                    <span style="font-weight: 600; color: #1e293b; min-width: 30px; text-align: center; flex-shrink: 0;">${item.quantity}</span>
                                    <button type="button" onclick="updateItemQuantity(${index}, 1)" style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; border-radius: 4px; width: 28px; height: 28px; cursor: pointer; font-weight: bold; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">+</button>
                                </div>
                                <div style="text-align: right; flex-shrink: 0;">
                                    <div style="font-weight: 700; color: #8BC34A; font-size: 1.1rem;">₱${itemTotal.toFixed(2)}</div>
                                    ${item.type === 'product' ? `<div style="font-size: 0.75rem; color: ${item.stock - item.quantity <= 5 ? '#ef4444' : '#64748b'};">Stock: ${item.stock - item.quantity} remaining</div>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            cartContainer.innerHTML = cartHTML;
            
            const tax = subtotal * 0.12; // 12% tax
            const total = subtotal + tax;
            
            subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
            taxElement.textContent = `₱${tax.toFixed(2)}`;
            totalElement.textContent = `₱${total.toFixed(2)}`;
        }

        function updateItemQuantity(index, change) {
            const item = posCart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeFromCart(index);
                return;
            }
            
            // For products, check stock availability
            if (item.type === 'product') {
                const productSelect = document.getElementById('posProductSelect');
                const selectedOption = productSelect.querySelector(`option[value="${item.id}"]`);
                if (selectedOption) {
                    const availableStock = parseInt(selectedOption.getAttribute('data-stock'));
                    const currentCartQuantity = posCart.filter(cartItem => cartItem.id === item.id && cartItem.type === 'product').reduce((sum, cartItem) => sum + cartItem.quantity, 0);
                    const maxAllowed = availableStock + (item.quantity - currentCartQuantity);
                    
                    if (newQuantity > maxAllowed) {
                        alert(`Cannot increase quantity! Only ${availableStock} available in stock for ${item.name}`);
                        return;
                    }
                }
            }
            
            item.quantity = newQuantity;
            updatePOSDisplay();
        }

        function removeFromCart(index) {
            if (confirm('Are you sure you want to remove this item from the cart?')) {
                posCart.splice(index, 1);
                updatePOSDisplay();
            }
        }

        function clearPOSCart() {
            if (posCart.length === 0) {
                alert('Cart is already empty!');
                return;
            }
            
            if (confirm('Are you sure you want to clear all items from the cart?')) {
                posCart = [];
                updatePOSDisplay();
                clearPOSForm();
            }
        }

        function clearPOSForm() {
            document.getElementById('posProductSelect').value = '';
            document.getElementById('posProductQty').value = '1';
            document.getElementById('posServiceSelect').value = '';
            document.getElementById('posServiceQty').value = '1';
        }

        function resetPOSSystem() {
            posCart = [];
            posCustomerInfo = {
                patientId: '',
                customerName: '',
                petName: '',
                petType: ''
            };
            updatePOSDisplay();
            clearPOSForm();
            document.getElementById('posCustomerName').value = '';
            document.getElementById('posCustomerName').disabled = false;
            document.getElementById('posPatientId').value = '';
            document.getElementById('posPaymentMethod').value = 'Cash';
            document.getElementById('posPrintReceipt').checked = true;
            document.getElementById('posEmailReceipt').checked = false;
        }

        function processPOSSale() {
            if (posCart.length === 0) {
                alert('Please add items to the cart before processing the sale.');
                return;
            }
            
            const customerName = document.getElementById('posCustomerName').value || posCustomerInfo.customerName;
            if (!customerName) {
                alert('Please enter customer name or select a customer.');
                return;
            }
            
            // Final stock validation before processing
            let stockValidationError = false;
            let errorMessage = '';
            
            posCart.forEach(item => {
                if (item.type === 'product') {
                    const productSelect = document.getElementById('posProductSelect');
                    const selectedOption = productSelect.querySelector(`option[value="${item.id}"]`);
                    if (selectedOption) {
                        const currentStock = parseInt(selectedOption.getAttribute('data-stock'));
                        if (item.quantity > currentStock) {
                            stockValidationError = true;
                            errorMessage += `Insufficient stock for ${item.name}. Available: ${currentStock}, Requested: ${item.quantity}\n`;
                        }
                    }
                }
            });
            
            if (stockValidationError) {
                alert('Stock validation failed:\n' + errorMessage + '\nPlease update your cart and try again.');
                return;
            }
            
            const paymentMethod = document.getElementById('posPaymentMethod').value;
            const printReceipt = document.getElementById('posPrintReceipt').checked;
            const emailReceipt = document.getElementById('posEmailReceipt').checked;
            
            // Calculate totals
            let subtotal = 0;
            posCart.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            const tax = subtotal * 0.12;
            const total = subtotal + tax;
            
            // Prepare sale data
            const saleData = {
                action: 'add_sale',
                patient_id: posCustomerInfo.patientId || null,
                items: JSON.stringify(posCart),
                total_amount: total,
                payment_method: paymentMethod,
                pet_type: posCustomerInfo.petName || 'N/A',
                customer_name: customerName
            };
            
            // Submit sale data
            submitPOSSale(saleData, printReceipt, emailReceipt);
            
            // Generate receipt if requested
            if (printReceipt) {
                generatePOSReceipt(saleData, posCart);
            }
        }

        function submitPOSSale(saleData, printReceipt, emailReceipt) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            Object.keys(saleData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = saleData[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            
            // Show success message
            showPOSSuccess();
            
            // Close POS modal
            closeModal('posModal');
            
            // Submit the form
            form.submit();
        }

        function generatePOSReceipt(saleData, cartItems) {
            const currentDate = new Date().toLocaleString();
            const customerName = saleData.customer_name;
            const petName = saleData.pet_type;
            const paymentMethod = saleData.payment_method;
            const total = saleData.total_amount;
            const serviceFromAppt = new URLSearchParams(window.location.search).get('service') || '';
            const doctorFromAppt = new URLSearchParams(window.location.search).get('doctor') || '';
            
            // Calculate subtotal and tax
            let subtotal = 0;
            cartItems.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            const tax = subtotal * 0.12;
            
            // Create receipt content with improved design
            const receiptContent = `
                <div style="font-family: 'Inter', Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; background: white; color: #1e293b;">
                    <!-- Header Section -->
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h1 style="margin: 0; color: #8BC34A; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">Mabolo Veterinary Clinic</h1>
                        <p style="margin: 8px 0 4px 0; color: #64748b; font-size: 14px;">Juan Luna Ave, Mabolo, Cebu City, Philippines</p>
                        <p style="margin: 4px 0; color: #64748b; font-size: 14px;">Phone: 233-20-30</p>
                        <p style="margin: 4px 0; color: #64748b; font-size: 14px;">VAT Reg TIN: 649-058-316-00000</p>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                    
                    <!-- Receipt Details -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px;">
                        <span style="color: #64748b; font-weight: 500;">Receipt #: POS-${Date.now()}</span>
                        <span style="color: #64748b; font-weight: 500;">Date: ${currentDate}</span>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                    
                    <!-- Customer Info -->
                    <div style="margin-bottom: 20px; font-size: 14px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Customer:</span>
                            <span style="color: #1e293b; font-weight: 500;">${customerName}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Pet:</span>
                            <span style="color: #1e293b; font-weight: 500;">${petName}</span>
                        </div>
                        ${serviceFromAppt ? `<div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Service:</span><span style="color: #1e293b; font-weight: 500;">${serviceFromAppt}</span></div>` : ''}
                        ${doctorFromAppt ? `<div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Doctor:</span><span style="color: #1e293b; font-weight: 500;">${doctorFromAppt}</span></div>` : ''}
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Payment:</span>
                            <span style="color: #1e293b; font-weight: 500;">${paymentMethod}</span>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <th style="text-align: left; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Item</th>
                                    <th style="text-align: center; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Qty</th>
                                    <th style="text-align: right; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Price</th>
                                    <th style="text-align: right; padding: 8px 0; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${cartItems.map(item => `
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="text-align: left; padding: 8px 0; color: #1e293b;">${item.name} (${item.category})</td>
                                        <td style="text-align: center; padding: 8px 0; color: #1e293b;">${item.quantity}</td>
                                        <td style="text-align: right; padding: 8px 0; color: #1e293b;">₱${item.price.toFixed(2)}</td>
                                        <td style="text-align: right; padding: 8px 0; color: #1e293b;">₱${(item.price * item.quantity).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Totals Section -->
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Subtotal:</span>
                            <span style="color: #1e293b; font-weight: 500;">₱${subtotal.toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #64748b;">Tax (12%):</span>
                            <span style="color: #1e293b; font-weight: 500;">₱${tax.toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                            <span style="color: #1e293b; font-weight: 600; font-size: 16px;">Total:</span>
                            <span style="color: #8BC34A; font-weight: 700; font-size: 18px;">₱${total.toFixed(2)}</span>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 5px 0; color: #64748b; font-size: 14px; font-weight: 500;">Thank you for your purchase!</p>
                        <p style="margin: 5px 0; color: #64748b; font-size: 14px; font-weight: 500;">Come again!</p>
                    </div>
                </div>
            `;

            // Open print window with improved styling
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>POS Receipt - Mabolo Veterinary Clinic</title>
                        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                        <style>
                            body { 
                                margin: 0; 
                                padding: 20px; 
                                background: #f8fafc; 
                                font-family: 'Inter', Arial, sans-serif;
                            }
                            @media print {
                                body { 
                                    margin: 0; 
                                    padding: 0; 
                                    background: white; 
                                }
                                .no-print { display: none; }
                                .receipt-container {
                                    box-shadow: none !important;
                                    border: none !important;
                                }
                            }
                            .receipt-container {
                                background: white;
                                border-radius: 12px;
                                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                max-width: 400px;
                                margin: 0 auto;
                                overflow: hidden;
                            }
                            .print-btn {
                                background: #8BC34A;
                                color: white;
                                border: none;
                                border-radius: 8px;
                                padding: 12px 24px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: background 0.2s;
                                font-family: 'Inter', sans-serif;
                            }
                            .print-btn:hover {
                                background: #7CB342;
                            }
                            .close-btn {
                                background: #64748b;
                                color: white;
                                border: none;
                                border-radius: 8px;
                                padding: 12px 24px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: background 0.2s;
                                font-family: 'Inter', sans-serif;
                                margin-left: 10px;
                            }
                            .close-btn:hover {
                                background: #475569;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="receipt-container">
                            ${receiptContent}
                        </div>
                        <div class="no-print" style="text-align: center; margin-top: 20px;">
                            <button onclick="window.print()" class="print-btn">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <button onclick="window.close()" class="close-btn">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Auto-hide success message
        const successMessage = document.getElementById('successMessage');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 5000);
        }

        // Show success message after POS sale
        function showPOSSuccess() {
            const successDiv = document.createElement('div');
            successDiv.className = 'message success-message';
            successDiv.innerHTML = '<i class="fas fa-check-circle"></i> POS Sale completed successfully! Receipt generated. Stock updated.';
            successDiv.style.position = 'fixed';
            successDiv.style.top = '20px';
            successDiv.style.right = '20px';
            successDiv.style.zIndex = '9999';
            successDiv.style.minWidth = '300px';
            
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                successDiv.remove();
            }, 5000);
        }

        // Function to refresh product list after sale
        function refreshProductList() {
            // This would typically make an AJAX call to refresh the product list
            // For now, we'll just show a message that the page should be refreshed
            setTimeout(() => {
                alert('Product list has been updated. Please refresh the page to see current stock levels.');
            }, 2000);
        }
    </script>
</body>
</html>

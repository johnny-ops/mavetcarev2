<?php
session_name('admin_session');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: adminLogin.php');
    exit();
}

// Handle form submissions and actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_patient':
            // Add patient functionality
            $pet_name = $_POST['pet_name'] ?? '';
            $client_name = $_POST['client_name'] ?? '';
            $species = $_POST['species'] ?? '';
            $breed = $_POST['breed'] ?? '';
            $age = $_POST['age'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            
            if ($pet_name && $client_name && $species) {
                $stmt = $pdo->prepare("INSERT INTO patients (pet_name, client_name, species, breed, age, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$pet_name, $client_name, $species, $breed, $age, $contact_number]);
                $success_message = "Patient added successfully!";
            }
            break;
            
        case 'add_appointment':
            // Add appointment functionality
            $patient_id = $_POST['patient_id'] ?? '';
            $service = $_POST['service'] ?? '';
            $appointment_date = $_POST['appointment_date'] ?? '';
            $appointment_time = $_POST['appointment_time'] ?? '';
            $doctor_id = $_POST['doctor_id'] ?? '';
            
            if ($patient_id && $service && $appointment_date && $appointment_time) {
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, service, appointment_date, appointment_time, doctor_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$patient_id, $service, $appointment_date, $appointment_time, $doctor_id]);
                $success_message = "Appointment scheduled successfully!";
            }
            break;
            
        case 'add_sale':
            // Add sale functionality
            $patient_id = $_POST['patient_id'] ?? '';
            $items_or_services = $_POST['items_or_services'] ?? '';
            $total_amount = $_POST['total_amount'] ?? '';
            $payment_method = $_POST['payment_method'] ?? '';
            
            if ($patient_id && $items_or_services && $total_amount && $payment_method) {
                $stmt = $pdo->prepare("INSERT INTO sales (patient_id, items_or_services, total_amount, payment_method, sale_date) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$patient_id, $items_or_services, $total_amount, $payment_method]);
                $success_message = "Sale recorded successfully!";
            }
            break;
            
        case 'add_inventory':
            // Add inventory item functionality
            $name = $_POST['name'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = $_POST['price'] ?? '';
            $quantity = $_POST['quantity'] ?? '';
            $minimum_stock = $_POST['minimum_stock'] ?? '';
            
            if ($name && $category && $price && $quantity && $minimum_stock) {
                $stmt = $pdo->prepare("INSERT INTO inventory (name, category, price, quantity, minimum_stock) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $category, $price, $quantity, $minimum_stock]);
                $success_message = "Inventory item added successfully!";
            }
            break;
            
        case 'add_staff':
            // Add staff functionality
            $name = $_POST['name'] ?? '';
            $position = $_POST['position'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if ($name && $position && $email && $phone) {
                $stmt = $pdo->prepare("INSERT INTO staff (name, position, email, phone, status, hire_date) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $position, $email, $phone, $status]);
                $success_message = "Staff member added successfully!";
            }
            break;
            
        case 'add_service':
            // Add service functionality
            $service_name = $_POST['service_name'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = $_POST['price'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if ($service_name && $category && $price) {
                $stmt = $pdo->prepare("INSERT INTO services (service_name, category, price, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$service_name, $category, $price, $description]);
                $success_message = "Service added successfully!";
            }
            break;
            
        case 'update_appointment_status':
            // Update appointment status
            $appointment_id = $_POST['appointment_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if ($appointment_id && $status) {
                $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                $stmt->execute([$status, $appointment_id]);
                $success_message = "Appointment status updated successfully!";
            }
            break;
            
        case 'assign_doctor':
            // Assign doctor to appointment
            $appointment_id = $_POST['appointment_id'] ?? '';
            $doctor_id = $_POST['doctor_id'] ?? '';
            
            if ($appointment_id && $doctor_id) {
                $stmt = $pdo->prepare("UPDATE appointments SET doctor_id = ? WHERE id = ?");
                $stmt->execute([$doctor_id, $appointment_id]);
                $success_message = "Doctor assigned successfully!";
            }
            break;
            
        case 'update_inventory_stock':
            // Update inventory stock
            $inventory_id = $_POST['inventory_id'] ?? '';
            $quantity = $_POST['quantity'] ?? '';
            
            if ($inventory_id && $quantity !== '') {
                $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $stmt->execute([$quantity, $inventory_id]);
                $success_message = "Inventory stock updated successfully!";
            }
            break;
    }
}

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    switch ($export_type) {
        case 'patients':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="patients_export.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Pet Name', 'Client Name', 'Species', 'Breed', 'Age', 'Contact Number', 'Created Date']);
            
            $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit();
            break;
            
        case 'appointments':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="appointments_export.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Patient', 'Service', 'Date', 'Time', 'Doctor', 'Status']);
            
            $stmt = $pdo->query("
                SELECT a.*, p.pet_name, s.name as doctor_name 
                FROM appointments a 
                LEFT JOIN patients p ON a.patient_id = p.id 
                LEFT JOIN staff s ON a.doctor_id = s.id 
                ORDER BY a.appointment_date DESC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['pet_name'],
                    $row['service'],
                    $row['appointment_date'],
                    $row['appointment_time'],
                    $row['doctor_name'],
                    $row['status']
                ]);
            }
            fclose($output);
            exit();
            break;
            
        case 'sales':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sales_export.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Patient', 'Items/Services', 'Amount', 'Payment Method', 'Date']);
            
            $stmt = $pdo->query("
                SELECT s.*, p.pet_name 
                FROM sales s 
                LEFT JOIN patients p ON s.patient_id = p.id 
                ORDER BY s.sale_date DESC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['pet_name'],
                    $row['items_or_services'],
                    $row['total_amount'],
                    $row['payment_method'],
                    $row['sale_date']
                ]);
            }
            fclose($output);
            exit();
            break;
            
        case 'inventory':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="inventory_export.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Name', 'Category', 'Price', 'Quantity', 'Minimum Stock']);
            
            $stmt = $pdo->query("SELECT * FROM inventory ORDER BY category, name");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit();
            break;
    }
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

// Get dashboard statistics
try {
    // Total patients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total appointments today
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = CURDATE()");
    $todayAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total sales this month
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $monthlySales = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Low stock items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory WHERE quantity <= minimum_stock");
    $lowStockItems = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent appointments
    $stmt = $pdo->query("
        SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN staff s ON a.doctor_id = s.id 
        WHERE a.appointment_date >= CURDATE() 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC 
        LIMIT 5
    ");
    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent sales
    $stmt = $pdo->query("
        SELECT s.*, p.pet_name, p.client_name 
        FROM sales s 
        LEFT JOIN patients p ON s.patient_id = p.id 
        ORDER BY s.sale_date DESC 
        LIMIT 5
    ");
    $recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low stock inventory
    $stmt = $pdo->query("
        SELECT * FROM inventory 
        WHERE quantity <= minimum_stock 
        ORDER BY quantity ASC 
        LIMIT 5
    ");
    $lowStockInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Analytics Data
    // Monthly revenue for the last 6 months
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(sale_date, '%Y-%m') as month, 
               SUM(total_amount) as revenue 
        FROM sales 
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Appointment statistics
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM appointments 
        GROUP BY status
    ");
    $appointmentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Patient species distribution
    $stmt = $pdo->query("
        SELECT species, COUNT(*) as count 
        FROM patients 
        GROUP BY species
    ");
    $speciesDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All patients for patients section
    $stmt = $pdo->query("
        SELECT * FROM patients 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $allPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All sales for sales section
    $stmt = $pdo->query("
        SELECT s.*, p.pet_name, p.client_name 
        FROM sales s 
        LEFT JOIN patients p ON s.patient_id = p.id 
        ORDER BY s.sale_date DESC 
        LIMIT 20
    ");
    $allSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All inventory for inventory section
    $stmt = $pdo->query("
        SELECT * FROM inventory 
        ORDER BY category, name ASC
    ");
    $allInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sales summary statistics
    $stmt = $pdo->query("
        SELECT 
            SUM(total_amount) as total_revenue,
            COUNT(*) as total_transactions,
            AVG(total_amount) as avg_transaction,
            SUM(CASE WHEN payment_method = 'Cash' THEN total_amount ELSE 0 END) as cash_sales,
            SUM(CASE WHEN payment_method = 'Card' THEN total_amount ELSE 0 END) as card_sales
        FROM sales 
        WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())
    ");
    $salesSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Appointments Data
    $stmt = $pdo->query("
        SELECT a.*, p.pet_name, p.client_name, p.client_contact, s.name as doctor_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN staff s ON a.doctor_id = s.id 
        ORDER BY a.appointment_date DESC, a.appointment_time ASC
    ");
    $allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Today's appointments
    $stmt = $pdo->query("
        SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN staff s ON a.doctor_id = s.id 
        WHERE DATE(a.appointment_date) = CURDATE()
        ORDER BY a.appointment_time ASC
    ");
    $todayAppointmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Upcoming appointments
    $stmt = $pdo->query("
        SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN staff s ON a.doctor_id = s.id 
        WHERE a.appointment_date > CURDATE()
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 10
    ");
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Staff Data
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE position LIKE '%Doctor%'");
    $totalDoctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE status = 'on-duty'");
    $onDutyStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $recentHires = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY position, name ASC");
    $allStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Services Data
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM services");
    $totalServices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT AVG(price) as average FROM services");
    $averageServicePrice = $stmt->fetch(PDO::FETCH_ASSOC)['average'] ?? 0;
    
    $stmt = $pdo->query("SELECT MAX(price) as max_price FROM services");
    $maxServicePrice = $stmt->fetch(PDO::FETCH_ASSOC)['max_price'] ?? 0;
    
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM services GROUP BY category");
    $servicesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM services ORDER BY category, service_name ASC");
    $allServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Products Data
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT SUM(quantity * price) as total_value FROM inventory");
    $totalInventoryValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM inventory GROUP BY category");
    $productsByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY category, name ASC");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Settings Data
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'admin'");
    $adminUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'client'");
    $clientUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $recentRegistrations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT * FROM users WHERE user_type = 'admin' ORDER BY created_at DESC");
    $adminUsersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MavetCare</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .stat-icon.patients { background: #3b82f6; }
        .stat-icon.appointments { background: #8BC34A; }
        .stat-icon.sales { background: #f59e0b; }
        .stat-icon.inventory { background: #ef4444; }
        
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
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        /* Doctor Assignment Styles */
        .form-input.doctor-assignment {
            min-width: 150px;
        }
        
        .form-input.doctor-assignment:focus {
            border-color: #8BC34A;
            box-shadow: 0 0 0 2px rgba(139, 195, 74, 0.2);
        }
        .font-weight-600 { font-weight: 600; }
        .text-sm { font-size: 0.875rem; }
        .text-gray-500 { color: #6b7280; }
        .text-center { text-align: center; }
        
        /* Dynamic Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .section-content {
            padding: 1.5rem;
        }
        
        /* Analytics Charts */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        /* Enhanced Analytics Styles */
        .analytics-section {
            margin-bottom: 2rem;
        }

        .analytics-subtitle {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .insight-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .insight-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .insight-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            flex-shrink: 0;
        }

        .insight-card:nth-child(1) .insight-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .insight-card:nth-child(2) .insight-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .insight-card:nth-child(3) .insight-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .insight-card:nth-child(4) .insight-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .insight-content h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .insight-content p {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        /* Staff icon color */
        .stat-icon.staff {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Services icon color */
        .stat-icon.services {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }

        /* Products icon color */
        .stat-icon.products {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        /* Analytics Summary Styles */
        .analytics-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            margin: 2rem 0;
        }

        .summary-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .summary-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .summary-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        .summary-stat {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            font-size: 0.875rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Patient Cards */
        .patient-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .patient-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .patient-species {
            background: #e0f2fe;
            color: #0277bd;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .patient-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 500;
            color: #1e293b;
        }
        
        /* Inventory Items */
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
        
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .inventory-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .stock-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .stock-ok {
            background: #d1fae5;
            color: #065f46;
        }
        
        .stock-low {
            background: #fef3c7;
            color: #92400e;
        }
        
        .stock-out {
            background: #fee2e2;
            color: #991b1b;
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
        }
        
        /* Sales Summary */
        .sales-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .sales-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .sales-value {
            font-size: 2rem;
            font-weight: 700;
            color: #8BC34A;
            margin-bottom: 0.5rem;
        }
        
        .sales-label {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Staff Section */
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .staff-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .staff-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .staff-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .position-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .position-doctor { background: #dbeafe; color: #1e40af; }
        .position-veterinarian { background: #d1fae5; color: #065f46; }
        .position-nurse { background: #fef3c7; color: #92400e; }
        .position-receptionist { background: #f3e8ff; color: #7c3aed; }
        .position-technician { background: #fef2f2; color: #dc2626; }
        
        .staff-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .staff-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-on-duty { background: #d1fae5; color: #065f46; }
        .status-off-duty { background: #fee2e2; color: #991b1b; }
        .status-on-leave { background: #fef3c7; color: #92400e; }
        
        /* Services Section */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .service-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .service-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .category-treatment { background: #dbeafe; color: #1e40af; }
        .category-surgery { background: #fef3c7; color: #92400e; }
        .category-vaccination { background: #d1fae5; color: #065f46; }
        .category-grooming { background: #f3e8ff; color: #7c3aed; }
        .category-diagnostic { background: #fef2f2; color: #dc2626; }
        
        .service-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #8BC34A;
            margin-bottom: 0.5rem;
        }
        
        .service-description {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.5;
        }
        
        /* Settings Section */
        .settings-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .settings-form {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #8BC34A;
            box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.1);
        }
        
        .user-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
                 .user-type-admin { background: #dbeafe; color: #1e40af; }
         .user-type-client { background: #d1fae5; color: #065f46; }
         
         /* Modal Styles */
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
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        /* Scroll animations for modal content */
        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-body .form-group {
            opacity: 0;
            transform: translateY(20px);
            animation: slideInUp 0.5s ease forwards;
        }

        .modal-body .form-group:nth-child(1) { animation-delay: 0.1s; }
        .modal-body .form-group:nth-child(2) { animation-delay: 0.2s; }
        .modal-body .form-group:nth-child(3) { animation-delay: 0.3s; }
        .modal-body .form-group:nth-child(4) { animation-delay: 0.4s; }
        .modal-body .form-group:nth-child(5) { animation-delay: 0.5s; }
        .modal-body .form-group:nth-child(6) { animation-delay: 0.6s; }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Additional scroll animations for form groups */
        .form-group {
            transition: all 0.3s ease;
        }

        .form-group:hover {
            transform: translateX(5px);
        }

        .form-input:focus {
            transform: scale(1.02);
            transition: transform 0.2s ease;
        }

        /* Smooth scroll for modal content */
        .modal-content {
            scroll-behavior: smooth;
        }

        /* Enhanced modal backdrop animation */
        .modal {
            backdrop-filter: blur(5px);
            transition: backdrop-filter 0.3s ease;
        }
         
         /* Custom Scrollbar for Modal */
         .modal-content::-webkit-scrollbar {
             width: 8px;
         }
         
         .modal-content::-webkit-scrollbar-track {
             background: #f1f1f1;
             border-radius: 4px;
         }
         
         .modal-content::-webkit-scrollbar-thumb {
             background: #8BC34A;
             border-radius: 4px;
         }
         
         .modal-content::-webkit-scrollbar-thumb:hover {
             background: #7CB342;
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
         
         /* Dropdown Styles */
         .dropdown {
             position: relative;
             display: inline-block;
         }
         
         .dropdown-toggle {
             background: #64748b;
             color: white;
             border: none;
             padding: 0.75rem 1.5rem;
             border-radius: 8px;
             font-weight: 500;
             cursor: pointer;
             display: inline-flex;
             align-items: center;
             gap: 0.5rem;
         }
         
         .dropdown-toggle:hover {
             background: #475569;
         }
         
         .dropdown-menu {
             display: none;
             position: absolute;
             right: 0;
             background-color: white;
             min-width: 200px;
             box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
             border-radius: 8px;
             z-index: 1000;
             border: 1px solid #e2e8f0;
         }
         
         .dropdown-menu.show {
             display: block;
         }
         
         .dropdown-item {
             color: #1e293b;
             padding: 0.75rem 1rem;
             text-decoration: none;
             display: block;
             transition: background-color 0.2s;
         }
         
         .dropdown-item:hover {
             background-color: #f8fafc;
             text-decoration: none;
         }
         
        /* Chart Styles */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            text-align: center;
        }
         
         /* Success Message */
         .success-message {
             background: #d1fae5;
             color: #065f46;
             padding: 1rem;
             border-radius: 8px;
             margin-bottom: 1rem;
             border: 1px solid #a7f3d0;
         }
         
         @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
                    <a href="#" class="nav-item active" data-section="overview">
                        <i class="fas fa-tachometer-alt"></i>
                        Overview
                    </a>
                    <a href="/admin/analytics.php" class="nav-item" data-section="analytics">
                        <i class="fas fa-chart-line"></i>
                        Analytics
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Management</div>
                    <a href="/admin/patients.php" class="nav-item" data-section="patients">
                        <i class="fas fa-users"></i>
                        Patients
                    </a>
                    <a href="#" class="nav-item" data-section="appointments">
                        <i class="fas fa-calendar-check"></i>
                        Appointments
                    </a>
                    <a href="/admin/sales.php" class="nav-item" data-section="sales">
                        <i class="fas fa-shopping-cart"></i>
                        Sales
                    </a>
                    <a href="#" class="nav-item" data-section="inventory">
                        <i class="fas fa-boxes"></i>
                        Inventory
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Staff</div>
                    <a href="staff.php" class="nav-item" data-section="staff">
                        <i class="fas fa-user-md"></i>
                        Doctors
                    </a>
                    <a href="#" class="nav-item" data-section="staff">
                        <i class="fas fa-user-nurse"></i>
                        Staff
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Services</div>
                    <a href="services.php" class="nav-item" data-section="services">
                        <i class="fas fa-stethoscope"></i>
                        Services
                    </a>
                    <a href="products.php" class="nav-item" data-section="products">
                        <i class="fas fa-tags"></i>
                        Products
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">System</div>
                    <a href="#" class="nav-item" data-section="settings">
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
                <h1 class="page-title" id="page-title">Dashboard Overview</h1>
                                 <div class="header-actions">
                     <a href="#" class="btn btn-primary" onclick="openModal('appointmentModal')">
                         <i class="fas fa-plus"></i>
                         New Appointment
                     </a>
                     <div class="dropdown">
                         <button class="btn btn-secondary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown">
                             <i class="fas fa-download"></i>
                             Export Report
                         </button>
                         <div class="dropdown-menu">
                             <a class="dropdown-item" href="?export=patients">Export Patients</a>
                             <a class="dropdown-item" href="?export=appointments">Export Appointments</a>
                             <a class="dropdown-item" href="?export=sales">Export Sales</a>
                             <a class="dropdown-item" href="?export=inventory">Export Inventory</a>
                         </div>
                     </div>
                 </div>
            </div>
            
            <!-- Overview Section -->
            <div class="content-section active" id="overview-section">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Total Patients</span>
                            <div class="stat-icon patients">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalPatients); ?></div>
                        <div class="stat-change">+12% from last month</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Today's Appointments</span>
                            <div class="stat-icon appointments">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($todayAppointments); ?></div>
                        <div class="stat-change"><?php echo $todayAppointments > 0 ? 'Active' : 'No appointments'; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Monthly Sales</span>
                            <div class="stat-icon sales">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stat-value">₱<?php echo number_format($monthlySales, 2); ?></div>
                        <div class="stat-change">+8.5% from last month</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Low Stock Items</span>
                            <div class="stat-icon inventory">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($lowStockItems); ?></div>
                        <div class="stat-change negative">Needs attention</div>
                    </div>
                </div>
                
                <!-- Analytics Summary -->
                <div class="analytics-summary" style="margin: 2rem 0;">
                    <div class="summary-header">
                        <h2 class="summary-title">Analytics Summary</h2>
                        <p class="summary-subtitle">Key performance indicators and insights for your veterinary clinic</p>
                    </div>
                    <div class="summary-stats">
                        <div class="summary-stat">
                            <div class="summary-value">₱<?php echo number_format($salesSummary['total_revenue'] ?? 0, 2); ?></div>
                            <div class="summary-label">Monthly Revenue</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-value"><?php echo number_format($totalPatients); ?></div>
                            <div class="summary-label">Total Patients</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-value"><?php echo number_format($completionRate ?? 0, 1); ?>%</div>
                            <div class="summary-label">Completion Rate</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-value"><?php echo number_format($totalInventoryValue, 2); ?></div>
                            <div class="summary-label">Inventory Value</div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Recent Appointments -->
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">Recent Appointments</h2>
                            <a href="#" class="section-action">View All</a>
                        </div>
                        <div class="section-content">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Service</th>
                                            <th>Date & Time</th>
                                            <th>Doctor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentAppointments)): ?>
                                            <?php foreach ($recentAppointments as $appointment): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <div class="font-weight-600"><?php echo htmlspecialchars($appointment['pet_name']); ?></div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $date = new DateTime($appointment['appointment_date']);
                                                        $time = new DateTime($appointment['appointment_time']);
                                                        echo $date->format('M d, Y') . ' at ' . $time->format('g:i A');
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unassigned'); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-gray-500">No recent appointments</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Sales -->
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">Recent Sales</h2>
                            <a href="#" class="section-action">View All</a>
                        </div>
                        <div class="section-content">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Amount</th>
                                            <th>Payment</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentSales)): ?>
                                            <?php foreach ($recentSales as $sale): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <div class="font-weight-600"><?php echo htmlspecialchars($sale['pet_name'] ?? 'N/A'); ?></div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sale['client_name'] ?? 'N/A'); ?></div>
                                                        </div>
                                                    </td>
                                                    <td class="font-weight-600">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($sale['payment_method']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $saleDate = new DateTime($sale['sale_date']);
                                                        echo $saleDate->format('M d, Y');
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-gray-500">No recent sales</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Charts -->
                <div class="content-grid" style="margin-top: 2rem;">
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">Monthly Revenue Trend</h2>
                        </div>
                        <div class="section-content">
                            <canvas id="revenueChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">Appointment Status</h2>
                        </div>
                        <div class="section-content">
                            <canvas id="appointmentChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Analytics Dashboard -->
                <div class="content-section" style="margin-top: 2rem;">
                    <div class="section-header">
                        <h2 class="section-title">Analytics Overview</h2>
                    </div>
                    <div class="section-content">
                        <!-- Sales Analytics -->
                        <div class="analytics-section">
                            <h3 class="analytics-subtitle">Sales Performance</h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Total Revenue (This Month)</span>
                                        <div class="stat-icon sales">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($salesSummary['total_revenue'] ?? 0, 2); ?></div>
                                    <div class="stat-change"><?php echo $salesSummary['total_transactions'] ?? 0; ?> transactions</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Average Transaction</span>
                                        <div class="stat-icon sales">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($salesSummary['avg_transaction'] ?? 0, 2); ?></div>
                                    <div class="stat-change">Per transaction</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Cash Sales</span>
                                        <div class="stat-icon sales">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($salesSummary['cash_sales'] ?? 0, 2); ?></div>
                                    <div class="stat-change">Cash payments</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Card Sales</span>
                                        <div class="stat-icon sales">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($salesSummary['card_sales'] ?? 0, 2); ?></div>
                                    <div class="stat-change">Card payments</div>
                                </div>
                            </div>
                        </div>

                        <!-- Operational Analytics -->
                        <div class="analytics-section" style="margin-top: 2rem;">
                            <h3 class="analytics-subtitle">Operational Metrics</h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Appointment Completion Rate</span>
                                        <div class="stat-icon appointments">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">
                                        <?php 
                                        $totalAppointments = array_sum(array_column($appointmentStats, 'count'));
                                        $completedAppointments = 0;
                                        foreach ($appointmentStats as $stat) {
                                            if ($stat['status'] == 'completed') {
                                                $completedAppointments = $stat['count'];
                                                break;
                                            }
                                        }
                                        $completionRate = $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 1) : 0;
                                        echo $completionRate . '%';
                                        ?>
                                    </div>
                                    <div class="stat-change"><?php echo $completedAppointments; ?> of <?php echo $totalAppointments; ?> completed</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Patient Growth</span>
                                        <div class="stat-icon patients">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value"><?php echo number_format($totalPatients); ?></div>
                                    <div class="stat-change">Total registered patients</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Inventory Value</span>
                                        <div class="stat-icon inventory">
                                            <i class="fas fa-boxes"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($totalInventoryValue, 2); ?></div>
                                    <div class="stat-change">Total stock value</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Staff Efficiency</span>
                                        <div class="stat-icon staff">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">
                                        <?php 
                                        $totalStaff = count($allStaff);
                                        $activeStaff = 0;
                                        foreach ($allStaff as $staff) {
                                            if ($staff['status'] == 'active') {
                                                $activeStaff++;
                                            }
                                        }
                                        echo $activeStaff . '/' . $totalStaff;
                                        ?>
                                    </div>
                                    <div class="stat-change">Active staff members</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Insights -->
                        <div class="analytics-section" style="margin-top: 2rem;">
                            <h3 class="analytics-subtitle">Quick Insights</h3>
                            <div class="insights-grid">
                                <div class="insight-card">
                                    <div class="insight-icon">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h4>Today's Schedule</h4>
                                        <p><?php echo count($todayAppointmentsList); ?> appointments scheduled for today</p>
                                    </div>
                                </div>
                                
                                <div class="insight-card">
                                    <div class="insight-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h4>Stock Alerts</h4>
                                        <p><?php echo count($lowStockInventory); ?> items need restocking</p>
                                    </div>
                                </div>
                                
                                <div class="insight-card">
                                    <div class="insight-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h4>Top Species</h4>
                                        <p>
                                            <?php 
                                            if (!empty($speciesDistribution)) {
                                                $topSpecies = $speciesDistribution[0];
                                                echo $topSpecies['species'] . ' (' . $topSpecies['count'] . ' patients)';
                                            } else {
                                                echo 'No data available';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="insight-card">
                                    <div class="insight-icon">
                                        <i class="fas fa-trending-up"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h4>Revenue Trend</h4>
                                        <p>
                                            <?php 
                                            if (count($monthlyRevenue) >= 2) {
                                                $currentMonth = end($monthlyRevenue);
                                                $previousMonth = prev($monthlyRevenue);
                                                $currentRevenue = $currentMonth['revenue'] ?? 0;
                                                $previousRevenue = $previousMonth['revenue'] ?? 0;
                                                
                                                if ($previousRevenue > 0) {
                                                    $growth = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
                                                    $trend = $growth >= 0 ? '+' . round($growth, 1) . '%' : round($growth, 1) . '%';
                                                    echo $trend . ' from last month';
                                                } else {
                                                    echo 'New data';
                                                }
                                            } else {
                                                echo 'Insufficient data';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service & Product Analytics -->
                        <div class="analytics-section" style="margin-top: 2rem;">
                            <h3 class="analytics-subtitle">Service & Product Overview</h3>
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
                                        <span class="stat-title">Average Service Price</span>
                                        <div class="stat-icon services">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($averageServicePrice, 2); ?></div>
                                    <div class="stat-change">Per service</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">Total Products</span>
                                        <div class="stat-icon products">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                                    <div class="stat-change">In inventory</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-header">
                                        <span class="stat-title">On-Duty Staff</span>
                                        <div class="stat-icon staff">
                                            <i class="fas fa-user-clock"></i>
                                        </div>
                                    </div>
                                    <div class="stat-value"><?php echo number_format($onDutyStaff); ?></div>
                                    <div class="stat-change">Currently available</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($lowStockInventory)): ?>
                <div class="content-section" style="margin-top: 2rem;">
                    <div class="section-header">
                        <h2 class="section-title">Low Stock Alert</h2>
                        <a href="#" class="section-action">Manage Inventory</a>
                    </div>
                    <div class="section-content">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Minimum Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockInventory as $item): ?>
                                        <tr>
                                            <td class="font-weight-600"><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td class="font-weight-600"><?php echo $item['quantity']; ?></td>
                                            <td><?php echo $item['minimum_stock']; ?></td>
                                            <td>
                                                <span class="status-badge status-cancelled">
                                                    Low Stock
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Analytics Section -->
            <div class="content-section" id="analytics-section">
                <div class="chart-container">
                    <h3 class="chart-title">Monthly Revenue Trend</h3>
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
                
                <div class="content-grid">
                    <div class="chart-container">
                        <h3 class="chart-title">Appointment Status Distribution</h3>
                        <canvas id="appointmentChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3 class="chart-title">Patient Species Distribution</h3>
                        <canvas id="speciesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Patients Section -->
            <div class="content-section" id="patients-section">
                                 <div class="section-header">
                     <h2 class="section-title">Patient Records</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('patientModal')">
                         <i class="fas fa-plus"></i>
                         Add Patient
                     </a>
                 </div>
                <div class="section-content">
                    <?php if (!empty($allPatients)): ?>
                        <?php foreach ($allPatients as $patient): ?>
                            <div class="patient-card">
                                <div class="patient-header">
                                    <div class="patient-name"><?php echo htmlspecialchars($patient['pet_name']); ?></div>
                                    <span class="patient-species"><?php echo htmlspecialchars($patient['species']); ?></span>
                                </div>
                                <div class="patient-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Owner</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['client_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Breed</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['breed']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Age</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['age']); ?> years</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['contact_number']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-gray-500">No patients found</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sales Section -->
            <div class="content-section" id="sales-section">
                                 <div class="section-header">
                     <h2 class="section-title">Sales Management</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('saleModal')">
                         <i class="fas fa-plus"></i>
                         New Sale
                     </a>
                 </div>
                
                <!-- Sales Summary -->
                <div class="sales-summary">
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($salesSummary['total_revenue'] ?? 0, 2); ?></div>
                        <div class="sales-label">Total Revenue (This Month)</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($salesSummary['total_transactions'] ?? 0); ?></div>
                        <div class="sales-label">Total Transactions</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($salesSummary['avg_transaction'] ?? 0, 2); ?></div>
                        <div class="sales-label">Average Transaction</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($salesSummary['cash_sales'] ?? 0, 2); ?></div>
                        <div class="sales-label">Cash Sales</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($salesSummary['card_sales'] ?? 0, 2); ?></div>
                        <div class="sales-label">Card Sales</div>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Items/Services</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allSales)): ?>
                                    <?php foreach ($allSales as $sale): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $saleDate = new DateTime($sale['sale_date']);
                                                echo $saleDate->format('M d, Y');
                                                ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-weight-600"><?php echo htmlspecialchars($sale['pet_name'] ?? 'N/A'); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sale['client_name'] ?? 'N/A'); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($sale['items_or_services']); ?></td>
                                            <td class="font-weight-600">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($sale['payment_method']); ?></td>
                                            <td>
                                                <span class="status-badge status-confirmed">
                                                    Completed
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-500">No sales records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Section -->
            <div class="content-section" id="inventory-section">
                                 <div class="section-header">
                     <h2 class="section-title">Inventory Management</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('inventoryModal')">
                         <i class="fas fa-plus"></i>
                         Add Item
                     </a>
                 </div>
                <div class="section-content">
                    <div class="inventory-grid">
                        <?php if (!empty($allInventory)): ?>
                            <?php foreach ($allInventory as $item): ?>
                                <div class="inventory-card">
                                    <div class="inventory-header">
                                        <div class="inventory-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php
                                        $stockStatus = '';
                                        $stockClass = '';
                                        if ($item['quantity'] == 0) {
                                            $stockStatus = 'Out of Stock';
                                            $stockClass = 'stock-out';
                                        } elseif ($item['quantity'] <= $item['minimum_stock']) {
                                            $stockStatus = 'Low Stock';
                                            $stockClass = 'stock-low';
                                        } else {
                                            $stockStatus = 'In Stock';
                                            $stockClass = 'stock-ok';
                                        }
                                        ?>
                                        <span class="stock-status <?php echo $stockClass; ?>">
                                            <?php echo $stockStatus; ?>
                                        </span>
                                    </div>
                                    <div class="inventory-price">₱<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="inventory-stock">
                                        Stock: <?php echo $item['quantity']; ?> units
                                        <?php if ($item['quantity'] <= $item['minimum_stock']): ?>
                                            <br><small style="color: #ef4444;">Minimum: <?php echo $item['minimum_stock']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                                                         <div style="margin-top: 1rem; font-size: 0.875rem; color: #64748b;">
                                         Category: <?php echo htmlspecialchars($item['category']); ?>
                                     </div>
                                     <div style="margin-top: 1rem;">
                                         <button onclick="updateInventoryStock(<?php echo $item['id']; ?>, <?php echo $item['quantity']; ?>)" class="btn btn-sm btn-primary">
                                             <i class="fas fa-edit"></i> Update Stock
                                         </button>
                                     </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-500">No inventory items found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Appointments Section -->
            <div class="content-section" id="appointments-section">
                                 <div class="section-header">
                     <h2 class="section-title">Appointment Management</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('appointmentModal')">
                         <i class="fas fa-plus"></i>
                         New Appointment
                     </a>
                 </div>
                
                <!-- Appointments Summary -->
                <div class="sales-summary">
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format(count($allAppointments)); ?></div>
                        <div class="sales-label">Total Appointments</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format(count($todayAppointmentsList)); ?></div>
                        <div class="sales-label">Today's Appointments</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format(count($upcomingAppointments)); ?></div>
                        <div class="sales-label">Upcoming Appointments</div>
                    </div>
                </div>
                
                <!-- Today's Appointments -->
                <?php if (!empty($todayAppointmentsList)): ?>
                <div class="content-section" style="margin-bottom: 2rem;">
                    <div class="section-header">
                        <h3 class="section-title">Today's Schedule</h3>
                    </div>
                    <div class="section-content">
                        <div class="alert alert-info" style="background: #e3f2fd; color: #1565c0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbdefb;">
                            <i class="fas fa-info-circle"></i>
                            <strong>Doctor Assignment:</strong> For confirmed appointments, you can assign a doctor using the dropdown in the "Doctor" column.
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Service</th>
                                        <th>Doctor</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayAppointmentsList as $appointment): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $time = new DateTime($appointment['appointment_time']);
                                                echo $time->format('g:i A');
                                                ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-weight-600"><?php echo htmlspecialchars($appointment['pet_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                            <td>
                                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                                    <select onchange="assignDoctor(<?php echo $appointment['id']; ?>, this.value)" class="form-input" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                        <option value="">Select Doctor</option>
                                                        <?php foreach ($allStaff as $staff): ?>
                                                            <?php if (strpos(strtolower($staff['position']), 'doctor') !== false): ?>
                                                                <option value="<?php echo $staff['id']; ?>" <?php echo $appointment['doctor_id'] == $staff['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($staff['name']); ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unassigned'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <select onchange="updateAppointmentStatus(<?php echo $appointment['id']; ?>, this.value)" class="form-input" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- All Appointments -->
                <div class="section-content">
                    <div class="alert alert-info" style="background: #e3f2fd; color: #1565c0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbdefb;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Doctor Assignment:</strong> Once an appointment is confirmed, you can assign a doctor from the dropdown menu in the "Doctor" column.
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allAppointments)): ?>
                                    <?php foreach ($allAppointments as $appointment): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $date = new DateTime($appointment['appointment_date']);
                                                $time = new DateTime($appointment['appointment_time']);
                                                echo $date->format('M d, Y') . ' at ' . $time->format('g:i A');
                                                ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-weight-600"><?php echo htmlspecialchars($appointment['pet_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                                                                        <td>
                                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                                    <select onchange="assignDoctor(<?php echo $appointment['id']; ?>, this.value)" class="form-input" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                        <option value="">Select Doctor</option>
                                                        <?php foreach ($allStaff as $staff): ?>
                                                            <?php if (strpos(strtolower($staff['position']), 'doctor') !== false): ?>
                                                                <option value="<?php echo $staff['id']; ?>" <?php echo $appointment['doctor_id'] == $staff['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($staff['name']); ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unassigned'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <select onchange="updateAppointmentStatus(<?php echo $appointment['id']; ?>, this.value)" class="form-input" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </td>
                                         </tr>
                                     <?php endforeach; ?>
                                 <?php else: ?>
                                     <tr>
                                         <td colspan="6" class="text-center text-gray-500">No appointments found</td>
                                     </tr>
                                 <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Staff Section -->
            <div class="content-section" id="staff-section">
                                 <div class="section-header">
                     <h2 class="section-title">Staff Management</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('staffModal')">
                         <i class="fas fa-plus"></i>
                         Add Staff
                     </a>
                 </div>
                
                <!-- Staff Summary -->
                <div class="sales-summary">
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($totalStaff); ?></div>
                        <div class="sales-label">Total Staff</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($totalDoctors); ?></div>
                        <div class="sales-label">Doctors</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($onDutyStaff); ?></div>
                        <div class="sales-label">On Duty</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($recentHires); ?></div>
                        <div class="sales-label">Recent Hires (30 days)</div>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="staff-grid">
                        <?php if (!empty($allStaff)): ?>
                            <?php foreach ($allStaff as $staff): ?>
                                <div class="staff-card">
                                    <div class="staff-header">
                                        <div class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></div>
                                        <span class="position-badge position-<?php echo strtolower(str_replace(' ', '-', $staff['position'])); ?>">
                                            <?php echo htmlspecialchars($staff['position']); ?>
                                        </span>
                                    </div>
                                    <div class="staff-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Email</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($staff['email']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Phone</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($staff['phone']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Hire Date</span>
                                            <span class="detail-value">
                                                <?php 
                                                $hireDate = new DateTime($staff['hire_date']);
                                                echo $hireDate->format('M d, Y');
                                                ?>
                                            </span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Status</span>
                                            <span class="staff-status status-<?php echo str_replace(' ', '-', $staff['status']); ?>">
                                                <?php echo ucfirst($staff['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-500">No staff members found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Services Section -->
            <div class="content-section" id="services-section">
                                 <div class="section-header">
                     <h2 class="section-title">Services Management</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('serviceModal')">
                         <i class="fas fa-plus"></i>
                         Add Service
                     </a>
                 </div>
                
                <!-- Services Summary -->
                <div class="sales-summary">
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($totalServices); ?></div>
                        <div class="sales-label">Total Services</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($averageServicePrice, 2); ?></div>
                        <div class="sales-label">Average Price</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($maxServicePrice, 2); ?></div>
                        <div class="sales-label">Highest Price</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format(count($servicesByCategory)); ?></div>
                        <div class="sales-label">Categories</div>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="services-grid">
                        <?php if (!empty($allServices)): ?>
                            <?php foreach ($allServices as $service): ?>
                                <div class="service-card">
                                    <div class="service-header">
                                        <div class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                        <span class="category-badge category-<?php echo strtolower($service['category']); ?>">
                                            <?php echo htmlspecialchars($service['category']); ?>
                                        </span>
                                    </div>
                                    <div class="service-price">₱<?php echo number_format($service['price'], 2); ?></div>
                                    <div class="service-description">
                                        <?php echo htmlspecialchars($service['description'] ?? 'No description available'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-500">No services found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Products Section -->
            <div class="content-section" id="products-section">
                                 <div class="section-header">
                     <h2 class="section-title">Products Management</h2>
                     <a href="#" class="btn btn-primary" onclick="openModal('inventoryModal')">
                         <i class="fas fa-plus"></i>
                         Add Product
                     </a>
                 </div>
                
                <!-- Products Summary -->
                <div class="sales-summary">
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($totalProducts); ?></div>
                        <div class="sales-label">Total Products</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value">₱<?php echo number_format($totalInventoryValue, 2); ?></div>
                        <div class="sales-label">Inventory Value</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($lowStockItems); ?></div>
                        <div class="sales-label">Low Stock Items</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format(count($productsByCategory)); ?></div>
                        <div class="sales-label">Categories</div>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="inventory-grid">
                        <?php if (!empty($allProducts)): ?>
                            <?php foreach ($allProducts as $product): ?>
                                <div class="inventory-card">
                                    <div class="inventory-header">
                                        <div class="inventory-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <?php
                                        $stockStatus = '';
                                        $stockClass = '';
                                        if ($product['quantity'] == 0) {
                                            $stockStatus = 'Out of Stock';
                                            $stockClass = 'stock-out';
                                        } elseif ($product['quantity'] <= $product['minimum_stock']) {
                                            $stockStatus = 'Low Stock';
                                            $stockClass = 'stock-low';
                                        } else {
                                            $stockStatus = 'In Stock';
                                            $stockClass = 'stock-ok';
                                        }
                                        ?>
                                        <span class="stock-status <?php echo $stockClass; ?>">
                                            <?php echo $stockStatus; ?>
                                        </span>
                                    </div>
                                    <div class="inventory-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                    <div class="inventory-stock">
                                        Stock: <?php echo $product['quantity']; ?> units
                                        <?php if ($product['quantity'] <= $product['minimum_stock']): ?>
                                            <br><small style="color: #ef4444;">Minimum: <?php echo $product['minimum_stock']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                                                         <div style="margin-top: 1rem; font-size: 0.875rem; color: #64748b;">
                                         Category: <?php echo htmlspecialchars($product['category']); ?>
                                     </div>
                                     <div style="margin-top: 1rem;">
                                         <button onclick="updateInventoryStock(<?php echo $product['id']; ?>, <?php echo $product['quantity']; ?>)" class="btn btn-sm btn-primary">
                                             <i class="fas fa-edit"></i> Update Stock
                                         </button>
                                     </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-500">No products found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Settings Section -->
            <div class="content-section" id="settings-section">
                <div class="section-header">
                    <h2 class="section-title">System Settings</h2>
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Settings
                    </a>
                </div>
                
                <!-- Settings Summary -->
                <div class="sales-summary">
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($totalUsers); ?></div>
                        <div class="sales-label">Total Users</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($adminUsers); ?></div>
                        <div class="sales-label">Admin Users</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($clientUsers); ?></div>
                        <div class="sales-label">Client Users</div>
                    </div>
                    <div class="sales-card">
                        <div class="sales-value"><?php echo number_format($recentRegistrations); ?></div>
                        <div class="sales-label">Recent Registrations (30 days)</div>
                    </div>
                </div>
                
                <div class="settings-grid">
                    <!-- Settings Form -->
                    <div class="settings-form">
                        <h3 style="margin-bottom: 1.5rem; color: #1e293b;">Clinic Information</h3>
                        <div class="form-group">
                            <label class="form-label">Clinic Name</label>
                            <input type="text" class="form-input" value="MavetCare Veterinary Clinic" placeholder="Enter clinic name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-input" value="info@mavetcare.com" placeholder="Enter contact email">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-input" value="+63 912 345 6789" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea class="form-input" rows="3" placeholder="Enter clinic address">123 Veterinary Street, Pet City, Philippines</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Business Hours</label>
                            <input type="text" class="form-input" value="Monday - Friday: 8:00 AM - 6:00 PM" placeholder="Enter business hours">
                        </div>
                    </div>
                    
                    <!-- Recent Users -->
                    <div class="content-section">
                        <div class="section-header">
                            <h3 class="section-title">Recent Users</h3>
                        </div>
                        <div class="section-content">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentUsers)): ?>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <tr>
                                                    <td class="font-weight-600"><?php echo htmlspecialchars($user['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="user-type-badge user-type-<?php echo $user['user_type']; ?>">
                                                            <?php echo ucfirst($user['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $createdDate = new DateTime($user['created_at']);
                                                        echo $createdDate->format('M d, Y');
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-gray-500">No users found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                 </div>
     </div>
     
     <!-- Success Message Display -->
     <?php if (isset($success_message)): ?>
     <div class="success-message" id="successMessage">
         <?php echo htmlspecialchars($success_message); ?>
     </div>
     <?php endif; ?>
     
     <!-- Patient Modal -->
     <div id="patientModal" class="modal">
         <div class="modal-content">
             <div class="modal-header">
                 <h3 class="modal-title">Add New Patient</h3>
                 <button type="button" class="close" onclick="closeModal('patientModal')">&times;</button>
             </div>
             <form method="POST" class="modal-body">
                 <input type="hidden" name="action" value="add_patient">
                 <div class="form-group">
                     <label class="form-label">Pet Name *</label>
                     <input type="text" name="pet_name" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Client Name *</label>
                     <input type="text" name="client_name" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Species *</label>
                     <select name="species" class="form-input" required>
                         <option value="">Select Species</option>
                         <option value="Dog">Dog</option>
                         <option value="Cat">Cat</option>
                         <option value="Bird">Bird</option>
                         <option value="Rabbit">Rabbit</option>
                         <option value="Other">Other</option>
                     </select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Breed</label>
                     <input type="text" name="breed" class="form-input">
                 </div>
                 <div class="form-group">
                     <label class="form-label">Age (years)</label>
                     <input type="number" name="age" class="form-input" min="0" step="0.1">
                 </div>
                 <div class="form-group">
                     <label class="form-label">Contact Number</label>
                     <input type="tel" name="contact_number" class="form-input">
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeModal('patientModal')">Cancel</button>
                     <button type="submit" class="btn btn-primary">Add Patient</button>
                 </div>
             </form>
         </div>
     </div>
     
     <!-- Appointment Modal -->
     <div id="appointmentModal" class="modal">
         <div class="modal-content">
             <div class="modal-header">
                 <h3 class="modal-title">Schedule New Appointment</h3>
                 <button type="button" class="close" onclick="closeModal('appointmentModal')">&times;</button>
             </div>
             <form method="POST" class="modal-body">
                 <input type="hidden" name="action" value="add_appointment">
                 <div class="form-group">
                     <label class="form-label">Patient *</label>
                     <select name="patient_id" class="form-input" required>
                         <option value="">Select Patient</option>
                         <?php foreach ($allPatients as $patient): ?>
                         <option value="<?php echo $patient['id']; ?>">
                             <?php echo htmlspecialchars($patient['pet_name'] . ' (' . $patient['client_name'] . ')'); ?>
                         </option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Service *</label>
                     <input type="text" name="service" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Date *</label>
                     <input type="date" name="appointment_date" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Time *</label>
                     <input type="time" name="appointment_time" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Doctor</label>
                     <select name="doctor_id" class="form-input">
                         <option value="">Select Doctor</option>
                         <?php foreach ($allStaff as $staff): ?>
                         <?php if (strpos(strtolower($staff['position']), 'doctor') !== false): ?>
                         <option value="<?php echo $staff['id']; ?>">
                             <?php echo htmlspecialchars($staff['name'] . ' (' . $staff['position'] . ')'); ?>
                         </option>
                         <?php endif; ?>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeModal('appointmentModal')">Cancel</button>
                     <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                 </div>
             </form>
         </div>
     </div>
     
     <!-- Sale Modal -->
     <div id="saleModal" class="modal">
         <div class="modal-content">
             <div class="modal-header">
                 <h3 class="modal-title">Record New Sale</h3>
                 <button type="button" class="close" onclick="closeModal('saleModal')">&times;</button>
             </div>
             <form method="POST" class="modal-body">
                 <input type="hidden" name="action" value="add_sale">
                 <div class="form-group">
                     <label class="form-label">Patient</label>
                     <select name="patient_id" class="form-input">
                         <option value="">Select Patient (Optional)</option>
                         <?php foreach ($allPatients as $patient): ?>
                         <option value="<?php echo $patient['id']; ?>">
                             <?php echo htmlspecialchars($patient['pet_name'] . ' (' . $patient['client_name'] . ')'); ?>
                         </option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Items/Services *</label>
                     <textarea name="items_or_services" class="form-input" rows="3" required placeholder="Describe items or services sold"></textarea>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Total Amount *</label>
                     <input type="number" name="total_amount" class="form-input" step="0.01" min="0" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Payment Method *</label>
                     <select name="payment_method" class="form-input" required>
                         <option value="">Select Payment Method</option>
                         <option value="Cash">Cash</option>
                         <option value="Card">Card</option>
                         <option value="Bank Transfer">Bank Transfer</option>
                     </select>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeModal('saleModal')">Cancel</button>
                     <button type="submit" class="btn btn-primary">Record Sale</button>
                 </div>
             </form>
         </div>
     </div>
     
     <!-- Inventory Modal -->
     <div id="inventoryModal" class="modal">
         <div class="modal-content">
             <div class="modal-header">
                 <h3 class="modal-title">Add Inventory Item</h3>
                 <button type="button" class="close" onclick="closeModal('inventoryModal')">&times;</button>
             </div>
             <form method="POST" class="modal-body">
                 <input type="hidden" name="action" value="add_inventory">
                 <div class="form-group">
                     <label class="form-label">Item Name *</label>
                     <input type="text" name="name" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Category *</label>
                     <select name="category" class="form-input" required>
                         <option value="">Select Category</option>
                         <option value="Medication">Medication</option>
                         <option value="Food">Food</option>
                         <option value="Supplies">Supplies</option>
                         <option value="Equipment">Equipment</option>
                         <option value="Other">Other</option>
                     </select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Price *</label>
                     <input type="number" name="price" class="form-input" step="0.01" min="0" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Quantity *</label>
                     <input type="number" name="quantity" class="form-input" min="0" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Minimum Stock *</label>
                     <input type="number" name="minimum_stock" class="form-input" min="0" required>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeModal('inventoryModal')">Cancel</button>
                     <button type="submit" class="btn btn-primary">Add Item</button>
                 </div>
             </form>
         </div>
     </div>
     
     <!-- Staff Modal -->
     <div id="staffModal" class="modal">
         <div class="modal-content">
             <div class="modal-header">
                 <h3 class="modal-title">Add Staff Member</h3>
                 <button type="button" class="close" onclick="closeModal('staffModal')">&times;</button>
             </div>
             <form method="POST" class="modal-body">
                 <input type="hidden" name="action" value="add_staff">
                 <div class="form-group">
                     <label class="form-label">Name *</label>
                     <input type="text" name="name" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Position *</label>
                     <select name="position" class="form-input" required>
                         <option value="">Select Position</option>
                         <option value="Veterinarian">Veterinarian</option>
                         <option value="Veterinary Nurse">Veterinary Nurse</option>
                         <option value="Receptionist">Receptionist</option>
                         <option value="Technician">Technician</option>
                         <option value="Assistant">Assistant</option>
                     </select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Email *</label>
                     <input type="email" name="email" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Phone *</label>
                     <input type="tel" name="phone" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Status</label>
                     <select name="status" class="form-input">
                         <option value="on-duty">On Duty</option>
                         <option value="off-duty">Off Duty</option>
                         <option value="on-leave">On Leave</option>
                     </select>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeModal('staffModal')">Cancel</button>
                     <button type="submit" class="btn btn-primary">Add Staff</button>
                 </div>
             </form>
         </div>
     </div>
     
     <!-- Service Modal -->
     <div id="serviceModal" class="modal">
         <div class="modal-content">
             <div class="modal-header">
                 <h3 class="modal-title">Add New Service</h3>
                 <button type="button" class="close" onclick="closeModal('serviceModal')">&times;</button>
             </div>
             <form method="POST" class="modal-body">
                 <input type="hidden" name="action" value="add_service">
                 <div class="form-group">
                     <label class="form-label">Service Name *</label>
                     <input type="text" name="service_name" class="form-input" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Category *</label>
                     <select name="category" class="form-input" required>
                         <option value="">Select Category</option>
                         <option value="Treatment">Treatment</option>
                         <option value="Surgery">Surgery</option>
                         <option value="Vaccination">Vaccination</option>
                         <option value="Grooming">Grooming</option>
                         <option value="Diagnostic">Diagnostic</option>
                     </select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Price *</label>
                     <input type="number" name="price" class="form-input" step="0.01" min="0" required>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Description</label>
                     <textarea name="description" class="form-input" rows="3" placeholder="Service description"></textarea>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeModal('serviceModal')">Cancel</button>
                     <button type="submit" class="btn btn-primary">Add Service</button>
                 </div>
             </form>
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
        
        // Dynamic Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item[data-section]');
            const contentSections = document.querySelectorAll('.content-section');
            const pageTitle = document.getElementById('page-title');
            
            // Section titles mapping
            const sectionTitles = {
                'overview': 'Dashboard Overview',
                'analytics': 'Analytics & Reports',
                'patients': 'Patient Management',
                'appointments': 'Appointment Management',
                'sales': 'Sales Management',
                'inventory': 'Inventory Management',
                'staff': 'Staff Management',
                'services': 'Services Management',
                'products': 'Products Management',
                'settings': 'System Settings'
            };
            
            // Navigation click handler
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetSection = this.getAttribute('data-section');
                    
                    // Update active navigation
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Hide all content sections
                    contentSections.forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Show target section
                    const targetElement = document.getElementById(targetSection + '-section');
                    if (targetElement) {
                        targetElement.classList.add('active');
                    }
                    
                    // Update page title
                    if (sectionTitles[targetSection]) {
                        pageTitle.textContent = sectionTitles[targetSection];
                    }
                    
                    // Initialize charts if analytics section is shown
                    if (targetSection === 'analytics') {
                        initializeCharts();
                    }
                });
            });
            
            // Initialize charts function
            function initializeCharts() {
                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx && !revenueCtx.chart) {
                    const revenueData = <?php echo json_encode($monthlyRevenue); ?>;
                    const labels = revenueData.map(item => {
                        const date = new Date(item.month + '-01');
                        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                    });
                    const data = revenueData.map(item => parseFloat(item.revenue));
                    
                    revenueCtx.chart = new Chart(revenueCtx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Monthly Revenue',
                                data: data,
                                borderColor: '#8BC34A',
                                backgroundColor: 'rgba(139, 195, 74, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                
                // Appointment Status Chart
                const appointmentCtx = document.getElementById('appointmentChart');
                if (appointmentCtx && !appointmentCtx.chart) {
                    const appointmentData = <?php echo json_encode($appointmentStats); ?>;
                    const labels = appointmentData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
                    const data = appointmentData.map(item => parseInt(item.count));
                    const colors = ['#3b82f6', '#8BC34A', '#f59e0b', '#ef4444'];
                    
                    appointmentCtx.chart = new Chart(appointmentCtx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: colors,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
                
                // Species Distribution Chart
                const speciesCtx = document.getElementById('speciesChart');
                if (speciesCtx && !speciesCtx.chart) {
                    const speciesData = <?php echo json_encode($speciesDistribution); ?>;
                    const labels = speciesData.map(item => item.species);
                    const data = speciesData.map(item => parseInt(item.count));
                    const colors = ['#8BC34A', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
                    
                    speciesCtx.chart = new Chart(speciesCtx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Number of Patients',
                                data: data,
                                backgroundColor: colors,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
        
                 // Auto-refresh dashboard data every 5 minutes
         setInterval(function() {
             console.log('Dashboard data refreshed');
         }, 300000);
        
        // Initialize charts for overview section
        function initializeOverviewCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx && !revenueCtx.chart) {
                const revenueData = <?php echo json_encode($monthlyRevenue); ?>;
                const labels = revenueData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                });
                const data = revenueData.map(item => parseFloat(item.revenue));
                
                revenueCtx.chart = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Monthly Revenue',
                            data: data,
                            borderColor: '#8BC34A',
                            backgroundColor: 'rgba(139, 195, 74, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Appointment Status Chart
            const appointmentCtx = document.getElementById('appointmentChart');
            if (appointmentCtx && !appointmentCtx.chart) {
                const appointmentData = <?php echo json_encode($appointmentStats); ?>;
                const labels = appointmentData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
                const data = appointmentData.map(item => parseInt(item.count));
                const colors = ['#8BC34A', '#3b82f6', '#f59e0b', '#ef4444'];
                
                appointmentCtx.chart = new Chart(appointmentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
        
        // Initialize overview charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeOverviewCharts();
        });
         
         // Modal functions
         function openModal(modalId) {
             const modal = document.getElementById(modalId);
             modal.style.display = 'block';
             
             // Add animation class after a small delay
             setTimeout(() => {
                 modal.classList.add('show');
             }, 10);
         }
         
         function closeModal(modalId) {
             const modal = document.getElementById(modalId);
             modal.classList.remove('show');
             
             // Hide modal after animation completes
             setTimeout(() => {
                 modal.style.display = 'none';
             }, 300);
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
         
         // Dropdown functionality
         document.addEventListener('DOMContentLoaded', function() {
             const dropdownToggle = document.getElementById('exportDropdown');
             const dropdownMenu = document.querySelector('.dropdown-menu');
             
             if (dropdownToggle && dropdownMenu) {
                 dropdownToggle.addEventListener('click', function(e) {
                     e.preventDefault();
                     dropdownMenu.classList.toggle('show');
                 });
                 
                 // Close dropdown when clicking outside
                 document.addEventListener('click', function(e) {
                     if (!dropdownToggle.contains(e.target)) {
                         dropdownMenu.classList.remove('show');
                     }
                 });
             }
             
             // Auto-hide success message after 5 seconds
             const successMessage = document.getElementById('successMessage');
             if (successMessage) {
                 setTimeout(function() {
                     successMessage.style.display = 'none';
                 }, 5000);
             }
         });
         
         // Add action buttons functionality
         function updateAppointmentStatus(appointmentId, status) {
             const form = document.createElement('form');
             form.method = 'POST';
             form.innerHTML = `
                 <input type="hidden" name="action" value="update_appointment_status">
                 <input type="hidden" name="appointment_id" value="${appointmentId}">
                 <input type="hidden" name="status" value="${status}">
             `;
             document.body.appendChild(form);
             form.submit();
         }
         
         function assignDoctor(appointmentId, doctorId) {
             if (doctorId === '') {
                 alert('Please select a doctor to assign.');
                 return;
             }
             
             const form = document.createElement('form');
             form.method = 'POST';
             form.innerHTML = `
                 <input type="hidden" name="action" value="assign_doctor">
                 <input type="hidden" name="appointment_id" value="${appointmentId}">
                 <input type="hidden" name="doctor_id" value="${doctorId}">
             `;
             document.body.appendChild(form);
             form.submit();
         }
         
         function updateInventoryStock(inventoryId, currentQuantity) {
             const newQuantity = prompt('Enter new quantity:', currentQuantity);
             if (newQuantity !== null && newQuantity !== '') {
                 const form = document.createElement('form');
                 form.method = 'POST';
                 form.innerHTML = `
                     <input type="hidden" name="action" value="update_inventory_stock">
                     <input type="hidden" name="inventory_id" value="${inventoryId}">
                     <input type="hidden" name="quantity" value="${newQuantity}">
                 `;
                 document.body.appendChild(form);
                 form.submit();
             }
         }
     </script>
</body>
</html>

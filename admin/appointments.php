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
        case 'add_appointment':
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, status, doctor_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['appointment_date'],
                    $_POST['appointment_time'],
                    $_POST['service'],
                    $_POST['status'] ?? 'pending',
                    $_POST['doctor_id'] ?? null
                ]);
                $success_message = "Appointment scheduled successfully!";
            } catch(PDOException $e) {
                $error_message = "Error scheduling appointment: " . $e->getMessage();
            }
            break;
            
        case 'update_appointment':
            try {
                $stmt = $pdo->prepare("
                    UPDATE appointments SET 
                    patient_id = ?, appointment_date = ?, appointment_time = ?, 
                    service = ?, status = ?, doctor_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['patient_id'],
                    $_POST['appointment_date'],
                    $_POST['appointment_time'],
                    $_POST['service'],
                    $_POST['status'],
                    $_POST['doctor_id'] ?? null,
                    $_POST['appointment_id']
                ]);
                $success_message = "Appointment updated successfully!";
            } catch(PDOException $e) {
                $error_message = "Error updating appointment: " . $e->getMessage();
            }
            break;
            
        case 'update_status':
            try {
                if ($_POST['status'] === 'confirmed') {
                    if (!empty($_POST['doctor_id'])) {
                        $stmt = $pdo->prepare("UPDATE appointments SET status = ?, doctor_id = ? WHERE id = ?");
                        $stmt->execute(['confirmed', $_POST['doctor_id'], $_POST['appointment_id']]);
                    } else {
                        // Auto-assign available on-duty doctor for the appointment's date/time
                        $apptStmt = $pdo->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE id = ?");
                        $apptStmt->execute([$_POST['appointment_id']]);
                        $appt = $apptStmt->fetch(PDO::FETCH_ASSOC);
                        if ($appt) {
                            $date = $appt['appointment_date'];
                            $time = $appt['appointment_time'];
                            // Find on-duty doctors without conflict at same slot; choose least busy that day
                            $findStmt = $pdo->prepare("\n                                SELECT s.id\n                                FROM staff s\n                                WHERE s.position LIKE '%Doctor%' AND s.status = 'on-duty'\n                                  AND s.id NOT IN (\n                                    SELECT a.doctor_id\n                                    FROM appointments a\n                                    WHERE a.doctor_id IS NOT NULL\n                                      AND a.appointment_date = ?\n                                      AND a.appointment_time = ?\n                                      AND a.status != 'cancelled'\n                                  )\n                                ORDER BY (\n                                  SELECT COUNT(*) FROM appointments a2\n                                  WHERE a2.doctor_id = s.id AND a2.appointment_date = ?\n                                ) ASC\n                                LIMIT 1\n                            ");
                            $findStmt->execute([$date, $time, $date]);
                            $doc = $findStmt->fetch(PDO::FETCH_ASSOC);
                            if ($doc && !empty($doc['id'])) {
                                $upd = $pdo->prepare("UPDATE appointments SET status = 'confirmed', doctor_id = ? WHERE id = ?");
                                $upd->execute([$doc['id'], $_POST['appointment_id']]);
                            } else {
                                // No available doctor; just confirm without assignment
                                $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                                $stmt->execute(['confirmed', $_POST['appointment_id']]);
                            }
                        } else {
                            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                            $stmt->execute(['confirmed', $_POST['appointment_id']]);
                        }
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                    $stmt->execute([$_POST['status'], $_POST['appointment_id']]);
                }
                $success_message = "Appointment status updated successfully!";
            } catch(PDOException $e) {
                $error_message = "Error updating status: " . $e->getMessage();
            }
            break;
            
        case 'delete_appointment':
            try {
                $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                $stmt->execute([$_POST['appointment_id']]);
                $success_message = "Appointment deleted successfully!";
            } catch(PDOException $e) {
                $error_message = "Error deleting appointment: " . $e->getMessage();
            }
            break;
    }
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'appointments') {
    try {
        $stmt = $pdo->query("
            SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.id 
            LEFT JOIN staff s ON a.doctor_id = s.id 
            ORDER BY a.appointment_date DESC
        ");
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="appointments_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Patient', 'Client', 'Service', 'Date', 'Time', 'Doctor', 'Status']);
        
        foreach ($appointments as $appointment) {
            fputcsv($output, [
                $appointment['id'],
                $appointment['pet_name'],
                $appointment['client_name'],
                $appointment['service'],
                $appointment['appointment_date'],
                $appointment['appointment_time'],
                $appointment['doctor_name'] ?? 'Unassigned',
                $appointment['status']
            ]);
        }
        
        fclose($output);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error exporting data: " . $e->getMessage();
    }
}

// Get appointments with patient and doctor info
try {
    $stmt = $pdo->query("
        SELECT a.*, p.pet_name, p.client_name, p.client_contact, s.name as doctor_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN staff s ON a.doctor_id = s.id 
        ORDER BY a.appointment_date DESC, a.appointment_time ASC
    ");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's appointments
    $stmt = $pdo->query("
        SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN staff s ON a.doctor_id = s.id 
        WHERE DATE(a.appointment_date) = CURDATE()
        ORDER BY a.appointment_time ASC
    ");
    $todayAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming appointments
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
    
    // Get patients for dropdown
    $stmt = $pdo->query("SELECT id, pet_name, client_name FROM patients ORDER BY pet_name");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get doctors for dropdown
    $stmt = $pdo->query("SELECT id, name FROM staff WHERE position LIKE '%Doctor%' ORDER BY name");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - MavetCare Admin</title>
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
        
        .stat-icon.today { background: #8BC34A; }
        .stat-icon.upcoming { background: #3b82f6; }
        .stat-icon.completed { background: #10b981; }
        .stat-icon.pending { background: #f59e0b; }
        
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
        
        .actions {
            display: flex;
            gap: 0.5rem;
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
                    <a href="appointments.php" class="nav-item active">
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
                <h1 class="page-title">Appointments Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addAppointmentModal')">
                        <i class="fas fa-plus"></i>
                        New Appointment
                    </button>
                    <a href="?export=appointments" class="btn btn-warning">
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
                    <span class="stat-title">Today's Appointments</span>
                    <div class="stat-icon today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($todayAppointments); ?></div>
                <div style="font-size: 0.875rem; color: #10b981;">Active today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Upcoming</span>
                    <div class="stat-icon upcoming">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($upcomingAppointments); ?></div>
                <div style="font-size: 0.875rem; color: #3b82f6;">Next 10 days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Completed</span>
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count(array_filter($appointments, function($a) { return $a['status'] == 'completed'; })); ?></div>
                <div style="font-size: 0.875rem; color: #10b981;">This month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pending</span>
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count(array_filter($appointments, function($a) { return $a['status'] == 'pending'; })); ?></div>
                <div style="font-size: 0.875rem; color: #f59e0b;">Awaiting confirmation</div>
            </div>
        </div>
        
        <div class="content-grid">
            <!-- All Appointments Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">All Appointments</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <div class="appointment-info">
                                            <div class="pet-name"><?php echo htmlspecialchars($appointment['pet_name']); ?></div>
                                            <div class="client-name"><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                    <td>
                                        <?php 
                                        $date = new DateTime($appointment['appointment_date']);
                                        $time = new DateTime($appointment['appointment_time']);
                                        echo $date->format('M d, Y') . '<br><small>' . $time->format('g:i A') . '</small>';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unassigned'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn btn-secondary btn-sm" onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-primary btn-sm" onclick="editAppointment(<?php echo $appointment['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a class="btn btn-warning btn-sm" title="Create POS Sale" href="sales.php?openPos=1&patient_id=<?php echo (int)$appointment['patient_id']; ?>&service=<?php echo urlencode($appointment['service']); ?>&doctor=<?php echo urlencode($appointment['doctor_name'] ?? ''); ?>&client=<?php echo urlencode($appointment['client_name']); ?>&pet=<?php echo urlencode($appointment['pet_name']); ?>">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                            <?php if ($appointment['status'] == 'completed'): ?>
                                                <a class="btn btn-warning btn-sm" title="Create POS Receipt" href="sales.php?openPos=1&patient_id=<?php echo (int)$appointment['patient_id']; ?>&service=<?php echo urlencode($appointment['service']); ?>&doctor=<?php echo urlencode($appointment['doctor_name'] ?? ''); ?>&client=<?php echo urlencode($appointment['client_name']); ?>&pet=<?php echo urlencode($appointment['pet_name']); ?>">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] == 'pending'): ?>
                                                <button class="btn btn-success btn-sm" onclick="openConfirmModal(<?php echo $appointment['id']; ?>)"><i class="fas fa-check"></i></button>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] != 'completed' && $appointment['status'] != 'cancelled'): ?>
                                                <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #6b7280;">No appointments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Today's Schedule -->
            <div>
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Today's Schedule</h2>
                    </div>
                    
                    <div style="padding: 1.5rem;">
                        <?php if (!empty($todayAppointments)): ?>
                            <?php foreach ($todayAppointments as $appointment): ?>
                                <div class="appointment-card">
                                    <div class="appointment-time">
                                        <?php 
                                        $time = new DateTime($appointment['appointment_time']);
                                        echo $time->format('g:i A');
                                        ?>
                                    </div>
                                    <div class="appointment-details">
                                        <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($appointment['pet_name']); ?>
                                        </div>
                                        <div style="font-size: 0.9rem; color: #6b7280; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($appointment['client_name']); ?>
                                        </div>
                                        <div style="font-size: 0.9rem; color: #6b7280;">
                                            <?php echo htmlspecialchars($appointment['service']); ?>
                                        </div>
                                    </div>
                                    <div class="appointment-actions">
                                        <button class="btn btn-success btn-sm" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'completed')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" onclick="editAppointment(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-secondary btn-sm" onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: #6b7280; padding: 2rem;">
                                <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <div>No appointments scheduled for today</div>
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
                            <button class="btn btn-primary" onclick="openModal('addAppointmentModal')">
                                <i class="fas fa-plus"></i>
                                Schedule New Appointment
                            </button>
                            <a href="?export=appointments" class="btn btn-warning">
                                <i class="fas fa-file-export"></i>
                                Export Schedule
                            </a>
                            <button class="btn btn-success" onclick="sendReminders()">
                                <i class="fas fa-bell"></i>
                                Send Reminders
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Appointment Modal -->
    <div id="addAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Schedule New Appointment</h2>
                <span class="close" onclick="closeModal('addAppointmentModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_appointment">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" class="form-input" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['pet_name'] . ' (' . $patient['client_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service</label>
                        <input type="text" name="service" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="appointment_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" name="appointment_time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-input">
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addAppointmentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Appointment Modal -->
    <div id="editAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Appointment</h2>
                <span class="close" onclick="closeModal('editAppointmentModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_appointment">
                <input type="hidden" name="appointment_id" id="edit_appointment_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" id="edit_patient_id" class="form-input" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['pet_name'] . ' (' . $patient['client_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service</label>
                        <input type="text" name="service" id="edit_service" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="appointment_date" id="edit_appointment_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" name="appointment_time" id="edit_appointment_time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" id="edit_doctor_id" class="form-input">
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-input">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editAppointmentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Appointment & Assign Doctor Modal -->
    <div id="confirmAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Appointment</h2>
                <span class="close" onclick="closeModal('confirmAppointmentModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" id="confirm_appointment_id">
                <input type="hidden" name="status" value="confirmed">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Assign Doctor</label>
                        <select name="doctor_id" class="form-input" required>
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('confirmAppointmentModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm & Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <div id="viewAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Appointment Details</h2>
                <span class="close" onclick="closeModal('viewAppointmentModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="appointmentDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewAppointmentModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editAppointment(appointmentId) {
            // In a real application, you would fetch appointment data via AJAX
            document.getElementById('edit_appointment_id').value = appointmentId;
            openModal('editAppointmentModal');
        }
        
        function viewAppointment(appointmentId) {
            // In a real application, you would fetch appointment data via AJAX
            document.getElementById('appointmentDetails').innerHTML = '<p>Loading appointment details...</p>';
            openModal('viewAppointmentModal');
        }
        
        function updateStatus(appointmentId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" value="${appointmentId}">
                <input type="hidden" name="status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function openConfirmModal(appointmentId) {
            document.getElementById('confirm_appointment_id').value = appointmentId;
            openModal('confirmAppointmentModal');
        }
        
        function sendReminders() {
            alert('Reminder functionality would be implemented here. This could send SMS or email reminders to clients.');
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
    </script>
</body>
</html>

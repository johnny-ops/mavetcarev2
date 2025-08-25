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

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_staff':
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO staff (name, position, email, phone, schedule, status, hire_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['position'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['schedule'],
                    $_POST['status'],
                    $_POST['hire_date'] ?? null
                ]);
                $success_message = "Staff member added successfully!";
            } catch(PDOException $e) {
                $error_message = "Error adding staff member: " . $e->getMessage();
            }
            break;
            
        case 'update_staff':
            try {
                $stmt = $pdo->prepare("
                    UPDATE staff SET 
                    name = ?, position = ?, email = ?, phone = ?, 
                    schedule = ?, status = ?, hire_date = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['position'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['schedule'],
                    $_POST['status'],
                    $_POST['hire_date'] ?? null,
                    $_POST['staff_id']
                ]);
                $success_message = "Staff member updated successfully!";
            } catch(PDOException $e) {
                $error_message = "Error updating staff member: " . $e->getMessage();
            }
            break;
            
        case 'delete_staff':
            try {
                $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
                $stmt->execute([$_POST['staff_id']]);
                $success_message = "Staff member deleted successfully!";
            } catch(PDOException $e) {
                $error_message = "Error deleting staff member: " . $e->getMessage();
            }
            break;
    }
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'staff') {
    try {
        $stmt = $pdo->query("SELECT * FROM staff ORDER BY position, name ASC");
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="staff_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Position', 'Email', 'Phone', 'Schedule', 'Status', 'Hire Date', 'Created At']);
        
        foreach ($staff as $member) {
            fputcsv($output, $member);
        }
        
        fclose($output);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error exporting data: " . $e->getMessage();
    }
}

// Get staff statistics
try {
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Doctors count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE position LIKE '%Doctor%'");
    $totalDoctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // On-duty staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE status = 'on-duty'");
    $onDutyStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent hires (last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $recentHires = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get all staff
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY position, name ASC");
    $staffMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - MavetCare</title>
    
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
        
        .stat-icon.staff { background: #3b82f6; }
        .stat-icon.doctors { background: #8BC34A; }
        .stat-icon.on-duty { background: #f59e0b; }
        .stat-icon.hires { background: #ef4444; }
        
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-on-duty { background: #d1fae5; color: #065f46; }
        .status-off-duty { background: #fee2e2; color: #991b1b; }
        
        .position-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .position-doctor { background: #dbeafe; color: #1e40af; }
        .position-staff { background: #fef3c7; color: #92400e; }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .font-weight-600 { font-weight: 600; }
        .text-sm { font-size: 0.875rem; }
        .text-gray-500 { color: #6b7280; }
        .text-center { text-align: center; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-height: 90vh; overflow: hidden; }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 1.25rem; font-weight: 600; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .modal-body { padding: 1.5rem; max-height: calc(90vh - 180px); overflow-y: auto; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; }
        .form-input:focus { outline: none; border-color: #8BC34A; box-shadow: 0 0 0 3px rgba(139, 195, 74, 0.1); }
        
        /* Success/Error Messages */
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .success-message { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .error-message { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        
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
                    <a href="staff.php" class="nav-item active">
                        <i class="fas fa-user-md"></i>
                        Doctors
                    </a>
                    <a href="staff.php" class="nav-item active">
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
                <h1 class="page-title">Staff Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addStaffModal')">
                        <i class="fas fa-plus"></i>
                        Add Staff Member
                    </button>
                    <a href="?export=staff" class="btn btn-secondary">
                        <i class="fas fa-download"></i>
                        Export Staff List
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
                        <span class="stat-title">Total Staff</span>
                        <div class="stat-icon staff">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalStaff); ?></div>
                    <div class="stat-change">All staff members</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Doctors</span>
                        <div class="stat-icon doctors">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalDoctors); ?></div>
                    <div class="stat-change">Medical professionals</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">On Duty</span>
                        <div class="stat-icon on-duty">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($onDutyStaff); ?></div>
                    <div class="stat-change">Currently available</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Recent Hires</span>
                        <div class="stat-icon hires">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($recentHires); ?></div>
                    <div class="stat-change">Last 30 days</div>
                </div>
            </div>
            
            <!-- Staff List -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Staff Directory</h2>
                    <a href="#" class="section-action">View All</a>
                </div>
                <div class="section-content">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Position</th>
                                    <th>Contact</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Hire Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($staffMembers)): ?>
                                    <?php foreach ($staffMembers as $staff): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="font-weight-600"><?php echo htmlspecialchars($staff['name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($staff['email']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="position-badge position-<?php echo strtolower(str_replace(' ', '-', $staff['position'])); ?>">
                                                    <?php echo htmlspecialchars($staff['position']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                            <td class="text-sm"><?php echo htmlspecialchars($staff['schedule']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $staff['status']; ?>">
                                                    <?php echo ucfirst(str_replace('-', ' ', $staff['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-sm">
                                                <?php 
                                                if ($staff['hire_date']) {
                                                    $hireDate = new DateTime($staff['hire_date']);
                                                    echo $hireDate->format('M d, Y');
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <button class="btn btn-secondary btn-sm" onclick="viewStaff(<?php echo $staff['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-primary btn-sm" onclick="editStaff(<?php echo $staff['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-gray-500">No staff members found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Staff Member</h2>
                <span class="close" onclick="closeModal('addStaffModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_staff">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
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
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Schedule</label>
                        <input type="text" name="schedule" class="form-input" placeholder="e.g., Monday-Friday 8AM-5PM">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="on-duty">On Duty</option>
                            <option value="off-duty">Off Duty</option>
                            <option value="on-leave">On Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" class="form-input">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Staff Member</h2>
                <span class="close" onclick="closeModal('editStaffModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_staff">
                <input type="hidden" name="staff_id" id="edit_staff_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <select name="position" id="edit_position" class="form-input" required>
                            <option value="">Select Position</option>
                            <option value="Veterinarian">Veterinarian</option>
                            <option value="Veterinary Nurse">Veterinary Nurse</option>
                            <option value="Receptionist">Receptionist</option>
                            <option value="Technician">Technician</option>
                            <option value="Assistant">Assistant</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Schedule</label>
                        <input type="text" name="schedule" id="edit_schedule" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-input">
                            <option value="on-duty">On Duty</option>
                            <option value="off-duty">Off Duty</option>
                            <option value="on-leave">On Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" id="edit_hire_date" class="form-input">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Staff Modal -->
    <div id="viewStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Staff Member Details</h2>
                <span class="close" onclick="closeModal('viewStaffModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="staffDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewStaffModal')">Close</button>
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
        
        function editStaff(staffId) {
            // In a real application, you would fetch staff data via AJAX
            document.getElementById('edit_staff_id').value = staffId;
            openModal('editStaffModal');
        }
        
        function viewStaff(staffId) {
            // In a real application, you would fetch staff data via AJAX
            document.getElementById('staffDetails').innerHTML = '<p>Loading staff details...</p>';
            openModal('viewStaffModal');
        }
        
        function deleteStaff(staffId, staffName) {
            if (confirm('Are you sure you want to delete ' + staffName + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_staff">
                    <input type="hidden" name="staff_id" value="${staffId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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

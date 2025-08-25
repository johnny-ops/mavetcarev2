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
        case 'add_patient':
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO patients (pet_name, pet_code, client_name, pet_type, breed, age, weight, client_contact, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_POST['pet_name'],
                    $_POST['pet_code'],
                    $_POST['client_name'],
                    $_POST['pet_type'],
                    $_POST['breed'],
                    $_POST['age'],
                    $_POST['weight'],
                    $_POST['client_contact']
                ]);
                $success_message = "Patient added successfully!";
            } catch(PDOException $e) {
                $error_message = "Error adding patient: " . $e->getMessage();
            }
            break;
            
        case 'update_patient':
            try {
                $stmt = $pdo->prepare("
                    UPDATE patients SET 
                    pet_name = ?, pet_code = ?, client_name = ?, pet_type = ?, 
                    breed = ?, age = ?, weight = ?, client_contact = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['pet_name'],
                    $_POST['pet_code'],
                    $_POST['client_name'],
                    $_POST['pet_type'],
                    $_POST['breed'],
                    $_POST['age'],
                    $_POST['weight'],
                    $_POST['client_contact'],
                    $_POST['patient_id']
                ]);
                $success_message = "Patient updated successfully!";
            } catch(PDOException $e) {
                $error_message = "Error updating patient: " . $e->getMessage();
            }
            break;
            
        case 'delete_patient':
            try {
                $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
                $stmt->execute([$_POST['patient_id']]);
                $success_message = "Patient deleted successfully!";
            } catch(PDOException $e) {
                $error_message = "Error deleting patient: " . $e->getMessage();
            }
            break;
    }
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'patients') {
    try {
        $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="patients_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Pet Name', 'Pet Code', 'Client Name', 'Pet Type', 'Breed', 'Age', 'Weight', 'Client Contact', 'Created At']);
        
        foreach ($patients as $patient) {
            fputcsv($output, $patient);
        }
        
        fclose($output);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error exporting data: " . $e->getMessage();
    }
}

// Get patients
try {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - MavetCare Admin</title>
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
        
        .section-content {
            padding: 1.5rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
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
            letter-spacing: 0.05em;
        }
        
        .data-table tr:hover {
            background: #f8fafc;
        }
        
        .pet-info {
            display: flex;
            flex-direction: column;
        }
        
        .pet-name {
            font-weight: 600;
        }
        
        .pet-code {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
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
                    <a href="patients.php" class="nav-item active">
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
                <h1 class="page-title">Patients Management</h1>
                <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('addPatientModal')">
                    <i class="fas fa-plus"></i>
                    Add New Patient
                </button>
                <a href="?export=patients" class="btn btn-secondary">
                    <i class="fas fa-download"></i>
                    Export Patients
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
        
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Patients List (<?php echo count($patients); ?> total)</h2>
                <a href="#" class="section-action">View All</a>
            </div>
            <div class="section-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pet Info</th>
                        <th>Client</th>
                        <th>Type/Breed</th>
                        <th>Age/Weight</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>
                                    <div class="pet-info">
                                        <div class="pet-name"><?php echo htmlspecialchars($patient['pet_name']); ?></div>
                                        <div class="pet-code"><?php echo htmlspecialchars($patient['pet_code']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($patient['client_name']); ?></td>
                                <td>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($patient['pet_type']); ?></div>
                                        <div style="font-size: 0.9rem; color: #6b7280;"><?php echo htmlspecialchars($patient['breed']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div><?php echo htmlspecialchars($patient['age']); ?></div>
                                        <div style="font-size: 0.9rem; color: #6b7280;"><?php echo $patient['weight']; ?> kg</div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($patient['client_contact']); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-secondary btn-sm" onclick="viewPatient(<?php echo $patient['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" onclick="editPatient(<?php echo $patient['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deletePatient(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['pet_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #6b7280;">No patients found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Patient</h2>
                <span class="close" onclick="closeModal('addPatientModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_patient">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Pet Name</label>
                        <input type="text" name="pet_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pet Code</label>
                        <input type="text" name="pet_code" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pet Type</label>
                        <input type="text" name="pet_type" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Breed</label>
                        <input type="text" name="breed" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Age</label>
                        <input type="text" name="age" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Contact</label>
                        <input type="text" name="client_contact" class="form-input" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addPatientModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div id="editPatientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Patient</h2>
                <span class="close" onclick="closeModal('editPatientModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_patient">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Pet Name</label>
                        <input type="text" name="pet_name" id="edit_pet_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pet Code</label>
                        <input type="text" name="pet_code" id="edit_pet_code" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" id="edit_client_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pet Type</label>
                        <input type="text" name="pet_type" id="edit_pet_type" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Breed</label>
                        <input type="text" name="breed" id="edit_breed" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Age</label>
                        <input type="text" name="age" id="edit_age" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" id="edit_weight" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Contact</label>
                        <input type="text" name="client_contact" id="edit_client_contact" class="form-input" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPatientModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Patient Modal -->
    <div id="viewPatientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Patient Details</h2>
                <span class="close" onclick="closeModal('viewPatientModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="patientDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewPatientModal')">Close</button>
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
        
        function editPatient(patientId) {
            // In a real application, you would fetch patient data via AJAX
            // For now, we'll use a simple approach
            document.getElementById('edit_patient_id').value = patientId;
            openModal('editPatientModal');
        }
        
        function viewPatient(patientId) {
            // In a real application, you would fetch patient data via AJAX
            document.getElementById('patientDetails').innerHTML = '<p>Loading patient details...</p>';
            openModal('viewPatientModal');
        }
        
        function deletePatient(patientId, petName) {
            if (confirm('Are you sure you want to delete ' + petName + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_patient">
                    <input type="hidden" name="patient_id" value="${patientId}">
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MavetCare Admin Dashboard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            line-height: 1.6;
        }
        
        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #1e293b;
            border-right: 1px solid #334155;
            flex-shrink: 0;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #334155;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        }
        
        .nav-item:hover {
            background: #334155;
            color: white;
        }
        
        .nav-item.active {
            background: #3b82f6;
            color: white;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 1.5rem 2rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Content */
        .content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .section-content {
            display: none;
        }
        
        .section-content.active {
            display: block;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #1e293b;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-card.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card.green { background: linear-gradient(135deg, #10b981, #047857); }
        .stat-card.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        
        .stat-info h3 {
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .stat-info .number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        /* Tables */
        .table-container {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            margin-bottom: 2rem;
        }
        
        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: white;
        }
        
        .table-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 1rem 2rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        
        table th {
            background: #334155;
            font-weight: 600;
            color: #e2e8f0;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        table td {
            color: #cbd5e1;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            background: #64748b;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary {
            background: #3b82f6;
        }
        
        .btn-success {
            background: #10b981;
        }
        
        .btn-danger {
            background: #ef4444;
        }
        
        .btn-warning {
            background: #f59e0b;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        
        /* Forms */
        .form-container {
            background: #1e293b;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #334155;
            margin-bottom: 2rem;
        }
        
        .form-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #475569;
            border-radius: 6px;
            background: #334155;
            color: #e2e8f0;
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-box {
            padding: 0.5rem 1rem;
            border: 1px solid #475569;
            border-radius: 6px;
            background: #334155;
            color: #e2e8f0;
            width: 250px;
        }
        
        /* Status */
        .status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-on-duty { background: #d1fae5; color: #065f46; }
        .status-off-duty { background: #f3f4f6; color: #374151; }
        
        .stock-low { background: #fee2e2; color: #991b1b; }
        .stock-medium { background: #fef3c7; color: #92400e; }
        .stock-high { background: #d1fae5; color: #065f46; }
        
        /* Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .doctor-card {
            background: #1e293b;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #334155;
            text-align: center;
        }
        
        .doctor-avatar {
            width: 60px;
            height: 60px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: #1e293b;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        
        .modal-header {
            padding: 2rem 2rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        
        .modal-header h3 {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .close {
            color: #94a3b8;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .close:hover {
            color: white;
            background: #475569;
        }
        
        .modal form {
            padding: 2rem;
        }
        
        /* POS System */
        .pos-section {
            display: none;
        }
        
        .pos-section.active {
            display: block;
        }
        
        .pos-product-item:hover div {
            background: #64748b !important;
            transform: translateY(-2px);
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border: 1px solid #475569;
            border-radius: 6px;
            background: transparent;
            color: #cbd5e1;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .filter-tab:hover {
            background: #475569;
        }
        
        .filter-tab.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        
        /* Timeline Badges */
        .timeline-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .timeline-just-now { background: #dcfce7; color: #166534; }
        .timeline-minutes { background: #dbeafe; color: #1e40af; }
        .timeline-hours { background: #fef3c7; color: #92400e; }
        .timeline-days { background: #fed7d7; color: #c53030; }
        .timeline-weeks { background: #e9d5ff; color: #7c3aed; }
        .timeline-months { background: #f3f4f6; color: #374151; }
        .timeline-years { background: #f3f4f6; color: #6b7280; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Your existing HTML content goes here -->
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">M</div>
                    <div>
                        <h1 style="font-size: 1.25rem; font-weight: bold;">MavetCare</h1>
                        <p style="font-size: 0.875rem; color: #94a3b8;">Admin Panel</p>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">Main</div>
                    <a href="?section=dashboard" class="nav-item active">
                        <i class="fas fa-chart-bar"></i> Dashboard
                    </a>
                </div>
            </nav>
        </div>
        
        <div class="main-content">
            <header class="header">
                <div class="header-content">
                    <h1>Dashboard</h1>
                    <div class="user-info">
                        <div>Admin</div>
                        <div class="user-avatar">A</div>
                    </div>
                </div>
            </header>
            
            <main class="content">
                <div class="section-content active">
                    <div class="form-container">
                        <div class="form-title">Test Buttons</div>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button class="btn btn-primary" id="addPatientBtn">
                                <i class="fas fa-plus"></i> Add Patient
                            </button>
                            <button class="btn btn-success" id="addDoctorBtn">
                                <i class="fas fa-user-md"></i> Add Doctor
                            </button>
                            <button class="btn btn-danger" id="emergencyAppointmentBtn">
                                <i class="fas fa-ambulance"></i> Emergency
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Simple Test Modal -->
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Patient</h3>
                <span class="close" data-modal="patientModal">&times;</span>
            </div>
            <form id="patientForm">
                <div class="form-group">
                    <label class="form-label">Patient Name</label>
                    <input type="text" name="patient_name" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">Save</button>
            </form>
        </div>
    </div>

    <!-- Include Your JavaScript -->
    <script>
        // Test if basic functionality works
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOM loaded successfully');
            
            // Test button existence
            const testButtons = ['addPatientBtn', 'addDoctorBtn', 'emergencyAppointmentBtn'];
            testButtons.forEach(id => {
                const btn = document.getElementById(id);
                console.log(`${id}:`, btn ? '✅ FOUND' : '❌ MISSING');
                
                if (btn) {
                    btn.addEventListener('click', function() {
                        console.log(`✅ ${id} clicked!`);
                        if (id === 'addPatientBtn') {
                            document.getElementById('patientModal').style.display = 'block';
                        }
                    });
                }
            });
            
            // Test modal close
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('close')) {
                    const modalId = e.target.dataset.modal;
                    if (modalId) {
                        document.getElementById(modalId).style.display = 'none';
                    }
                }
                
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                }
            });
            
            console.log('✅ Event listeners attached');
        });
    </script>
</body>
</html>
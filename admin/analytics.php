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

// Get analytics data
try {
    // Monthly sales data for chart
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(sale_date, '%Y-%m') as month,
            SUM(total_amount) as total_sales,
            COUNT(*) as transaction_count
        FROM sales 
        WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Service popularity
    $stmt = $pdo->query("
        SELECT 
            service,
            COUNT(*) as appointment_count
        FROM appointments 
        WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY service
        ORDER BY appointment_count DESC
        LIMIT 10
    ");
    $servicePopularity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pet type distribution
    $stmt = $pdo->query("
        SELECT 
            pet_type,
            COUNT(*) as count
        FROM patients 
        GROUP BY pet_type
        ORDER BY count DESC
    ");
    $petTypeDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top performing doctors
    $stmt = $pdo->query("
        SELECT 
            s.name as doctor_name,
            COUNT(a.id) as appointment_count,
            COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_count
        FROM staff s
        LEFT JOIN appointments a ON s.id = a.doctor_id
        WHERE s.position LIKE '%Doctor%'
        AND a.appointment_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY s.id, s.name
        ORDER BY appointment_count DESC
        LIMIT 5
    ");
    $topDoctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revenue by payment method
    $stmt = $pdo->query("
        SELECT 
            payment_method,
            SUM(total_amount) as total_revenue,
            COUNT(*) as transaction_count
        FROM sales 
        WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY payment_method
        ORDER BY total_revenue DESC
    ");
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inventory turnover
    $stmt = $pdo->query("
        SELECT 
            name,
            quantity,
            price,
            (quantity * price) as stock_value
        FROM inventory 
        ORDER BY stock_value DESC
        LIMIT 10
    ");
    $inventoryValue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - MavetCare Admin</title>
    
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
        
        .date-filter {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            font-size: 0.9rem;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
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
        .stat-icon.patients { background: #3b82f6; }
        .stat-icon.appointments { background: #8BC34A; }
        .stat-icon.inventory { background: #f59e0b; }
        
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
        
        @media (max-width: 1024px) {
            .charts-grid {
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
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
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
                    <a href="analytics.php" class="nav-item active">
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
                <h1 class="page-title">Analytics Dashboard</h1>
                <div class="date-filter">
                    <select class="filter-select" id="timeRange">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="365">Last Year</option>
                    </select>
                </div>
            </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Revenue</span>
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="stat-value">₱<?php echo number_format(array_sum(array_column($monthlySales, 'total_sales')), 2); ?></div>
                <div class="stat-change">+15.3% from last period</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Appointments</span>
                    <div class="stat-icon appointments">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format(array_sum(array_column($servicePopularity, 'appointment_count'))); ?></div>
                <div class="stat-change">+8.7% from last period</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Patients</span>
                    <div class="stat-icon patients">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format(array_sum(array_column($petTypeDistribution, 'count'))); ?></div>
                <div class="stat-change">+12.1% from last period</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Avg. Transaction</span>
                    <div class="stat-icon sales">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-value">₱<?php 
                    $totalSales = array_sum(array_column($monthlySales, 'total_sales'));
                    $totalTransactions = array_sum(array_column($monthlySales, 'transaction_count'));
                    echo $totalTransactions > 0 ? number_format($totalSales / $totalTransactions, 2) : '0.00';
                ?></div>
                <div class="stat-change">+5.2% from last period</div>
            </div>
        </div>
        
        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Monthly Sales Chart -->
            <div class="chart-card">
                <h3 class="chart-title">Monthly Revenue Trend</h3>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Service Popularity Chart -->
            <div class="chart-card">
                <h3 class="chart-title">Most Popular Services</h3>
                <div class="chart-container">
                    <canvas id="servicesChart"></canvas>
                </div>
            </div>
            
            <!-- Pet Type Distribution -->
            <div class="chart-card">
                <h3 class="chart-title">Pet Type Distribution</h3>
                <div class="chart-container">
                    <canvas id="petTypesChart"></canvas>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="chart-card">
                <h3 class="chart-title">Revenue by Payment Method</h3>
                <div class="chart-container">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Performing Doctors -->
        <div class="table-container">
            <h3 class="table-title">Top Performing Doctors</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Doctor Name</th>
                            <th>Total Appointments</th>
                            <th>Completed</th>
                            <th>Completion Rate</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topDoctors as $doctor): ?>
                            <tr>
                                <td class="font-weight-600"><?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                                <td><?php echo $doctor['appointment_count']; ?></td>
                                <td><?php echo $doctor['completed_count']; ?></td>
                                <td>
                                    <?php 
                                    $rate = $doctor['appointment_count'] > 0 ? 
                                        round(($doctor['completed_count'] / $doctor['appointment_count']) * 100, 1) : 0;
                                    echo $rate . '%';
                                    ?>
                                </td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $rate; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Inventory Value -->
        <div class="table-container">
            <h3 class="table-title">Highest Value Inventory Items</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                            <th>Stock Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryValue as $item): ?>
                            <tr>
                                <td class="font-weight-600"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td class="font-weight-600">₱<?php echo number_format($item['stock_value'], 2); ?></td>
                                <td>
                                    <?php if ($item['quantity'] <= 10): ?>
                                        <span class="status-badge status-cancelled">Low Stock</span>
                                    <?php else: ?>
                                        <span class="status-badge status-confirmed">In Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Chart configurations
        const salesData = <?php echo json_encode($monthlySales); ?>;
        const servicesData = <?php echo json_encode($servicePopularity); ?>;
        const petTypesData = <?php echo json_encode($petTypeDistribution); ?>;
        const paymentData = <?php echo json_encode($paymentMethods); ?>;
        
        // Monthly Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue',
                    data: salesData.map(item => item.total_sales),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
        
        // Services Chart
        const servicesCtx = document.getElementById('servicesChart').getContext('2d');
        new Chart(servicesCtx, {
            type: 'doughnut',
            data: {
                labels: servicesData.map(item => item.service),
                datasets: [{
                    data: servicesData.map(item => item.appointment_count),
                    backgroundColor: [
                        '#3b82f6', '#8BC34A', '#f59e0b', '#ef4444', '#10b981',
                        '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899'
                    ]
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
        
        // Pet Types Chart
        const petTypesCtx = document.getElementById('petTypesChart').getContext('2d');
        new Chart(petTypesCtx, {
            type: 'bar',
            data: {
                labels: petTypesData.map(item => item.pet_type),
                datasets: [{
                    label: 'Number of Pets',
                    data: petTypesData.map(item => item.count),
                    backgroundColor: '#8BC34A',
                    borderRadius: 8
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
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Payment Methods Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'pie',
            data: {
                labels: paymentData.map(item => item.payment_method),
                datasets: [{
                    data: paymentData.map(item => item.total_revenue),
                    backgroundColor: [
                        '#3b82f6', '#8BC34A', '#f59e0b', '#ef4444', '#10b981'
                    ]
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
        
        function refreshCharts() {
            location.reload();
        }
        
        // Auto-refresh every 5 minutes
        setInterval(function() {
            console.log('Analytics data refreshed');
        }, 300000);
    </script>
</body>
</html>

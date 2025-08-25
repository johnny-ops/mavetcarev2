<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userType = $_SESSION['user_type'];

// Get user's recent appointments
$stmt = $pdo->prepare("SELECT a.*, p.pet_name, p.pet_type, p.client_name 
                       FROM appointments a 
                       LEFT JOIN patients p ON a.patient_id = p.id 
                       WHERE a.user_id = ? 
                       ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count appointments by status
$count_stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE user_id = ? GROUP BY status");
$count_stmt->execute([$user_id]);
$appointment_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);

$status_counts = [
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach($appointment_counts as $count) {
    $status_counts[$count['status']] = $count['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MavetCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card.pending i { color: #ffc107; }
        .stat-card.confirmed i { color: #17a2b8; }
        .stat-card.completed i { color: #28a745; }
        .stat-card.cancelled i { color: #dc3545; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .appointments-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            color: #333;
            font-size: 1.5rem;
        }

        .view-all-btn {
            background: #8BC34A;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .view-all-btn:hover {
            background: #7CB342;
        }

        .appointment-item {
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: border-color 0.3s;
        }

        .appointment-item:hover {
            border-color: #8BC34A;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .appointment-date {
            font-weight: bold;
            color: #333;
        }

        .appointment-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .appointment-details {
            color: #666;
            font-size: 0.9rem;
        }

        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .action-btn {
            display: block;
            width: 100%;
            background: #8BC34A;
            color: white;
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #7CB342;
        }

        .action-btn.secondary {
            background: #6c757d;
        }

        .action-btn.secondary:hover {
            background: #5a6268;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #8BC34A;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
            <p>Here's an overview of your pet care activities</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card pending">
                <i class="fas fa-clock"></i>
                <div class="stat-number"><?php echo $status_counts['pending']; ?></div>
                <div class="stat-label">Pending Appointments</div>
            </div>
            <div class="stat-card confirmed">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $status_counts['confirmed']; ?></div>
                <div class="stat-label">Confirmed Appointments</div>
            </div>
            <div class="stat-card completed">
                <i class="fas fa-check-double"></i>
                <div class="stat-number"><?php echo $status_counts['completed']; ?></div>
                <div class="stat-label">Completed Visits</div>
            </div>
            <div class="stat-card cancelled">
                <i class="fas fa-times-circle"></i>
                <div class="stat-number"><?php echo $status_counts['cancelled']; ?></div>
                <div class="stat-label">Cancelled Appointments</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="appointments-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-alt"></i> Recent Appointments</h2>
                    <a href="myAppointments.php" class="view-all-btn">View All</a>
                </div>

                <?php if (empty($recent_appointments)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No appointments found. <a href="myAppointments.php" style="color: #8BC34A;">Book your first appointment</a></p>
                <?php else: ?>
                    <?php foreach ($recent_appointments as $appointment): ?>
                        <div class="appointment-item">
                            <div class="appointment-header">
                                <div class="appointment-date">
                                    <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                </div>
                                <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </div>
                            <div class="appointment-details">
                                <strong>Service:</strong> <?php echo htmlspecialchars($appointment['service']); ?><br>
                                <strong>Pet:</strong> <?php echo htmlspecialchars($appointment['pet_name'] ?? 'N/A'); ?> 
                                (<?php echo htmlspecialchars($appointment['pet_type'] ?? 'N/A'); ?>)
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="quick-actions">
                <h2 style="margin-bottom: 25px; color: #333;"><i class="fas fa-bolt"></i> Quick Actions</h2>
                
                <a href="myAppointments.php" class="action-btn">
                    <i class="fas fa-plus"></i> Book New Appointment
                </a>
                
                <a href="myAppointments.php" class="action-btn">
                    <i class="fas fa-calendar-check"></i> View All Appointments
                </a>
                
                <a href="profile.php" class="action-btn secondary">
                    <i class="fas fa-user-cog"></i> Edit Profile
                </a>
                
                <a href="products.php" class="action-btn secondary">
                    <i class="fas fa-shopping-cart"></i> Browse Products
                </a>
                
                <a href="services.php" class="action-btn secondary">
                    <i class="fas fa-stethoscope"></i> Our Services
                </a>
            </div>
        </div>
    </div>
</body>
</html>

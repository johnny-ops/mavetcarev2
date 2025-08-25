<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $dbStatus = "✅ Database connected successfully";
} catch (PDOException $e) {
    $dbStatus = "❌ Database connection failed: " . $e->getMessage();
}

// Test basic queries
$testQueries = [];
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM patients');
    $testQueries['patients'] = "✅ Patients table: " . $stmt->fetch()['count'] . " records";
} catch (Exception $e) {
    $testQueries['patients'] = "❌ Patients table error: " . $e->getMessage();
}

try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM services');
    $testQueries['services'] = "✅ Services table: " . $stmt->fetch()['count'] . " records";
} catch (Exception $e) {
    $testQueries['services'] = "❌ Services table error: " . $e->getMessage();
}

try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM inventory');
    $testQueries['inventory'] = "✅ Inventory table: " . $stmt->fetch()['count'] . " records";
} catch (Exception $e) {
    $testQueries['inventory'] = "❌ Inventory table error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Button Test - MavetCare</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🔧 MavetCare Button & Database Test</h1>
    
    <div class="test-section">
        <h2>Database Status</h2>
        <div class="status <?= strpos($dbStatus, '✅') !== false ? 'status-success' : 'status-error' ?>">
            <?= $dbStatus ?>
        </div>
        
        <h3>Table Tests:</h3>
        <?php foreach ($testQueries as $table => $result): ?>
            <div class="test-result"><?= $result ?></div>
        <?php endforeach; ?>
    </div>

    <div class="test-section">
        <h2>Button Functionality Test</h2>
        <p>Click these buttons to test if JavaScript event listeners are working:</p>
        
        <div>
            <button class="btn btn-primary" id="testBtn1">Test Button 1</button>
            <button class="btn btn-success" id="testBtn2">Test Button 2</button>
            <button class="btn btn-warning" id="testBtn3">Test Button 3</button>
            <button class="btn btn-danger" id="testBtn4">Test Button 4</button>
        </div>
        
        <div id="testResults" style="margin-top: 20px;"></div>
    </div>

    <div class="test-section">
        <h2>Modal Test</h2>
        <button class="btn btn-primary" id="openModalBtn">Open Test Modal</button>
        
        <!-- Test Modal -->
        <div id="testModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 500px;">
                <h3>Test Modal</h3>
                <p>This is a test modal to verify modal functionality.</p>
                <button class="btn btn-primary" id="closeModalBtn">Close Modal</button>
            </div>
        </div>
    </div>

    <div class="test-section">
        <h2>Navigation</h2>
        <a href="admin/adminDashboard.php" class="btn btn-primary">Go to Admin Dashboard</a>
        <a href="index.php" class="btn btn-success">Go to Homepage</a>
    </div>

    <script>
        // Test button functionality
        let clickCount = 0;
        
        function addTestResult(message) {
            const results = document.getElementById('testResults');
            const result = document.createElement('div');
            result.className = 'test-result';
            result.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            results.appendChild(result);
        }
        
        // Test button event listeners
        document.getElementById('testBtn1').addEventListener('click', function() {
            clickCount++;
            addTestResult(`Button 1 clicked! Count: ${clickCount}`);
        });
        
        document.getElementById('testBtn2').addEventListener('click', function() {
            addTestResult('Button 2 clicked! Event listener working.');
        });
        
        document.getElementById('testBtn3').addEventListener('click', function() {
            addTestResult('Button 3 clicked! JavaScript is functional.');
        });
        
        document.getElementById('testBtn4').addEventListener('click', function() {
            addTestResult('Button 4 clicked! All systems operational.');
        });
        
        // Modal test
        document.getElementById('openModalBtn').addEventListener('click', function() {
            document.getElementById('testModal').style.display = 'block';
            addTestResult('Modal opened successfully!');
        });
        
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            document.getElementById('testModal').style.display = 'none';
            addTestResult('Modal closed successfully!');
        });
        
        // Close modal when clicking outside
        document.getElementById('testModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                addTestResult('Modal closed by clicking outside!');
            }
        });
        
        // Page load test
        document.addEventListener('DOMContentLoaded', function() {
            addTestResult('Page loaded successfully! DOM ready.');
            addTestResult('All event listeners should be working now.');
        });
    </script>
</body>
</html>



<?php
header('Content-Type: text/plain');

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'mavetcare_db';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!\n\n";
    
    // Test tables
    echo "📋 Available tables:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\n📊 Appointments table structure:\n";
    $columns = $pdo->query("DESCRIBE appointments")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Null']}) {$column['Key']}\n";
    }
    
    echo "\n🐾 Patients table structure:\n";
    $columns = $pdo->query("DESCRIBE patients")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Null']}) {$column['Key']}\n";
    }
    
    echo "\n🔧 Services table structure:\n";
    $columns = $pdo->query("DESCRIBE services")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Null']}) {$column['Key']}\n";
    }
    
    // Test sample data
    echo "\n📈 Sample data:\n";
    $appointments = $pdo->query("SELECT COUNT(*) as count FROM appointments")->fetch();
    echo "- Total appointments: {$appointments['count']}\n";
    
    $patients = $pdo->query("SELECT COUNT(*) as count FROM patients")->fetch();
    echo "- Total patients: {$patients['count']}\n";
    
    $services = $pdo->query("SELECT COUNT(*) as count FROM services")->fetch();
    echo "- Total services: {$services['count']}\n";
    
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

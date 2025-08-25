<?php
// Test script to verify about page database connections
$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Testing About Page Database Connections</h2>";
    
    // Test testimonials
    echo "<h3>1. Testing Testimonials</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM testimonials WHERE is_featured = 1");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ Featured testimonials: $count</p>";
    
    $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_featured = 1 LIMIT 2");
    $testimonials = $stmt->fetchAll();
    foreach ($testimonials as $testimonial) {
        echo "<p>• {$testimonial['client_name']} - {$testimonial['pet_name']} ({$testimonial['pet_type']}) - Rating: {$testimonial['rating']}/5</p>";
    }
    
    // Test products
    echo "<h3>2. Testing Products</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity > 0");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ Available products: $count</p>";
    
    $stmt = $pdo->query("SELECT * FROM inventory WHERE quantity > 0 ORDER BY price DESC LIMIT 2");
    $products = $stmt->fetchAll();
    foreach ($products as $product) {
        echo "<p>• {$product['name']} - {$product['category']} - ₱{$product['price']}</p>";
    }
    
    // Test staff
    echo "<h3>3. Testing Staff</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff WHERE status = 'on-duty'");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ On-duty staff: $count</p>";
    
    $stmt = $pdo->query("SELECT * FROM staff WHERE status = 'on-duty' ORDER BY position, name ASC LIMIT 2");
    $staff = $stmt->fetchAll();
    foreach ($staff as $member) {
        echo "<p>• {$member['name']} - {$member['position']} - Schedule: {$member['schedule']}</p>";
    }
    
    echo "<h3 style='color: green;'>🎉 All database connections working properly!</h3>";
    echo "<p><a href='about.php' style='background: #8BC34A; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View About Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

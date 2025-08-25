<?php
// Test script to verify product images functionality
session_name('user_session');
session_start();

// Database connection
$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query to get products with images
    $stmt = $pdo->query('SELECT id, name, category, product_image FROM inventory LIMIT 5');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Product Images Test</h2>";
    echo "<p>Database connection: SUCCESS</p>";
    echo "<p>Products found: " . count($products) . "</p>";
    
    echo "<h3>Sample Products:</h3>";
    foreach ($products as $product) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
        echo "<strong>ID:</strong> " . $product['id'] . "<br>";
        echo "<strong>Name:</strong> " . $product['name'] . "<br>";
        echo "<strong>Category:</strong> " . $product['category'] . "<br>";
        echo "<strong>Image:</strong> " . ($product['product_image'] ?: 'No image') . "<br>";
        
        if ($product['product_image']) {
            echo "<img src='" . htmlspecialchars($product['product_image']) . "' style='width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd;' onerror='this.style.display=\"none\"; this.nextSibling.style.display=\"block\";'>";
            echo "<div style='display: none; width: 100px; height: 100px; background: #f0f0f0; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center;'>Image not found</div>";
        }
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>


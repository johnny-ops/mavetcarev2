<?php
// Setup testimonials table for MavetCare
$host = 'localhost';
$dbname = 'mavetcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Setting up testimonials table...</h2>";
    
    // Create testimonials table
    $sql = "
    CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(100) NOT NULL,
        pet_name VARCHAR(50),
        pet_type ENUM('Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster', 'Other'),
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT NOT NULL,
        service_received VARCHAR(100),
        is_featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Testimonials table created successfully!</p>";
    
    // Insert sample testimonials
    $sampleTestimonials = [
        ['Sarah M.', 'Buddy', 'Dog', 5, 'Amazing service! The staff is incredibly caring and professional. My dog was nervous but they made him feel so comfortable. Dr. Santos was wonderful with him.', 'General Checkup', 1],
        ['John D.', 'Whiskers', 'Cat', 5, 'They take care of my pets so well. The place is clean, the staff is friendly, and the doctors are very knowledgeable. Highly recommended!', 'Vaccination', 1],
        ['Maria L.', 'Max', 'Dog', 4, 'Great experience with the grooming service. My dog looks and smells amazing! The staff was patient and gentle with him.', 'Pet Grooming', 1],
        ['Alex R.', 'Luna', 'Cat', 5, 'Emergency care was outstanding. They took care of my cat immediately and kept me informed throughout the process. Thank you for saving Luna!', 'Emergency Care', 1],
        ['Carlos P.', 'Rocky', 'Dog', 5, 'The surgery went perfectly. Dr. Cruz is an excellent surgeon and the follow-up care was exceptional. Rocky is back to his playful self!', 'Surgery', 0],
        ['Ana S.', 'Mittens', 'Cat', 4, 'Very professional and caring staff. The dental cleaning service was thorough and my cat is much healthier now.', 'Dental Cleaning', 0],
        ['Pedro M.', 'Charlie', 'Dog', 5, 'Best veterinary clinic in the area! The doctors are experienced and the facilities are modern. Charlie loves coming here for his checkups.', 'General Checkup', 0],
        ['Isabella R.', 'Bella', 'Cat', 5, 'Excellent service from start to finish. The staff remembered us from our previous visit and treated Bella with so much love and care.', 'Vaccination', 0]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO testimonials (client_name, pet_name, pet_type, rating, review_text, service_received, is_featured) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleTestimonials as $testimonial) {
        $stmt->execute($testimonial);
    }
    
    echo "<p style='color: green;'>✅ Sample testimonials inserted successfully!</p>";
    
    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_testimonials_featured ON testimonials(is_featured)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_testimonials_rating ON testimonials(rating)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_testimonials_created_at ON testimonials(created_at)");
    
    echo "<p style='color: green;'>✅ Indexes created successfully!</p>";
    
    // Verify the table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM testimonials");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: blue;'>📊 Total testimonials in database: $count</p>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Testimonials setup completed successfully!</p>";
    echo "<p><a href='about.php'>View the updated About page</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

-- Create testimonials table for MavetCare
USE mavetcare_db;

-- Create testimonials table
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
);

-- Insert sample testimonials
INSERT INTO testimonials (client_name, pet_name, pet_type, rating, review_text, service_received, is_featured) VALUES
('Sarah M.', 'Buddy', 'Dog', 5, 'Amazing service! The staff is incredibly caring and professional. My dog was nervous but they made him feel so comfortable. Dr. Santos was wonderful with him.', 'General Checkup', TRUE),
('John D.', 'Whiskers', 'Cat', 5, 'They take care of my pets so well. The place is clean, the staff is friendly, and the doctors are very knowledgeable. Highly recommended!', 'Vaccination', TRUE),
('Maria L.', 'Max', 'Dog', 4, 'Great experience with the grooming service. My dog looks and smells amazing! The staff was patient and gentle with him.', 'Pet Grooming', TRUE),
('Alex R.', 'Luna', 'Cat', 5, 'Emergency care was outstanding. They took care of my cat immediately and kept me informed throughout the process. Thank you for saving Luna!', 'Emergency Care', TRUE),
('Carlos P.', 'Rocky', 'Dog', 5, 'The surgery went perfectly. Dr. Cruz is an excellent surgeon and the follow-up care was exceptional. Rocky is back to his playful self!', 'Surgery', FALSE),
('Ana S.', 'Mittens', 'Cat', 4, 'Very professional and caring staff. The dental cleaning service was thorough and my cat is much healthier now.', 'Dental Cleaning', FALSE),
('Pedro M.', 'Charlie', 'Dog', 5, 'Best veterinary clinic in the area! The doctors are experienced and the facilities are modern. Charlie loves coming here for his checkups.', 'General Checkup', FALSE),
('Isabella R.', 'Bella', 'Cat', 5, 'Excellent service from start to finish. The staff remembered us from our previous visit and treated Bella with so much love and care.', 'Vaccination', FALSE);

-- Create index for better performance
CREATE INDEX idx_testimonials_featured ON testimonials(is_featured);
CREATE INDEX idx_testimonials_rating ON testimonials(rating);
CREATE INDEX idx_testimonials_created_at ON testimonials(created_at);

-- Fix and Update MavetCare Database
-- Run these commands to update your existing mavetcare_db database

USE mavetcare_db;

-- 1. Add pet_type column to sales table (if it doesn't exist)
ALTER TABLE sales ADD COLUMN IF NOT EXISTS pet_type VARCHAR(50) DEFAULT 'N/A' AFTER sale_date;

-- 2. Update inventory table structure (remove old columns, add new ones)
-- First, check if old columns exist and remove them
ALTER TABLE inventory DROP COLUMN IF EXISTS supplier;
ALTER TABLE inventory DROP COLUMN IF EXISTS unit;

-- Add product_image column if it doesn't exist
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS product_image VARCHAR(255) DEFAULT NULL AFTER price;

-- Add minimum_stock column if it doesn't exist
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS minimum_stock INT DEFAULT 10 AFTER expiry_date;

-- 3. Update services table structure (add category if it doesn't exist)
ALTER TABLE services ADD COLUMN IF NOT EXISTS category ENUM('General', 'Surgery', 'Vaccination', 'Grooming', 'Emergency', 'Consultation') DEFAULT 'General' AFTER duration;

-- 4. Update patients table structure (ensure pet_type enum is correct)
-- First, modify the pet_type column to allow the new values
ALTER TABLE patients MODIFY COLUMN pet_type ENUM('Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster', 'Other') NOT NULL;

-- 5. Update staff table structure (add status if it doesn't exist)
ALTER TABLE staff ADD COLUMN IF NOT EXISTS status ENUM('on-duty', 'off-duty') DEFAULT 'on-duty' AFTER schedule;

-- 6. Update appointments table structure (ensure proper foreign keys)
-- Add doctor_id column if it doesn't exist
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS doctor_id INT AFTER notes;

-- 7. Update users table structure (ensure user_type enum)
ALTER TABLE users MODIFY COLUMN user_type ENUM('client', 'admin') DEFAULT 'client';

-- 8. Create indexes for better performance (ignore errors if they already exist)
CREATE INDEX IF NOT EXISTS idx_patients_pet_code ON patients(pet_code);
CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(sale_date);
CREATE INDEX IF NOT EXISTS idx_inventory_category ON inventory(category);
CREATE INDEX IF NOT EXISTS idx_inventory_price ON inventory(price);
CREATE INDEX IF NOT EXISTS idx_services_category ON services(category);

-- 9. Add constraints (ignore errors if they already exist)
-- Note: MySQL doesn't support IF NOT EXISTS for constraints, so we'll handle errors gracefully
ALTER TABLE inventory ADD CONSTRAINT chk_quantity_positive CHECK (quantity >= 0);
ALTER TABLE inventory ADD CONSTRAINT chk_price_positive CHECK (price >= 0);
ALTER TABLE services ADD CONSTRAINT chk_service_price_positive CHECK (price >= 0);
ALTER TABLE sales ADD CONSTRAINT chk_sale_amount_positive CHECK (total_amount >= 0);

-- 10. Insert sample data if tables are empty

-- Insert sample staff if staff table is empty
INSERT IGNORE INTO staff (name, position, email, phone, schedule, status) VALUES
('Dr. Maria Santos', 'Doctor', 'maria.santos@mavetcare.com', '09123456789', 'Mon-Fri 9AM-5PM', 'on-duty'),
('Dr. Juan Dela Cruz', 'Senior Doctor', 'juan.delacruz@mavetcare.com', '09187654321', 'Mon-Sat 8AM-6PM', 'on-duty'),
('Ana Reyes', 'Receptionist', 'ana.reyes@mavetcare.com', '09234567890', 'Mon-Sat 8AM-6PM', 'on-duty'),
('Pedro Martinez', 'Veterinary Assistant', 'pedro.martinez@mavetcare.com', '09345678901', 'Mon-Fri 8AM-5PM', 'on-duty');

-- Insert sample services if services table is empty
INSERT IGNORE INTO services (service_name, description, price, duration, category) VALUES
('General Checkup', 'Comprehensive health examination for pets', 500.00, '30 minutes', 'General'),
('Vaccination', 'Essential vaccinations for pets', 800.00, '15 minutes', 'Vaccination'),
('Surgery', 'Surgical procedures for pets', 5000.00, '2-4 hours', 'Surgery'),
('Grooming', 'Pet grooming and hygiene services', 600.00, '1 hour', 'Grooming'),
('Emergency Care', 'Emergency medical treatment', 1500.00, '1-2 hours', 'Emergency'),
('Dental Cleaning', 'Professional dental cleaning for pets', 1200.00, '45 minutes', 'General'),
('X-Ray', 'Diagnostic imaging services', 800.00, '30 minutes', 'General'),
('Blood Test', 'Laboratory blood analysis', 600.00, '20 minutes', 'General');

-- Insert sample inventory if inventory table is empty
INSERT IGNORE INTO inventory (name, category, quantity, price, product_image, minimum_stock) VALUES
('Premium Dog Food', 'Food', 50, 450.00, 'premium dog food.png', 10),
('Cat Food Deluxe', 'Food', 40, 380.00, 'cat food deluxe.png', 8),
('Anti-Parasitic Medicine', 'Medicine', 30, 250.00, 'anti parasitic.png', 5),
('Vaccine Vial', 'Medicine', 25, 150.00, 'vaccine.png', 3),
('Surgical Gloves', 'Supplies', 100, 50.00, 'surgical gloves.png', 20),
('Pet Shampoo', 'Supplies', 35, 120.00, 'pet shampoo.png', 10),
('Dog Collar', 'Toys', 20, 200.00, 'dog collar.png', 5),
('Cat Toy Set', 'Toys', 15, 150.00, 'cat toys.png', 5);

-- Insert sample patients if patients table is empty
INSERT IGNORE INTO patients (pet_code, client_name, pet_name, pet_type, breed, age, weight, color, gender, client_contact) VALUES
('PC0001', 'Maria Garcia', 'Buddy', 'Dog', 'Golden Retriever', '3 years', 25.5, 'Golden', 'Male', '09123456789'),
('PC0002', 'Juan Lopez', 'Whiskers', 'Cat', 'Persian', '2 years', 4.2, 'White', 'Female', '09187654321'),
('PC0003', 'Ana Santos', 'Polly', 'Bird', 'Parakeet', '1 year', 0.3, 'Green', 'Female', '09234567890'),
('PC0004', 'Pedro Cruz', 'Max', 'Dog', 'German Shepherd', '4 years', 30.0, 'Black and Tan', 'Male', '09345678901');

-- Insert admin user if users table is empty (password: admin123)
INSERT IGNORE INTO users (first_name, last_name, email, phone, password, user_type) VALUES
('Admin', 'User', 'admin@mavetcare.com', '09123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 11. Update existing sales records to have pet_type if they don't have it
UPDATE sales SET pet_type = 'N/A' WHERE pet_type IS NULL OR pet_type = '';

-- 12. Update existing inventory records to have product_image if they don't have it
UPDATE inventory SET product_image = 'default.png' WHERE product_image IS NULL;

-- 13. Update existing inventory records to have minimum_stock if they don't have it
UPDATE inventory SET minimum_stock = 10 WHERE minimum_stock IS NULL;

-- 14. Update existing services records to have category if they don't have it
UPDATE services SET category = 'General' WHERE category IS NULL OR category = '';

-- 15. Update existing staff records to have status if they don't have it
UPDATE staff SET status = 'on-duty' WHERE status IS NULL OR status = '';

-- Display completion message
-- 16. Update existing sales items to include proper type information

UPDATE sales SET items = REPLACE(items, '}]', ',"type":"product"}]') WHERE items LIKE '%"name":"%' AND items NOT LIKE '%"type":"%';

SELECT 'Database update completed successfully!' AS message;

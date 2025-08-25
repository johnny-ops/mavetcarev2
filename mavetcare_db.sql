-- MavetCare Database Structure
-- Complete database setup for MavetCare Veterinary Clinic

-- Create database
CREATE DATABASE IF NOT EXISTS mavetcare_db;
USE mavetcare_db;

-- Users table for client accounts
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    user_type ENUM('client', 'admin') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patients table (pets)
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_code VARCHAR(20) UNIQUE NOT NULL,
    client_name VARCHAR(100) NOT NULL,
    pet_name VARCHAR(50) NOT NULL,
    pet_type ENUM('Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster', 'Other') NOT NULL,
    breed VARCHAR(50),
    age VARCHAR(20),
    weight DECIMAL(5,2),
    color VARCHAR(30),
    gender ENUM('Male', 'Female'),
    medical_history TEXT,
    client_contact VARCHAR(20),
    client_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff table (doctors and other staff)
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    schedule TEXT,
    status ENUM('on-duty', 'off-duty') DEFAULT 'on-duty',
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50),
    category ENUM('General', 'Surgery', 'Vaccination', 'Grooming', 'Emergency', 'Consultation') DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory table (products)
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('Medicine', 'Equipment', 'Food', 'Supplies', 'Toys') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    product_image VARCHAR(255) DEFAULT NULL,
    expiry_date DATE,
    minimum_stock INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    service VARCHAR(100) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    doctor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES staff(id) ON DELETE SET NULL
);

-- Sales table with pet_type column
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    items TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'GCash', 'PayMaya', 'Bank Transfer') NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pet_type VARCHAR(50) DEFAULT 'N/A',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- Insert sample data

-- Sample staff members
INSERT INTO staff (name, position, email, phone, schedule, status) VALUES
('Dr. Maria Santos', 'Doctor', 'maria.santos@mavetcare.com', '09123456789', 'Mon-Fri 9AM-5PM', 'on-duty'),
('Dr. Juan Dela Cruz', 'Senior Doctor', 'juan.delacruz@mavetcare.com', '09187654321', 'Mon-Sat 8AM-6PM', 'on-duty'),
('Ana Reyes', 'Receptionist', 'ana.reyes@mavetcare.com', '09234567890', 'Mon-Sat 8AM-6PM', 'on-duty'),
('Pedro Martinez', 'Veterinary Assistant', 'pedro.martinez@mavetcare.com', '09345678901', 'Mon-Fri 8AM-5PM', 'on-duty');

-- Sample services
INSERT INTO services (service_name, description, price, duration, category) VALUES
('General Checkup', 'Comprehensive health examination for pets', 500.00, '30 minutes', 'General'),
('Vaccination', 'Essential vaccinations for pets', 800.00, '15 minutes', 'Vaccination'),
('Surgery', 'Surgical procedures for pets', 5000.00, '2-4 hours', 'Surgery'),
('Grooming', 'Pet grooming and hygiene services', 600.00, '1 hour', 'Grooming'),
('Emergency Care', 'Emergency medical treatment', 1500.00, '1-2 hours', 'Emergency'),
('Dental Cleaning', 'Professional dental cleaning for pets', 1200.00, '45 minutes', 'General'),
('X-Ray', 'Diagnostic imaging services', 800.00, '30 minutes', 'General'),
('Blood Test', 'Laboratory blood analysis', 600.00, '20 minutes', 'General');

-- Sample inventory items
INSERT INTO inventory (name, category, quantity, price, product_image, minimum_stock) VALUES
('Premium Dog Food', 'Food', 50, 450.00, 'premium dog food.png', 10),
('Cat Food Deluxe', 'Food', 40, 380.00, 'cat food deluxe.png', 8),
('Anti-Parasitic Medicine', 'Medicine', 30, 250.00, 'anti parasitic.png', 5),
('Vaccine Vial', 'Medicine', 25, 150.00, 'vaccine.png', 3),
('Surgical Gloves', 'Supplies', 100, 50.00, 'surgical gloves.png', 20),
('Pet Shampoo', 'Supplies', 35, 120.00, 'pet shampoo.png', 10),
('Dog Collar', 'Toys', 20, 200.00, 'dog collar.png', 5),
('Cat Toy Set', 'Toys', 15, 150.00, 'cat toys.png', 5);

-- Sample patients
INSERT INTO patients (pet_code, client_name, pet_name, pet_type, breed, age, weight, color, gender, client_contact) VALUES
('PC0001', 'Maria Garcia', 'Buddy', 'Dog', 'Golden Retriever', '3 years', 25.5, 'Golden', 'Male', '09123456789'),
('PC0002', 'Juan Lopez', 'Whiskers', 'Cat', 'Persian', '2 years', 4.2, 'White', 'Female', '09187654321'),
('PC0003', 'Ana Santos', 'Polly', 'Bird', 'Parakeet', '1 year', 0.3, 'Green', 'Female', '09234567890'),
('PC0004', 'Pedro Cruz', 'Max', 'Dog', 'German Shepherd', '4 years', 30.0, 'Black and Tan', 'Male', '09345678901');

-- Sample appointments
INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, status, doctor_id) VALUES
(1, CURDATE(), '09:00:00', 'General Checkup', 'confirmed', 1),
(2, CURDATE(), '10:30:00', 'Vaccination', 'pending', 2),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Grooming', 'confirmed', 1),
(4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00:00', 'Surgery', 'pending', 2);

-- Sample sales
INSERT INTO sales (patient_id, items, total_amount, payment_method, pet_type) VALUES
(1, '[{"name":"Premium Dog Food","quantity":2,"price":450.00,"total":900.00,"type":"product"}]', 900.00, 'Cash', 'Dog'),
(2, '[{"name":"Vaccination","quantity":1,"price":800.00,"total":800.00,"type":"service"}]', 800.00, 'GCash', 'Cat'),
(3, '[{"name":"Cat Food Deluxe","quantity":1,"price":380.00,"total":380.00,"type":"product"},{"name":"Grooming","quantity":1,"price":600.00,"total":600.00,"type":"service"}]', 980.00, 'Card', 'Cat');

-- Create indexes for better performance
CREATE INDEX idx_patients_pet_code ON patients(pet_code);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_inventory_category ON inventory(category);
CREATE INDEX idx_inventory_price ON inventory(price);
CREATE INDEX idx_services_category ON services(category);

-- Add constraints
ALTER TABLE inventory ADD CONSTRAINT chk_quantity_positive CHECK (quantity >= 0);
ALTER TABLE inventory ADD CONSTRAINT chk_price_positive CHECK (price >= 0);
ALTER TABLE services ADD CONSTRAINT chk_service_price_positive CHECK (price >= 0);
ALTER TABLE sales ADD CONSTRAINT chk_sale_amount_positive CHECK (total_amount >= 0);

-- Create admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, phone, password, user_type) VALUES
('Admin', 'User', 'admin@mavetcare.com', '09123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create sample client user (password: client123)
INSERT INTO users (first_name, last_name, email, phone, password, user_type) VALUES
('John', 'Doe', 'john.doe@example.com', '09187654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');



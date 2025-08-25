<?php
/**
 * MavetCare Database Connection and Helper Functions
 * 
 * This file provides a centralized database connection and helper functions
 * for the MavetCare Veterinary Clinic Management System.
 * 
 * Database Structure Overview:
 * - users: Client and admin user accounts
 * - patients: Pet information and client details
 * - staff: Doctors and clinic staff information
 * - services: Available veterinary services
 * - inventory: Products and medical supplies
 * - appointments: Scheduled appointments
 * - sales: Transaction records
 * 
 * @author MavetCare System
 * @version 2.0
 */

// Database Configuration
class MavetCareDB {
    private $host = 'localhost';
    private $dbname = 'mavetcare_db';
    private $username = 'root';
    private $password = '';
    private $pdo;
    private $columnCache = [];
    
    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO instance
     */
    public function getPDO() {
        return $this->pdo;
    }

    private function tableHasColumn(string $table, string $column): bool {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $exists = (bool)$stmt->fetch();
        $this->columnCache[$key] = $exists;
        return $exists;
    }
    
    /**
     * PATIENTS MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all patients
     */
    public function getAllPatients() {
        $stmt = $this->pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get patient by ID
     */
    public function getPatientById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Add new patient
     */
    public function addPatient($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO patients (pet_name, pet_code, client_name, pet_type, breed, age, weight, client_contact, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['pet_name'],
            $data['pet_code'],
            $data['client_name'],
            $data['pet_type'],
            $data['breed'],
            $data['age'],
            $data['weight'],
            $data['client_contact']
        ]);
    }
    
    /**
     * Update patient
     */
    public function updatePatient($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE patients SET 
            pet_name = ?, pet_code = ?, client_name = ?, pet_type = ?, 
            breed = ?, age = ?, weight = ?, client_contact = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['pet_name'],
            $data['pet_code'],
            $data['client_name'],
            $data['pet_type'],
            $data['breed'],
            $data['age'],
            $data['weight'],
            $data['client_contact'],
            $id
        ]);
    }
    
    /**
     * Delete patient
     */
    public function deletePatient($id) {
        $stmt = $this->pdo->prepare("DELETE FROM patients WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * APPOINTMENTS MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all appointments with patient and doctor info
     */
    public function getAllAppointments() {
        $stmt = $this->pdo->query("
            SELECT a.*, p.pet_name, p.client_name, p.client_contact, s.name as doctor_name 
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.id 
            LEFT JOIN staff s ON a.doctor_id = s.id 
            ORDER BY a.appointment_date DESC, a.appointment_time ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get today's appointments
     */
    public function getTodayAppointments() {
        $stmt = $this->pdo->query("
            SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.id 
            LEFT JOIN staff s ON a.doctor_id = s.id 
            WHERE DATE(a.appointment_date) = CURDATE()
            ORDER BY a.appointment_time ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get upcoming appointments
     */
    public function getUpcomingAppointments($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, p.pet_name, p.client_name, s.name as doctor_name 
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.id 
            LEFT JOIN staff s ON a.doctor_id = s.id 
            WHERE a.appointment_date > CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add new appointment
     */
    public function addAppointment($data) {
        $hasType = $this->tableHasColumn('appointments', 'appointment_type');
        if ($hasType) {
            $stmt = $this->pdo->prepare("
                INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, appointment_type, status, doctor_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                $data['patient_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['service'],
                $data['appointment_type'] ?? null,
                $data['status'] ?? 'pending',
                $data['doctor_id']
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, status, doctor_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                $data['patient_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['service'],
                $data['status'] ?? 'pending',
                $data['doctor_id']
            ]);
        }
    }
    
    /**
     * Update appointment status
     */
    public function updateAppointmentStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    /**
     * SALES MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all sales with patient info
     */
    public function getAllSales() {
        $stmt = $this->pdo->query("
            SELECT s.*, p.pet_name, p.client_name 
            FROM sales s 
            LEFT JOIN patients p ON s.patient_id = p.id 
            ORDER BY s.sale_date DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get today's sales
     */
    public function getTodaySales() {
        $stmt = $this->pdo->query("
            SELECT s.*, p.pet_name, p.client_name 
            FROM sales s 
            LEFT JOIN patients p ON s.patient_id = p.id 
            WHERE DATE(s.sale_date) = CURDATE()
            ORDER BY s.sale_date DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Add new sale
     */
    public function addSale($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO sales (patient_id, items, total_amount, payment_method, pet_type, sale_date) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['patient_id'],
            $data['items'],
            $data['total_amount'],
            $data['payment_method'],
            $data['pet_type'] ?? 'N/A'
        ]);
    }
    
    /**
     * Get monthly revenue
     */
    public function getMonthlyRevenue($months = 12) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                SUM(total_amount) as revenue,
                COUNT(*) as transactions
            FROM sales 
            WHERE sale_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
            ORDER BY month DESC
        ");
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }
    
    /**
     * INVENTORY MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all inventory items
     */
    public function getAllInventory() {
        $stmt = $this->pdo->query("SELECT * FROM inventory ORDER BY category, name ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get low stock items
     */
    public function getLowStockItems() {
        $stmt = $this->pdo->query("
            SELECT * FROM inventory 
            WHERE quantity <= minimum_stock 
            ORDER BY quantity ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Add new inventory item
     */
    public function addInventoryItem($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO inventory (name, category, quantity, price, product_image, expiry_date, minimum_stock, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['name'],
            $data['category'],
            $data['quantity'],
            $data['price'],
            $data['product_image'] ?? null,
            $data['expiry_date'] ?? null,
            $data['minimum_stock'] ?? 10
        ]);
    }
    
    /**
     * Update inventory stock
     */
    public function updateInventoryStock($id, $quantity) {
        $stmt = $this->pdo->prepare("UPDATE inventory SET quantity = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$quantity, $id]);
    }
    
    /**
     * STAFF MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all staff members
     */
    public function getAllStaff() {
        $stmt = $this->pdo->query("SELECT * FROM staff ORDER BY position, name ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get doctors only
     */
    public function getDoctors() {
        $stmt = $this->pdo->query("
            SELECT * FROM staff 
            WHERE position LIKE '%Doctor%' 
            ORDER BY name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get on-duty staff
     */
    public function getOnDutyStaff() {
        $stmt = $this->pdo->query("
            SELECT * FROM staff 
            WHERE status = 'on-duty' 
            ORDER BY position, name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Add new staff member
     */
    public function addStaff($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO staff (name, position, email, phone, schedule, status, hire_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['name'],
            $data['position'],
            $data['email'],
            $data['phone'],
            $data['schedule'],
            $data['status'] ?? 'on-duty',
            $data['hire_date'] ?? null
        ]);
    }
    
    /**
     * SERVICES MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all services
     */
    public function getAllServices() {
        $stmt = $this->pdo->query("SELECT * FROM services ORDER BY category, service_name ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get services by category
     */
    public function getServicesByCategory($category) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM services 
            WHERE category = ? 
            ORDER BY service_name ASC
        ");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add new service
     */
    public function addService($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO services (service_name, description, price, duration, category, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['service_name'],
            $data['description'],
            $data['price'],
            $data['duration'],
            $data['category']
        ]);
    }
    
    /**
     * USERS MANAGEMENT FUNCTIONS
     */
    
    /**
     * Get all users
     */
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get users by type
     */
    public function getUsersByType($type) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE user_type = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * STATISTICS FUNCTIONS
     */
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        $stats = [];
        
        // Total patients
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM patients");
        $stats['total_patients'] = $stmt->fetch()['count'];
        
        // Total appointments today
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()");
        $stats['today_appointments'] = $stmt->fetch()['count'];
        
        // Total sales today
        $stmt = $this->pdo->query("SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM sales WHERE DATE(sale_date) = CURDATE()");
        $todaySales = $stmt->fetch();
        $stats['today_sales'] = $todaySales['count'];
        $stats['today_revenue'] = $todaySales['revenue'] ?? 0;
        
        // Total inventory items
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM inventory");
        $stats['total_inventory'] = $stmt->fetch()['count'];
        
        // Low stock items
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= minimum_stock");
        $stats['low_stock_items'] = $stmt->fetch()['count'];
        
        // Total staff
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM staff");
        $stats['total_staff'] = $stmt->fetch()['count'];
        
        // Total services
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM services");
        $stats['total_services'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * EXPORT FUNCTIONS
     */
    
    /**
     * Export data to CSV
     */
    public function exportToCSV($table, $filename = null) {
        if (!$filename) {
            $filename = $table . '_' . date('Y-m-d') . '.csv';
        }
        
        $stmt = $this->pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
        $data = $stmt->fetchAll();
        
        if (empty($data)) {
            return false;
        }
        
        // Get column headers
        $headers = array_keys($data[0]);
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        return true;
    }
    
    /**
     * UTILITY FUNCTIONS
     */
    
    /**
     * Generate unique pet code
     */
    public function generatePetCode() {
        $prefix = 'PC';
        $stmt = $this->pdo->query("SELECT MAX(CAST(SUBSTRING(pet_code, 3) AS UNSIGNED)) as max_num FROM patients");
        $result = $stmt->fetch();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->execute([$email]);
        }
        return $stmt->fetch()['count'] > 0;
    }
    
    /**
     * Validate user credentials
     */
    public function validateUser($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $hashedPassword,
            $data['user_type'] ?? 'client'
        ]);
    }
}

// Create global database instance
$db = new MavetCareDB();

// Helper functions for backward compatibility
function getPDO() {
    global $db;
    return $db->getPDO();
}

function getAllPatients() {
    global $db;
    return $db->getAllPatients();
}

function getAllAppointments() {
    global $db;
    return $db->getAllAppointments();
}

function getAllSales() {
    global $db;
    return $db->getAllSales();
}

function getAllInventory() {
    global $db;
    return $db->getAllInventory();
}

function getAllStaff() {
    global $db;
    return $db->getAllStaff();
}

function getAllServices() {
    global $db;
    return $db->getAllServices();
}

function getAllUsers() {
    global $db;
    return $db->getAllUsers();
}

function getDashboardStats() {
    global $db;
    return $db->getDashboardStats();
}

/**
 * Database Schema Documentation:
 * 
 * TABLES:
 * 
 * 1. users
 *    - id (Primary Key)
 *    - first_name, last_name, email, phone
 *    - password (hashed)
 *    - user_type (client/admin)
 *    - created_at, updated_at
 * 
 * 2. patients
 *    - id (Primary Key)
 *    - pet_code (unique identifier)
 *    - client_name, pet_name
 *    - pet_type (Dog, Cat, Bird, Rabbit, Hamster, Other)
 *    - breed, age, weight, color, gender
 *    - medical_history, client_contact, client_address
 *    - created_at
 * 
 * 3. staff
 *    - id (Primary Key)
 *    - name, position, email, phone
 *    - schedule, status (on-duty/off-duty)
 *    - hire_date, created_at
 * 
 * 4. services
 *    - id (Primary Key)
 *    - service_name, description, price
 *    - duration, category (General, Surgery, Vaccination, Grooming, Emergency, Consultation)
 *    - created_at
 * 
 * 5. inventory
 *    - id (Primary Key)
 *    - name, category (Medicine, Equipment, Food, Supplies, Toys)
 *    - quantity, price, product_image
 *    - expiry_date, minimum_stock
 *    - created_at, updated_at
 * 
 * 6. appointments
 *    - id (Primary Key)
 *    - patient_id (Foreign Key to patients)
 *    - appointment_date, appointment_time
 *    - service, status (pending, confirmed, completed, cancelled)
 *    - notes, doctor_id (Foreign Key to staff)
 *    - created_at
 * 
 * 7. sales
 *    - id (Primary Key)
 *    - patient_id (Foreign Key to patients)
 *    - items (JSON format)
 *    - total_amount, payment_method
 *    - sale_date, pet_type
 *    - created_at
 * 
 * RELATIONSHIPS:
 * - appointments.patient_id -> patients.id
 * - appointments.doctor_id -> staff.id
 * - sales.patient_id -> patients.id
 * 
 * INDEXES:
 * - patients.pet_code
 * - appointments.appointment_date
 * - sales.sale_date
 * - inventory.category, inventory.price
 * - services.category
 * 
 * CONSTRAINTS:
 * - inventory.quantity >= 0
 * - inventory.price >= 0
 * - services.price >= 0
 * - sales.total_amount >= 0
 */
?>

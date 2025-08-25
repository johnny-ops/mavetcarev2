<?php
session_name('admin_session');
session_start();

// Only allow from admin area (optional: add your own admin auth check here)

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Notification functions
function ensureNotificationsTable(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function addNotification(PDO $pdo, $type, $message) {
    ensureNotificationsTable($pdo);
    $stmt = $pdo->prepare('INSERT INTO notifications (type, message) VALUES (?, ?)');
    return $stmt->execute([$type, $message]);
}

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action provided']);
    exit;
}

header('Content-Type: application/json');

try {
    switch ($_POST['action']) {
        case 'add_appointment':
            $stmt = $pdo->prepare(
                'INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, status, notes, doctor_id) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $_POST['patient_id'],
                $_POST['appointment_date'],
                $_POST['appointment_time'],
                $_POST['service'],
                $_POST['status'] ?? 'pending',
                $_POST['notes'] ?? '',
                $_POST['doctor_id'] !== '' ? $_POST['doctor_id'] : null,
            ]);
            if ($result) {
                addNotification($pdo, 'appointment', 'Appointment scheduled on ' . $_POST['appointment_date'] . ' at ' . $_POST['appointment_time']);
            }
            echo json_encode(['success' => $result, 'message' => 'Appointment added successfully']);
            break;

        case 'add_patient':
            $pet_code = 'PC' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare(
                'INSERT INTO patients (pet_code, client_name, pet_name, pet_type, breed, age, weight, color, gender, medical_history, client_contact, client_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $pet_code,
                $_POST['client_name'],
                $_POST['pet_name'],
                $_POST['pet_type'],
                $_POST['breed'] ?? '',
                $_POST['age'] ?? '',
                $_POST['weight'] !== '' ? $_POST['weight'] : null,
                $_POST['color'] ?? '',
                $_POST['gender'] ?? '',
                $_POST['medical_history'] ?? '',
                $_POST['client_contact'] ?? '',
                $_POST['client_address'] ?? '',
            ]);
            if ($result) {
                addNotification($pdo, 'patient', 'New patient registered: ' . $_POST['pet_name'] . ' (' . $_POST['client_name'] . ')');
            }
            echo json_encode(['success' => $result, 'message' => 'Patient added successfully', 'pet_code' => $pet_code]);
            break;

        case 'add_service':
            $stmt = $pdo->prepare(
                'INSERT INTO services (service_name, description, price, duration, category) VALUES (?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $_POST['service_name'],
                $_POST['description'] ?? '',
                $_POST['price'],
                $_POST['duration'] ?? '',
                $_POST['category'] ?? 'General',
            ]);
            if ($result) {
                addNotification($pdo, 'service', 'New service added: ' . $_POST['service_name']);
            }
            echo json_encode(['success' => $result, 'message' => 'Service added successfully']);
            break;

        case 'add_staff':
            $stmt = $pdo->prepare(
                'INSERT INTO staff (name, position, email, phone, schedule, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['position'],
                $_POST['email'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['schedule'] ?? '',
                $_POST['status'] ?? 'on-duty',
                date('Y-m-d'),
            ]);
            if ($result) {
                addNotification($pdo, 'staff', 'New staff member added: ' . $_POST['name'] . ' (' . $_POST['position'] . ')');
            }
            echo json_encode(['success' => $result, 'message' => 'Staff added successfully']);
            break;

        case 'add_inventory':
            $stmt = $pdo->prepare(
                'INSERT INTO inventory (name, category, quantity, unit, price, supplier, expiry_date, minimum_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['category'],
                $_POST['quantity'],
                $_POST['unit'] ?? 'pcs',
                $_POST['price'],
                $_POST['supplier'] ?? '',
                $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null,
                $_POST['minimum_stock'] !== '' ? $_POST['minimum_stock'] : 10,
            ]);
            if ($result) {
                addNotification($pdo, 'inventory', 'New inventory item added: ' . $_POST['name'] . ' (Qty: ' . $_POST['quantity'] . ')');
            }
            echo json_encode(['success' => $result, 'message' => 'Item added to inventory']);
            break;

        case 'record_sale':
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO sales (patient_id, items, total_amount, payment_method, sale_date) VALUES (?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $_POST['patient_id'] !== '' ? $_POST['patient_id'] : null,
                $_POST['items'],
                $_POST['total_amount'],
                $_POST['payment_method'],
                date('Y-m-d H:i:s'),
            ]);
            if ($result) {
                addNotification($pdo, 'sale', 'New sale recorded: ₱' . number_format($_POST['total_amount'], 2));
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Sale recorded successfully']);
            break;

        case 'update_appointment_status':
            $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
            $result = $stmt->execute([$_POST['status'], $_POST['appointment_id']]);
            if ($result) {
                addNotification($pdo, 'appointment', 'Appointment status updated to: ' . $_POST['status']);
            }
            echo json_encode(['success' => $result, 'message' => 'Status updated successfully']);
            break;

        case 'get_notifications':
            ensureNotificationsTable($pdo);
            $stmt = $pdo->prepare('SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?');
            $stmt->execute([$_POST['limit'] ?? 10]);
            $notifications = $stmt->fetchAll();
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;

        case 'mark_notification_read':
            ensureNotificationsTable($pdo);
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = TRUE WHERE id = ?');
            $result = $stmt->execute([$_POST['notification_id']]);
            echo json_encode(['success' => $result, 'message' => 'Notification marked as read']);
            break;

        case 'mark_all_notifications_read':
            ensureNotificationsTable($pdo);
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = TRUE');
            $result = $stmt->execute();
            echo json_encode(['success' => $result, 'message' => 'All notifications marked as read']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>



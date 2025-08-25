<?php
session_name('user_session');
session_start();

require_once __DIR__ . '/admin/mavetcare_db.php';

// Redirect guests to login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

/**
 * Fetch current user info
 */
$pdo = getPDO();
$stmtUser = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    header('Location: logout.php');
    exit();
}

$fullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
$userPhone = $currentUser['phone'] ?? '';

// Helpers
function fetchUserPets(PDO $pdo, string $clientName, string $clientPhone) {
    $stmt = $pdo->prepare("SELECT id, pet_name, pet_type, breed FROM patients WHERE client_name = ? OR client_contact = ? ORDER BY created_at DESC");
    $stmt->execute([$clientName, $clientPhone]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchServices(PDO $pdo) {
    $stmt = $pdo->query("SELECT id, service_name FROM services ORDER BY category, service_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function canBookSlot(PDO $pdo, string $date, string $time): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $stmt->execute([$date, $time]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($row['cnt'] ?? 0) < 3; // max 3 per slot
}

function createPatient(PDO $pdo, array $data): int {
    $stmt = $pdo->prepare("INSERT INTO patients (pet_code, client_name, pet_name, pet_type, breed, age, medical_history, client_contact, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $petCode = 'PC' . str_pad((string)rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $stmt->execute([
        $petCode,
        $data['client_name'],
        $data['pet_name'],
        $data['pet_type'],
        $data['breed'] ?? null,
        $data['age'] ?? null,
        $data['medical_history'] ?? null,
        $data['client_contact'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function createAppointment(PDO $pdo, array $data): bool {
    // Try insert with appointment_type if column exists
    try {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, appointment_type, status, notes, doctor_id, created_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, NULL, NOW())");
        return $stmt->execute([
            $data['patient_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['service'],
            $data['appointment_type'] ?? null,
            $data['notes'] ?? null,
        ]);
} catch (Exception $e) {
        // Fallback for older schema without appointment_type
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, service, status, notes, doctor_id, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NULL, NOW())");
        return $stmt->execute([
            $data['patient_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['service'],
            $data['notes'] ?? null,
        ]);
    }
}

// Handle form submissions
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'book_existing') {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $service = trim($_POST['service'] ?? '');
        $apptType = trim($_POST['appointment_type'] ?? '');
        $date = trim($_POST['preferred_date'] ?? '');
        $time = trim($_POST['preferred_time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $reminder = isset($_POST['reminder_opt_in']) ? 'Yes' : 'No';

        if (!$patientId) $errors[] = 'Please select a pet.';
        if ($service === '') $errors[] = 'Please select a service.';
        if ($apptType === '') $errors[] = 'Please select a type of appointment.';
        if ($date === '') $errors[] = 'Please choose a preferred date.';
        if ($time === '') $errors[] = 'Please choose a preferred time.';

        if (!$errors && !canBookSlot($pdo, $date, $time)) {
            $errors[] = 'Selected time slot is fully booked. Please choose another time.';
        }

        if (!$errors) {
            $fullNotes = $notes;
            if ($apptType !== '') {
                $fullNotes = ($fullNotes ? ($fullNotes . " \n") : '') . 'Type: ' . $apptType;
            }
            $fullNotes .= "\nReminder Opt-in: " . $reminder;
            $ok = createAppointment($pdo, [
                'patient_id' => $patientId,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'service' => $service,
                'appointment_type' => $apptType,
                'notes' => $fullNotes,
            ]);
            if ($ok) {
                $_SESSION['appt_success'] = 'Appointment request submitted.';
                header('Location: myAppointments.php?tab=existing');
                exit();
            } else {
                $errors[] = 'Failed to submit appointment.';
            }
        }
    }

    if ($action === 'book_new') {
        $petName = trim($_POST['pet_name'] ?? '');
        $petType = trim($_POST['pet_type'] ?? '');
        $petBreed = trim($_POST['pet_breed'] ?? '');
        $petAge = trim($_POST['pet_age'] ?? '');
        $medicalHistory = trim($_POST['medical_history'] ?? '');

        $service = trim($_POST['service'] ?? '');
        $apptType = trim($_POST['appointment_type'] ?? '');
        $date = trim($_POST['preferred_date'] ?? '');
        $time = trim($_POST['preferred_time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $reminder = isset($_POST['reminder_opt_in']) ? 'Yes' : 'No';

        if ($petName === '') $errors[] = 'Please enter pet name.';
        if ($petType === '') $errors[] = 'Please select pet type.';
        if ($service === '') $errors[] = 'Please select a service.';
        if ($apptType === '') $errors[] = 'Please select a type of appointment.';
        if ($date === '') $errors[] = 'Please choose a preferred date.';
        if ($time === '') $errors[] = 'Please choose a preferred time.';

        if (!$errors && !canBookSlot($pdo, $date, $time)) {
            $errors[] = 'Selected time slot is fully booked. Please choose another time.';
        }

        if (!$errors) {
            $patientId = createPatient($pdo, [
                'client_name' => $fullName,
                'client_contact' => $userPhone,
                'pet_name' => $petName,
                'pet_type' => $petType,
                'breed' => $petBreed,
                'age' => $petAge,
                'medical_history' => $medicalHistory,
            ]);

            $fullNotes = $notes;
            if ($apptType !== '') {
                $fullNotes = ($fullNotes ? ($fullNotes . " \n") : '') . 'Type: ' . $apptType;
            }
            $fullNotes .= "\nReminder Opt-in: " . $reminder;

            $ok = createAppointment($pdo, [
                'patient_id' => $patientId,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'service' => $service,
                'appointment_type' => $apptType,
                'notes' => $fullNotes,
            ]);
            if ($ok) {
                $_SESSION['appt_success'] = 'New pet added and appointment request submitted.';
                header('Location: myAppointments.php?tab=new');
                exit();
            } else {
                $errors[] = 'Failed to submit appointment.';
            }
        }
    }
}

$services = fetchServices($pdo);
$pets = fetchUserPets($pdo, $fullName, $userPhone);

// Fetch user's bookings
$stmtBookings = $pdo->prepare("SELECT a.id, a.appointment_date, a.appointment_time, a.service, a.appointment_type, a.status, a.notes, p.pet_name FROM appointments a LEFT JOIN patients p ON a.patient_id = p.id WHERE p.client_name = ? OR p.client_contact = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmtBookings->execute([$fullName, $userPhone]);
$bookings = $stmtBookings->fetchAll(PDO::FETCH_ASSOC);

// PRG flash success and initial tab
if (!empty($_SESSION['appt_success'])) {
    $success = $_SESSION['appt_success'];
    unset($_SESSION['appt_success']);
}
$initialTab = isset($_GET['tab']) && in_array($_GET['tab'], ['existing','new']) ? $_GET['tab'] : 'existing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Appointments - MavetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="styles/myAppointments.css">
    <script>
        function switchTab(key){
            document.querySelectorAll('[data-tab]').forEach(el=>{ el.style.display = 'none'; });
            document.getElementById('tab-'+key).style.display = 'block';
            document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            document.getElementById('btn-'+key).classList.add('active');
        }
        function goToBooking(tab){
            switchTab(tab || 'existing');
            const target = document.getElementById('booking-section');
            if (target) target.scrollIntoView({behavior:'smooth', block:'start'});
        }
        document.addEventListener('DOMContentLoaded',()=>{
            switchTab('<?php echo htmlspecialchars($initialTab); ?>');
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth()+1).padStart(2,'0');
            const dd = String(today.getDate()).padStart(2,'0');
            document.querySelectorAll('input[type="date"]').forEach(inp=>{ inp.min = `${yyyy}-${mm}-${dd}`; });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="brand-bar">
            <div class="brand-left">
                <span class="brand-dot"></span>
                <span class="brand-title">MavetCare</span>
        </div>
            <a href="index.php" class="back-pill">⟵ Back to Home</a>
        </div>
        <div class="hero" style="margin-top:16px;">
            <div>
                <div class="muted" style="color:#eaffea; opacity:0.95; font-weight:700;">Hello, <?php echo htmlspecialchars(trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''))); ?>!</div>
                <h2>My Appointments</h2>
                <div class="sub">View and manage your scheduled appointments</div>
                </div>
            <div>
                <button class="cta" onclick="goToBooking('existing')">＋ Book New Appointment</button>
            </div>
        </div>
        <div class="card">
            <h1>Your Appointments</h1>
            <div class="list">
                <?php if (!$bookings): ?>
                    <div style="text-align:center; padding:24px 6px;">
                        <div style="font-size:22px; font-weight:800; color:#111827;">No appointments found</div>
                        <div class="muted" style="margin:6px 0 16px;">You haven't booked any appointments yet.</div>
                        <button class="cta" onclick="goToBooking('existing')">＋ Book Your First Appointment</button>
            </div>
                <?php else: ?>
                    <?php foreach ($bookings as $bk): ?>
                        <div class="list-item">
                            <div>
                                <div style="font-weight:700; font-size:16px;"><?php echo htmlspecialchars($bk['pet_name'] ?? ''); ?> • <?php echo htmlspecialchars($bk['service']); ?><?php if (!empty($bk['appointment_type'])): ?> — <span style="font-weight:600; color:#16a34a;"><?php echo htmlspecialchars($bk['appointment_type']); ?></span><?php endif; ?></div>
                                <div class="muted"><?php echo htmlspecialchars($bk['appointment_date']); ?> @ <?php echo htmlspecialchars(substr($bk['appointment_time'],0,5)); ?></div>
                </div>
                            <div>
                                <span class="status <?php echo htmlspecialchars($bk['status']); ?>"><?php echo htmlspecialchars(ucfirst($bk['status'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                </div>
        <div class="card" id="booking-section">
            <h1>Appointment Booking</h1>
            <div class="muted" style="margin-bottom:10px;">Choose an existing pet or add a new one, then select your preferred schedule.</div>
            <div class="tabs">
                <button id="btn-existing" class="tab-btn" onclick="switchTab('existing')">Booking with Existing Pet</button>
                <button id="btn-new" class="tab-btn" onclick="switchTab('new')">Booking with New Pet</button>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $e): ?>
                        <div>- <?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div id="tab-existing" data-tab class="card" style="display:none;">
                <form method="post">
                    <input type="hidden" name="action" value="book_existing" />
                    <div class="grid">
                        <div>
                            <label>Select a Pet</label>
                            <select name="patient_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($pets as $pet): ?>
                                    <option value="<?php echo (int)$pet['id']; ?>">
                                        <?php echo htmlspecialchars($pet['pet_name'] . ' (' . $pet['pet_type'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                        <div>
                            <label>Select Service</label>
                            <select name="service" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($services as $svc): ?>
                                    <option value="<?php echo htmlspecialchars($svc['service_name']); ?>">
                                        <?php echo htmlspecialchars($svc['service_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Type of Appointment</label>
                            <input type="text" name="appointment_type" placeholder="e.g., Follow-up, First Visit" />
                        </div>
                        <div class="row">
                            <div>
                                <label>Preferred Date</label>
                                <input type="date" name="preferred_date" required />
                            </div>
                            <div>
                                <label>Preferred Time</label>
                                <input type="time" name="preferred_time" required />
                                <div class="muted">Max 3 appointments per time slot</div>
                            </div>
                        </div>
                        <div>
                            <label>Additional Notes</label>
                            <textarea name="notes" placeholder="Anything we should know?"></textarea>
                        </div>
                        <div>
                            <label><input type="checkbox" name="reminder_opt_in" /> Receive appointment reminder via SMS/email</label>
                        </div>
                    </div>
                    <br />
                    <button class="btn" type="submit">Submit Booking</button>
                </form>
                </div>

            <div id="tab-new" data-tab class="card" style="display:none;">
                <form method="post">
                    <input type="hidden" name="action" value="book_new" />
                    <div class="grid">
                        <div>
                            <label>Pet Name</label>
                            <input type="text" name="pet_name" required />
                    </div>
                        <div>
                            <label>Pet Type</label>
                            <select name="pet_type" required>
                                <option value="">-- Select --</option>
                                <option>Dog</option>
                                <option>Cat</option>
                                <option>Bird</option>
                                <option>Rabbit</option>
                                <option>Hamster</option>
                                <option>Other</option>
                        </select>
                    </div>
                        <div>
                            <label>Pet Breed</label>
                            <input type="text" name="pet_breed" />
                    </div>
                        <div>
                            <label>Pet Age (years)</label>
                            <input type="text" name="pet_age" placeholder="e.g., 3 years" />
                    </div>
                        <div style="grid-column: 1 / -1;">
                            <label>Medical History (optional)</label>
                            <textarea name="medical_history"></textarea>
                </div>

                        <div style="grid-column: 1 / -1; border-top: 1px dashed #e5e7eb; padding-top: 10px; margin-top: 10px; font-weight:700;">Appointment Details</div>

                        <div>
                            <label>Select a Service</label>
                            <select name="service" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($services as $svc): ?>
                                    <option value="<?php echo htmlspecialchars($svc['service_name']); ?>">
                                        <?php echo htmlspecialchars($svc['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                        <div>
                            <label>Type of Appointment</label>
                            <input type="text" name="appointment_type" placeholder="e.g., Follow-up, First Visit" />
                    </div>
                        <div class="row">
                            <div>
                                <label>Preferred Date</label>
                                <input type="date" name="preferred_date" required />
                    </div>
                            <div>
                                <label>Preferred Time</label>
                                <input type="time" name="preferred_time" required />
                                <div class="muted">Max 3 appointments per time slot</div>
                    </div>
                </div>
                        <div>
                            <label>Additional Notes</label>
                            <textarea name="notes" placeholder="Anything we should know?"></textarea>
                </div>
                        <div>
                            <label><input type="checkbox" name="reminder_opt_in" /> Receive appointment reminder via SMS/email</label>
                </div>
                    </div>
                    <br />
                    <button class="btn" type="submit">Add Pet & Submit Booking</button>
            </form>
            </div>
        </div>

        
                </div>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3 class="section-title">Quick Links</h3>
                <ul class="quick-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="reviews.php">Reviews</a></li>
                </ul>
                                </div>
            <div class="divider"></div>
            <div class="footer-section center">
                <div class="logo-section">
                    <div class="footer-logo">
                        <div class="paw-icon"></div>
                        <span class="brand-name">MaVetCare</span>
                            </div>
                    <p class="tagline">Leave your pets in safe hands.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                </div>
            <div class="divider"></div>
            <div class="footer-section right">
                <h3 class="section-title">Get in Touch!</h3>
                <div class="contact-section">
                    <div class="email-display">
                        <span>mavetcare@email.com</span>
                        <div class="paw-btn"></div>
        </div>
                    <div class="phone-info">
                        <div class="phone-icon">
                            <i class="fa-solid fa-mobile"></i>
    </div>
                        <span>+63 123 456 7890</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="pet-images-footer">
            <div class="cat-image"></div>
            <div class="dog-image"></div>
        </div>
        <div class="copyright">
            All Rights Reserved to <strong>Mavetcare 2025</strong>
        </div>
    </footer>
                    </body>
                </html>



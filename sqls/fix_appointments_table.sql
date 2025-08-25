-- Fix appointments table to include user_id and proper relationships
-- This will allow appointments to be linked to logged-in users

-- First, let's add the user_id column to the appointments table
ALTER TABLE appointments ADD COLUMN user_id INT AFTER id;
ALTER TABLE appointments ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Update existing appointments to link to a default user (you can change this as needed)
UPDATE appointments SET user_id = (SELECT id FROM users WHERE user_type = 'client' LIMIT 1) WHERE user_id IS NULL;

-- Make user_id NOT NULL after setting default values
ALTER TABLE appointments MODIFY COLUMN user_id INT NOT NULL;

-- Add index for better performance
CREATE INDEX idx_appointments_user_id ON appointments(user_id);

-- Optional: Add some sample appointments for the existing users
INSERT INTO appointments (user_id, patient_id, appointment_date, appointment_time, service, status, doctor_id, notes) VALUES
((SELECT id FROM users WHERE email = 'john.doe@example.com'), 1, CURDATE(), '09:00:00', 'General Checkup', 'confirmed', 1, 'Regular checkup for Buddy'),
((SELECT id FROM users WHERE email = 'john.doe@example.com'), 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30:00', 'Vaccination', 'pending', 2, 'Annual vaccination for Whiskers'),
((SELECT id FROM users WHERE email = 'admin@mavetcare.com'), 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Grooming', 'confirmed', 1, 'Grooming session for Polly');

-- Display the updated structure
DESCRIBE appointments;

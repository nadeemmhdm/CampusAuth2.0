<?php
require 'config.php';

echo "<h2>Updating Database to V3 (Profile & Features Support)...</h2>";

// --- 1. Users Table Columns (Profile Module) ---
$cols = [
    "first_login_at DATETIME DEFAULT NULL",
    "first_login_ip VARCHAR(45) DEFAULT NULL",
    "first_login_device VARCHAR(255) DEFAULT NULL",
    "last_login_at DATETIME DEFAULT NULL",
    "last_login_ip VARCHAR(45) DEFAULT NULL",
    "last_login_device VARCHAR(255) DEFAULT NULL",
    "password_last_changed_at DATETIME DEFAULT NULL",
    "failed_login_attempts INT DEFAULT 0",
    "account_locked_until DATETIME DEFAULT NULL",
    "created_at DATETIME DEFAULT CURRENT_TIMESTAMP"
];

echo "<h3>1. Updating Users Table</h3>";
foreach ($cols as $col) {
    $conn->query("ALTER TABLE users ADD COLUMN $col");
    // Suppress errors for existing columns
}

// --- 2. Login Logs Table ---
echo "<h3>2. Creating Login Logs</h3>";
$sql = "CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    device_info VARCHAR(255),
    status ENUM('success', 'failed') DEFAULT 'success',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql))
    echo "Table 'login_logs' check passed.<br>";
else
    echo "Error login_logs: " . $conn->error . "<br>";

// --- 3. Medical Certificates Table ---
echo "<h3>3. Creating Medical Certificates Table</h3>";
$sql = "CREATE TABLE IF NOT EXISTS medical_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    reason TEXT,
    start_date DATE,
    end_date DATE,
    file_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql))
    echo "Table 'medical_certificates' check passed.<br>";
else
    echo "Error medical_certificates: " . $conn->error . "<br>";

// --- 4. Attendance Settings Table ---
echo "<h3>4. Creating Attendance Settings Table</h3>";
$sql = "CREATE TABLE IF NOT EXISTS attendance_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    half_day_percent DECIMAL(5,2) DEFAULT 50.00,
    leave_day_percent DECIMAL(5,2) DEFAULT 0.00,
    min_attendance_percent DECIMAL(5,2) DEFAULT 75.00, /* legacy field support */
    eligibility_percent DECIMAL(5,2) DEFAULT 80.00,
    medical_bonus_percent DECIMAL(5,2) DEFAULT 5.00,
    exam_fee_amount DECIMAL(10,2) DEFAULT 500.00,
    UNIQUE KEY (class_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
)";
if ($conn->query($sql))
    echo "Table 'attendance_settings' check passed.<br>";
else
    echo "Error attendance_settings: " . $conn->error . "<br>";

// --- 5. Student Results Table (Final Eligibility) ---
echo "<h3>5. Creating Student Results Table</h3>";
$sql = "CREATE TABLE IF NOT EXISTS student_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    final_percent DECIMAL(5,2),
    eligibility_status ENUM('eligible', 'condonation_required', 'not_eligible') DEFAULT 'not_eligible',
    medical_bonus_applied TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (student_id, class_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql))
    echo "Table 'student_results' check passed.<br>";
else
    echo "Error student_results: " . $conn->error . "<br>";

echo "<h2>Database V3 Update Complete.</h2>";
?>
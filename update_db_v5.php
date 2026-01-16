<?php
require 'config.php';

echo "<h2>Updating Database to V5 (Leaves & Semesters)...</h2>";

// 1. Leave Requests Table
$sql = "CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    leave_type ENUM('full', 'half') DEFAULT 'full',
    reason TEXT,
    attachment_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT DEFAULT NULL, /* Teacher or Admin ID */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
)";
if ($conn->query($sql))
    echo "Table 'leave_requests' created/checked.<br>";
else
    echo "Error leave_requests: " . $conn->error . "<br>";

// 2. Archived Results Table (For Semester End)
$sql = "CREATE TABLE IF NOT EXISTS archived_semester_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sem_name VARCHAR(100),
    academic_year VARCHAR(20),
    student_id INT,
    class_name VARCHAR(100),
    final_attendance DECIMAL(5,2),
    medical_bonus_applied TINYINT(1),
    eligibility_status VARCHAR(50),
    archived_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql))
    echo "Table 'archived_semester_results' created/checked.<br>";
else
    echo "Error archived_semester_results: " . $conn->error . "<br>";

// 3. System Settings Table (Current Semester State)
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT
)";
if ($conn->query($sql)) {
    echo "Table 'system_settings' created/checked.<br>";
    // Insert default semester state allowed
    $conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('semester_status', 'active')");
    $conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('current_academic_year', '2025-2026')");
    $conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('current_semester', 'Sem 1')");
}

echo "<h3>Database V5 Update Complete.</h3>";
?>
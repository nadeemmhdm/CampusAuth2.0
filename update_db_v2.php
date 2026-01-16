<?php
require 'config.php';

echo "<h2>Appling V2 Updates (Strict Mode & Approval System)...</h2>";

// 1. Users Table - Add Status
$sql = "SHOW COLUMNS FROM users LIKE 'status'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'blocked') DEFAULT 'active'");
    echo "Added 'status' column to users.<br>";
}

// 2. Attendance Edit Requests Table
$sql = "CREATE TABLE IF NOT EXISTS attendance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    date DATE NOT NULL,
    original_status VARCHAR(20),
    requested_status VARCHAR(20),
    reason TEXT,
    full_day_request_type ENUM('present', 'absent', 'half_day', 'leave'), /* Normalized status field */
    requested_by INT NOT NULL, /* Teacher ID */
    admin_action_by INT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance_requests' created.<br>";
} else {
    echo "Error creating 'attendance_requests': " . $conn->error . "<br>";
}

echo "<h3>Database V2 Update Complete!</h3>";
?>
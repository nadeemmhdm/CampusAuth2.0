<?php
require 'config.php';

echo "<h2>Updating Database for Exam Eligibility System...</h2>";

// 1. Update Attendance Settings Table
$sql = "ALTER TABLE attendance_settings 
        ADD COLUMN eligibility_percent FLOAT DEFAULT 80.0,
        ADD COLUMN medical_bonus_percent FLOAT DEFAULT 5.0,
        ADD COLUMN exam_fee_amount FLOAT DEFAULT 500.00";

if ($conn->query($sql) === TRUE) {
    echo "Updated 'attendance_settings' table.<br>";
} else {
    // If column exists, it might error, but we proceed
    echo "Notice on 'attendance_settings': " . $conn->error . "<br>";
}

// 2. Medical Certificates Table
$sql = "CREATE TABLE IF NOT EXISTS medical_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY (student_id),
    KEY (class_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'medical_certificates' created/ready.<br>";
} else {
    echo "Error creating 'medical_certificates': " . $conn->error . "<br>";
}

// 3. Student Final Results / Eligibility Table
$sql = "CREATE TABLE IF NOT EXISTS student_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    academic_year VARCHAR(50),
    total_days INT,
    attended_score FLOAT, -- Sum of daily scores
    final_percent FLOAT,
    medical_bonus_applied BOOLEAN DEFAULT FALSE,
    eligibility_status ENUM('eligible', 'condonation_required', 'not_eligible') NOT NULL,
    is_finalized BOOLEAN DEFAULT FALSE,
    finalized_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (student_id, class_id, academic_year)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'student_results' created/ready.<br>";
} else {
    echo "Error creating 'student_results': " . $conn->error . "<br>";
}

echo "<h3>Database Update Complete!</h3>";
echo "<a href='index.php'>Go to Login</a>";
?>
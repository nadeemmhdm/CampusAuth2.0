<?php
require 'config.php';

echo "<h2>Setting up Database...</h2>";

// 1. Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE, -- Email for Admin/Teacher/Parent, Admission No for Student
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default_avatar.png',
    class_id INT DEFAULT NULL, -- For Students (assigned class) and Teachers (assigned class tutor)
    parent_id INT DEFAULT NULL, -- For Students (linked parent)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully.<br>";
} else {
    echo "Error creating table 'users': " . $conn->error . "<br>";
}

// 2. Classes Table
$sql = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    academic_year VARCHAR(50) NOT NULL,
    tutor_id INT DEFAULT NULL, -- Linked to users(id) where role='teacher'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'classes' created successfully.<br>";
} else {
    echo "Error creating table 'classes': " . $conn->error . "<br>";
}

// 3. Attendance Settings Table (Per Class)
$sql = "CREATE TABLE IF NOT EXISTS attendance_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    half_day_percent FLOAT DEFAULT 50.0,
    leave_day_percent FLOAT DEFAULT 0.0,
    min_attendance_percent FLOAT DEFAULT 75.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance_settings' created successfully.<br>";
} else {
    echo "Error creating table 'attendance_settings': " . $conn->error . "<br>";
}

// 4. Attendance Records Table
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'half_day', 'leave') NOT NULL,
    marked_by INT NOT NULL, -- Teacher/Admin ID
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, date)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance' created successfully.<br>";
} else {
    echo "Error creating table 'attendance': " . $conn->error . "<br>";
}

// 5. Audit Logs Table
$sql = "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'audit_logs' created successfully.<br>";
} else {
    echo "Error creating table 'audit_logs': " . $conn->error . "<br>";
}

// Create Default Admin if it doesn't exist
$admin_user = 'admin';
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
$check_admin = $conn->query("SELECT * FROM users WHERE username = '$admin_user'");

if ($check_admin->num_rows == 0) {
    $sql = "INSERT INTO users (username, password, role, full_name) VALUES ('$admin_user', '$admin_pass', 'admin', 'System Administrator')";
    if ($conn->query($sql) === TRUE) {
        echo "Default Admin user created (Username: admin, Password: admin123).<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

echo "<h3>Database Setup Complete!</h3>";
?>
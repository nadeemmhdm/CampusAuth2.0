<?php
require 'config.php';

echo "<h2>Updating Database to V6 (Announcements & Notifications)...</h2>";

// 1. Announcements Table
// Stores the main content, media links (JSON), target roles/classes.
$sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    media_paths TEXT, /* JSON array of file paths for images/videos */
    link_url VARCHAR(255),
    link_title VARCHAR(255),
    
    target_role ENUM('all', 'student', 'teacher', 'parent', 'admin') DEFAULT 'all',
    target_class_id INT DEFAULT NULL, /* If NULL, global for the role. If set, specific class. */
    
    posted_by INT NOT NULL, /* User ID */
    posted_by_role VARCHAR(50), /* admin, teacher */
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    is_deleted TINYINT(1) DEFAULT 0
)";

if ($conn->query($sql))
    echo "Table 'announcements' created/checked.<br>";
else
    echo "Error announcements: " . $conn->error . "<br>";

// 2. Notification Reads (Optional but good for 'Unread' badge)
// We might not store a row for every user for every announcement (too heavy).
// Instead, we can store "last read time" in user table or a simple 'read_log' table.
// For now, let's keep it simple: A read_log table.
$sql = "CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (announcement_id, user_id)
)";

if ($conn->query($sql))
    echo "Table 'announcement_reads' created/checked.<br>";
else
    echo "Error announcement_reads: " . $conn->error . "<br>";

echo "<h3>Database V6 Update Complete.</h3>";
?>
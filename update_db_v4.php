<?php
require 'config.php';

echo "<h2>Updating Database to V4 (Advanced Roles)...</h2>";

// 1. Modify Role Enum to include 'sub_admin' and 'temp_teacher'
// We attempt to change the column definition. If it's VARCHAR, this might convert it to ENUM or update ENUM.
// Safest is to modify it to be larger or update ENUM list.
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student', 'parent', 'sub_admin', 'temp_teacher') NOT NULL");
echo "Updated 'role' column ENUM.<br>";

// 2. Add New Columns
$cols = [
    "expires_at DATETIME DEFAULT NULL",
    "permissions TEXT DEFAULT NULL", /* JSON string for sub_admin permissions */
    "created_by INT DEFAULT NULL", /* Who created this temp/sub user */
    "temp_class_id INT DEFAULT NULL" /* Specific class access for temp teacher */
];

foreach ($cols as $col) {
    if ($conn->query("ALTER TABLE users ADD COLUMN $col")) {
        echo "Added column: $col <br>";
    } else {
        // If fails, likely exists
    }
}

echo "<h3>Database V4 Update Complete.</h3>";
?>
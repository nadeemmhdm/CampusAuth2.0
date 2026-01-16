<?php
require 'config.php';
// Common Notifications Page for All Roles
checkLogin();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$class_id = isset($_SESSION['class_id']) ? $_SESSION['class_id'] : 0;
// If parent, maybe fetch child's class? 
// For now parent sees announcements for 'parent' role (Global) + Class announcements if we link parent->child->class.
// Let's handle parent logic:
if ($role == 'parent') {
    // Parent sees announcements targeted to 'parent'
    // AND announcements targeted to 'parent' for their child's class.
    // We need child's class id.
    $child_res = $conn->query("SELECT class_id FROM users WHERE parent_id=$user_id LIMIT 1");
    if ($child_res->num_rows > 0) {
        $class_id = $child_res->fetch_row()[0];
    }
}
if ($role == 'teacher') {
    // Teachers see 'teacher' role announcements
    // + Global announcements
    // Usually teachers don't receive class announcements unless they are also posted 'to teachers'.
}

// Build Query
// 1. Role match: target_role = 'all' OR target_role = $role
// 2. Class match: target_class_id IS NULL (Global) OR target_class_id = $class_id
// 3. Not deleted, Not expired.

$sql = "SELECT a.*, u.full_name as author_name, c.class_name 
        FROM announcements a 
        LEFT JOIN users u ON a.posted_by = u.id 
        LEFT JOIN classes c ON a.target_class_id = c.id
        WHERE a.is_deleted = 0 
        AND (a.expires_at IS NULL OR a.expires_at > NOW())
        AND (a.target_role = 'all' OR a.target_role = '$role') 
        AND (a.target_class_id IS NULL OR a.target_class_id = $class_id)
        ORDER BY a.created_at DESC LIMIT 50";

$notifs = $conn->query($sql);
?>
<!-- We need layout. Since this file is in root but menu links point to role folders... 
Actually, sidebar links point to 'notifications.php' in role folder usually? 
The sidebar said 'notifications.php'. If we put this file in root, we need to adjust includes.
Wait, sidebar links are relative. 
Admin: admin/notifications.php -> ../includes/header.php
Student: student/notifications.php -> ../includes/header.php
So we should place this file in EACH role folder? Or one common file included?
Common file included is better. 
Let's create 'includes/notifications_content.php' and wrapper pages.
-->
<?php include 'includes/header.php'; ?>
<!-- Wait, header path depends on location. -->
<!-- RE-STRATEGY: Create this content as a simplified include or just copy to folders. Copy safest for relative paths. -->
<!-- Actually, I will create role/notifications.php and include this content... but header include needs to be localized. -->
<!-- Let's write the CORE CONTENT here, and then I will create the 4 wrapper files. -->
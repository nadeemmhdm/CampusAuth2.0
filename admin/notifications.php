<?php
require '../config.php';
// Logic
if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$class_id = isset($_SESSION['class_id']) ? $_SESSION['class_id'] : 0;

if ($role == 'parent') {
    $child_res = $conn->query("SELECT class_id FROM users WHERE parent_id=$user_id LIMIT 1");
    if ($child_res && $child_res->num_rows > 0) {
        $class_id = $child_res->fetch_row()[0];
    }
}

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
<?php include '../includes/header.php'; ?>
<?php include '../includes/notifications_view.php'; ?>
<?php include '../includes/footer.php'; ?>
<?php
// includes/sidebar.php

$role = $_SESSION['role'];
$base_url = ($role == 'admin') ? '../admin/' : (($role == 'teacher') ? '../teacher/' : (($role == 'student') ? '../student/' : '../parent/'));

// Define Menus for each role
$all_menus = [
    'admin' => [
        ['link' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Dashboard', 'perm' => 'view_dashboard'],
        ['link' => 'announcements.php', 'icon' => 'fa-bullhorn', 'text' => 'Announcements', 'perm' => 'manage_announcements'], // Admin Manage
        ['link' => 'notifications.php', 'icon' => 'fa-bell', 'text' => 'My Notifications', 'perm' => 'view_notifications'],   // Personal View
        ['link' => 'manage_classes.php', 'icon' => 'fa-chalkboard', 'text' => 'Manage Classes', 'perm' => 'manage_classes'],
        ['link' => 'manage_users.php', 'icon' => 'fa-users', 'text' => 'Manage Users', 'perm' => 'manage_users'],
        ['link' => 'attendance_reports.php', 'icon' => 'fa-chart-line', 'text' => 'Reports', 'perm' => 'view_reports'],
        ['link' => 'attendance_requests.php', 'icon' => 'fa-clipboard-check', 'text' => 'Edit Requests', 'perm' => 'approve_attendance'],
        ['link' => 'manage_leaves.php', 'icon' => 'fa-calendar-check', 'text' => 'Leave Requests', 'perm' => 'approve_attendance'], // Admin view
        ['link' => 'manage_medical.php', 'icon' => 'fa-hospital', 'text' => 'Medical Certificates', 'perm' => 'approve_medical'],
        ['link' => 'process_results.php', 'icon' => 'fa-graduation-cap', 'text' => 'End Semester', 'perm' => 'view_eligibility'], // Restricted
        ['link' => 'settings.php', 'icon' => 'fa-cogs', 'text' => 'Settings', 'perm' => 'system_settings'],
    ],
    'teacher' => [
        ['link' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Dashboard'],
        ['link' => 'announcements.php', 'icon' => 'fa-bullhorn', 'text' => 'Class Announcements'], // Teacher Manage
        ['link' => 'notifications.php', 'icon' => 'fa-bell', 'text' => 'Notifications'], // View
        ['link' => 'take_attendance.php', 'icon' => 'fa-check-square', 'text' => 'Take Attendance'],
        ['link' => 'manage_leaves.php', 'icon' => 'fa-calendar-alt', 'text' => 'Leave Requests'],
        ['link' => 'analytics.php', 'icon' => 'fa-chart-pie', 'text' => 'Analytics'],
        ['link' => 'temp_teachers.php', 'icon' => 'fa-user-clock', 'text' => 'Temp Teachers'], // New Access
    ],
    'student' => [
        ['link' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Dashboard'],
        ['link' => 'notifications.php', 'icon' => 'fa-bell', 'text' => 'Notifications'],
        ['link' => 'apply_leave.php', 'icon' => 'fa-calendar-plus', 'text' => 'Apply Leave'],
        ['link' => 'apply_medical.php', 'icon' => 'fa-file-medical', 'text' => 'Medical Cert'],
    ],
    'parent' => [
        ['link' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Dashboard'],
        ['link' => 'notifications.php', 'icon' => 'fa-bell', 'text' => 'Notifications'],
    ],
    'temp_teacher' => [
        // Very limited menu
        ['link' => '../teacher/take_attendance.php?class_id=' . (isset($_SESSION['temp_class_id']) ? $_SESSION['temp_class_id'] : ''), 'icon' => 'fa-check-square', 'text' => 'Take Attendance'],
    ]
];

// Start Menu Logic
$my_menu = [];

if (isset($all_menus[$role])) {
    if ($role == 'admin') {
        // Admin gets the full admin menu
        $my_menu = $all_menus['admin'];
    } elseif ($role == 'sub_admin') {
        // Filter for Sub Admin based on permissions
        foreach ($all_menus['admin'] as $item) { // Sub-admin uses the admin menu but filtered
            $perm = isset($item['perm']) ? $item['perm'] : '';
            if (empty($perm) || hasPermission($perm)) {
                $my_menu[] = $item;
            }
        }
    } else {
        // Other roles get their predefined menu directly
        $my_menu = $all_menus[$role];
    }
}
?>
<div class="sidebar glass" id="sidebar">
    <div class="mb-4 text-center">
        <h3><i class="fas fa-university"></i> CampusAuth</h3>
    </div>
    <nav
        style="display: flex; flex-direction: column; gap: 0.5rem; flex-grow: 1; overflow-y: auto; padding-right: 5px;">
        <?php foreach ($my_menu as $m):
            $active = basename($_SERVER['PHP_SELF']) == $m['link'] ? 'btn-primary' : '';
            $bg = $active ? 'var(--primary-color)' : 'transparent';
            $color = $active ? 'white' : 'var(--text-main)';
            ?>
            <?php
            // Calculate unread if not already matched in header or needed here 
            // Reuse $unread from header if available, else calc
            $show_badge = false;
            // Identifying the Notification link
            if (strpos($m['link'], 'notifications.php') !== false) {
                if (!isset($unread)) {
                    $uid = $_SESSION['user_id'];
                    $cid = isset($_SESSION['class_id']) ? $_SESSION['class_id'] : 0;
                    $unread = getUnreadCount($conn, $uid, $role, $cid);
                }
                if ($unread > 0)
                    $show_badge = true;
            }
            ?>
            <a href="<?php echo $m['link']; ?>" class="btn"
                style="justify-content: flex-start; background: <?php echo $bg; ?>; color: <?php echo $color; ?>; position: relative;">
                <i class="fas <?php echo $m['icon']; ?>"></i>
                <?php echo $m['text']; ?>
                <?php if ($show_badge): ?>
                    <span
                        style="margin-left: auto; background: #ef4444; color: white; width: 8px; height: 8px; border-radius: 50%;"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
        <a href="profile.php" class="btn"
            style="justify-content: flex-start; color: var(--text-main); background: transparent;">
            <i class="fas fa-user-circle"></i> My Profile
        </a>

        <a href="../logout.php" class="btn"
            style="justify-content: flex-start; color: var(--danger-color); background: transparent;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
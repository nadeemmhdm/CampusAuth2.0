<?php
// includes/header.php
// This only contains the layout wrapper start. Sidebar is separate.
// Assuming style.css is linked in parent page head.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusAuth</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<script>
    // Desktop Only Enforcement
    if (window.innerWidth < 1024) {
        window.location.href = '../mobile_restriction.php';
    }
    window.addEventListener('resize', function () {
        if (window.innerWidth < 1024) {
            window.location.href = '../mobile_restriction.php';
        }
    });
</script>

<body>
    <div class="dashboard-layout">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <?php
            // Calculate Unread Count for Header
            $unread = 0;
            if (isset($_SESSION['user_id'])) {
                $role = $_SESSION['role'];
                $uid = $_SESSION['user_id'];
                $cid = isset($_SESSION['class_id']) ? $_SESSION['class_id'] : 0;
                $unread = getUnreadCount($conn, $uid, $role, $cid);
            }
            ?>
            <div class="top-header glass"
                style="margin-bottom: 2rem; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-radius: 1rem;">
                <!-- Breadcrumb or Title -->
                <div style="font-weight: 600; color: var(--text-main); font-size: 1.1rem;">
                    <?php
                    $page = basename($_SERVER['PHP_SELF'], ".php");
                    $title = ucwords(str_replace('_', ' ', $page));
                    if ($title == 'Index')
                        $title = 'Dashboard';
                    echo $title;
                    ?>
                </div>

                <!-- Right Actions -->
                <div style="display: flex; align-items: center; gap: 1.5rem;">

                    <!-- Notification Bell -->
                    <a href="notifications.php"
                        style="position: relative; color: var(--text-main); font-size: 1.2rem; text-decoration: none;">
                        <i class="fas fa-bell <?php echo ($unread > 0) ? 'fa-shake' : ''; ?>"
                            style="animation-duration: 2s; animation-iteration-count: infinite;"></i>
                        <?php if ($unread > 0): ?>
                            <span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; 
                                       font-size: 0.7rem; font-weight: bold; width: 18px; height: 18px; 
                                       display: flex; justify-content: center; align-items: center; 
                                       border-radius: 50%; border: 2px solid white;">
                                <?php echo $unread > 9 ? '9+' : $unread; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Allow Teacher/Admin to Quick Post -->
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')): ?>
                        <a href="announcements.php" class="btn"
                            style="padding: 0.5rem; background: transparent; color: var(--primary-color);"
                            title="Post Announcement">
                            <i class="fas fa-plus-circle fa-lg"></i>
                        </a>
                    <?php endif; ?>

                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="text-align: right; display: none; margin-right: 1rem; width: max-content;">
                            <!-- Optional user info -->
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=random"
                            alt="Profile"
                            style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    </div>
                </div>
            </div>
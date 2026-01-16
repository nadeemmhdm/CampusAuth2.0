<?php
// config.php

$db_host = 'sql305.infinityfree.com';
$db_user = 'if0_40614083';
$db_pass = 'p1gSgzSdNIWFR';
$db_name = 'if0_40614083_collagee';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Helper function for redirection
function redirect($url)
{
    header("Location: $url");
    exit();
}

// Helper function for checking login
function checkLogin()
{
    if (!isset($_SESSION['user_id'])) {
        redirect('../index.php');
    }

    // Check Expiry (for Sub Admin / Temp Teacher)
    if (isset($_SESSION['expires_at']) && !empty($_SESSION['expires_at'])) {
        if (strtotime($_SESSION['expires_at']) < time()) {
            // Expired
            session_destroy();
            redirect('../index.php?error=Account Expired');
        }
    }
}

// Helper: Check Permission (for Sub Admin)
function hasPermission($perm)
{
    if ($_SESSION['role'] === 'admin')
        return true; // Super admin has all
    if ($_SESSION['role'] !== 'sub_admin')
        return false;

    // Check session permissions
    if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        return in_array($perm, $_SESSION['permissions']);
    }
    return false;
}

// Helper: Get IP Address
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

// Helper: Get Simplified Device Info
function get_device_info()
{
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $device = 'Desktop';
    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $ua)) {
        $device = 'Mobile';
    }

    // Browser
    $browser = 'Unknown Browser';
    if (strpos($ua, 'Firefox') !== false)
        $browser = 'Firefox';
    elseif (strpos($ua, 'Chrome') !== false)
        $browser = 'Chrome';
    elseif (strpos($ua, 'Safari') !== false)
        $browser = 'Safari';
    elseif (strpos($ua, 'Edge') !== false)
        $browser = 'Edge';
    elseif (strpos($ua, 'MSIE') !== false || strpos($ua, 'Trident') !== false)
        $browser = 'Internet Explorer';

    return "$device ($browser)";
}

// Helper: Get Unread Notification Count
function getUnreadCount($conn, $user_id, $role, $class_id = 0)
{
    if (!$conn)
        return 0;

    // Parent logic for class_id
    if ($role == 'parent' && $class_id == 0) {
        $c_res = $conn->query("SELECT class_id FROM users WHERE parent_id=$user_id LIMIT 1");
        if ($c_res && $c_res->num_rows > 0)
            $class_id = $c_res->fetch_row()[0];
    }

    // Query Total Matches
    $sql = "SELECT COUNT(*) as total FROM announcements a 
            WHERE a.is_deleted = 0 
            AND (a.expires_at IS NULL OR a.expires_at > NOW())
            AND (a.target_role = 'all' OR a.target_role = '$role') 
            AND (a.target_class_id IS NULL OR a.target_class_id = $class_id)";

    $res = $conn->query($sql);
    $total = $res ? $res->fetch_assoc()['total'] : 0;

    // Query Read Count
    // We count matching announcements that are ALSO in announcement_reads
    $sql_read = "SELECT COUNT(*) as read_count 
                 FROM announcements a 
                 JOIN announcement_reads r ON a.id = r.announcement_id 
                 WHERE r.user_id = $user_id
                 AND a.is_deleted = 0 
                 AND (a.expires_at IS NULL OR a.expires_at > NOW())
                 AND (a.target_role = 'all' OR a.target_role = '$role') 
                 AND (a.target_class_id IS NULL OR a.target_class_id = $class_id)";

    $res_read = $conn->query($sql_read);
    $read = $res_read ? $res_read->fetch_assoc()['read_count'] : 0;

    return max(0, $total - $read);
}
?>
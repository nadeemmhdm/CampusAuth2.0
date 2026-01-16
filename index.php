<?php
require 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Basic Validation
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Check if blocked
                if (isset($row['status']) && $row['status'] === 'blocked') {
                    $error = "Your account has been blocked. Please contact Admin.";
                } else {
                    // --- SECURITY & LOGGING ---
                    $user_id = $row['id'];
                    $ip = get_client_ip();
                    $device = get_device_info();
                    $now = date('Y-m-d H:i:s');

                    // Update User Profile (First Login / Last Login)
                    // We use COALESCE to keep existing first_login if it exists
                    $upd = "UPDATE users SET 
                            last_login_at = '$now',
                            last_login_ip = '$ip',
                            last_login_device = '$device',
                            first_login_at = COALESCE(first_login_at, '$now'),
                            first_login_ip = COALESCE(first_login_ip, '$ip'),
                            first_login_device = COALESCE(first_login_device, '$device'),
                            failed_login_attempts = 0 
                            WHERE id = $user_id";
                    $conn->query($upd);

                    // Insert Login Log
                    $conn->query("INSERT INTO login_logs (user_id, login_time, ip_address, device_info, status) 
                                  VALUES ($user_id, '$now', '$ip', '$device', 'success')");

                    // Session Variables
                    $_SESSION['user_id'] = $row['id'];
                    $role = $row['role']; // Get role from DB
                    $_SESSION['role'] = $role;
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['class_id'] = $row['class_id'];
                    $_SESSION['login_time'] = $now;

                    // Advanced Roles Data
                    $_SESSION['expires_at'] = $row['expires_at'];
                    if ($role == 'sub_admin') {
                        $_SESSION['permissions'] = json_decode($row['permissions'], true);
                    }
                    if ($role == 'temp_teacher') {
                        $_SESSION['temp_class_id'] = $row['temp_class_id'];
                    }

                    // Role-based redirection
                    switch ($role) {
                        case 'admin':
                        case 'sub_admin':
                            redirect('admin/dashboard.php');
                            break;
                        case 'teacher':
                            redirect('teacher/dashboard.php');
                            break;
                        case 'temp_teacher':
                            // Temp teacher goes strictly to attendance
                            redirect('teacher/take_attendance.php?class_id=' . $row['temp_class_id']);
                            break;
                        case 'student':
                            redirect('student/dashboard.php');
                            break;
                        case 'parent':
                            redirect('parent/dashboard.php');
                            break;
                    }
                }
            } else {
                // Log Failure
                $user_id = $row['id'];
                $ip = get_client_ip();
                $device = get_device_info();
                $conn->query("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = $user_id");
                $conn->query("INSERT INTO login_logs (user_id, ip_address, device_info, status) VALUES ($user_id, '$ip', '$device', 'failed')");

                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #desktop-check {
            display: none;
        }

        /* Mobile Block Style */
        @media only screen and (max-width: 1023px) {
            body>*:not(#mobile-block) {
                display: none !important;
            }

            #mobile-block {
                display: flex !important;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Block Overlay -->
    <div id="mobile-block"
        style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #111827; color: white; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; text-align: center; padding: 2rem;">
        <i class="fas fa-desktop fa-4x mb-4" style="color: var(--primary-color); animation: pulse 2s infinite;"></i>
        <h1 style="color: white; margin-bottom: 1rem;">Please use Desktop</h1>
        <p style="color: #9ca3af; max-width: 300px;">This website is optimized for desktop devices only. Mobile support
            is coming soon.</p>
    </div>

    <!-- Main Login Container -->
    <div class="login-container">
        <div class="login-card glass slide-in">
            <div class="text-center mb-4">
                <div class="icon-circle">
                    <i class="fas fa-university fa-3x" style="color: var(--primary-color);"></i>
                </div>
                <h1 style="margin-top: 1rem;">Campus Login</h1>
                <p style="color: var(--text-muted);">Secure Single Sign-On</p>
            </div>

            <form method="POST" action="" id="loginForm" class="<?php echo $error ? 'shake' : ''; ?>">
                <div class="form-group">
                    <label>Username / Admission No.</label>
                    <div style="position: relative;">
                        <input type="text" name="username" placeholder="Enter ID or Username" required>
                        <i class="fas fa-user"
                            style="position: absolute; right: 1rem; top: 1rem; color: var(--text-muted);"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" placeholder="Enter Password" required>
                        <i class="fas fa-lock"
                            style="position: absolute; right: 1rem; top: 1rem; color: var(--text-muted);"></i>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div style="color: var(--danger-color); margin-bottom: 1rem; font-size: 0.875rem; text-align: center;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-full">
                    Log In <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Double check via JS to redirect
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 1024) {
            window.location.href = 'mobile_restriction.php';
        }
    </script>
</body>

</html>
<?php
// includes/profile_content.php
// This is shared across all roles. 
// Expects: $conn, $_SESSION

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$msg = '';
$err = '';

// 1. Handle Updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update Profile Info
    if (isset($_POST['update_info'])) {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        // Username is typically immutable or requires admin, but requirement says "Username cannot be edited".
        // Name can be edited? Request says "Full Name: Editable".

        if (!empty($full_name)) {
            $conn->query("UPDATE users SET full_name = '$full_name' WHERE id = $user_id");
            $_SESSION['full_name'] = $full_name; // Update session
            $msg = "Profile details updated successfully.";
        } else {
            $err = "Full Name cannot be empty.";
        }
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        // Fetch current hash
        $u = $conn->query("SELECT password FROM users WHERE id = $user_id")->fetch_assoc();

        if (password_verify($current, $u['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 6) { // Basic strength rule
                    $new_hash = password_hash($new, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET password = '$new_hash', password_last_changed_at = NOW() WHERE id = $user_id");

                    // Auto Logout
                    session_destroy();
                    redirect('../index.php?msg=PasswordChanged');
                } else {
                    $err = "New password must be at least 6 characters.";
                }
            } else {
                $err = "New passwords do not match.";
            }
        } else {
            $err = "Current password is incorrect.";
        }
    }
}

// 2. Fetch User Data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// 3. Fetch Login History (Last 5)
$logs = $conn->query("SELECT * FROM login_logs WHERE user_id = $user_id ORDER BY login_time DESC LIMIT 5");

// 4. Role Specific Data
$role_data = [];
if ($role == 'student') {
    // Class Name, Eligibility
    if ($user['class_id']) {
        $c = $conn->query("SELECT class_name FROM classes WHERE id = {$user['class_id']}")->fetch_assoc();
        $role_data['Class'] = $c['class_name'] ?? 'N/A';
    }
    // Eligibility
    $res = $conn->query("SELECT eligibility_status FROM student_results WHERE student_id = $user_id")->fetch_assoc();
    $role_data['Eligibility'] = $res ? ucwords(str_replace('_', ' ', $res['eligibility_status'])) : 'Pending';
} elseif ($role == 'teacher') {
    $cc = $conn->query("SELECT COUNT(*) FROM classes WHERE tutor_id = $user_id")->fetch_row()[0];
    $role_data['Assigned Classes'] = $cc;
} elseif ($role == 'parent') {
    // Linked Students
    $kids = $conn->query("SELECT COUNT(*) FROM users WHERE parent_id = $user_id")->fetch_row()[0];
    $role_data['Linked Children'] = $kids;
}

?>

<style>
    .profile-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .avatar-circle {
        width: 80px;
        height: 80px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .tab-nav {
        display: flex;
        gap: 1rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 1.5rem;
    }

    .tab-btn {
        padding: 0.75rem 1.5rem;
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        font-weight: 500;
        transition: all 0.2s;
        border-bottom: 2px solid transparent;
    }

    .tab-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .info-card {
        background: rgba(255, 255, 255, 0.05);
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
    }

    .info-label {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .info-val {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .timeline-item {
        position: relative;
        padding-left: 2rem;
        padding-bottom: 1.5rem;
        border-left: 2px solid var(--border);
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -9px;
        top: 0;
        width: 16px;
        height: 16px;
        background: var(--surface);
        border: 2px solid var(--primary-color);
        border-radius: 50%;
    }

    .timeline-item:last-child {
        border-left: none;
    }
</style>

<div class="profile-header glass slide-in" style="border-radius: 1rem; padding: 2rem;">
    <div class="avatar-circle">
        <i class="fas fa-user"></i>
    </div>
    <div>
        <h2 style="margin: 0;">
            <?php echo $user['full_name']; ?>
        </h2>
        <p class="text-muted" style="margin: 0;">
            <span class="status-badge" style="background: var(--primary-color); color: white;">
                <?php echo ucfirst($role); ?>
            </span>
            <?php echo $user['username']; ?> | Joined:
            <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?>
        </p>
    </div>
</div>

<?php if ($msg): ?>
    <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>
<?php if ($err): ?>
    <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; margin-bottom: 1rem;">
        <?php echo $err; ?>
    </div>
<?php endif; ?>

<div class="tab-nav slide-in">
    <button class="tab-btn active" onclick="switchTab('basic')"><i class="fas fa-user-edit"></i> Basic Details</button>
    <button class="tab-btn" onclick="switchTab('device')"><i class="fas fa-desktop"></i> Device Info</button>
    <button class="tab-btn" onclick="switchTab('security')"><i class="fas fa-shield-alt"></i> Security</button>
    <button class="tab-btn" onclick="switchTab('history')"><i class="fas fa-history"></i> Login History</button>
</div>

<!-- SECTION 1: BASIC DETAILS -->
<div id="basic" class="tab-content active glass" style="padding: 2rem; border-radius: 1rem;">
    <form method="POST">
        <input type="hidden" name="update_info" value="1">
        <h3 class="mb-4">Profile Information</h3>
        <div class="info-grid">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
            </div>
            <div class="form-group">
                <label>Username / ID</label>
                <input type="text" value="<?php echo $user['username']; ?>" disabled
                    style="background: rgba(0,0,0,0.05);">
                <small class="text-muted">Cannot be changed.</small>
            </div>
            <div class="form-group">
                <label>Email (if set)</label>
                <input type="email" value="<?php echo $user['email']; ?>" disabled>
            </div>
            <div class="form-group">
                <label>Account Status</label>
                <input type="text" value="<?php echo ucfirst($user['status']); ?>" disabled
                    style="color: green; font-weight: bold;">
            </div>
        </div>

        <?php if (!empty($role_data)): ?>
            <h4 class="mt-4 mb-2">Role Specifics</h4>
            <div class="info-grid">
                <?php foreach ($role_data as $k => $v): ?>
                    <div class="info-card">
                        <div class="info-label">
                            <?php echo $k; ?>
                        </div>
                        <div class="info-val">
                            <?php echo $v; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button class="btn btn-primary mt-4">Save Changes</button>
    </form>
</div>

<!-- SECTION 2: DEVICE INFO -->
<div id="device" class="tab-content glass" style="padding: 2rem; border-radius: 1rem;">
    <h3 class="mb-4">Device & Session Information</h3>

    <div style="display: grid; gap: 2rem;">
        <!-- Current -->
        <div class="timeline-item">
            <h4 style="color: var(--primary-color);">Current Session</h4>
            <div class="info-grid mt-2">
                <div class="info-card">
                    <div class="info-label">Device</div>
                    <div class="info-val"><i class="fas fa-laptop"></i>
                        <?php echo get_device_info(); ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">IP Address</div>
                    <div class="info-val">
                        <?php echo get_client_ip(); ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">Login Time</div>
                    <time class="info-val">
                        <?php echo isset($_SESSION['login_time']) ? date('d M Y, h:i A', strtotime($_SESSION['login_time'])) : 'Just now'; ?>
                    </time>
                </div>
            </div>
        </div>

        <!-- Last Login -->
        <div class="timeline-item">
            <h4>Last Previous Login</h4>
            <div class="info-grid mt-2">
                <div class="info-card">
                    <div class="info-label">Date</div>
                    <div class="info-val">
                        <?php echo $user['last_login_at'] ? date('d M Y, h:i A', strtotime($user['last_login_at'])) : 'N/A'; ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">Device</div>
                    <div class="info-val">
                        <?php echo $user['last_login_device'] ?? '-'; ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">IP Address</div>
                    <div class="info-val">
                        <?php echo $user['last_login_ip'] ?? '-'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- First Login -->
        <div class="timeline-item">
            <h4>First Ever Login</h4>
            <div class="info-grid mt-2">
                <div class="info-card">
                    <div class="info-label">Date</div>
                    <div class="info-val">
                        <?php echo $user['first_login_at'] ? date('d M Y, h:i A', strtotime($user['first_login_at'])) : 'N/A'; ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">Device</div>
                    <div class="info-val">
                        <?php echo $user['first_login_device'] ?? '-'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: SECURITY -->
<div id="security" class="tab-content glass" style="padding: 2rem; border-radius: 1rem;">
    <h3 class="mb-4">Security Settings</h3>

    <div class="info-grid mb-4">
        <div class="info-card">
            <div class="info-label">Password Last Changed</div>
            <div class="info-val">
                <?php echo $user['password_last_changed_at'] ? date('d M Y', strtotime($user['password_last_changed_at'])) : 'Never'; ?>
            </div>
        </div>
        <div class="info-card">
            <div class="info-label">Failed Attempts (24h)</div>
            <div class="info-val" style="color: <?php echo $user['failed_login_attempts'] > 0 ? 'red' : 'green'; ?>;">
                <?php echo $user['failed_login_attempts']; ?>
            </div>
        </div>
    </div>

    <form method="POST"
        style="max-width: 500px; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 2rem;">
        <input type="hidden" name="change_password" value="1">
        <h4>Change Password</h4>
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required minlength="6">
            <small class="text-muted">Min 6 characters.</small>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button class="btn btn-danger">Update Password & Logout</button>
    </form>
</div>

<!-- SECTION 4: LOGIN HISTORY -->
<div id="history" class="tab-content glass" style="padding: 2rem; border-radius: 1rem;">
    <h3 class="mb-4">Recent Login Activity</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                <th style="padding: 0.75rem;">Date</th>
                <th style="padding: 0.75rem;">Status</th>
                <th style="padding: 0.75rem;">IP Address</th>
                <th style="padding: 0.75rem;">Device</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($l = $logs->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <td style="padding: 0.75rem;">
                        <?php echo date('d M Y, H:i', strtotime($l['login_time'] ?? 'now')); ?>
                    </td>
                    <td style="padding: 0.75rem;">
                        <?php if ($l['status'] == 'success'): ?>
                            <span style="color: green;"><i class="fas fa-check-circle"></i> Success</span>
                        <?php else: ?>
                            <span style="color: red;"><i class="fas fa-times-circle"></i> Failed</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem; font-family: monospace;">
                        <?php echo $l['ip_address']; ?>
                    </td>
                    <td style="padding: 0.75rem; font-size: 0.9rem; color: var(--text-muted);">
                        <?php echo $l['device_info']; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    function switchTab(tabId) {
        // Hide all
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

        // Show target
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>
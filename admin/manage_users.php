<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

$message = '';

// Handle Actions (Block/Unblock/Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $id = intval($_POST['user_id']);

        if ($_POST['action'] == 'delete_user' && $id != $_SESSION['user_id']) {
            $conn->query("DELETE FROM users WHERE id=$id");
            $message = "User deleted.";
        }

        if ($_POST['action'] == 'block_user' && $id != $_SESSION['user_id']) {
            $conn->query("UPDATE users SET status='blocked' WHERE id=$id");
            $message = "User blocked.";
        }

        if ($_POST['action'] == 'unblock_user') {
            $conn->query("UPDATE users SET status='active' WHERE id=$id");
            $message = "User unlocked.";
        }

        // Add Parent Logic
        if ($_POST['action'] == 'add_parent') {
            $name = $_POST['full_name'];
            $email = $_POST['username']; // Email as username for parent
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $adm_no = $_POST['student_adm']; // Admission No to link

            // Find student
            $stu_res = $conn->query("SELECT id FROM users WHERE username='$adm_no' AND role='student'");
            if ($stu_res->num_rows > 0) {
                $stu_row = $stu_res->fetch_assoc();
                $stu_id = $stu_row['id'];

                // Create Parent
                $sql = "INSERT INTO users (username, password, role, full_name, status) 
                        VALUES ('$email', '$password', 'parent', '$name', 'active')";
                if ($conn->query($sql)) {
                    $parent_id = $conn->insert_id;
                    // Link Student to Parent
                    $conn->query("UPDATE users SET parent_id = $parent_id WHERE id = $stu_id");
                    $message = "Parent account created and linked to Student ($adm_no).";
                } else {
                    $message = "Error creating parent: " . $conn->error;
                }
            } else {
                $message = "Error: Student with Admission Number '$adm_no' not found.";
            }
        }
        if ($_POST['action'] == 'add_sub_admin') {
            $name = $_POST['full_name'];
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $expires = $_POST['expires_at'];
            $perms = json_encode(isset($_POST['perms']) ? $_POST['perms'] : []);

            $sql = "INSERT INTO users (username, password, role, full_name, status, expires_at, permissions) 
                    VALUES ('$username', '$password', 'sub_admin', '$name', 'active', '$expires', '$perms')";
            if ($conn->query($sql)) {
                $message = "Sub Admin created successfully.";
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }
}

// Reuse existing Add User logic for others (Student/Teacher) ...
if (isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $role = $_POST['role'];
    $name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $class_id = isset($_POST['class_id']) && $_POST['class_id'] !== '' ? $_POST['class_id'] : "NULL";

    $conn->query("INSERT INTO users (username, password, role, full_name, class_id) VALUES ('$username', '$password', '$role', '$name', $class_id)");
    $message = "User added.";
}

$users = $conn->query("SELECT u.*, c.class_name FROM users u LEFT JOIN classes c ON u.class_id = c.id ORDER BY u.created_at DESC LIMIT 50");
$classes = $conn->query("SELECT * FROM classes");
?>
<!-- Use Layout -->
<?php include '../includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2>Manage Users (Strict)</h2>
    <button class="btn btn-primary" onclick="openPanel()">
        <i class="fas fa-user-plus"></i> Add User
    </button>
</div>

<?php if ($message): ?>
    <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="glass" style="border-radius: 1rem; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: rgba(99, 102, 241, 0.1);">
            <tr>
                <th style="padding: 1rem; text-align: left;">Name</th>
                <th style="padding: 1rem; text-align: left;">Role</th>
                <th style="padding: 1rem; text-align: center;">Status</th>
                <th style="padding: 1rem; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $users->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;">
                        <div><?php echo $row['full_name']; ?></div>
                        <div class="text-muted" style="font-size: 0.8rem;"><?php echo $row['username']; ?></div>
                        <?php if ($row['role'] == 'sub_admin' && $row['expires_at']): ?>
                            <small style="color: purple;"><i class="fas fa-clock"></i> Exp:
                                <?php echo date('d M, H:i', strtotime($row['expires_at'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem;"><?php echo ucfirst(str_replace('_', ' ', $row['role'])); ?></td>
                    <td style="padding: 1rem; text-align: center;">
                        <?php if (isset($row['status']) && $row['status'] == 'blocked'): ?>
                            <span style="background: #fee2e2; color: red; padding: 2px 6px; border-radius: 4px;">Blocked</span>
                        <?php elseif ($row['expires_at'] && strtotime($row['expires_at']) < time()): ?>
                            <span
                                style="background: #f3f4f6; color: #6b7280; padding: 2px 6px; border-radius: 4px;">Expired</span>
                        <?php else: ?>
                            <span style="background: #d1fae5; color: green; padding: 2px 6px; border-radius: 4px;">Active</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <?php if ($row['role'] !== 'admin'): ?>
                            <div style="display: inline-flex; gap: 0.5rem;">
                                <!-- Block/Unblock -->
                                <?php if (isset($row['status']) && $row['status'] == 'blocked'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="unblock_user">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button class="btn" style="padding: 0.25rem 0.5rem; background: #10b981; color: white;"
                                            title="Unblock"><i class="fas fa-unlock"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="block_user">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button class="btn" style="padding: 0.25rem 0.5rem; background: #ef4444; color: white;"
                                            title="Block"><i class="fas fa-ban"></i></button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete -->
                                <form method="POST" onsubmit="return confirm('Delete user?')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button class="btn" style="padding: 0.25rem 0.5rem; color: var(--danger-color);"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Slide Panel -->
<div class="overlay" id="overlay" onclick="closePanel()"></div>
<div class="slide-panel" id="userPanel">
    <h3 class="mb-4">Add User</h3>

    <!-- Role Tabs -->
    <div class="role-selector" style="margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 5px;">
        <div class="role-btn active" onclick="switchForm('student')">Student</div>
        <div class="role-btn" onclick="switchForm('teacher')">Teacher</div>
        <div class="role-btn" onclick="switchForm('parent')">Parent</div>
        <div class="role-btn" onclick="switchForm('sub_admin')"
            style="background: #eef2ff; color: var(--primary-color);">Sub Admin</div>
    </div>

    <!-- Student/Teacher Form -->
    <form method="POST" id="stdForm">
        <input type="hidden" name="action" value="add_user">
        <input type="hidden" name="role" id="formRole" value="student">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label id="uLabel">Admission No</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group" id="classDiv">
            <label>Class</label>
            <select name="class_id">
                <option value="">Select Class</option>
                <?php
                $classes->data_seek(0);
                while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button class="btn btn-primary w-full">Create</button>
    </form>

    <!-- Parent Form -->
    <form method="POST" id="parentForm" class="hidden">
        <input type="hidden" name="action" value="add_parent">
        <div class="form-group">
            <label>Parent Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Email / Mobile (Login)</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label style="color: var(--primary-color);">Link Student (Admission No)*</label>
            <input type="text" name="student_adm" placeholder="Enter Child's Adm No" required>
            <small class="text-muted">System will auto-link.</small>
        </div>
        <button class="btn btn-primary w-full">Create Parent</button>
    </form>

    <!-- Sub Admin Form -->
    <form method="POST" id="subAdminForm" class="hidden">
        <input type="hidden" name="action" value="add_sub_admin">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Expiry Date & Time</label>
            <input type="datetime-local" name="expires_at" required>
        </div>

        <div class="form-group">
            <label>Permissions (Select allowed modules)</label>
            <div style="display: grid; gap: 0.5rem; text-align: left;">
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="view_dashboard"
                        checked> View Dashboard</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="manage_users"> Manage
                    Users</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="manage_classes"> Manage
                    Classes</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="view_reports">
                    Attendance Reports</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="approve_attendance">
                    Attendance Edit Approval</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="approve_medical">
                    Medical Cert Approval</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="view_eligibility"> Exam
                    Eligibility View</label>
                <label style="font-weight: normal;"><input type="checkbox" name="perms[]" value="system_settings">
                    Settings</label>
            </div>
        </div>

        <button class="btn btn-primary w-full">Create Sub Admin</button>
    </form>
</div>

<script>
    function openPanel() { document.getElementById('userPanel').classList.add('active'); document.getElementById('overlay').classList.add('active'); }
    function closePanel() { document.getElementById('userPanel').classList.remove('active'); document.getElementById('overlay').classList.remove('active'); }

    function switchForm(role) {
        document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');

        document.getElementById('stdForm').classList.add('hidden');
        document.getElementById('parentForm').classList.add('hidden');
        document.getElementById('subAdminForm').classList.add('hidden');

        if (role === 'parent') {
            document.getElementById('parentForm').classList.remove('hidden');
        } else if (role === 'sub_admin') {
            document.getElementById('subAdminForm').classList.remove('hidden');
        } else {
            document.getElementById('stdForm').classList.remove('hidden');
            document.getElementById('formRole').value = role;
            if (role === 'student') {
                document.getElementById('uLabel').innerText = 'Admission No';
                document.getElementById('classDiv').classList.remove('hidden');
            } else {
                document.getElementById('uLabel').innerText = 'Username';
            }
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
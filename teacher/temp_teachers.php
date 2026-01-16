<?php
require '../config.php';
checkLogin();

if ($_SESSION['role'] !== 'teacher') {
    redirect('../index.php');
}

$message = '';
$tutor_id = $_SESSION['user_id'];
$class_id = $_SESSION['class_id']; // This might be unreliable if teacher not directly mapped in session, better fetch

// Fetch teacher's assigned classes
$classes = $conn->query("SELECT * FROM classes WHERE tutor_id = $tutor_id");
$my_classes = [];
while ($c = $classes->fetch_assoc())
    $my_classes[] = $c;

// Handle Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_temp_teacher') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $duration_hours = intval($_POST['hours']);
        $target_class_id = intval($_POST['class_id']);

        // Verify class belongs to teacher
        $owned = false;
        foreach ($my_classes as $mc) {
            if ($mc['id'] == $target_class_id)
                $owned = true;
        }

        if (!$owned) {
            $message = "Error: Invalid Class ID.";
        } else {
            $expires = date('Y-m-d H:i:s', strtotime("+$duration_hours hours"));

            $sql = "INSERT INTO users (username, password, role, full_name, status, expires_at, temp_class_id, created_by) 
                    VALUES ('$username', '$password', 'temp_teacher', '$name', 'active', '$expires', $target_class_id, $tutor_id)";

            if ($conn->query($sql)) {
                $message = "Temporary Teacher Created. Expires in $duration_hours hours.";
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }

    if ($_POST['action'] == 'revoke_access') {
        $u_id = intval($_POST['user_id']);
        $conn->query("UPDATE users SET expires_at = NOW(), status='blocked' WHERE id=$u_id AND created_by=$tutor_id");
        $message = "Access Revoked.";
    }
}

// Fetch Active Temp Teachers
$temps = $conn->query("SELECT u.*, c.class_name 
                      FROM users u 
                      JOIN classes c ON u.temp_class_id = c.id
                      WHERE u.role = 'temp_teacher' AND u.created_by = $tutor_id 
                      ORDER BY u.created_at DESC");

?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Manage Temporary Teachers</h2>

<?php if ($message): ?>
    <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 2rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- List -->
    <div>
        <h3 class="mb-2">Active / Recent Temp Teachers</h3>
        <?php if ($temps->num_rows > 0): ?>
            <div class="stats-grid" style="grid-template-columns: 1fr;">
                <?php while ($t = $temps->fetch_assoc()):
                    $expired = strtotime($t['expires_at']) < time();
                    ?>
                    <div class="glass"
                        style="padding: 1rem; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid <?php echo $expired ? 'gray' : '#10b981'; ?>;">
                        <div>
                            <h4 style="margin: 0;">
                                <?php echo $t['full_name']; ?> (
                                <?php echo $t['username']; ?>)
                            </h4>
                            <small class="text-muted">Class:
                                <?php echo $t['class_name']; ?>
                            </small>
                            <br>
                            <?php if (!$expired): ?>
                                <span style="font-size: 0.8rem; color: #10b981;"><i class="fas fa-clock"></i> Expires:
                                    <?php echo date('d M, H:i', strtotime($t['expires_at'])); ?>
                                </span>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: #6b7280;">Expired on
                                    <?php echo date('d M, H:i', strtotime($t['expires_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!$expired && $t['status'] == 'active'): ?>
                            <form method="POST" onsubmit="return confirm('Revoke access immediately?');">
                                <input type="hidden" name="action" value="revoke_access">
                                <input type="hidden" name="user_id" value="<?php echo $t['id']; ?>">
                                <button class="btn" style="background: #fee2e2; color: red; padding: 0.5rem;">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No temporary teachers created.</p>
        <?php endif; ?>
    </div>

    <!-- Create Form -->
    <div class="glass" style="padding: 2rem; border-radius: 1rem; height: fit-content;">
        <h3 class="mb-4">Create Temporary Access</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_temp_teacher">

            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required placeholder="e.g. Substitute Mr. John">
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
                <label>Assign to Class</label>
                <select name="class_id" required>
                    <?php foreach ($my_classes as $mc): ?>
                        <option value="<?php echo $mc['id']; ?>">
                            <?php echo $mc['class_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Duration (Hours)</label>
                <input type="number" name="hours" value="2" min="1" max="24" required>
                <small class="text-muted">Auto-expires after this time.</small>
            </div>

            <button class="btn btn-primary w-full">Grant Access</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
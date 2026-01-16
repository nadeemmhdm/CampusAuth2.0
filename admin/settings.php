<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

$message = '';
$classes = $conn->query("SELECT * FROM classes");

// Process Logic
if (isset($_POST['update_settings'])) {
    $cid = $_POST['class_id'];
    $half = $_POST['half_day_percent'];
    $leave = $_POST['leave_day_percent'];
    $eligibility = $_POST['eligibility_percent'];
    $medical = $_POST['medical_bonus_percent'];
    $fee = $_POST['exam_fee_amount'];

    // Check if settings exist
    $check = $conn->query("SELECT id FROM attendance_settings WHERE class_id = $cid");
    if ($check->num_rows > 0) {
        $sql = "UPDATE attendance_settings SET 
                half_day_percent=$half, 
                leave_day_percent=$leave, 
                eligibility_percent=$eligibility,
                medical_bonus_percent=$medical,
                exam_fee_amount=$fee
                WHERE class_id=$cid";
    } else {
        $sql = "INSERT INTO attendance_settings (class_id, half_day_percent, leave_day_percent, eligibility_percent, medical_bonus_percent, exam_fee_amount)
                VALUES ($cid, $half, $leave, $eligibility, $medical, $fee)";
    }

    if ($conn->query($sql)) {
        $message = "Settings updated successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

if (isset($_POST['change_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $admin_id = $_SESSION['user_id'];
    $sql = "UPDATE users SET password = '$new_password' WHERE id = $admin_id";
    if ($conn->query($sql)) {
        $message = "Admin password updated successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

$selected_class_id = isset($_POST['class_id']) ? $_POST['class_id'] : (isset($_GET['class_id']) ? $_GET['class_id'] : 0);
$current_settings = [];
if ($selected_class_id) {
    $current_settings = $conn->query("SELECT * FROM attendance_settings WHERE class_id = $selected_class_id")->fetch_assoc();
}

?>
<?php include '../includes/header.php'; ?>

    <h2 class="slide-in mb-4">Rule Configuration</h2>

    <?php if ($message): ?>
        <div class="slide-in" style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="glass slide-in" style="padding: 2rem; border-radius: 1rem;">
        <h3 class="mb-4">Global / Class Rules</h3>
        <p class="text-muted mb-4">
            Select a class to configure specific eligibility rules.
        </p>

        <form method="GET">
            <div class="form-group">
                <label>Select Class</label>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class to Edit --</option>
                    <?php 
                    $classes->data_seek(0);
                    while ($c = $classes->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $selected_class_id ? 'selected' : ''; ?>>
                            <?php echo $c['class_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>

        <?php if ($selected_class_id): ?>
            <hr style="margin: 2rem 0; border-color: var(--border);">

            <form method="POST">
                <input type="hidden" name="update_settings" value="1">
                <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">

                <h4 class="mb-2">Attendance Weights</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Half Day Contribution (%)</label>
                        <input type="number" step="0.1" name="half_day_percent"
                            value="<?php echo $current_settings['half_day_percent'] ?? 50; ?>">
                        <small class="text-muted">e.g. 50% means 0.5 day</small>
                    </div>
                    <div class="form-group">
                        <label>Leave Day Contribution (%)</label>
                        <input type="number" step="0.1" name="leave_day_percent"
                            value="<?php echo $current_settings['leave_day_percent'] ?? 0; ?>">
                        <small class="text-muted">e.g. 0% means absent</small>
                    </div>
                </div>

                <h4 class="mb-2">Exam Eligibility Rules</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Minimum Eligibility (%)</label>
                        <input type="number" step="0.1" name="eligibility_percent"
                            value="<?php echo $current_settings['eligibility_percent'] ?? 80; ?>">
                        <small class="text-muted">Below this = Not Eligible / Condonation</small>
                    </div>
                    <div class="form-group">
                        <label>Medical Bonus (%)</label>
                        <input type="number" step="0.1" name="medical_bonus_percent"
                            value="<?php echo $current_settings['medical_bonus_percent'] ?? 5; ?>">
                        <small class="text-muted">Added to Final % if Medical Approved</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Condonation Fee Amount ($)</label>
                    <input type="number" step="1" name="exam_fee_amount"
                        value="<?php echo $current_settings['exam_fee_amount'] ?? 500; ?>">
                </div>

                <button class="btn btn-primary">Save Rules</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Admin Profile Section -->
    <div class="glass slide-in" style="margin-top: 2rem; padding: 2rem; border-radius: 1rem;">
        <h3 class="mb-4">Admin Profile</h3>
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <button class="btn btn-danger">Update Password</button>
        </form>
    </div>

<?php include '../includes/footer.php'; ?>
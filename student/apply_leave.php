<?php
require '../config.php';
checkLogin();

if ($_SESSION['role'] !== 'student') {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];
$class_id = $_SESSION['class_id'];
$message = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_leave'])) {
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $type = $_POST['leave_type'];
    $reason = $conn->real_escape_string($_POST['reason']);

    // Validations
    if (strtotime($start) < strtotime(date('Y-m-d'))) {
        $message = "Error: Cannot apply for past dates.";
    } elseif (strtotime($end) < strtotime($start)) {
        $message = "Error: End date cannot be before start date.";
    } else {
        // Prepare SQL
        $sql = "INSERT INTO leave_requests (student_id, class_id, start_date, end_date, leave_type, reason, status) 
                VALUES ($user_id, $class_id, '$start', '$end', '$type', '$reason', 'pending')";

        if ($conn->query($sql)) {
            $message = "Leave application submitted successfully. Waiting for teacher approval.";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Handle Cancel
if (isset($_GET['cancel_id'])) {
    $lid = intval($_GET['cancel_id']);
    // Ensure ownership and pending status
    $conn->query("UPDATE leave_requests SET status='cancelled' WHERE id=$lid AND student_id=$user_id AND status='pending'");
    redirect('apply_leave.php');
}

// Fetch My Requests
$my_leaves = $conn->query("SELECT * FROM leave_requests WHERE student_id = $user_id ORDER BY created_at DESC");

?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Apply for Leave</h2>

<?php if ($message): ?>
    <div
        style="padding: 1rem; background: <?php echo strpos($message, 'Error') !== false ? '#fee2e2' : '#d1fae5'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991b1b' : '#065f46'; ?>; border-radius: 0.5rem; margin-bottom: 2rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Form -->
    <div class="glass" style="padding: 2rem; border-radius: 1rem; height: fit-content;">
        <h3 class="mb-4">New Application</h3>
        <form method="POST">
            <input type="hidden" name="apply_leave" value="1">

            <div class="form-group">
                <label>Leave Type</label>
                <select name="leave_type" required>
                    <option value="full">Full Day</option>
                    <option value="half">Half Day</option>
                </select>
            </div>

            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>Reason</label>
                <textarea name="reason" rows="3" required placeholder="Reason for leave..."></textarea>
            </div>

            <button class="btn btn-primary w-full">Submit Request</button>
        </form>
    </div>

    <!-- History -->
    <div>
        <h3 class="mb-4">My Leave History</h3>
        <?php if ($my_leaves->num_rows > 0): ?>
            <div class="stats-grid" style="grid-template-columns: 1fr;">
                <?php while ($l = $my_leaves->fetch_assoc()):
                    $st = $l['status'];
                    $color = ($st == 'approved') ? '#10b981' : (($st == 'rejected') ? '#ef4444' : (($st == 'cancelled') ? '#6b7280' : '#f59e0b'));
                    ?>
                    <div class="glass"
                        style="padding: 1.5rem; border-left: 4px solid <?php echo $color; ?>; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: bold; margin-bottom: 0.25rem;">
                                <?php echo date('d M Y', strtotime($l['start_date'])); ?>
                                <?php if ($l['start_date'] != $l['end_date'])
                                    echo " - " . date('d M Y', strtotime($l['end_date'])); ?>
                            </div>
                            <span class="text-muted" style="font-size: 0.9rem;">
                                <?php echo ucfirst($l['leave_type']); ?> Day Leave
                            </span>
                            <div style="margin-top: 0.5rem; font-size: 0.9rem;">"
                                <?php echo $l['reason']; ?>"
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span class="status-badge"
                                style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                <?php echo ucfirst($st); ?>
                            </span>

                            <?php if ($st == 'pending'): ?>
                                <div style="margin-top: 0.5rem;">
                                    <a href="?cancel_id=<?php echo $l['id']; ?>" class="text-muted"
                                        style="font-size: 0.8rem; text-decoration: underline;"
                                        onclick="return confirm('Cancel this request?');">Cancel</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted glass" style="padding: 2rem; border-radius: 1rem;">No leave history found.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
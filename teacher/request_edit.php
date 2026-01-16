<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$date = date('Y-m-d'); // Today only for now, logic restricts to current day generally but can be flexible if admin allows. 

// Get Student Info & Current Status
$student = $conn->query("SELECT full_name FROM users WHERE id=$student_id")->fetch_assoc();
$current = $conn->query("SELECT status FROM attendance WHERE student_id=$student_id AND date='$date'")->fetch_assoc();

if (!$current) {
    die("No attendance found for today to edit.");
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $req_status = $_POST['requested_status'];
    $reason = $_POST['reason'];
    $orig_status = $current['status'];

    $sql = "INSERT INTO attendance_requests (student_id, class_id, date, original_status, requested_status, reason, requested_by, status) 
            VALUES ($student_id, $class_id, '$date', '$orig_status', '$req_status', '$reason', $teacher_id, 'pending')";

    if ($conn->query($sql)) {
        // Redirect back
        header("Location: take_attendance.php?class_id=$class_id&msg=RequestSent");
        exit();
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>
<?php include '../includes/header.php'; ?>

<a href="take_attendance.php?class_id=<?php echo $class_id; ?>" class="btn"
    style="color: var(--text-muted); padding-left:0;">
    <i class="fas fa-arrow-left"></i> Back to Attendance
</a>

<div class="glass" style="max-width: 500px; margin: 2rem auto; padding: 2rem; border-radius: 1rem;">
    <h3>Request Edit:
        <?php echo $student['full_name']; ?>
    </h3>
    <p class="text-muted mb-4">Date:
        <?php echo $date; ?>
    </p>

    <form method="POST">
        <div class="form-group">
            <label>Original Status</label>
            <input type="text" value="<?php echo ucfirst($current['status']); ?>" disabled style="background: #f3f4f6;">
        </div>

        <div class="form-group">
            <label>Requested Status</label>
            <select name="requested_status" required>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="half_day">Half Day</option>
                <option value="leave">On Leave</option>
            </select>
        </div>

        <div class="form-group">
            <label>Reason for Change</label>
            <textarea name="reason" rows="3" required placeholder="Why is this change needed?"></textarea>
        </div>

        <button class="btn btn-primary w-full">Submit Request</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
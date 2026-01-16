<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = $_POST['reason'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // File Upload
    $file_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['document']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = "med_" . time() . "_" . $user_id . "." . $ext;
            $upload_dir = "../uploads/medical/";
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $new_name)) {
                $file_path = "uploads/medical/" . $new_name;
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, PDF allowed.";
        }
    }

    if (!$message) {
        $sql = "INSERT INTO medical_certificates (student_id, reason, start_date, end_date, file_path) 
                VALUES ('$user_id', '$reason', '$start_date', '$end_date', '$file_path')";

        if ($conn->query($sql)) {
            $message = "Application submitted successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Fetch my applications
$my_apps = $conn->query("SELECT * FROM medical_certificates WHERE student_id = $user_id ORDER BY created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Apply for Medical Certificate</h2>

<?php if ($message): ?>
    <div class="slide-in"
        style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 2rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="glass slide-in" style="padding: 2rem; border-radius: 1rem; margin-bottom: 2rem;">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Reason for Absence</label>
            <textarea name="reason" rows="3" required placeholder="Briefly describe the medical reason..."></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" required>
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" required>
            </div>
        </div>

        <div class="form-group">
            <label>Upload Document (Proof)</label>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-muted">Supported: PDF, JPG, PNG</small>
        </div>

        <button class="btn btn-primary">Submit Application</button>
    </form>
</div>

<h3 class="mb-4">My Applications history</h3>
<div class="glass slide-in" style="border-radius: 1rem; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(99, 102, 241, 0.1); text-align: left;">
                <th style="padding: 1rem;">Date Applied</th>
                <th style="padding: 1rem;">Reason</th>
                <th style="padding: 1rem;">Period</th>
                <th style="padding: 1rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $my_apps->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td style="padding: 1rem;"><?php echo $row['reason']; ?></td>
                    <td style="padding: 1rem;"><?php echo $row['start_date']; ?> - <?php echo $row['end_date']; ?></td>
                    <td style="padding: 1rem;">
                        <?php
                        $st = $row['status'];
                        $class = $st == 'approved' ? 'status-eligible' : ($st == 'rejected' ? 'status-not_eligible' : 'status-pending');
                        // Map css class names if needed or use inline colors
                        $color = $st == 'approved' ? 'green' : ($st == 'rejected' ? 'red' : 'orange');
                        ?>
                        <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo ucfirst($st); ?></span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
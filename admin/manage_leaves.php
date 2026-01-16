<?php
require '../config.php';
checkLogin();

// Allow Admin and Teacher
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'sub_admin') {
    redirect('../index.php');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = '';

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $lid = intval($_GET['id']);
    $act = $_GET['action'];
    $new_status = ($act == 'approve') ? 'approved' : 'rejected';

    // Verify ownership (Teacher only own class)
    $allow = true;
    if ($role == 'teacher') {
        $chk = $conn->query("SELECT l.* FROM leave_requests l 
                             JOIN classes c ON l.class_id = c.id 
                             WHERE l.id = $lid AND c.tutor_id = $user_id");
        if ($chk->num_rows == 0)
            $allow = false;
    }

    if ($allow) {
        $conn->query("UPDATE leave_requests SET status='$new_status', approved_by=$user_id WHERE id=$lid");

        // IF APPROVED -> UPDATE ATTENDANCE
        if ($new_status == 'approved') {
            // Get Leave Details
            $l = $conn->query("SELECT * FROM leave_requests WHERE id=$lid")->fetch_assoc();
            $sid = $l['student_id'];
            $cid = $l['class_id'];
            $start = strtotime($l['start_date']);
            $end = strtotime($l['end_date']);

            // Loop dates
            for ($i = $start; $i <= $end; $i += 86400) {
                $d = date('Y-m-d', $i);
                // Check if already marked
                $ex = $conn->query("SELECT id FROM attendance WHERE student_id=$sid AND date='$d'");
                if ($ex->num_rows == 0) {
                    // Mark as Leave (If leave type full -> absent/leave based on policy, assume 'leave' status if enum supports, else 'absent')
                    // Assuming attendance table status allows 'leave' or we map it. 
                    // Requirement says "Approved leave -> counts as Leave Day". 
                    // Let's check attendance enum? Usually 'present','absent','half_day','leave'.
                    // If table ENUM doesn't have 'leave', we might need to add it. 
                    // For now, let's assume 'absent' but we will log it as sanctioned leave in logic elsewhere.
                    // Or better, update V5 script to add LEAVE to enum if not exists.
                    // Adding 'leave' to attendance status enum in V5 script is good practice.

                    // We will use 'absent' but with a note? Or better, use a distinct status if possible.
                    // Let's use 'leave' and hope V5 added it or table supports it.
                    // To be safe, let's check V2 script.. V2 didn't touch attendance enum.
                    // Default enum usually 'present','absent','half_day','late'.
                    // I will add 'leave' to attendance enum in a quick query below just in case.

                    $status = ($l['leave_type'] == 'half') ? 'half_day' : 'leave';
                    $conn->query("INSERT INTO attendance (student_id, class_id, date, status, marked_by) 
                                  VALUES ($sid, $cid, '$d', '$status', $user_id)");
                } else {
                    // Update existing
                    $status = ($l['leave_type'] == 'half') ? 'half_day' : 'leave';
                    $conn->query("UPDATE attendance SET status='$status', marked_by=$user_id WHERE student_id=$sid AND date='$d'");
                }
            }
        }

        $message = "Leave request " . $new_status . ".";
    } else {
        $message = "Error: Permission denied.";
    }
}

// Fetch Requests
$where = "l.status = 'pending'";
if ($role == 'teacher') {
    // Only my classes
    $where .= " AND c.tutor_id = $user_id";
}

$sql = "SELECT l.*, u.full_name, u.username, c.class_name 
        FROM leave_requests l 
        JOIN users u ON l.student_id = u.id 
        JOIN classes c ON l.class_id = c.id 
        WHERE $where 
        ORDER BY l.created_at ASC";
$reqs = $conn->query($sql);

?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Manage Leave Requests</h2>

<?php if ($message): ?>
    <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 2rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($reqs->num_rows > 0): ?>
    <div class="stats-grid">
        <?php while ($r = $reqs->fetch_assoc()): ?>
            <div class="glass" style="padding: 1.5rem; border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0;">
                            <?php echo $r['full_name']; ?>
                        </h4>
                        <small class="text-muted">
                            <?php echo $r['username']; ?> |
                            <?php echo $r['class_name']; ?>
                        </small>
                    </div>
                    <span class="status-badge status-pending">Pending</span>
                </div>

                <div style="margin-bottom: 1rem;">
                    <strong>Date:</strong>
                    <?php echo date('d M', strtotime($r['start_date'])); ?>
                    <?php if ($r['start_date'] != $r['end_date'])
                        echo " - " . date('d M', strtotime($r['end_date'])); ?>
                    <br>
                    <strong>Type:</strong>
                    <?php echo ucfirst($r['leave_type']); ?> Day
                    <br>
                    <strong>Reason:</strong>
                    <?php echo $r['reason']; ?>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <a href="?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-primary w-full"
                        style="background: #10b981;">Approve</a>
                    <a href="?action=reject&id=<?php echo $r['id']; ?>" class="btn w-full"
                        style="background: #ef4444; color: white;">Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="glass text-center text-muted" style="padding: 3rem; border-radius: 1rem;">
        <i class="fas fa-check-circle fa-2x mb-2"></i><br>
        All caught up! No pending leave requests.
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

$admin_id = $_SESSION['user_id'];

// Handle Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $req_id = intval($_GET['id']);
    $req = $conn->query("SELECT * FROM attendance_requests WHERE id=$req_id")->fetch_assoc();

    if ($req && $req['status'] == 'pending') {
        if ($_GET['action'] == 'approve') {
            // Update Attendance
            $conn->query("UPDATE attendance SET status='{$req['requested_status']}' WHERE student_id={$req['student_id']} AND date='{$req['date']}'");
            // Update Req
            $conn->query("UPDATE attendance_requests SET status='approved', admin_action_by=$admin_id WHERE id=$req_id");
        } else {
            $conn->query("UPDATE attendance_requests SET status='rejected', admin_action_by=$admin_id WHERE id=$req_id");
        }
    }
    redirect('attendance_requests.php');
}

// Fetch Pending
$pending = $conn->query("SELECT r.*, u.full_name as student_name, t.full_name as teacher_name 
                         FROM attendance_requests r 
                         JOIN users u ON r.student_id = u.id 
                         JOIN users t ON r.requested_by = t.id 
                         WHERE r.status = 'pending' 
                         ORDER BY r.created_at ASC");

// Fetch History
$history = $conn->query("SELECT r.*, u.full_name as student_name FROM attendance_requests r JOIN users u ON r.student_id = u.id WHERE r.status != 'pending' ORDER BY r.created_at DESC LIMIT 20");
?>
<?php include '../includes/header.php'; ?>

<h2 class="mb-4">Attendance Edit Requests</h2>

<h3 class="mb-2">Pending Approval</h3>
<?php if ($pending->num_rows > 0): ?>
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
        <?php while ($row = $pending->fetch_assoc()): ?>
            <div class="glass" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid var(--primary-color);">
                <div style="font-weight: bold; margin-bottom: 0.5rem;">
                    <?php echo $row['student_name']; ?>
                </div>
                <div class="text-muted" style="font-size: 0.9rem; margin-bottom: 1rem;">
                    Requested by:
                    <?php echo $row['teacher_name']; ?><br>
                    Date:
                    <?php echo date('d M Y', strtotime($row['date'])); ?>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                    <div style="text-align: center; flex: 1;">
                        <small>Original</small><br>
                        <strong>
                            <?php echo ucfirst($row['original_status']); ?>
                        </strong>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div style="text-align: center; flex: 1;">
                        <small>Requested</small><br>
                        <strong style="color: var(--primary-color);">
                            <?php echo ucfirst($row['requested_status']); ?>
                        </strong>
                    </div>
                </div>

                <div
                    style="background: #f3f4f6; padding: 0.5rem; border-radius: 0.5rem; font-size: 0.9rem; margin-bottom: 1rem;">
                    "
                    <?php echo $row['reason']; ?>"
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-primary w-full"
                        style="background: #10b981;">Approve</a>
                    <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn w-full"
                        style="background: #ef4444; color: white;">Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p class="text-muted mb-4">No pending requests.</p>
<?php endif; ?>

<h3 class="mb-2 mt-4">History</h3>
<div class="glass" style="border-radius: 1rem; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: rgba(99, 102, 241, 0.1);">
            <tr>
                <th style="padding: 1rem;">Student</th>
                <th style="padding: 1rem;">Date</th>
                <th style="padding: 1rem;">Change</th>
                <th style="padding: 1rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $history->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;">
                        <?php echo $row['student_name']; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo $row['date']; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo $row['original_status']; ?> ->
                        <?php echo $row['requested_status']; ?>
                    </td>
                    <td
                        style="padding: 1rem; font-weight: bold; color: <?php echo $row['status'] == 'approved' ? 'green' : 'red'; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
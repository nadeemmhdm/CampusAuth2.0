<?php
require '../config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function checkTableExists($conn, $table)
{
    if (!$conn->query("SHOW TABLES LIKE '$table'")->num_rows) {
        // Stop execution cleanly rather than 500 error
        echo "<div style='padding:2rem; color:red; font-family:sans-serif;'>
                <h2>Critical Database Error</h2>
                <p>Table <strong>$table</strong> does not exist.</p>
                <p>Please run the database update script: <a href='http://localhost/update_db_v3.php'>Click Here to Update Database</a></p>
              </div>";
        exit;
    }
}

checkTableExists($conn, 'medical_certificates');
checkTableExists($conn, 'users');
checkTableExists($conn, 'classes');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// Fetch pending requests
$pending_sql = "SELECT m.*, u.full_name as student_name, c.class_name 
               FROM medical_certificates m 
               JOIN users u ON m.student_id = u.id 
               JOIN classes c ON u.class_id = c.id 
               WHERE m.status = 'pending' 
               ORDER BY m.created_at ASC";
$pending = $conn->query($pending_sql);
if (!$pending) {
    echo "<div class='glass' style='color:red; padding:1rem;'>DB Error (Pending): " . $conn->error . "</div>";
    $pending = false;
}

// Fetch history
$history_sql = "SELECT m.*, u.full_name as student_name 
               FROM medical_certificates m 
               JOIN users u ON m.student_id = u.id 
               WHERE m.status != 'pending' 
               ORDER BY m.id DESC LIMIT 10";
$history = $conn->query($history_sql);
if (!$history) {
    echo "<div class='glass' style='color:red; padding:1rem;'>DB Error (History): " . $conn->error . "</div>";
    $history = false;
}

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $mid = intval($_GET['id']);
    $action = $_GET['action']; // approve, reject
    $status = ($action == 'approve') ? 'approved' : 'rejected';

    $conn->query("UPDATE medical_certificates SET status='$status', updated_at=NOW() WHERE id=$mid");
    header("Location: manage_medical.php");
    exit();
}
?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Medical Certificates Review</h2>

<h3 class="mb-4">Pending Requests</h3>
<?php if ($pending && $pending->num_rows > 0): ?>
    <div class="stats-grid">
        <?php while ($row = $pending->fetch_assoc()): ?>
            <div class="glass" style="padding: 1.5rem; border-radius: 1rem; border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0;"><?php echo $row['student_name']; ?></h4>
                        <small class="text-muted"><?php echo $row['class_name']; ?></small>
                    </div>
                    <span class="status-badge status-pending">Pending</span>
                </div>

                <p style="margin-bottom: 0.5rem;"><strong>Reason:</strong> <?php echo $row['reason']; ?></p>
                <p style="margin-bottom: 1rem; font-size: 0.9rem;">
                    <strong>Start:</strong> <?php echo $row['start_date']; ?> |
                    <strong>End:</strong> <?php echo $row['end_date']; ?>
                </p>

                <?php if ($row['file_path']): ?>
                    <a href="../<?php echo $row['file_path']; ?>" target="_blank" class="btn"
                        style="background: #e0e7ff; color: var(--primary-color); display: block; text-align: center; margin-bottom: 1rem;">
                        <i class="fas fa-paperclip"></i> View Attachment
                    </a>
                <?php endif; ?>

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
    <div class="glass" style="padding: 2rem; text-align: center; color: var(--text-muted); border-radius: 1rem;">
        <?php echo ($pending === false) ? "Unable to load requests." : "No pending medical certificates."; ?>
    </div>
<?php endif; ?>

<h3 class="mb-4 mt-8">Recent History</h3>
<div class="glass" style="border-radius: 1rem; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(99, 102, 241, 0.1); text-align: left;">
                <th style="padding: 1rem;">Student</th>
                <th style="padding: 1rem;">Reason</th>
                <th style="padding: 1rem;">Dates</th>
                <th style="padding: 1rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($history):
                while ($row = $history->fetch_assoc()):
                    $st = $row['status'];
                    $col = $st == 'approved' ? 'green' : 'red';
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;"><?php echo $row['student_name']; ?></td>
                        <td style="padding: 1rem;"><?php echo $row['reason']; ?></td>
                        <td style="padding: 1rem; font-size: 0.9rem;">
                            <?php echo isset($row['start_date']) ? $row['start_date'] : '-'; ?> to
                            <?php echo isset($row['end_date']) ? $row['end_date'] : '-'; ?>
                        </td>
                        <td style="padding: 1rem; font-weight: bold; color: <?php echo $col; ?>;">
                            <?php echo ucfirst($st); ?>
                        </td>
                    </tr>
                    <?php
                endwhile;
            endif;
            ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
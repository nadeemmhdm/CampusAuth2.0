<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch Assigned Classes
$classes = $conn->query("SELECT * FROM classes WHERE tutor_id = $teacher_id");

// Stats
$assigned_classes_count = $classes->num_rows;
$attendance_marked = $conn->query("SELECT * FROM attendance WHERE date = '$today' AND marked_by = $teacher_id")->num_rows > 0;

?>
<?php include '../includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 class="slide-in">Teacher Dashboard</h2>
    <div class="user-profile">Welcome, <?php echo $_SESSION['full_name']; ?></div>
</div>

<!-- Stats -->
<div class="stats-grid slide-in">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book"></i></div>
        <div>
            <h3><?php echo $assigned_classes_count; ?></h3>
            <p style="color: var(--text-muted);">My Classes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"
            style="<?php echo $attendance_marked ? 'color: var(--secondary-color); bg: rgba(16, 185, 129, 0.1);' : 'color: var(--warning-color);'; ?>">
            <i class="fas <?php echo $attendance_marked ? 'fa-check' : 'fa-clock'; ?>"></i>
        </div>
        <div>
            <h3><?php echo $attendance_marked ? 'Marked' : 'Pending'; ?></h3>
            <p style="color: var(--text-muted);">Today's Attendance</p>
        </div>
    </div>
</div>

<!-- Class Cards -->
<h3 class="mb-4">My Designated Classes</h3>
<div class="stats-grid">
    <?php
    if ($assigned_classes_count > 0):
        $classes->data_seek(0);
        while ($row = $classes->fetch_assoc()):
            ?>
            <div class="glass" style="padding: 1.5rem; border-radius: 1rem;">
                <h4><?php echo $row['class_name']; ?></h4>
                <p class="text-muted mb-4"><?php echo $row['academic_year']; ?></p>
                <a href="take_attendance.php?class_id=<?php echo $row['id']; ?>" class="btn btn-primary w-full">
                    Mark Attendance <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php
        endwhile;
    else:
        ?>
        <p>No classes assigned yet.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
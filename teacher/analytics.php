<?php
require '../config.php';

// Ensure user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['user_id'];
// Get teachers classes
$classes = $conn->query("SELECT * FROM classes WHERE tutor_id = $teacher_id");
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

$students = [];
$low_attendance = [];

if ($class_id) {
    // Verify ownership
    $check = $conn->query("SELECT id FROM classes WHERE id=$class_id AND tutor_id=$teacher_id");
    if ($check->num_rows > 0) {
        $st_res = $conn->query("SELECT * FROM users WHERE class_id=$class_id AND role='student'");
        while ($s = $st_res->fetch_assoc()) {

            // Calculate Current %
            $sid = $s['id'];
            $att_stats = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as p FROM attendance WHERE student_id=$sid")->fetch_assoc();
            $total = $att_stats['total'];
            $perc = $total > 0 ? round(($att_stats['p'] / $total) * 100, 1) : 0;

            $s['current_percent'] = $perc;

            if ($perc < 75) { // Assuming 75 is safe threshold for warning
                $low_attendance[] = $s;
            }
            $students[] = $s;
        }
    }
}

?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Class Analytics</h2>

<form method="GET" class="glass slide-in" style="padding: 1rem; margin-bottom: 2rem;">
    <label>Select Your Class</label>
    <select name="class_id" onchange="this.form.submit()">
        <option value="">-- Select --</option>
        <?php
        $classes->data_seek(0);
        while ($c = $classes->fetch_assoc()): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $class_id ? 'selected' : ''; ?>>
                <?php echo $c['class_name']; ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if ($class_id): ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <h3><?php echo count($students); ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: red;"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <h3><?php echo count($low_attendance); ?></h3>
                <p>At Risk (< 75%)</p>
            </div>
        </div>
    </div>

    <?php if (count($low_attendance) > 0): ?>
        <h3 class="mb-2 mt-4" style="color: var(--danger-color);">At Risk Students</h3>
        <div class="glass" style="border-radius: 1rem; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(239, 68, 68, 0.1);">
                    <tr>
                        <th style="padding: 1rem;">Student Name</th>
                        <th style="padding: 1rem;">Current Attendance</th>
                        <th style="padding: 1rem;">Parent Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_attendance as $st): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><?php echo $st['full_name']; ?></td>
                            <td style="padding: 1rem; font-weight: bold; color: var(--danger-color);">
                                <?php echo $st['current_percent']; ?>%</td>
                            <td style="padding: 1rem;">
                                <!-- Mock Parent Contact fetch -->
                                <?php
                                $pid = $st['parent_id'];
                                if ($pid) {
                                    $p = $conn->query("SELECT email FROM users WHERE id=$pid")->fetch_assoc();
                                    echo $p['email'];
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h3 class="mb-2 mt-4">Full Class List</h3>
    <div class="glass" style="border-radius: 1rem; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(99, 102, 241, 0.1);">
                <tr>
                    <th style="padding: 1rem;">Student Name</th>
                    <th style="padding: 1rem;">Attendance %</th>
                    <th style="padding: 1rem;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $st):
                    $p = $st['current_percent'];
                    $col = $p >= 75 ? 'green' : 'red';
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;"><?php echo $st['full_name']; ?></td>
                        <td style="padding: 1rem; font-weight: bold; color: <?php echo $col; ?>;"><?php echo $p; ?>%</td>
                        <td style="padding: 1rem;">
                            <?php echo $p >= 75 ? 'Safe' : 'At Risk'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
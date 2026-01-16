<?php
require '../config.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

$classes = $conn->query("SELECT * FROM classes");

// Inputs
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$report_data = [];

if ($class_id) {
    if ($report_type == 'monthly' && $month) {
        // Fetch users
        $users = $conn->query("SELECT * FROM users WHERE class_id = $class_id AND role='student' ORDER BY username ASC");

        while ($u = $users->fetch_assoc()) {
            $sid = $u['id'];
            // Calc stats for month
            $start_date = "$month-01";
            $end_date = date("Y-m-t", strtotime($start_date));

            $stats = $conn->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as p,
                SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as a,
                SUM(CASE WHEN status='half_day' THEN 1 ELSE 0 END) as h,
                SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as l
                FROM attendance 
                WHERE student_id=$sid AND date BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();

            // Get Settings for weights
            $settings = $conn->query("SELECT * FROM attendance_settings WHERE class_id = $class_id")->fetch_assoc();
            $half_val = isset($settings['half_day_percent']) ? $settings['half_day_percent'] / 100 : 0.5;
            $leave_val = isset($settings['leave_day_percent']) ? $settings['leave_day_percent'] / 100 : 0;

            $score = ($stats['p'] * 1) + ($stats['h'] * $half_val) + ($stats['l'] * $leave_val);
            $total_days = $stats['total'];
            $perc = $total_days > 0 ? round(($score / $total_days) * 100, 1) : 0;

            $u['stats'] = $stats;
            $u['percent'] = $perc;
            $report_data[] = $u;
        }

    } elseif ($report_type == 'final') {
        // Fetch Final Results from student_results table
        $res = $conn->query("SELECT r.*, u.full_name, u.username 
                             FROM student_results r 
                             JOIN users u ON r.student_id = u.id 
                             WHERE r.class_id = $class_id 
                             ORDER BY u.username ASC");

        while ($row = $res->fetch_assoc()) {
            $report_data[] = $row;
        }
    }
}

?>
<?php include '../includes/header.php'; ?>

<h2 class="slide-in mb-4">Attendance Reports</h2>

<form method="GET" class="glass slide-in" style="padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem;">
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Report Type</label>
            <select name="type" onchange="this.form.submit()">
                <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Breakdown</option>
                <option value="final" <?php echo $report_type == 'final' ? 'selected' : ''; ?>>Final Eligibility List</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label>Class</label>
            <select name="class_id" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php
                $classes->data_seek(0);
                while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $class_id ? 'selected' : ''; ?>>
                        <?php echo $c['class_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <?php if ($report_type == 'monthly'): ?>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Month</label>
                <input type="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
            </div>
        <?php endif; ?>

        <div class="form-group" style="margin-bottom: 0;">
            <button type="button" onclick="window.print()" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
</form>

<?php if ($class_id && count($report_data) > 0): ?>
    <div class="glass slide-in" style="border-radius: 1rem; overflow: hidden;">
        <div style="padding: 1rem; border-bottom: 1px solid var(--border); background: rgba(99, 102, 241, 0.05);">
            <h3 style="margin: 0;">
                <?php echo $report_type == 'monthly' ? "Monthly Report: " . date('F Y', strtotime($month)) : "Final Eligibility List"; ?>
            </h3>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(99, 102, 241, 0.1);">
                <tr>
                    <th style="padding: 1rem;">Student</th>
                    <?php if ($report_type == 'monthly'): ?>
                        <th style="padding: 1rem; text-align: center;">Present</th>
                        <th style="padding: 1rem; text-align: center;">Absent</th>
                        <th style="padding: 1rem; text-align: center;">Half Day</th>
                        <th style="padding: 1rem; text-align: center;">Total %</th>
                    <?php else: ?>
                        <th style="padding: 1rem; text-align: center;">Final Score</th>
                        <th style="padding: 1rem; text-align: center;">Medical Bonus</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;">
                            <strong><?php echo $row['full_name']; ?></strong><br>
                            <small class="text-muted"><?php echo $row['username']; ?></small>
                        </td>

                        <?php if ($report_type == 'monthly'): ?>
                            <td style="padding: 1rem; text-align: center; color: green;"><?php echo $row['stats']['p']; ?></td>
                            <td style="padding: 1rem; text-align: center; color: red;"><?php echo $row['stats']['a']; ?></td>
                            <td style="padding: 1rem; text-align: center; color: orange;"><?php echo $row['stats']['h']; ?></td>
                            <td style="padding: 1rem; text-align: center; font-weight: bold;">
                                <?php echo $row['percent']; ?>%
                            </td>
                        <?php else: ?>
                            <td style="padding: 1rem; text-align: center; font-weight: bold; font-size: 1.1rem;">
                                <?php echo $row['final_percent']; ?>%
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php echo $row['medical_bonus_applied'] ? '<span class="status-badge" style="background:#d1fae5; color:#065f46;">Yes</span>' : '-'; ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                $st = $row['eligibility_status'];
                                $bg = $st == 'eligible' ? '#d1fae5' : ($st == 'not_eligible' ? '#fee2e2' : '#ffedd5');
                                $col = $st == 'eligible' ? '#065f46' : ($st == 'not_eligible' ? '#991b1b' : '#9a3412');
                                ?>
                                <span
                                    style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.9rem;">
                                    <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                                </span>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($class_id): ?>
    <p class="text-muted text-center mt-4">No data found for the selected criteria.</p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
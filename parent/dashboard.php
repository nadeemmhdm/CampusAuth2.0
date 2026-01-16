<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    redirect('../index.php');
}

$parent_id = $_SESSION['user_id'];
$children = $conn->query("SELECT * FROM users WHERE parent_id = $parent_id");
$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;

if ($child_id == 0 && $children->num_rows > 0) {
    $children->data_seek(0);
    $first_child = $children->fetch_assoc();
    $child_id = $first_child['id'];
    $children->data_seek(0);
}

// Logic vars
$percentage = 0;
$final_res = null;
$stats = [];
$settings = null;
$min_eligibility = 80;

if ($child_id) {
    $child_info = $conn->query("SELECT * FROM users WHERE id = $child_id")->fetch_assoc();

    // Check finalized
    $final_res = $conn->query("SELECT * FROM student_results WHERE student_id = $child_id ORDER BY finalized_at DESC LIMIT 1")->fetch_assoc();
    $is_finalized = $final_res ? true : false;

    if ($child_info['class_id']) {
        $settings = $conn->query("SELECT * FROM attendance_settings WHERE class_id = {$child_info['class_id']}")->fetch_assoc();
        $min_eligibility = $settings['eligibility_percent'] ?? 80;
        $half_val = isset($settings['half_day_percent']) ? $settings['half_day_percent'] / 100 : 0.5;
        $leave_val = isset($settings['leave_day_percent']) ? $settings['leave_day_percent'] / 100 : 0.0;

        // Current Calc
        $sql = "SELECT status, COUNT(*) as count FROM attendance WHERE student_id = $child_id GROUP BY status";
        $r = $conn->query($sql);
        $p = 0;
        $a = 0;
        $h = 0;
        $l = 0;
        while ($row = $r->fetch_assoc()) {
            if ($row['status'] == 'present')
                $p = $row['count'];
            if ($row['status'] == 'absent')
                $a = $row['count'];
            if ($row['status'] == 'half_day')
                $h = $row['count'];
            if ($row['status'] == 'leave')
                $l = $row['count'];
        }
        $total = $p + $a + $h + $l;
        $sc = ($p) + ($h * $half_val) + ($l * $leave_val);
        $percentage = $total > 0 ? ($sc / $total) * 100 : 0;
        $percentage = round($percentage, 1);
        $stats = ['present' => $p, 'absent' => $a];
    }
}
?>
<?php include '../includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2>Parent Dashboard</h2>
    <?php if ($children->num_rows > 1): ?>
        <form method="GET">
            <select name="child_id" onchange="this.form.submit()" style="padding: 0.5rem;">
                <?php while ($c = $children->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $child_id ? 'selected' : ''; ?>>
                        <?php echo $c['full_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if ($child_id && isset($child_info)): ?>

    <?php if ($is_finalized):
        $st = $final_res['eligibility_status'];
        $color = ($st == 'eligible') ? 'green' : (($st == 'not_eligible') ? 'red' : 'orange');
        $bg = ($st == 'eligible') ? '#d1fae5' : (($st == 'not_eligible') ? '#fee2e2' : '#ffedd5');
        ?>
        <div
            style="background: <?php echo $bg; ?>; padding: 2rem; border-radius: 1rem; margin-bottom: 2rem; border: 1px solid <?php echo $color; ?>;">
            <h3 style="color: <?php echo $color; ?>; margin-bottom: 0.5rem;">FINAL ELIGIBILITY:
                <?php echo strtoupper(str_replace('_', ' ', $st)); ?>
            </h3>
            <p>Final Score: <strong><?php echo $final_res['final_percent']; ?>%</strong> (Required:
                <?php echo $min_eligibility; ?>%)
            </p>
            <?php if ($final_res['medical_bonus_applied']): ?>
                <p style="font-size: 0.9rem;">* Includes Medical Bonus</p>
            <?php endif; ?>

            <?php if ($st == 'condonation_required'): ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.1);">
                    <strong>Action Required:</strong> Please pay the fine of
                    <span style="font-size: 1.2rem; font-weight: bold;">$<?php echo $settings['exam_fee_amount']; ?></span>
                    to make your child eligible for exams.
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Warning Banner -->
        <?php if ($percentage < $min_eligibility): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                <i class="fas fa-exclamation-circle"></i> Child at risk! Current attendance
                (<?php echo $percentage; ?>%)
                is below eligibility threshold (<?php echo $min_eligibility; ?>%).
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            <div>
                <h3><?php echo $percentage; ?>%</h3>
                <p>Current Attendance</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: red;"><i class="fas fa-times-circle"></i></div>
            <div>
                <h3><?php echo $stats['absent']; ?></h3>
                <p>Days Absent</p>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <h3 class="mb-4 mt-4">Monthly Breakdown</h3>
    <div class="glass" style="border-radius: 1rem; overflow: hidden; margin-bottom: 2rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(99, 102, 241, 0.1);">
                <tr>
                    <th style="padding: 1rem; text-align: left;">Month</th>
                    <th style="padding: 1rem; text-align: center;">Attendance %</th>
                    <th style="padding: 1rem; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Calculate Monthly Stats
                $months_sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month_year, 
                                        COUNT(*) as total,
                                        SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as p,
                                        SUM(CASE WHEN status='half_day' THEN 1 ELSE 0 END) as h,
                                        SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as l
                                        FROM attendance 
                                        WHERE student_id = $child_id 
                                        GROUP BY month_year 
                                        ORDER BY month_year DESC";
                $months_res = $conn->query($months_sql);

                // Use loop
                if ($months_res->num_rows > 0):
                    while ($m = $months_res->fetch_assoc()):
                        $m_score = ($m['p'] * 1) + ($m['h'] * $half_val) + ($m['l'] * $leave_val);
                        $m_perc = ($m_score / $m['total']) * 100;
                        $m_perc = round($m_perc, 1);
                        $m_color = $m_perc >= $min_eligibility ? 'green' : 'red';
                        ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><?php echo date('F Y', strtotime($m['month_year'])); ?></td>
                            <td style="padding: 1rem; text-align: center; font-weight: bold; color: <?php echo $m_color; ?>;">
                                <?php echo $m_perc; ?>%
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <span
                                    style="font-size: 0.8rem; padding: 0.2rem 0.5rem; border-radius: 4px; background: <?php echo $m_perc >= $min_eligibility ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $m_perc >= $min_eligibility ? '#065f46' : '#991b1b'; ?>;">
                                    <?php echo $m_perc >= $min_eligibility ? 'Good' : 'Needs Work'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="3" style="padding: 1rem; text-align: center;">No attendance data yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <p>No student data available.</p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
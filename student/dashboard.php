<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    redirect('../index.php');
}

$student_id = $_SESSION['user_id'];
$class_id = $_SESSION['class_id'];

// Get Final Results
$final_res = $conn->query("SELECT * FROM student_results WHERE student_id = $student_id ORDER BY finalized_at DESC LIMIT 1")->fetch_assoc();
$is_finalized = $final_res ? true : false;

// Get Class Settings
$settings = $conn->query("SELECT * FROM attendance_settings WHERE class_id = $class_id")->fetch_assoc();
$half_val = isset($settings['half_day_percent']) ? $settings['half_day_percent'] / 100 : 0.5;
$leave_val = isset($settings['leave_day_percent']) ? $settings['leave_day_percent'] / 100 : 0.0;
$min_eligibility = isset($settings['eligibility_percent']) ? $settings['eligibility_percent'] : 80;


// Existing Monthly Logic (Keep visual chart)
// Fetch Attendance Stats
$sql = "SELECT status, COUNT(*) as count FROM attendance WHERE student_id = $student_id GROUP BY status";
$result = $conn->query($sql);
$stats = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0];
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}
$total_days = array_sum($stats);
$score = ($stats['present'] * 1) + ($stats['half_day'] * $half_val) + ($stats['leave'] * $leave_val);
$percentage = $total_days > 0 ? ($score / $total_days) * 100 : 0;
$percentage = round($percentage, 1);
?>
<?php include '../includes/header.php'; ?>

<h2 class="mb-4 slide-in">My Attendance & Eligibility</h2>

<!-- Final Status Banner -->
<?php if ($is_finalized):
    $status_class = 'status-' . $final_res['eligibility_status'];
    $status_text = ucwords(str_replace('_', ' ', $final_res['eligibility_status']));
    ?>
    <div class="eligibility-card <?php echo $status_class; ?> slide-in">
        <h3>Exam Eligibility Status</h3>
        <h1 style="font-size: 3rem; margin: 1rem 0;"><?php echo $status_text; ?></h1>

        <div style="font-size: 1.1rem; opacity: 0.9;">
            Final Score: <?php echo round($final_res['final_percent'], 1); ?>%
            <?php if ($final_res['medical_bonus_applied']): ?>
                <span
                    style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 0.5rem; font-size: 0.9rem;">
                    +<?php echo $settings['medical_bonus_percent']; ?>% Med Bonus
                </span>
            <?php endif; ?>
        </div>

        <?php if ($final_res['eligibility_status'] == 'condonation_required'): ?>
            <div style="margin-top: 1rem; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 0.5rem;">
                <strong>Action Required:</strong> Please pay the condonation fee of
                $<?php echo $settings['exam_fee_amount']; ?> to the accounts office.
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Live Estimation -->
    <div class="glass slide-in" style="padding: 1.5rem; margin-bottom: 2rem; border-left: 5px solid var(--primary-color);">
        <h4>Current Status (Not Finalized)</h4>
        <p>
            Your current attendance is <strong><?php echo $percentage; ?>%</strong>.
            Required for eligibility: <strong><?php echo $min_eligibility; ?>%</strong>.
        </p>
        <?php if ($percentage < $min_eligibility): ?>
            <p style="color: var(--warning-color); margin-top: 0.5rem;">
                <i class="fas fa-exclamation-triangle"></i> You are at risk! Submit a <a href="apply_medical.php">Medical
                    Certificate</a> if applicable.
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <div class="glass slide-in" style="padding: 2rem; border-radius: 1rem; text-align: center;">
        <h3 class="mb-4">Live Percentage</h3>
        <div class="circle-chart" style="margin: 0 auto;">
            <div class="circle-content">
                <h2 style="font-size: 2.5rem; color: var(--primary-color);"><?php echo $percentage; ?>%</h2>
            </div>
        </div>
    </div>

    <div class="stats-grid slide-in">
        <div class="stat-card">
            <div class="stat-icon" style="color: green;"><i class="fas fa-check"></i></div>
            <div>
                <h3><?php echo $stats['present']; ?></h3>
                <p>Present</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: red;"><i class="fas fa-times"></i></div>
            <div>
                <h3><?php echo $stats['absent']; ?></h3>
                <p>Absent</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: orange;"><i class="fas fa-star-half"></i></div>
            <div>
                <h3><?php echo $stats['half_day']; ?></h3>
                <p>Half Day</p>
            </div>
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
                                       WHERE student_id = $student_id
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
                                <?php echo $m_perc >= $min_eligibility ? 'On Track' : 'Low'; ?>
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

<h3 class="mb-4">Recent Attendance History</h3>
<div class="glass" style="border-radius: 1rem; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: rgba(99, 102, 241, 0.1);">
            <tr>
                <th style="padding: 1rem; text-align: left;">Date</th>
                <th style="padding: 1rem; text-align: left;">Status</th>
                <th style="padding: 1rem; text-align: left;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php // Assuming history from page top still relevant but not fetched in this snippet if moved, need to ensure logic is there.
            // Re-fetch for safety or assume strict include
            $history = $conn->query("SELECT * FROM attendance WHERE student_id = $student_id ORDER BY date DESC LIMIT 10");
            while ($row = $history->fetch_assoc()):
                $badge_class = 'status-' . ($row['status'] == 'half_day' ? 'half' : $row['status']);
                $status_text = ucwords(str_replace('_', ' ', $row['status']));
                ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem;"><?php echo date('d M, Y', strtotime($row['date'])); ?></td>
                    <td style="padding: 1rem;"><span
                            class="status-badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                    <td style="padding: 1rem; color: var(--text-muted);">
                        <?php echo $row['remarks'] ? $row['remarks'] : '-'; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
<?php
require '../config.php';

// ONLY ADMIN CAN ACCESS THIS PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// ------------------------------------------
// 1. FETCH CURRENT SEMESTER STATE
// ------------------------------------------
$current_status = '';
$current_year = '';
$current_sem = '';

$res = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('semester_status', 'current_academic_year', 'current_semester')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['setting_key'] == 'semester_status')
            $current_status = $row['setting_value'];
        if ($row['setting_key'] == 'current_academic_year')
            $current_year = $row['setting_value'];
        if ($row['setting_key'] == 'current_semester')
            $current_sem = $row['setting_value'];
    }
}

// Default states if missing
if (empty($current_status))
    $current_status = 'active';

// ------------------------------------------
// 2. CHECK PENDING REQUESTS (BLOCKER)
// ------------------------------------------
$errors = [];
$blockers = 0;

if ($current_status == 'active') {
    // Check pending leaves
    $l_res = $conn->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'");
    $pending_leaves = $l_res ? $l_res->fetch_row()[0] : 0;

    // Check pending attendance edits
    $a_res = $conn->query("SELECT COUNT(*) FROM attendance_requests WHERE status='pending'");
    $pending_attendance_edits = $a_res ? $a_res->fetch_row()[0] : 0;

    // Check pending medical
    $m_res = $conn->query("SELECT COUNT(*) FROM medical_certificates WHERE status='pending'");
    $pending_medical = $m_res ? $m_res->fetch_row()[0] : 0;

    if ($pending_leaves > 0) {
        $errors[] = "There are $pending_leaves pending LEAVE requests. Please process them.";
        $blockers++;
    }
    if ($pending_attendance_edits > 0) {
        $errors[] = "There are $pending_attendance_edits pending ATTENDANCE EDIT requests. Please processing them.";
        $blockers++;
    }
    if ($pending_medical > 0) {
        $errors[] = "There are $pending_medical pending MEDICAL CERTIFICATES. Please review them.";
        $blockers++;
    }
}

// ------------------------------------------
// 3. ACTION HANDLERS
// ------------------------------------------
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    // === A. END SEMESTER ===
    if ($_POST['action'] == 'end_semester' && $current_status == 'active') {
        if ($blockers > 0) {
            $success_msg = "<span style='color:red'>Cannot end semester. Resolve pending requests first.</span>";
        } else {
            // 1. Calculate Finals & Archive
            // Logic: For every student, calculate standard attendance %, apply +5% if medical approved, store in archive.

            // Get all students
            $students = $conn->query("SELECT u.id, u.full_name, u.class_id, c.class_name, 
                                     ts.min_attendance_percent
                                     FROM users u 
                                     JOIN classes c ON u.class_id = c.id
                                     LEFT JOIN attendance_settings ts ON c.id = ts.class_id 
                                     WHERE u.role='student'");

            $archive_count = 0;

            while ($st = $students->fetch_assoc()) {
                $sid = $st['id'];
                $min_req = $st['min_attendance_percent'] ? $st['min_attendance_percent'] : 75;

                // Calc stats
                $stats = $conn->query("
                    SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 
                                 WHEN status = 'late' THEN 1 
                                 WHEN status = 'half_day' THEN 0.5 
                                 ELSE 0 END) as present_days
                    FROM attendance 
                    WHERE student_id = $sid
                ")->fetch_assoc();

                $total = $stats['total_days'];
                $present = $stats['present_days'];
                $percentage = ($total > 0) ? round(($present / $total) * 100, 2) : 0;

                // Medical Bonus? (Check if they have ANY approved medical cert in this period)
                // ideally we check date range, but for now simplistic: any approved cert
                $has_med = $conn->query("SELECT id FROM medical_certificates WHERE student_id=$sid AND status='approved' LIMIT 1")->num_rows > 0;

                $final_percent = $percentage;
                $bonus_applied = 0;
                if ($has_med) {
                    $final_percent = min(100, $percentage + 5);
                    $bonus_applied = 1;
                }

                $is_eligible = ($final_percent >= $min_req) ? 'Eligible' : 'Not Eligible';

                // ARCHIVE
                $stmt = $conn->prepare("INSERT INTO archived_semester_results (sem_name, academic_year, student_id, class_name, final_attendance, medical_bonus_applied, eligibility_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisdis", $current_sem, $current_year, $sid, $st['class_name'], $final_percent, $bonus_applied, $is_eligible);
                $stmt->execute();
                $archive_count++;
            }

            // 2. Set Status to ENDED
            $conn->query("UPDATE system_settings SET setting_value='ended' WHERE setting_key='semester_status'");

            $success_msg = "Semester Ended Successfully. $archive_count student records archived. System is now locked pending new semester start.";
            $current_status = 'ended';
        }
    }

    // === B. START NEW SEMESTER ===
    if ($_POST['action'] == 'start_new_semester' && $current_status == 'ended') {
        $confirm = $_POST['confirmation'];
        $pwd = $_POST['admin_pass'];
        $new_year = $_POST['new_year'];
        $new_sem = $_POST['new_sem'];

        // Verify Password
        $admin_id = $_SESSION['user_id'];
        $res = $conn->query("SELECT password FROM users WHERE id=$admin_id");
        $row = $res->fetch_assoc();

        if ($confirm === 'CONFIRM' && password_verify($pwd, $row['password'])) {
            // 1. TRUNCATE / CLEANUP ACTIVE DATA
            // Be very careful here. Order matters due to FKs.

            // Clear Attendance
            $conn->query("DELETE FROM attendance"); // or TRUNCATE if no constraints
            // Clear Leaves
            $conn->query("DELETE FROM leave_requests");
            // Clear Medical
            $conn->query("DELETE FROM medical_certificates");
            // Clear Edit Requests
            $conn->query("DELETE FROM attendance_requests");

            // 2. Update System Settings
            $conn->query("UPDATE system_settings SET setting_value='$new_year' WHERE setting_key='current_academic_year'");
            $conn->query("UPDATE system_settings SET setting_value='$new_sem' WHERE setting_key='current_semester'");
            $conn->query("UPDATE system_settings SET setting_value='active' WHERE setting_key='semester_status'");

            // 3. Log
            // (Optional audit log insert)

            $success_msg = "New Semester ($new_sem, $new_year) Started Successfully! All operational data reset.";
            $current_status = 'active';
            $current_year = $new_year;
            $current_sem = $new_sem;

        } else {
            $success_msg = "<span style='color:red;'>Creation Failed: Incorrect Password or Confirmation Code.</span>";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div style="max-width: 900px; margin: 0 auto;">
    <h2 class="slide-in mb-4">Semester Transition Manager</h2>

    <!-- Current Status Card -->
    <div class="glass slide-in"
        style="padding: 2rem; border-radius: 1rem; margin-bottom: 2rem; border-left: 5px solid var(--primary-color);">
        <h4 style="color: var(--text-muted); margin-bottom: 0.5rem;">Current Academic State</h4>
        <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
            <div>
                <small>Academic Year</small>
                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $current_year; ?></div>
            </div>
            <div>
                <small>Semester / Term</small>
                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $current_sem; ?></div>
            </div>
            <div>
                <small>Status</small>
                <div>
                    <?php if ($current_status == 'active'): ?>
                        <span class="status-badge status-eligible" style="font-size: 1rem; padding: 0.5rem 1rem;">ACTIVE -
                            RUNNING</span>
                    <?php else: ?>
                        <span class="status-badge status-not_eligible" style="font-size: 1rem; padding: 0.5rem 1rem;">ENDED
                            - LOCKED</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success_msg): ?>
        <div class="glass slide-in"
            style="padding: 1rem; background: #ecfdf5; border: 1px solid #10b981; color: #064e3b; border-radius: 0.5rem; margin-bottom: 2rem;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>


    <!-- SECTION A: END SEMESTER -->
    <?php if ($current_status == 'active'): ?>
        <div class="glass slide-in" style="padding: 2rem; border-radius: 1rem;">
            <h3>End Current Semester</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                This action will calculate final eligibility for all students, archive the results, and
                <strong>LOCK</strong> the system.
                No further attendance or edits will be possible until a new semester is started.
            </p>

            <!-- Pre-Check List -->
            <div style="background: rgba(0,0,0,0.03); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                <h5 style="margin-bottom: 0.5rem;">System Pre-Checks:</h5>
                <ul style="list-style: none;">
                    <li style="color: <?php echo ($blockers == 0) ? 'green' : 'red'; ?>; margin-bottom: 0.25rem;">
                        <i class="fas <?php echo ($blockers == 0) ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        Checking Pending Requests...
                        <?php if ($blockers > 0)
                            echo "<strong>FAILED ($blockers pending items)</strong>";
                        else
                            echo "<strong>PASSED</strong>"; ?>
                    </li>
                </ul>
                <?php if (!empty($errors)): ?>
                    <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #ef4444;">
                        <?php foreach ($errors as $err)
                            echo "<div>• $err</div>"; ?>
                    </div>
                    <a href="dashboard.php" class="btn" style="margin-top: 0.5rem; font-size: 0.8rem; background: #fff;">Go to
                        Dashboard to Resolve</a>
                <?php endif; ?>
            </div>

            <form method="POST"
                onsubmit="return confirm('Are you strictly sure? This will LOCK the system and calculate final results.');">
                <input type="hidden" name="action" value="end_semester">
                <?php if ($blockers > 0): ?>
                    <button type="button" class="btn" style="background: var(--text-muted); cursor: not-allowed;" disabled>End
                        Semester (Fix Errors First)</button>
                <?php else: ?>
                    <button class="btn btn-danger w-full" style="padding: 1rem;">
                        <i class="fas fa-lock"></i> Finalize & End Semester
                    </button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>


    <!-- SECTION B: START NEW SEMESTER -->
    <?php if ($current_status == 'ended'): ?>
        <div class="glass slide-in" style="padding: 2rem; border-radius: 1rem; border: 1px solid var(--primary-color);">
            <h3 style="color: var(--primary-color);">🚀 Start New Semester</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                This process will <strong>DELETE</strong> all old daily attendance records, leave requests, and medical
                certificates to make way for fresh data.
                <strong>Ensure you have exported any necessary reports before proceeding.</strong>
            </p>

            <form method="POST" style="display: grid; gap: 1rem;">
                <input type="hidden" name="action" value="start_new_semester">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>New Academic Year</label>
                        <input type="text" name="new_year" value="<?php echo $current_year; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>New Semester Name</label>
                        <input type="text" name="new_sem" placeholder="e.g. Sem 2" required>
                    </div>
                </div>

                <div style="background: #fee2e2; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fca5a5;">
                    <h5 style="color: #991b1b; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Danger
                        Zone</h5>
                    <div class="form-group">
                        <label style="color: #7f1d1d;">Type 'CONFIRM' to proceed</label>
                        <input type="text" name="confirmation" placeholder="CONFIRM" required
                            style="border-color: #fca5a5;">
                    </div>

                    <div class="form-group">
                        <label style="color: #7f1d1d;">Admin Password</label>
                        <input type="password" name="admin_pass" required style="border-color: #fca5a5;">
                    </div>
                </div>

                <button class="btn btn-primary w-full" style="padding: 1rem; font-size: 1.1rem;">
                    Start New Semester
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
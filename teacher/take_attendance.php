<?php
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}
// Allow teacher OR temp_teacher
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'temp_teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$date = date('Y-m-d'); // STRICT: ONLY TODAY
$message = '';

// Get assigned classes
if ($role == 'temp_teacher') {
    $temp_cid = $_SESSION['temp_class_id'];
    $classes = $conn->query("SELECT * FROM classes WHERE id = $temp_cid");
} else {
    $classes = $conn->query("SELECT * FROM classes WHERE tutor_id = $teacher_id");
}

// Selected Class
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Auto-select for temp teacher
if ($role == 'temp_teacher' && $class_id != $_SESSION['temp_class_id']) {
    $class_id = $_SESSION['temp_class_id'];
}

// Verify class ownership
if ($class_id) {
    if ($role == 'teacher') {
        $check = $conn->query("SELECT id FROM classes WHERE id=$class_id AND tutor_id=$teacher_id");
        if ($check->num_rows == 0)
            $class_id = 0;
    } elseif ($role == 'temp_teacher') {
        if ($class_id != $_SESSION['temp_class_id'])
            $class_id = 0;
    }
}

// Check if attendance already marked for today
$is_marked = false;
if ($class_id) {
    $check_att = $conn->query("SELECT id FROM attendance WHERE class_id=$class_id AND date='$date' LIMIT 1");
    if ($check_att->num_rows > 0) {
        $is_marked = true;
    }
}

// Handle Mark Attendance (Insert Only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_attendance']) && !$is_marked) {
    $students = $_POST['student_id'];
    $statuses = $_POST['status'];

    foreach ($students as $id) {
        $status = $statuses[$id]; // present, absent, half_day
        $conn->query("INSERT INTO attendance (student_id, class_id, date, status, marked_by) 
                      VALUES ($id, $class_id, '$date', '$status', $teacher_id)");
    }
    $message = "Attendance marked successfully!";
    $is_marked = true; // Switch view immediately
}

// Fetch Students
$students = [];
if ($class_id) {
    $res = $conn->query("SELECT * FROM users WHERE class_id=$class_id AND role='student' ORDER BY username ASC");
    while ($row = $res->fetch_assoc()) {
        // If marked, get status
        if ($is_marked) {
            $att = $conn->query("SELECT status FROM attendance WHERE student_id={$row['id']} AND date='$date'")->fetch_assoc();
            $row['status'] = $att['status'];
        } else {
            $row['status'] = ''; // default empty
        }
        $students[] = $row;
    }
}
?>
<?php include '../includes/header.php'; ?>

<h2 class="mb-4">Mark Attendance (<?php echo date('d M Y'); ?>)</h2>

<?php if ($message): ?>
    <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Class Selector -->
<form method="GET" class="glass" style="padding: 1rem; margin-bottom: 2rem;">
    <label>Select Class</label>
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

<?php if ($class_id && count($students) > 0): ?>

    <?php if ($is_marked): ?>
        <div style="background: #fff7ed; border: 1px solid #fdba74; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <i class="fas fa-lock"></i> <strong>Attendance Marked.</strong> Editing is restricted. Click "Request Edit" to
            change a specific student's status.
        </div>

        <div class="glass" style="border-radius: 1rem; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(99, 102, 241, 0.1);">
                    <tr>
                        <th style="padding: 1rem;">Student</th>
                        <th style="padding: 1rem;">Status</th>
                        <th style="padding: 1rem; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s):
                        $st = $s['status'];
                        $color = ($st == 'present') ? 'green' : (($st == 'absent') ? 'red' : 'orange');
                        ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><?php echo $s['full_name']; ?></td>
                            <td style="padding: 1rem; font-weight: bold; color: <?php echo $color; ?>;">
                                <?php echo ucfirst(str_replace('_', ' ', $st)); ?>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <?php if ($role !== 'temp_teacher'): ?>
                                    <a href="request_edit.php?student_id=<?php echo $s['id']; ?>&class_id=<?php echo $class_id; ?>"
                                        class="btn"
                                        style="padding: 0.25rem 0.5rem; background: var(--warning-color); color: white; font-size: 0.8rem;">
                                        Request Edit
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- MARKING MODE -->
        <form method="POST">
            <style>
                /* Radio Button Logic */
                .status-item input:checked+span {
                    font-weight: bold;
                }

                .status-item input:checked+span.p-span {
                    background: #d1fae5;
                    color: #065f46;
                    box-shadow: 0 0 0 2px #10b981;
                }

                .status-item input:checked+span.a-span {
                    background: #fee2e2;
                    color: #991b1b;
                    box-shadow: 0 0 0 2px #ef4444;
                }

                .status-item input:checked+span.h-span {
                    background: #ffedd5;
                    color: #9a3412;
                    box-shadow: 0 0 0 2px #f97316;
                }

                .status-badge {
                    padding: 0.5rem 1rem;
                    border-radius: 2rem;
                    border: 1px solid #ddd;
                    cursor: pointer;
                    transition: all 0.2s;
                }
            </style>

            <div class="glass" style="border-radius: 1rem; overflow: hidden;">
                <?php foreach ($students as $s): ?>
                    <div
                        style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <strong><?php echo $s['full_name']; ?></strong>
                            <div class="text-muted" style="font-size: 0.8rem;"><?php echo $s['username']; ?></div>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <input type="hidden" name="student_id[]" value="<?php echo $s['id']; ?>">

                            <label class="status-item">
                                <input type="radio" name="status[<?php echo $s['id']; ?>]" value="present" required
                                    style="display:none;">
                                <span class="status-badge p-span">Present</span>
                            </label>

                            <label class="status-item">
                                <input type="radio" name="status[<?php echo $s['id']; ?>]" value="absent" style="display:none;">
                                <span class="status-badge a-span">Absent</span>
                            </label>

                            <label class="status-item">
                                <input type="radio" name="status[<?php echo $s['id']; ?>]" value="half_day" style="display:none;">
                                <span class="status-badge h-span">Half Day</span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 2rem; text-align: right;">
                <button class="btn btn-primary" style="padding: 1rem 3rem;">Save Attendance</button>
            </div>
        </form>
    <?php endif; ?>

<?php elseif ($class_id): ?>
    <p>No students found.</p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
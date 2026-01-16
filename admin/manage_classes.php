<?php
require '../config.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

$message = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_class') {
            $name = $_POST['class_name'];
            $year = $_POST['academic_year'];
            $tutor = $_POST['tutor_id'];

            // Validation
            if (!empty($name) && !empty($year)) {
                $sql = "INSERT INTO classes (class_name, academic_year, tutor_id) VALUES ('$name', '$year', '$tutor')";
                if ($conn->query($sql)) {
                    $class_id = $conn->insert_id;
                    // Add Settings
                    $half = $_POST['half_day'];
                    $leave = $_POST['leave_day'];
                    $min = $_POST['min_attendance'];
                    $conn->query("INSERT INTO attendance_settings (class_id, half_day_percent, leave_day_percent, min_attendance_percent) VALUES ($class_id, $half, $leave, $min)");

                    $message = "Class added successfully!";
                } else {
                    $message = "Error: " . $conn->error;
                }
            }
        }
        // Handle Delete
        if ($_POST['action'] == 'delete_class') {
            $id = $_POST['class_id'];
            $conn->query("DELETE FROM classes WHERE id=$id");
            $conn->query("DELETE FROM attendance_settings WHERE class_id=$id");
            $message = "Class deleted.";
        }
    }
}

// Fetch Classes
$classes = $conn->query("SELECT c.*, u.full_name as tutor_name, s.min_attendance_percent, s.half_day_percent 
                        FROM classes c 
                        LEFT JOIN users u ON c.tutor_id = u.id 
                        LEFT JOIN attendance_settings s ON c.id = s.class_id");

// Fetch Teachers for Dropdown
$teachers = $conn->query("SELECT * FROM users WHERE role='teacher'");
?>
<?php include '../includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 class="slide-in">Manage Classes</h2>
    <button onclick="openModal()" class="btn btn-primary slide-in">
        <i class="fas fa-plus"></i> Add New Class
    </button>
</div>

<?php if ($message): ?>
    <div class="slide-in"
        style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="glass slide-in" style="border-radius: 1rem; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(99, 102, 241, 0.1); text-align: left;">
                <th style="padding: 1rem;">Class Name</th>
                <th style="padding: 1rem;">Academic Year</th>
                <th style="padding: 1rem;">Class Tutor</th>
                <th style="padding: 1rem;">Rules</th>
                <th style="padding: 1rem; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $classes->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem; font-weight: 500;">
                        <?php echo $row['class_name']; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo $row['academic_year']; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div
                                style="width: 30px; height: 30px; background: #e0e7ff; color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php echo $row['tutor_name'] ? $row['tutor_name'] : '<span class="text-muted">Unassigned</span>'; ?>
                        </div>
                    </td>
                    <td style="padding: 1rem;">
                        <small class="text-muted">Min: <?php echo $row['min_attendance_percent']; ?>%, Half:
                            <?php echo $row['half_day_percent']; ?>%</small>
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <form method="POST" onsubmit="return confirm('Delete this class?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_class">
                            <input type="hidden" name="class_id" value="<?php echo $row['id']; ?>">
                            <button class="btn" style="color: var(--danger-color); padding: 0.5rem;"><i
                                    class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal" id="addModal">
    <div class="modal-content glass">
        <h3 class="mb-4">Add New Class</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_class">

            <div class="form-group">
                <label>Class Name</label>
                <input type="text" name="class_name" placeholder="e.g. BCA 2nd Year A" required>
            </div>

            <div class="form-group">
                <label>Academic Year</label>
                <input type="text" name="academic_year" placeholder="2023-2024" required>
            </div>

            <div class="form-group">
                <label>Class Tutor</label>
                <select name="tutor_id">
                    <option value="">Select Teacher</option>
                    <?php while ($t = $teachers->fetch_assoc()): ?>
                        <option value="<?php echo $t['id']; ?>">
                            <?php echo $t['full_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <h4 class="mb-4" style="margin-top: 1.5rem;">Attendance Rules</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Half Day %</label>
                    <input type="number" step="0.1" name="half_day" value="50">
                </div>
                <div class="form-group">
                    <label>Leave Day %</label>
                    <input type="number" step="0.1" name="leave_day" value="0">
                </div>
                <div class="form-group">
                    <label>Min Attendance %</label>
                    <input type="number" step="0.1" name="min_attendance" value="75">
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn" onclick="closeModal()"
                    style="background: var(--border);">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Class</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('addModal').classList.add('active'); }
    function closeModal() { document.getElementById('addModal').classList.remove('active'); }
</script>

<?php include '../includes/footer.php'; ?>
</body>

</html>
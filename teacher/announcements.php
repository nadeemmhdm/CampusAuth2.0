<?php
require '../config.php';
// Teacher check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    redirect('../index.php');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = '';

// Handle Create / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'create_announcement') {
        $title = $conn->real_escape_string($_POST['title']);
        $msg = $conn->real_escape_string($_POST['message']);
        $target_role = $_POST['target_role']; // all, student, teacher, parent, admin
        // Admin can set any target. Teacher restricted to 'student', 'parent'

        $target_class = null;
        if ($role == 'teacher') {
            // Teacher force owns class
            // Teacher must have class_id in session or we fetch it. 
            // IMPORTANT: Teachers might have multiple classes in strict systems but here we usually use session.
            // If teacher dashboard assumes single class primary or we select one.
            // For now, let's assume global for teacher's context or specific class if provided.
            // Requirement: "Teacher Can Send to own class only"

            // Let's get teacher's class(es) first
            $t_classes = [];
            $tc_res = $conn->query("SELECT id FROM classes WHERE tutor_id=$user_id");
            while ($row = $tc_res->fetch_row())
                $t_classes[] = $row[0];

            if (empty($t_classes)) {
                $message = "Error: You are not assigned to any class.";
            } else {
                // If mulitple classes, use posted class_id but valid
                // If single, use that.
                if (count($t_classes) == 1)
                    $target_class = $t_classes[0];
                else {
                    $target_class = intval($_POST['class_id']);
                    if (!in_array($target_class, $t_classes))
                        $message = "Error: Invalid Class.";
                }
            }
        } elseif ($role == 'admin' || $role == 'sub_admin') {
            if (isset($_POST['class_id']) && $_POST['class_id'] != 'all') {
                $target_class = intval($_POST['class_id']);
            }
        }

        // Handle Media
        $media_paths = [];
        // Images/Videos
        // Simple file upload logic
        if (!empty($_FILES['media']['name'][0]) && !$message) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm'];
            $total = count($_FILES['media']['name']);
            for ($i = 0; $i < $total; $i++) {
                $tmpFilePath = $_FILES['media']['tmp_name'][$i];
                if ($tmpFilePath != "") {
                    $ext = strtolower(pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $newFilePath = "../uploads/" . uniqid('ann_') . '.' . $ext;
                        if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                            $media_paths[] = str_replace('../', '', $newFilePath); // Store relative to root
                        }
                    }
                }
            }
        }
        $media_json = json_encode($media_paths);

        // Link
        $link_url = $conn->real_escape_string($_POST['link_url']);
        $link_title = $conn->real_escape_string($_POST['link_title']);

        if (!$message) {
            $sql = "INSERT INTO announcements (title, message, media_paths, link_url, link_title, target_role, target_class_id, posted_by, posted_by_role) 
                    VALUES ('$title', '$msg', '$media_json', '$link_url', '$link_title', '$target_role', " . ($target_class ? $target_class : "NULL") . ", $user_id, '$role')";

            if ($conn->query($sql)) {
                $message = "Announcement published successfully.";
            } else {
                $message = "Database Error: " . $conn->error;
            }
        }
    }

    if ($action == 'delete' && isset($_POST['id'])) {
        $aid = intval($_POST['id']);
        // Verify ownership
        $can_delete = false;
        if ($role == 'admin' || $role == 'sub_admin')
            $can_delete = true;
        else {
            $chk = $conn->query("SELECT id FROM announcements WHERE id=$aid AND posted_by=$user_id");
            if ($chk->num_rows > 0)
                $can_delete = true;
        }

        if ($can_delete) {
            $conn->query("UPDATE announcements SET is_deleted=1 WHERE id=$aid");
            $message = "Announcement deleted.";
        }
    }
}

// Fetch Announcements (My Posts)
$my_posts_sql = "SELECT a.*, c.class_name 
                 FROM announcements a 
                 LEFT JOIN classes c ON a.target_class_id = c.id 
                 WHERE a.is_deleted = 0 ";

if ($role == 'teacher') {
    $my_posts_sql .= " AND a.posted_by = $user_id";
}
// Admin sees all or just theirs? Admin Dashboard usually sees all to manage.
$my_posts_sql .= " ORDER BY a.created_at DESC";
$posts = $conn->query($my_posts_sql);

// Get Classes for dropdown
if ($role == 'admin' || $role == 'sub_admin') {
    $classes = $conn->query("SELECT * FROM classes");
} elseif ($role == 'teacher') {
    // Already fetched above in POST, but fetch again for display
    $classes = $conn->query("SELECT * FROM classes WHERE tutor_id = $user_id");
}

?>
<?php include '../includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 class="slide-in">Manage Announcements</h2>
    <a href="notifications.php" class="btn" style="border: 1px solid var(--border); color: var(--text-main);"><i
            class="fas fa-eye"></i> View All</a>
</div>

<?php if ($message): ?>
    <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 2rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 3fr; gap: 2rem;">

    <!-- Create Form -->
    <div class="glass" style="padding: 2rem; height: fit-content; border-radius: 1rem;">
        <h3 class="mb-4">Post Announcement</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_announcement">

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="e.g. Exam Schedule">
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="4" required placeholder="Write your message..."></textarea>
            </div>

            <div class="form-group">
                <label>Target Audience</label>
                <select name="target_role" required>
                    <option value="all">Everywhere (All Roles)</option>
                    <option value="student">Students Only</option>
                    <option value="parent">Parents Only</option>
                    <?php if ($role == 'admin'): ?>
                        <option value="teacher">Teachers Only</option>
                    <?php endif; ?>
                </select>
            </div>

            <?php if (($role == 'admin' || $role == 'teacher') && $classes->num_rows > 0): ?>
                <div class="form-group">
                    <label>Specific Class (Optional)</label>
                    <select name="class_id">
                        <option value="all">All Classes / Global</option>
                        <?php
                        $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo $c['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Media (Images/Videos) - Optional</label>
                <input type="file" name="media[]" multiple accept="image/*,video/*">
            </div>

            <div style="display: flex; gap: 1rem;">
                <div class="form-group w-full">
                    <label>Link URL (Optional)</label>
                    <input type="url" name="link_url" placeholder="https://...">
                </div>
                <div class="form-group w-full">
                    <label>Link Title</label>
                    <input type="text" name="link_title" placeholder="e.g. Join Meeting">
                </div>
            </div>

            <button class="btn btn-primary w-full">Publish Now</button>
        </form>
    </div>

    <!-- List -->
    <div>
        <h3 class="mb-4">History</h3>
        <?php if ($posts->num_rows > 0): ?>
            <div class="stats-grid" style="grid-template-columns: 1fr;">
                <?php while ($p = $posts->fetch_assoc()): ?>
                    <div class="glass" style="padding: 1.5rem; position: relative;">
                        <!-- Delete Button -->
                        <form method="POST" onsubmit="return confirm('Delete this announcement?');"
                            style="position: absolute; top: 1rem; right: 1rem;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button class="btn" style="color: red; padding: 0.5rem;"><i class="fas fa-trash"></i></button>
                        </form>

                        <h4 style="margin: 0 0 0.5rem 0;">
                            <?php echo $p['title']; ?>
                        </h4>
                        <div class="text-muted" style="font-size: 0.9rem; margin-bottom: 1rem;">
                            To: <strong>
                                <?php echo ucfirst($p['target_role']); ?>
                            </strong> |
                            Class: <strong>
                                <?php echo $p['class_name'] ? $p['class_name'] : 'Global'; ?>
                            </strong> |
                            <?php echo date('d M, h:i A', strtotime($p['created_at'])); ?>
                        </div>

                        <p style="white-space: pre-wrap; margin-bottom: 1rem;">
                            <?php echo $p['message']; ?>
                        </p>

                        <!-- Media Preview -->
                        <?php
                        $media = json_decode($p['media_paths'], true);
                        if ($media && count($media) > 0) {
                            echo '<div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px;">';
                            foreach ($media as $m) {
                                $ext = pathinfo($m, PATHINFO_EXTENSION);
                                if (in_array($ext, ['mp4', 'webm'])) {
                                    echo '<video src="../' . $m . '" controls style="height: 100px; border-radius: 8px;"></video>';
                                } else {
                                    echo '<img src="../' . $m . '" style="height: 100px; border-radius: 8px; object-fit: cover;">';
                                }
                            }
                            echo '</div>';
                        }
                        ?>

                        <?php if ($p['link_url']): ?>
                            <a href="<?php echo $p['link_url']; ?>" target="_blank" class="btn"
                                style="display: inline-block; background: #eef2ff; color: var(--primary-color); margin-top: 1rem;">
                                <i class="fas fa-external-link-alt"></i>
                                <?php echo $p['link_title'] ? $p['link_title'] : 'Open Link'; ?>
                            </a>
                        <?php endif; ?>

                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No announcements posted yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
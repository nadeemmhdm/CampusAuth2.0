<?php
// Common Notifications Content
// Requires: $notifs result set
?>
<h2 class="slide-in mb-4">Notifications & Announcements</h2>

<?php if ($notifs->num_rows > 0): ?>
    <div class="stats-grid" style="grid-template-columns: 1fr;">
        <?php while ($n = $notifs->fetch_assoc()): ?>
            <div class="glass" style="padding: 1.5rem; border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <div>
                        <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <?php if ($n['posted_by_role'] == 'admin'): ?>
                                <span class="status-badge"
                                    style="background: #eef2ff; color: var(--primary-color); padding: 2px 6px;">Admin</span>
                            <?php else: ?>
                                <span class="status-badge"
                                    style="background: #fff7ed; color: #c2410c; padding: 2px 6px;">Teacher</span>
                            <?php endif; ?>
                            <?php echo $n['title']; ?>
                        </h4>
                        <div class="text-muted" style="font-size: 0.8rem; margin-top: 0.25rem;">
                            By
                            <?php echo $n['author_name']; ?>
                            <?php if ($n['class_name'])
                                echo " • For " . $n['class_name']; ?>
                            •
                            <?php echo date('d M, h:i A', strtotime($n['created_at'])); ?>
                        </div>
                    </div>
                </div>

                <div
                    style="margin: 1rem 0; white-space: pre-wrap; font-size: 0.95rem; line-height: 1.5; color: var(--text-main);">
                    <?php echo $n['message']; ?>
                </div>

                <!-- Media Display -->
                <?php
                $media = json_decode($n['media_paths'], true);
                if ($media && count($media) > 0) {
                    echo '<div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-top: 1rem;">';
                    foreach ($media as $m) {
                        $ext = pathinfo($m, PATHINFO_EXTENSION);
                        if (in_array($ext, ['mp4', 'webm'])) {
                            echo '<video src="../' . $m . '" controls style="height: 150px; border-radius: 8px; background:black;"></video>';
                        } else {
                            echo '<img src="../' . $m . '" onclick="window.open(this.src)" style="height: 150px; border-radius: 8px; object-fit: cover; cursor: pointer;">';
                        }
                    }
                    echo '</div>';
                }
                ?>

                <?php if ($n['link_url']): ?>
                    <a href="<?php echo $n['link_url']; ?>" target="_blank" class="btn"
                        style="display: inline-block; background: #eef2ff; color: var(--primary-color); margin-top: 1rem;">
                        <i class="fas fa-external-link-alt"></i>
                        <?php echo $n['link_title'] ? $n['link_title'] : 'Open Link'; ?>
                    </a>
                <?php endif; ?>

            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="glass text-center text-muted" style="padding: 4rem; border-radius: 1rem;">
        <i class="fas fa-bell-slash fa-2x mb-3" style="color: #cbd5e1;"></i><br>
        No new notifications.
    </div>
<?php endif; ?>
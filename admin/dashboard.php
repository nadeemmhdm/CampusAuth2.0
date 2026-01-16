<?php
require '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// Fetch Stats
$total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
$total_staff = $conn->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetch_row()[0];
$total_classes = $conn->query("SELECT COUNT(*) FROM classes")->fetch_row()[0];

// Mock attendance data for graph (usually calculate from DB)
?>
<?php include '../includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 class="slide-in">Admin Dashboard</h2>
    <div class="user-profile">
        Administrator
    </div>
</div>

<!-- Cards Logic from Previous implementation remains same but layout is unified -->
<div class="stats-grid slide-in">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div>
            <h3><?php echo $total_students; ?></h3>
            <p style="color: var(--text-muted);">Total Students</p>
        </div>
    </div>
    <script>
        // Chart.js Setup
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Average Attendance %',
                    data: [85, 88, 82, 90, 87, 75],
                    borderColor: '#6366f1',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    </script>
    </body>

    </html>
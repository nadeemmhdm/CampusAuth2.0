<?php
require '../config.php';
checkLogin();

if ($_SESSION['role'] !== 'teacher') {
    redirect('../index.php');
}
?>
<?php include '../includes/header.php'; ?>

<!-- Profile Content -->
<?php include '../includes/profile_content.php'; ?>

<?php include '../includes/footer.php'; ?>
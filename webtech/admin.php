<?php
session_start();
require 'db.php';
require 'aes.php'; // AES decryption helper

// Ensure admin access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();

// Fetch login info (for lockout check)
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT login_attempts, last_attempt, register_id FROM logins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($attempts, $last_attempt, $register_id);
$stmt->fetch();
$stmt->close();

// Optional: Lockout logic
$current_time = new DateTime();
$last_attempt_time = $last_attempt ? new DateTime($last_attempt) : null;
$lockout_duration = new DateInterval('PT1H');

if ($attempts >= 5 && $last_attempt_time && $current_time < (clone $last_attempt_time)->add($lockout_duration)) {
    $remaining_seconds = $last_attempt_time->add($lockout_duration)->getTimestamp() - $current_time->getTimestamp();
    $minutes = floor($remaining_seconds / 60);
    $seconds = $remaining_seconds % 60;

    session_unset();
    session_destroy();
    echo "<script>
        alert('⛔ Your admin account is locked. Try again in {$minutes}m {$seconds}s.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

// Fetch encrypted full name from register
$stmt = $conn->prepare("SELECT fname, mname, lname FROM registers WHERE id = ?");
$stmt->bind_param("i", $register_id);
$stmt->execute();
$stmt->bind_result($enc_fname, $enc_mname, $enc_lname);
$stmt->fetch();
$stmt->close();

// Decrypt
$fname = aes_decrypt($enc_fname);
$mname = aes_decrypt($enc_mname);
$lname = aes_decrypt($enc_lname);
$full_name = trim("$fname $mname $lname");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        .dashboard-container {
            margin-top: 100px;
        }
        .btn-group-vertical .btn {
            width: 300px;
        }
        .welcome-title {
            color: rgba(255, 204, 0, 0.9);
        }
    </style>
</head>
<body>
<div class="container text-center dashboard-container">
    <h1 class="mb-4">Welcome, Admin <?= htmlspecialchars($full_name); ?>!</h1>
    
    <div class="btn-group-vertical mx-auto">
        <a href="dashboard.php" class="btn btn-primary btn-lg mb-3">📘 Faculty Dashboard</a>
        <a href="student_dashboard.php" class="btn btn-secondary btn-lg mb-3">🎓 Student Dashboard</a>
        <a href="student_assistant_dashboard.php" class="btn btn-secondary btn-lg mb-3">🎓 Student Assistant Dashboard</a>
        <a href="manage_roles.php" class="btn btn-warning btn-lg mb-3">🛠 Manage Roles</a>
        <a href="logout.php" class="btn btn-danger btn-lg">🚪 Logout</a>
    </div>
</div>
</body>
</html>

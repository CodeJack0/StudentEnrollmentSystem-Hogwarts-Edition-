<?php
session_start();
require 'db.php';
require 'aes.php'; // Include AES functions

// 🔒 Security headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ⏳ Session timeout (10 minutes)
$timeout_duration = 600;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    echo "<script>alert('Session expired due to inactivity.'); window.location.href = 'login.php';</script>";
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['student', 'admin'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();
$username = $_SESSION['username'];

// 🔒 Lockout logic
$stmt = $conn->prepare("SELECT login_attempts, last_attempt, register_id FROM logins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($attempts, $last_attempt, $register_id);
$stmt->fetch();
$stmt->close();

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
        alert('⛔ Account locked due to failed attempts. Try again in {$minutes}m {$seconds}s.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

// 🔓 Get encrypted name and birthdate from registers
$stmt = $conn->prepare("SELECT fname, mname, lname, birthdate FROM registers WHERE id = ?");
$stmt->bind_param("i", $register_id);
$stmt->execute();
$stmt->bind_result($enc_fname, $enc_mname, $enc_lname, $birthdate);
$stmt->fetch();
$stmt->close();

$fname = aes_decrypt($enc_fname);
$mname = aes_decrypt($enc_mname);
$lname = aes_decrypt($enc_lname);
$full_name = trim("$fname $mname $lname");

// 🧮 Calculate age
$age = '';
if ($birthdate) {
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
     <link rel="stylesheet" href="assistant.css">
</head>
<body>
    <div class="container text-center dashboard-container">
        <h1 class="mb-4">
            Welcome, <?php echo htmlspecialchars($full_name); ?>!
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <small class="text-muted">(Viewing as Student)</small>
            <?php endif; ?>
        </h1>

        <p class="lead">This is the student dashboard view.</p>

        <div class="mt-4">
            <p><strong>Birthdate:</strong> <?= htmlspecialchars($birthdate); ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($age); ?></p>
        </div>

        <div class="btn-group-vertical mt-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="btn btn-primary btn-lg mb-3">🔙 Back to Admin Dashboard</a>
            <?php endif; ?>

            <a href="logout.php" class="btn btn-danger btn-lg">Logout</a>
        </div>
    </div>
</body>
</html>

<?php
ob_start(); // Start output buffering
include 'db.php';
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = getDbConnection();

$error = "";
$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, register_id, email, login_attempts, last_attempt FROM logins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $error = "No user found with this username.";
        $alert_script = "<script>window.onload = function() { alert('No user found with this username.'); }</script>";
    } else {
        $stmt->bind_result($login_id, $hash, $reg_id, $email, $attempts, $last_attempt);
        $stmt->fetch();

        $current_time = new DateTime();
        $last_attempt_time = $last_attempt ? new DateTime($last_attempt) : null;
        $lockout_duration = new DateInterval('PT1H'); // 1 hour lock

        if ($attempts >= 5 && $last_attempt_time && $current_time < (clone $last_attempt_time)->add($lockout_duration)) {
            $remaining_seconds = $last_attempt_time->add($lockout_duration)->getTimestamp() - $current_time->getTimestamp();
            $minutes = floor($remaining_seconds / 60);
            $seconds = $remaining_seconds % 60;
            $error = "Account locked. Try again in {$minutes}m {$seconds}s.";
            $alert_script = "<script>window.onload = function() { alert('Account locked due to too many failed attempts.\\nTry again in {$minutes}m {$seconds}s.'); }</script>";
        } elseif (password_verify($password, $hash)) {
            // Successful login
            $stmt->close();
            $stmt = $conn->prepare("UPDATE logins SET login_attempts = 0, last_attempt = NULL WHERE id = ?");
            $stmt->bind_param("i", $login_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("SELECT fname, lname FROM registers WHERE id = ?");
            $stmt->bind_param("i", $reg_id);
            $stmt->execute();
            $stmt->bind_result($fname, $lname);
            $stmt->fetch();
            $stmt->close();

            session_regenerate_id();
            $_SESSION = [
                'username' => $username,
                'register_id' => $reg_id,
                'email' => $email,
                'name' => $fname . ' ' . $lname
            ];
            header("Location: dashboard.php");
            exit();
        } else {
            // Failed login
            $attempts++;
            $now = date('Y-m-d H:i:s');
            $stmt->close();
            $stmt = $conn->prepare("UPDATE logins SET login_attempts = ?, last_attempt = ? WHERE id = ?");
            $stmt->bind_param("isi", $attempts, $now, $login_id);
            $stmt->execute();
            $stmt->close();

            $remaining_attempts = max(0, 5 - $attempts);
            if ($remaining_attempts > 0) {
                $error = "Incorrect password. Attempt {$attempts}/5.";
                $alert_script = "<script>window.onload = function() { alert('Incorrect password. Attempt {$attempts}/5.'); }</script>";
            } else {
                $error = "Too many failed attempts. Account locked for 1 hour.";
                $alert_script = "<script>window.onload = function() { alert('Incorrect password. Attempt 5/5.\\nAccount is now locked for 1 hour.'); }</script>";
            }
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="wrapper">
    <div class="logo"><img src="Hogwarts-Logo.png" alt="Logo"></div>
    <form method="POST">
        <h1>Login</h1>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="input-box">
            <div class="input-field">
                <input type="text" name="username" placeholder="Username" required autocomplete="off">
            </div>
            <div class="input-field">
                <input type="password" name="password" placeholder="Password" required autocomplete="off">
            </div>
        </div>
        <button type="submit" class="btn">Login</button>
        <a href="register.php">Don't have an account? Register here</a>
    </form>
</div>

<!-- Display the alert if any -->
<?php echo $alert_script; ?>
</body>
</html>

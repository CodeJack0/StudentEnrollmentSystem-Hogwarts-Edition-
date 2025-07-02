<?php
ob_start(); // Start output buffering
include 'db.php';

// ✅ Secure session cookie settings
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Adjust this to your domain
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// ✅ Optional: Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// ✅ CSP Header for XSS mitigation
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self';");

// ✅ CSRF Token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Optional: Test CSRF protection
///if (isset($_GET['force_csrf_fail']) && $_GET['force_csrf_fail'] == '1') {
  //  $_SESSION['csrf_token'] = 'FAKE_TOKEN';
//}

// ✅ Optional: Check HTTP referer to mitigate CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expected_referer = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $expected_referer) !== 0) {
        die("❌ Invalid referer header. Possible CSRF attack.");
    }
}

if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = getDbConnection();

$error = "";
$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ✅ CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("❌ Invalid CSRF token. Your session might have expired or the request was forged.");
    }

    // ✅ Sanitize inputs
    $username = strip_tags(trim($_POST['username']));
    $password = strip_tags($_POST['password']);

    // ✅ Regex validation: allow only letters, numbers, underscores
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = "Invalid username format.";
        $alert_script = "<script>window.onload = function() { alert('Invalid username format.'); }</script>";
    } else {
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
            $lockout_duration = new DateInterval('PT1H');

            if ($attempts >= 5 && $last_attempt_time && $current_time < (clone $last_attempt_time)->add($lockout_duration)) {
                $remaining_seconds = $last_attempt_time->add($lockout_duration)->getTimestamp() - $current_time->getTimestamp();
                $minutes = floor($remaining_seconds / 300);
                $seconds = $remaining_seconds % 300;
                $error = "Account locked. Try again in {$minutes}m {$seconds}s.";
                $alert_script = "<script>window.onload = function() { alert('Account locked due to too many failed attempts.\\nTry again in {$minutes}m {$seconds}s.'); }</script>";
            } elseif (password_verify($password, $hash)) {
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
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="wrapper">
    <div class="logo"><img src="Hogwarts-Logo.png" alt="Logo"></div>
    <form method="POST">
        <h1>Login</h1>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="input-box">
            <div class="input-field">
                <input type="text" name="username" placeholder="Username" required autocomplete="off"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            <div class="input-field">
                <input type="password" name="password" placeholder="Password" required autocomplete="off">
            </div>
        </div>
        <!-- CSRF Token Field -->
        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn">Login</button>
        <a href="register.php">Don't have an account? Register here</a>
    </form>
</div>


<?php echo $alert_script; ?>
</body>
</html>

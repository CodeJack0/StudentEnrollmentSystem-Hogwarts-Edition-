<?php
ob_start();
include 'db.php';
include 'aes.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Security-Policy: default-src 'self' https://www.google.com https://www.gstatic.com; script-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; style-src 'self'; img-src 'self';");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expected_referer = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $expected_referer) !== 0) {
        die("❌ Invalid referer header. Possible CSRF attack.");
    }
}

if (isset($_SESSION['username'])) {
    switch ($_SESSION['role']) {
        case 'student':
            header("Location: student_dashboard.php");
            break;
        case 'faculty':
            header("Location: dashboard.php");
            break;
        case 'admin':
            header("Location: admin.php");
            break;
        case 'student_assistance': // ✅ fixed role check
            header("Location: student_assistant_dashboard.php");
            break;
        default:
            session_destroy();
            header("Location: login.php");
    }
    exit();
}

$conn = getDbConnection();
$error = "";
$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("❌ Invalid CSRF token. Your session might have expired or the request was forged.");
    }

    $recaptcha_secret = '6LczcIErAAAAABq9F-YIHeC_Sgn3cmLvt-Rvo5Pd';
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptcha_response)) {
        die("❌ CAPTCHA is required.");
    }

    $verify_response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}&remoteip=" . $_SERVER['REMOTE_ADDR']);
    $captcha_success = json_decode($verify_response);

    if (!$captcha_success->success) {
        die("❌ CAPTCHA verification failed. Please try again.");
    }

    $username_input = strip_tags(trim($_POST['username']));
    $password_input = strip_tags($_POST['password']);

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username_input)) {
        $error = "Invalid username format.";
        $alert_script = "<script>window.onload = function() { alert('Invalid username format.'); }</script>";
    } else {
        // ✅ FIXED: only select matching username (instead of full table scan)
        $user_stmt = $conn->prepare("SELECT id, username, password, register_id, email, login_attempts, last_attempt, role FROM logins WHERE username = ?");
        $user_stmt->bind_param("s", $username_input);
        $user_stmt->execute();
        $result = $user_stmt->get_result();
        $user_found = false;

        if ($row = $result->fetch_assoc()) {
            $user_found = true;
            $login_id = $row['id'];
            $hashed_password = $row['password'];
            $decrypted_email = aes_decrypt($row['email']);
            $reg_id = $row['register_id'];
            $attempts = $row['login_attempts'];
            $last_attempt = $row['last_attempt'];
            $role = $row['role'];

            $current_time = new DateTime();
            $last_attempt_time = $last_attempt ? new DateTime($last_attempt) : null;
            $lockout_duration = new DateInterval('PT1H');
            $lockout_expires = $last_attempt_time ? (clone $last_attempt_time)->add($lockout_duration) : null;

            if ($attempts >= 5 && $last_attempt_time && $current_time < $lockout_expires) {
                $remaining_seconds = $lockout_expires->getTimestamp() - $current_time->getTimestamp();
                $minutes = floor($remaining_seconds / 60);
                $seconds = $remaining_seconds % 60;
                $error = "Account locked. Try again in {$minutes}m {$seconds}s.";
                $alert_script = "<script>window.onload = function() { alert('Account locked.\\nTry again in {$minutes}m {$seconds}s.'); }</script>";
            } elseif (password_verify($password_input, $hashed_password)) {
                $reset_stmt = $conn->prepare("UPDATE logins SET login_attempts = 0, last_attempt = NULL WHERE id = ?");
                $reset_stmt->bind_param("i", $login_id);
                $reset_stmt->execute();
                $reset_stmt->close();

                $name_stmt = $conn->prepare("SELECT fname, mname, lname FROM registers WHERE id = ?");
                $name_stmt->bind_param("i", $reg_id);
                $name_stmt->execute();
                $name_stmt->bind_result($fname, $mname, $lname);
                $name_stmt->fetch();
                $name_stmt->close();

                session_regenerate_id();
                $_SESSION['username'] = $username_input;
                $_SESSION['register_id'] = $reg_id;
                $_SESSION['email'] = $decrypted_email;
                $_SESSION['name'] = trim("$fname $mname $lname");
                $_SESSION['role'] = $role;

                switch ($role) {
                    case 'student':
                        header("Location: student_dashboard.php");
                        break;
                    case 'admin':
                        header("Location: admin.php");
                        break;
                    case 'faculty':
                        header("Location: dashboard.php");
                        break;
                    case 'student_assistance': // ✅ fixed
                        header("Location: student_assistant_dashboard.php");
                        break;
                    default:
                        session_destroy();
                        header("Location: login.php");
                }
                exit();
            } else {
                $attempts++;
                $now = date('Y-m-d H:i:s');
                $fail_stmt = $conn->prepare("UPDATE logins SET login_attempts = ?, last_attempt = ? WHERE id = ?");
                $fail_stmt->bind_param("isi", $attempts, $now, $login_id);
                $fail_stmt->execute();
                $fail_stmt->close();

                $remaining_attempts = max(0, 5 - $attempts);
                if ($remaining_attempts > 0) {
                    $error = "Incorrect password. Attempt {$attempts}/5.";
                    $alert_script = "<script>window.onload = function() { alert('Incorrect password. Attempt {$attempts}/5.'); }</script>";
                } else {
                    $error = "Too many failed attempts. Account locked for 1 hour.";
                    $alert_script = "<script>window.onload = function() { alert('Attempt 5/5.\\nAccount is now locked for 1 hour.'); }</script>";
                }
            }
        }

        if ($user_stmt instanceof mysqli_stmt) {
            $user_stmt->close();
        }

        if (!$user_found) {
            $error = "No user found with this username.";
            $alert_script = "<script>window.onload = function() { alert('No user found with this username.'); }</script>";
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
        <div class="g-recaptcha" data-sitekey="6LczcIErAAAAAFhji555etK09fOCrSIez92AvMdn"></div>
        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn">Login</button>
        <a href="register.php">Don't have an account? Register here</a>
    </form>
</div>

<?php echo $alert_script; ?>
</body>
</html>

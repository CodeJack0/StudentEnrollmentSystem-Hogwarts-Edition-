<?php
include 'db.php';
include 'aes.php';
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = getDbConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    // Sanitize inputs
    $faculty_ID = htmlspecialchars($_POST['faculty_ID']); 
    $fname = htmlspecialchars($_POST['fname']);
    $mname = htmlspecialchars($_POST['mname']);
    $lname = htmlspecialchars($_POST['lname']);
    $birthdate = htmlspecialchars($_POST['birthdate']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        die("Invalid email format");
    }

    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $confirm_password = htmlspecialchars($_POST['confirm_password']);
    $role = $_POST['role'];
    $allowed_roles = ['admin', 'faculty', 'student'];
    if (!in_array($role, $allowed_roles)) {
        die("Invalid role selected.");
    }

    if (strlen($password) < 6) {
        die("Password must be at least 6 characters long");
    }
    if ($password !== $confirm_password) {
        die("Passwords do not match");
    }

    $birthdate_obj = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthdate_obj)->y;

    // Check existing email or username
    $stmt = $conn->prepare("SELECT email, username FROM logins WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Email or Username already registered");
    }
    $stmt->close();

    // Encrypt selected fields
    $enc_fname = aes_encrypt($fname);
    $enc_mname = aes_encrypt($mname);
    $enc_lname = aes_encrypt($lname);
    $enc_email = aes_encrypt($email);

    // Insert into 'registers'
    $stmt = $conn->prepare("INSERT INTO registers (faculty_ID, fname, mname, lname, birthdate, age) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $faculty_ID, $enc_fname, $enc_mname, $enc_lname, $birthdate, $age);

    if ($stmt->execute()) {
        $register_id = $conn->insert_id;

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into 'logins' WITHOUT encrypting register_id
        $stmt = $conn->prepare("INSERT INTO logins (register_id, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $register_id, $username, $enc_email, $hashed_password, $role);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: " . $stmt->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="register.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>
        function generatefaculty_ID() {
            const chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let facultyID = '';
            for (let i = 0; i < 6; i++) {
                facultyID += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById("faculty_ID").value = facultyID.toUpperCase();
        }

        function calculateAge() {
            const birthdate = document.getElementById("birthdate").value;
            if (birthdate) {
                const birthDateObj = new Date(birthdate);
                const today = new Date();
                let age = today.getFullYear() - birthDateObj.getFullYear();
                const monthDiff = today.getMonth() - birthDateObj.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDateObj.getDate())) {
                    age--;
                }
                document.getElementById("age").value = age;
            }
        }

        window.onload = function() {
            generatefaculty_ID();
        };
    </script>
</head>
<body>
    <div class="wrapper">
        <div class="logo">
            <img src="Hogwarts-Logo.png" alt="Logo">
        </div>
        <form action="register.php" method="POST">
            <h1>Register</h1>

            <div class="input-box">
                <div class="input-field">
                    <input type="text" id="faculty_ID" name="faculty_ID" placeholder="Faculty ID" readonly>
                    <i class='bx bx-id-card'></i>
                </div>
                <div class="input-field">
                    <input type="text" name="fname" placeholder="First Name" required>
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-field">
                    <input type="text" name="mname" placeholder="Middle Name">
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-field">
                    <input type="text" name="lname" placeholder="Last Name" required>
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-field">
                    <input type="date" id="birthdate" name="birthdate" placeholder="Birthdate" required onchange="calculateAge()">
                    <i class='bx bx-calendar'></i>
                </div>
                <div class="input-field">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class='bx bx-envelope'></i>
                </div>
                <div class="input-field">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-field">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class='bx bxs-lock-alt'></i>
                </div>
                <div class="input-field">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <i class='bx bxs-lock-alt'></i>
                </div>
                <div class="input-field">
                    <input type="text" id="age" name="age" placeholder="Age" readonly>
                    <i class='bx bx-calendar'></i>
                </div>

                <div class="input-field">
                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="faculty">Faculty</option>
                        <option value="student">Student</option>
                        <!--<option value="admin">Admin</option>-->
                    </select>
                    <i class='bx bx-user-check'></i>
                </div>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit" class="btn">Register</button>
            <a href="login.php">Have an account? login here</a>
        </form>
    </div>
</body>
</html>

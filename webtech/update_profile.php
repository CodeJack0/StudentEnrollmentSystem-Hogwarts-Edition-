<?php
include 'db.php';

session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch user details
$query = $conn->prepare("
    SELECT registers.*, logins.username, logins.password 
    FROM logins 
    INNER JOIN registers ON logins.register_id = registers.id 
    WHERE logins.username = ?
");
$query->bind_param("s", $username);
$query->execute();
$user = $query->get_result()->fetch_assoc();

if (!$user) {
    header("Location: unauthorized.php"); // No such user found
    exit();
}

// Restriction: only allow 'admin' or the faculty editing their own account
if (!in_array($role, ['admin', 'faculty'])) {
    header("Location: unauthorized.php");
    exit();
}

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $faculty_id = $_POST['faculty_id'];
    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $birthdate = $_POST['birthdate'];
    $age = $_POST['age'];
    $new_username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($password) && $password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        $update_query_str = "
            UPDATE registers 
            JOIN logins ON logins.register_id = registers.id 
            SET 
                registers.faculty_ID = ?, 
                registers.fname = ?, 
                registers.mname = ?, 
                registers.lname = ?, 
                registers.birthdate = ?, 
                registers.age = ?, 
                logins.username = ?";

        if ($passwordHash) {
            $update_query_str .= ", logins.password = ?";
        }

        $update_query_str .= " WHERE logins.username = ?";

        $update_query = $conn->prepare($update_query_str);

        if ($passwordHash) {
            $update_query->bind_param(
                "sssssssss", 
                $faculty_id, $fname, $mname, $lname, $birthdate, $age, $new_username, $passwordHash, $username
            );
        } else {
            $update_query->bind_param(
                "ssssssss", 
                $faculty_id, $fname, $mname, $lname, $birthdate, $age, $new_username, $username
            );
        }

        if ($update_query->execute()) {
            $_SESSION['username'] = $new_username;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
}

// Handle profile image upload/removal
if (isset($_POST['upload_image']) || isset($_POST['remove_image'])) {
    $register_id = $user['id'];

    if (isset($_POST['upload_image']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $fileType = mime_content_type($_FILES['profile_image']['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

        if (in_array($fileType, $allowedTypes)) {
            $imageData = file_get_contents($_FILES['profile_image']['tmp_name']);
            $stmt = $conn->prepare("UPDATE registers SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $imageData, $register_id);

            if ($stmt->execute()) {
                $user['profile_image'] = $imageData;
            } else {
                echo "Failed to update image: " . $conn->error;
            }
        } else {
            echo "Invalid file type. Only JPEG, PNG, JPG, and GIF allowed.";
        }
    } elseif (isset($_POST['remove_image'])) {
        $stmt = $conn->prepare("UPDATE registers SET profile_image = NULL WHERE id = ?");
        $stmt->bind_param("i", $register_id);

        if ($stmt->execute()) {
            $user['profile_image'] = null;
        } else {
            echo "Failed to remove image: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <script>
        function calculateAge() {
            const birthdate = new Date(document.getElementById('birthdate').value);
            const today = new Date();
            let age = today.getFullYear() - birthdate.getFullYear();
            const m = today.getMonth() - birthdate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            document.getElementById('age').value = age;
        }

        function triggerFileInput() {
            document.getElementById('file-input').click();
        }

        function showSaveButton() {
            document.getElementById('save-button').style.display = 'inline';
        }
    </script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: rgba(60, 63, 65, 0.85);">
    <a class="navbar-brand" href="dashboard.php" style="color: rgba(255, 204, 0, 0.9);">Dashboard</a>
</nav>

<div class="container mt-4">
    <h2>Update Profile Image</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="upload_image" value="1">
        <div class="form-group">
            <button type="button" class="btn btn-info" onclick="triggerFileInput()">Upload Image</button>
            <input type="file" id="file-input" name="profile_image" accept="image/*" style="display:none;" onchange="showSaveButton()">
            <button type="submit" class="btn btn-success" id="save-button" style="display:none;">Save Image</button>
        </div>
    </form>
    <form action="" method="POST">
        <input type="hidden" name="remove_image" value="1">
        <button type="submit" class="btn btn-danger">Remove Image</button>
    </form>

    <h2 class="mt-4">Update Profile Information</h2>
    <form action="" method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
            <label for="faculty_id">Faculty ID</label>
            <input type="text" class="form-control" id="faculty_id" name="faculty_id" value="<?php echo htmlspecialchars($user['faculty_ID']); ?>" required>
        </div>
        <div class="form-group">
            <label for="fname">First Name</label>
            <input type="text" class="form-control" id="fname" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" required>
        </div>
        <div class="form-group">
            <label for="mname">Middle Name</label>
            <input type="text" class="form-control" id="mname" name="mname" value="<?php echo htmlspecialchars($user['mname']); ?>">
        </div>
        <div class="form-group">
            <label for="lname">Last Name</label>
            <input type="text" class="form-control" id="lname" name="lname" value="<?php echo htmlspecialchars($user['lname']); ?>" required>
        </div>
        <div class="form-group">
            <label for="birthdate">Birthdate</label>
            <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate']); ?>" required onchange="calculateAge()">
        </div>
        <div class="form-group">
            <label for="age">Age</label>
            <input type="number" class="form-control" id="age" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">New Password (optional)</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>

<?php
session_start();
require 'db.php';
require 'aes.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $login_id = intval($_POST['login_id']);
    $new_role = $_POST['new_role'];

    $stmt = $conn->prepare("UPDATE logins SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $login_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_roles.php?success=1");
    exit();
}

// Fetch all login accounts with register details
$sql = "SELECT logins.id as login_id, logins.username, logins.role, registers.fname, registers.mname, registers.lname 
        FROM logins 
        JOIN registers ON logins.register_id = registers.id";
$result = $conn->query($sql);

$accounts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['fname'] = aes_decrypt($row['fname']);
        $row['mname'] = aes_decrypt($row['mname']);
        $row['lname'] = aes_decrypt($row['lname']);
        $row['full_name'] = trim($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']);
        $accounts[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Roles</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="assistant.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Admin - Manage User Roles</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Role updated successfully!</div>
    <?php endif; ?>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Current Role</th>
                <th>Change Role</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?= htmlspecialchars($account['username']) ?></td>
                <td><?= htmlspecialchars($account['full_name']) ?></td>
                <td><?= htmlspecialchars($account['role']) ?></td>
                <td>
                    <form method="POST" style="display: flex; gap: 8px;">
                        <input type="hidden" name="login_id" value="<?= $account['login_id'] ?>">
                        <select name="new_role" class="form-control" required>
                            <?php if ($account['role'] === 'admin'): ?>
                                <option value="admin" selected>Admin</option>
                                <option value="faculty">Faculty</option>
                            <?php elseif ($account['role'] === 'faculty'): ?>
                                <option value="admin">Admin</option>
                                <option value="faculty" selected>Faculty</option>
                            <?php elseif ($account['role'] === 'student'): ?>
                                <option value="student" selected>Student</option>
                                <option value="student_assistance">Student Assistant</option>
                            <?php elseif ($account['role'] === 'student_assistance'): ?>
                                <option value="student">Student</option>
                                <option value="student_assistance" selected>Student Assistant</option>
                            <?php else: ?>
                                <option value="<?= $account['role'] ?>" selected><?= ucfirst($account['role']) ?></option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" name="update_role" class="btn btn-primary btn-sm">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="admin.php" class="btn btn-secondary mt-4">← Back to Admin Dashboard</a>
</div>
</body>
</html>

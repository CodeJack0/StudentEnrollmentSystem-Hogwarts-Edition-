<?php
session_start();
require 'db.php';
require 'aes.php';

// ✅ Allow only admin and student_assistance access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'student_assistance'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();

// 🔓 Decrypt and fetch the logged-in user's name (admin or student assistant)
$user_name = $_SESSION['role'] === 'admin' ? 'Admin' : 'Student Assistant';
$name_query = $conn->prepare("SELECT fname, mname, lname FROM registers WHERE id = ?");
$name_query->bind_param("i", $_SESSION['register_id']);
$name_query->execute();
$name_result = $name_query->get_result();

if ($name_result && $name_result->num_rows === 1) {
    $row = $name_result->fetch_assoc();
    $fname = aes_decrypt($row['fname']);
    $mname = aes_decrypt($row['mname']);
    $lname = aes_decrypt($row['lname']);

    $fname = $fname !== false ? $fname : $row['fname'];
    $mname = $mname !== false ? $mname : $row['mname'];
    $lname = $lname !== false ? $lname : $row['lname'];

    $user_name = trim("$fname $mname $lname");
}

// 🔓 Fetch all students and decrypt their full names
$sql = "SELECT id, fullname FROM students";
$result = $conn->query($sql);
$students = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fullname = aes_decrypt($row['fullname']);
        $fullname = $fullname !== false ? $fullname : $row['fullname'];

        $students[] = [
            'id' => $row['id'],
            'full_name' => $fullname
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Assistant Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assistant.css">

</head>
<body>
<div class="container">
    <h2>📋 Student Assistant Dashboard</h2>
    <p>Welcome, <?= htmlspecialchars($user_name) ?>!</p>

    <h4 class="mt-4">List of Students</h4>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Full Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) > 0): ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                        <td>
                            <a href="view_student.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2">No student records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
</div>
</body>
</html>

<?php
include 'db.php'; // Assuming this file contains the getDbConnection() function
require 'aes.php';

session_start();

// 🔒 Check if the user is logged in and has the correct role
$allowed_roles = ['student_assistance', 'faculty', 'admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo "Access denied.";
    exit();
}

$conn = getDbConnection();

// Check if the student ID is provided in the URL
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // Fetch the student's details along with the course code
    $student_query = $conn->prepare("
        SELECT s.*, c.course_code 
        FROM students s 
        JOIN courses c ON s.course = c.course_id 
        WHERE s.id = ?
    ");
    $student_query->bind_param("i", $student_id);
    $student_query->execute();
    $student = $student_query->get_result()->fetch_assoc();

    // Check if the student exists
    if (!$student) {
        echo "Student not found.";
        exit();
    }

    // 🔓 Decrypt full name
    $student['fullname'] = aes_decrypt($student['fullname']) ?: $student['fullname'];

    // Fetch subjects for the student's course
    $subjects_query = $conn->prepare("SELECT * FROM subjects WHERE course_code = ?");
    $subjects_query->bind_param("s", $student['course_code']);
    $subjects_query->execute();
    $subjects_result = $subjects_query->get_result();
} else {
    echo "No student ID provided.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Student Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="assistant.css">
</head>
<body>

<div class="container mt-5">
    <h2>Student Details</h2>
    <table class="table table-bordered">
        <tr>
            <th>Student ID</th>
            <td><?= htmlspecialchars($student['student_id']) ?></td>
        </tr>
        <tr>
            <th>Full Name</th>
            <td><?= htmlspecialchars($student['fullname']) ?></td>
        </tr>
        <tr>
            <th>Course Code</th>
            <td><?= htmlspecialchars($student['course_code']) ?></td>
        </tr>
        <tr>
            <th>Year Level</th>
            <td><?= htmlspecialchars($student['year_level']) ?></td>
        </tr>
    </table>

    <h3>Subjects for <?= htmlspecialchars($student['fullname']) ?></h3>
    <table class="table table-hover table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Subject ID</th>
                <th>Subject Detail</th>
                <th>Units</th>
                <th>Lab</th>
                <th>Lecture</th>
                <th>Year Level</th>
                <th>Semester</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($subjects_result->num_rows > 0): ?>
                <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject['subject_id']) ?></td>
                        <td><?= htmlspecialchars($subject['subject_detail']) ?></td>
                        <td><?= htmlspecialchars($subject['units']) ?></td>
                        <td><?= htmlspecialchars($subject['lab']) ?></td>
                        <td><?= htmlspecialchars($subject['lecture']) ?></td>
                        <td><?= htmlspecialchars($subject['year_level']) ?></td>
                        <td><?= htmlspecialchars($subject['semester']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No subjects found for this course.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

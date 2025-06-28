<?php
include 'db.php'; // Assuming this file contains the getDbConnection() function

session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit(); // Ensure this line is executed after the header
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
</head>
<body>

<div class="container">
    <h2>Student Details</h2>
    <table class="table">
        <tr>
            <th>Student ID</th>
            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
        </tr>
        <tr>
            <th>Full Name</th>
            <td><?php echo htmlspecialchars($student['fullname']); ?></td>
        </tr>
        <tr>
            <th>Course Code</th>
            <td><?php echo htmlspecialchars($student['course_code']); ?></td>
        </tr>
        <tr>
            <th>Year Level</th>
            <td><?php echo htmlspecialchars($student['year_level']); ?></td>
        </tr>
    </table>

    <h3>Subjects for <?php echo htmlspecialchars($student['fullname']); ?></h3>
    <table class="table">
        <thead>
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
                    <td><?php echo htmlspecialchars($subject['subject_id']); ?></td>
                    <td><?php echo htmlspecialchars($subject['subject_detail']); ?></td>
                    <td><?php echo htmlspecialchars($subject['units']); ?></td>
                    <td><?php echo htmlspecialchars($subject['lab']); ?></td>
                    <td><?php echo htmlspecialchars($subject['lecture']); ?></td>
                    <td><?php echo htmlspecialchars($subject['year_level']); ?></td>
                    <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No subjects found for this course.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

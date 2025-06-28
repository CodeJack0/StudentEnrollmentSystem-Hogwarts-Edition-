<?php
include 'db.php';
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// --- Session Timeout Check ---
$timeout_duration = 10; // 10 seconds for testing

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    echo "<script>
        alert('Session expired due to inactivity. You have been logged out.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

// Update timestamp only if not expired
$_SESSION['LAST_ACTIVITY'] = time();

// --- Auth Check ---
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();

// Fetch user info
$username = $_SESSION['username'];
$stmt = $conn->prepare("
    SELECT r.*, l.username, r.profile_image
    FROM registers r
    JOIN logins l ON r.id = l.register_id
    WHERE l.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$profile_image = $user['profile_image'] ? base64_encode($user['profile_image']) : null;

// Deletion helper
function deleteById($conn, $table, $column, $idKey) {
    if (isset($_GET[$idKey])) {
        $id = (int)$_GET[$idKey];
        $stmt = $conn->prepare("DELETE FROM $table WHERE $column = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php");
        exit();
    }
}

// Handle deletes
deleteById($conn, 'students', 'id', 'delete');
deleteById($conn, 'subjects', 'subject_id', 'delete_subject');
deleteById($conn, 'courses', 'course_id', 'delete_course');

// Fetch tables
$result_students = $conn->query("
    SELECT s.*, c.course_code
    FROM students s
    JOIN courses c ON s.course = c.course_id
");
$result_subjects = $conn->query("
    SELECT sub.*, c.course_code
    FROM subjects sub
    JOIN courses c ON sub.course_code = c.course_code
");
$result_courses = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="dashboard.css">
    <script>
         $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });

    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // JavaScript timeout matching PHP (10 seconds for testing)
    let inactivityTime = function () {
        let warningTimer;
        let logoutTimer;
        const warningDuration = 10000; // 10 seconds

        function showWarning() {
            let stayLoggedIn = confirm("You have been inactive. Do you want to stay logged in?");
            if (stayLoggedIn) {
                resetTimers();
            } else {
                window.location.href = 'logout.php';
            }
        }

        function resetTimers() {
            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);
            warningTimer = setTimeout(showWarning, warningDuration);
        }

        window.onload = resetTimers;
        document.onmousemove = resetTimers;
        document.onkeydown = resetTimers;
        document.onclick = resetTimers;
        document.onscroll = resetTimers;
    };

    inactivityTime();
    </script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: rgba(60, 63, 65, 0.85);">
    <a class="navbar-brand" href="#" style="color: rgba(255, 204, 0, 0.9);">Dashboard</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                    <?php if ($profile_image): ?>
                        <img src="data:image/jpeg;base64,<?php echo $profile_image; ?>" alt="Profile Image" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 5px;">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($user['username']); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="update_profile.php">Update Profile</a>
                    <a class="dropdown-item" href="logout.php">Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div class="container-xl">

    <!-- Student Table -->
    <div class="table-responsive">
        <div class="table-wrapper">
            <div class="table-title">
                <div class="row">
                    <div class="col-sm-8"><h2>Student <b>Details</b></h2></div>
                    <div class="col-sm-4">
                        <a href="add_student.php" class="btn add-student-btn">Add Student</a>
                    </div>
                </div>
            </div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course Code</th>
                        <th>Year Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                        <td>
                            <a href="view_student.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="view" title="View" data-toggle="tooltip"><i class="material-icons">&#xE417;</i></a>
                            <a href="update_student.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="edit" title="Edit" data-toggle="tooltip"><i class="material-icons">&#xE254;</i></a>
                            <a href="dashboard.php?delete=<?php echo htmlspecialchars($row['id']); ?>" class="delete" title="Delete" data-toggle="tooltip"><i class="material-icons">&#xE872;</i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Subject Table -->
    <div class="table-responsive mt-5">
        <div class="table-wrapper">
            <div class="table-title">
                <div class="row">
                    <div class="col-sm-8"><h2>Subject <b>Details</b></h2></div>
                    <div class="col-sm-4">
                        <a href="add_subject.php" class="btn add-student-btn">Add Subject</a>
                    </div>
                </div>
            </div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Subject ID</th>
                        <th>Course Code</th>
                        <th>Subject Detail</th>
                        <th>Units</th>
                        <th>Lab</th>
                        <th>Lecture</th>
                        <th>Pre Requisite</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($subject = $result_subjects->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($subject['subject_id']); ?></td>
                        <td><?php echo htmlspecialchars($subject['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($subject['subject_detail']); ?></td>
                        <td><?php echo htmlspecialchars($subject['units']); ?></td>
                        <td><?php echo htmlspecialchars($subject['lab']); ?></td>
                        <td><?php echo htmlspecialchars($subject['lecture']); ?></td>
                        <td><?php echo htmlspecialchars($subject['pre_requisite']); ?></td>
                        <td><?php echo htmlspecialchars($subject['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                        <td>
                            <a href="update_subject.php?id=<?php echo htmlspecialchars($subject['subject_id']); ?>" class="edit" title="Edit" data-toggle="tooltip"><i class="material-icons">&#xE254;</i></a>
                            <a href="dashboard.php?delete_subject=<?php echo htmlspecialchars($subject['subject_id']); ?>" class="delete" title="Delete" data-toggle="tooltip"><i class="material-icons">&#xE872;</i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Course Table -->
    <div class="table-responsive mt-5">
        <div class="table-wrapper">
            <div class="table-title">
                <div class="row">
                    <div class="col-sm-8"><h2>Course <b>Details</b></h2></div>
                    <div class="col-sm-4">
                        <a href="add_course.php" class="btn add-student-btn">Add Course</a>
                    </div>
                </div>
            </div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Program</th>
                        <th>Course Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($course = $result_courses->fetch_assoc()): ?>
                    <tr>
                        
                        <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                        <td><?php echo htmlspecialchars($course['program']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                        <td>
                            <a href="update_course.php?id=<?php echo htmlspecialchars($course['course_id']); ?>" class="edit" title="Edit" data-toggle="tooltip"><i class="material-icons">&#xE254;</i></a>
                            <a href="dashboard.php?delete_course=<?php echo htmlspecialchars($course['course_id']); ?>" class="delete" title="Delete" data-toggle="tooltip"><i class="material-icons">&#xE872;</i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>

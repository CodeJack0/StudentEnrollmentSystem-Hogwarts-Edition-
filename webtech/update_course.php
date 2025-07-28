<?php
include 'db.php';

session_start(); 

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Only allow admin and faculty
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'faculty'])) {
    header("Location: unauthorized.php"); // You can customize this page
    exit();
}

$conn = getDbConnection();

$errors = []; // For collecting error messages

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_course'])) {
    $course_id = intval($_POST['course_id']);
    $program = htmlspecialchars($_POST['program']);
    $course_code = htmlspecialchars($_POST['course_code']); // Get course code

    $stmt = $conn->prepare("UPDATE courses SET program = ?, course_code = ? WHERE course_id = ?");
    $stmt->bind_param("ssi", $program, $course_code, $course_id);
    
    if ($stmt->execute()) {
        // Redirect to dashboard on successful update
        header("Location: dashboard.php");
        exit();
    } else {
        // Handle error case
        $errors[] = "Error updating course: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch course details
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT program, course_code FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($program, $course_code);
$stmt->fetch();
$stmt->close();

// If no course is found, redirect to dashboard
if (!$program) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Update Course</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="update.css"> <!-- Link to your custom CSS -->
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="form-container">
                <h2 class="text-center mb-4">Update Course</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Update Course Form -->
                <form action="" method="POST">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                    <div class="form-group">
                        <label for="program">Program Name</label>
                        <input type="text" class="form-control" id="program" name="program" value="<?php echo htmlspecialchars($program); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="course_code">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo htmlspecialchars($course_code); ?>" required>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" name="update_course" class="btn btn-primary">Update Course</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
include 'db.php';

session_start(); 

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();

// Initialize variables
$program = "";
$course_code = "";
$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $program = trim($_POST['program']);
    $course_code = trim($_POST['course_code']);

    // Validate inputs
    if (empty($program)) {
        $errors[] = "Program name is required.";
    }
    if (empty($course_code)) {
        $errors[] = "Course code is required.";
    }

    // Insert into database if no errors
    if (count($errors) == 0) {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO courses (program, course_code) VALUES (?, ?)");
        $stmt->bind_param("ss", $program, $course_code);
        
        if ($stmt->execute()) {
            // Redirect back to dashboard after adding
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Error adding course: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Course</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="add_student.css"> <!-- Link to your custom CSS -->
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="form-container">
                <h2 class="text-center mb-4">Add New Course</h2>

                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="add_course.php" method="POST">
                    <div class="form-group">
                        <label for="program">Program Name</label>
                        <input type="text" class="form-control" id="program" name="program" value="<?php echo htmlspecialchars($program); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="course_code">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo htmlspecialchars($course_code); ?>" required>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-custom">Add Course</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>

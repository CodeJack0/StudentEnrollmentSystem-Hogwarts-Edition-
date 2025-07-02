<?php
include 'db.php';

session_start(); 

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = getDbConnection();

// Initialize variables
$fullname = $course = $year_level = "";
$errors = [];

// Fetch courses for dropdown
$courses = [];
$result_courses = $conn->query("SELECT course_id, course_code FROM courses");
if ($result_courses) {
    while ($row = $result_courses->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $fullname = trim($_POST['fullname']);
    $course = $_POST['course']; // Get course_id from dropdown
    $year_level = $_POST['year_level'];

    // Validate inputs
    if (empty($fullname)) {
        $errors[] = "Full Name is required.";
    }
    if (empty($course)) {
        $errors[] = "Course is required.";
    }
    if (empty($year_level)) {
        $errors[] = "Year Level is required.";
    }

    // Generate a unique student ID if no errors
    if (count($errors) == 0) {
        // Generate student ID based on the last inserted ID + 1
        $result = $conn->query("SELECT MAX(student_id) AS last_id FROM students");
        $row = $result->fetch_assoc();
        $last_id = $row['last_id'];
        $student_id = $last_id ? $last_id + 1 : 100001; // Start from 100001 if no students exist

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO students (student_id, fullname, course, year_level) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $student_id, $fullname, $course, $year_level);
        $stmt->execute();

        // Redirect back to dashboard after adding
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Student</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="add_student.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="form-container">
                <h2 class="text-center mb-4">Add New Student</h2>

                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="add_student.php" method="POST">
                    <!-- CSRF token field -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="course">Course</label>
                        <select class="form-control" id="course" name="course" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course_item): ?>
                                <option value="<?php echo htmlspecialchars($course_item['course_id']); ?>" <?php if ($course == $course_item['course_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($course_item['course_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select class="form-control" id="year_level" name="year_level" required>
                            <option value="1" <?php if ($year_level == '1') echo 'selected'; ?>>1st Year</option>
                            <option value="2" <?php if ($year_level == '2') echo 'selected'; ?>>2nd Year</option>
                            <option value="3" <?php if ($year_level == '3') echo 'selected'; ?>>3rd Year</option>
                            <option value="4" <?php if ($year_level == '4') echo 'selected'; ?>>4th Year</option>
                        </select>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-custom">Add Student</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>

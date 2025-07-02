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
$course_code = $subject_detail = $units = $lab = $lecture = $pre_requisite = $year_level = $semester = "";
$errors = [];

// Retrieve available course codes from the database
$courses = [];
$sql = "SELECT course_code FROM courses"; // Adjust table name as needed
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row['course_code']; // Store course codes in an array
    }
} else {
    $errors[] = "Error retrieving course codes: " . htmlspecialchars($conn->error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_code = trim($_POST['course_code']);
    $subject_detail = trim($_POST['subject_detail']);
    $units = (int)$_POST['units'];
    $lab = (int)$_POST['lab'];
    $lecture = (int)$_POST['lecture'];
    $pre_requisite = trim($_POST['pre_requisite']);
    $year_level = $_POST['year_level'];
    $semester = $_POST['semester'];

    // Validate inputs
    if (empty($course_code)) {
        $errors[] = "Course Code is required.";
    }
    if (empty($subject_detail)) {
        $errors[] = "Subject Detail is required.";
    }
    if ($units <= 0) {
        $errors[] = "Units must be a positive integer.";
    }
    if ($lab < 0) {
        $errors[] = "Lab hours cannot be negative.";
    }
    if ($lecture < 0) {
        $errors[] = "Lecture hours cannot be negative.";
    }
    if (empty($year_level)) {
        $errors[] = "Year Level is required.";
    }
    if (empty($semester)) {
        $errors[] = "Semester is required.";
    }

    // Insert into database if no errors
    if (count($errors) == 0) {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO subjects (course_code, subject_detail, units, lab, lecture, pre_requisite, year_level, semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiisss", $course_code, $subject_detail, $units, $lab, $lecture, $pre_requisite, $year_level, $semester);
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
    <title>Add Subject</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="add_subject.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="form-container">
                <h2 class="text-center mb-4">Add New Subject</h2>

                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="add_subject.php" method="POST">
                    <div class="form-group">
                        <label for="course_code">Course Code</label>
                        <select class="form-control" id="course_code" name="course_code" required>
                            <option value="">Select Course Code</option>
                            <?php foreach ($courses as $code): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>" <?php if ($course_code == $code) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($code); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject_detail">Subject Detail</label>
                        <input type="text" class="form-control" id="subject_detail" name="subject_detail" value="<?php echo htmlspecialchars($subject_detail); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="units">Units</label>
                        <input type="number" class="form-control" id="units" name="units" value="<?php echo htmlspecialchars($units); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lab">Lab Hours</label>
                        <input type="number" class="form-control" id="lab" name="lab" value="<?php echo htmlspecialchars($lab); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lecture">Lecture Hours</label>
                        <input type="number" class="form-control" id="lecture" name="lecture" value="<?php echo htmlspecialchars($lecture); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="pre_requisite">Pre-requisite</label>
                        <input type="text" class="form-control" id="pre_requisite" name="pre_requisite" value="<?php echo htmlspecialchars($pre_requisite); ?>">
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select class="form-control" id="year_level" name="year_level" required>
                            <option value="1" <?php if ($year_level == '1') echo 'selected'; ?>>1st Year</option>
                            <option value="2" <?php if ($year_level == '2') echo 'selected'; ?>>2nd Year</option>
                            <option value="3" <?php if ($year_level == '3') echo 'selected'; ?>>3rd Year</option>
                            <option value="4" <?php if ($year_level == '4') echo 'selected'; ?>>4th Year</option>
                            <option value="5" <?php if ($year_level == '5') echo 'selected'; ?>>5th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="1" <?php if ($semester == '1') echo 'selected'; ?>>First Semester</option>
                            <option value="2" <?php if ($semester == '2') echo 'selected'; ?>>Second Semester</option>
                        </select>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-custom">Add Subject</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>

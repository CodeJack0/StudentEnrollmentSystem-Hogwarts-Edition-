<?php
include 'db.php';

session_start(); 

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = getDbConnection();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_subject'])) {
    $id = intval($_POST['id']);
    $subject_detail = htmlspecialchars($_POST['subject_detail']);
    $course_code = htmlspecialchars($_POST['course']);
    $year_level = htmlspecialchars($_POST['year_level']);
    $units = intval($_POST['units']);
    $lab = intval($_POST['lab']);
    $lecture = intval($_POST['lecture']);
    $pre_requisite = htmlspecialchars($_POST['pre_requisite']);
    $semester = htmlspecialchars($_POST['semester']);
    
    $stmt = $conn->prepare("UPDATE subjects SET subject_detail = ?, course_code = ?, year_level = ?, units = ?, lab = ?, lecture = ?, pre_requisite = ?, semester = ? WHERE subject_id = ?");
    $stmt->bind_param("sssiiissi", $subject_detail, $course_code, $year_level, $units, $lab, $lecture, $pre_requisite, $semester, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: dashboard.php");
    exit();
}

// Fetch subject details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT subject_detail, course_code, year_level, units, lab, lecture, pre_requisite, semester FROM subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($subject_detail, $course_code, $year_level, $units, $lab, $lecture, $pre_requisite, $semester);
    $stmt->fetch();
    $stmt->close();
}

// Fetch courses for dropdown
$courses = [];
$result_courses = $conn->query("SELECT course_code FROM courses");
if ($result_courses) {
    while ($row = $result_courses->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Update Subject</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="update.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="form-container">
                <h2 class="text-center mb-4">Update Subject</h2>

                <!-- Update Subject Form -->
                <form action="" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                    <div class="form-group">
                        <label for="subject_detail">Subject Detail</label>
                        <input type="text" class="form-control" id="subject_detail" name="subject_detail" value="<?php echo htmlspecialchars($subject_detail); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="course">Course</label>
                        <select class="form-control" id="course" name="course" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course_item): ?>
                                <option value="<?php echo htmlspecialchars($course_item['course_code']); ?>" <?php if ($course_code == $course_item['course_code']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($course_item['course_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select class="form-control" id="year_level" name="year_level" required>
                            <option value="">Select Year Level</option>
                            <option value="1" <?php if ($year_level == '1') echo 'selected'; ?>>1st Year</option>
                            <option value="2" <?php if ($year_level == '2') echo 'selected'; ?>>2nd Year</option>
                            <option value="3" <?php if ($year_level == '3') echo 'selected'; ?>>3rd Year</option>
                            <option value="4" <?php if ($year_level == '4') echo 'selected'; ?>>4th Year</option>
                            <option value="5" <?php if ($year_level == '5') echo 'selected'; ?>>5th Year</option>
                        </select>
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
                        <label for="pre_requisite">Pre-Requisite</label>
                        <input type="text" class="form-control" id="pre_requisite" name="pre_requisite" value="<?php echo htmlspecialchars($pre_requisite); ?>">
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="1" <?php if ($semester == '1') echo 'selected'; ?>>1st Semester</option>
                            <option value="2" <?php if ($semester == '2') echo 'selected'; ?>>2nd Semester</option>
                        </select>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" name="update_subject" class="btn btn-primary">Update Subject</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>

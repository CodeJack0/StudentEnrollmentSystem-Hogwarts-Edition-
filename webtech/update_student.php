<?php
include 'db.php';
include 'aes.php'; // Add this to use aes_encrypt/decrypt

session_start(); 

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Only allow admin and faculty
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'faculty'])) {
    header("Location: unauthorized.php"); // Create this page if you want a custom access denied message
    exit();
}

$conn = getDbConnection();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $id = intval($_POST['id']);
    $fullname = aes_encrypt(htmlspecialchars($_POST['fullname'])); // Encrypt before update
    $course = htmlspecialchars($_POST['course']);
    $year_level = htmlspecialchars($_POST['year_level']);
    
    $stmt = $conn->prepare("UPDATE students SET fullname = ?, course = ?, year_level = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullname, $course, $year_level, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: dashboard.php");
    exit();
}

// Fetch student details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT fullname, course, year_level FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($encrypted_fullname, $course, $year_level);
    $stmt->fetch();
    $stmt->close();

    $fullname = aes_decrypt($encrypted_fullname); // Decrypt for display
}

// Fetch courses for dropdown
$courses = [];
$result_courses = $conn->query("SELECT course_id, course_code FROM courses");
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
    <title>Update Student</title>
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
                <h2 class="text-center mb-4">Update Student</h2>

                <!-- Update Student Form -->
                <form action="" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
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
                            <option value="">Select Year Level</option>
                            <option value="1" <?php if ($year_level == '1') echo 'selected'; ?>>1st Year</option>
                            <option value="2" <?php if ($year_level == '2') echo 'selected'; ?>>2nd Year</option>
                            <option value="3" <?php if ($year_level == '3') echo 'selected'; ?>>3rd Year</option>
                            <option value="4" <?php if ($year_level == '4') echo 'selected'; ?>>4th Year</option>
                            <option value="5" <?php if ($year_level == '5') echo 'selected'; ?>>5th Year</option>
                        </select>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// Database connection (you may want to put this in a separate file)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'School_Management';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch teacher-specific data
$teacher_id = $_SESSION['user_id'];
$upcoming_exams = $conn->query("SELECT * FROM exams WHERE teacher_id = $teacher_id AND exam_date > CURDATE() ORDER BY exam_date LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_results = $conn->query("SELECT * FROM exam_results WHERE teacher_id = $teacher_id ORDER BY submission_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 p-4 text-white">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Teacher Dashboard</h1>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Upcoming Exams</h2>
                <?php if (empty($upcoming_exams)): ?>
                    <p>No upcoming exams scheduled.</p>
                <?php else: ?>
                    <ul class="space-y-2">
                        <?php foreach ($upcoming_exams as $exam): ?>
                            <li><?php echo $exam['exam_name']; ?> - <?php echo $exam['exam_date']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Recent Results</h2>
                <?php if (empty($recent_results)): ?>
                    <p>No recent exam results available.</p>
                <?php else: ?>
                    <ul class="space-y-2">
                        <?php foreach ($recent_results as $result): ?>
                            <li><?php echo $result['exam_name']; ?> - Submitted on <?php echo $result['submission_date']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="upload_results.php" class="block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Upload Results</a>
                    <a href="grade_exams.php" class="block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Grade Exams</a>
                    <a href="view_student_performance.php" class="block bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">View Student Performance</a>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Announcements</h2>
                <ul class="space-y-2">
                    <li>Remember to submit grades for last week's quiz by Friday.</li>
                    <li>Parent-teacher conferences scheduled for next month.</li>
                    <li>New grading rubric available for download in the resources section.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
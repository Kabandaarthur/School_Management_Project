<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
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

// Fetch some basic stats
$total_students = $conn->query("SELECT COUNT(*) FROM students ")->fetch_row()[0];
$total_teachers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_exams = $conn->query("SELECT COUNT(*) FROM exams")->fetch_row()[0];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Admin Dashboard</h1>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Total Students</h2>
                <p class="text-3xl font-bold text-blue-600"><?php echo $total_students; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Total Teachers</h2>
                <p class="text-3xl font-bold text-green-600"><?php echo $total_teachers; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Total Exams</h2>
                <p class="text-3xl font-bold text-purple-600"><?php echo $total_exams; ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="manage_students.php" class="block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Manage Students</a>
                    <a href="manage_teachers.php" class="block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Manage Teachers</a>
                    <a href="manage_subjects.php" class="block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Manage Subjects</a>
                    <a href="manage_exams.php" class="block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Manage Exams</a>
                    <a href="view_reports.php" class="block bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">View Reports</a>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
                <ul class="space-y-2">
                    <li>New exam created: Mathematics Final</li>
                    <li>User account created: John Doe (Teacher)</li>
                    <li>Exam results uploaded: Science Midterm</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
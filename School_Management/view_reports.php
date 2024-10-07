<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'School_Management';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all classes
$classes_query = "SELECT id, name, grade_level, capacity FROM classes";
$classes_result = $conn->query($classes_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">View Reports</h1>
            <a href="admin_dashboard.php" class="bg-blue-500 hover:bg-blue-700 px-4 py-2 rounded">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <h2 class="text-2xl font-bold mb-4">Classes</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($class = $classes_result->fetch_assoc()) : ?>
                <a href="class_results.php?class_id=<?php echo $class['id']; ?>" class="block">
                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($class['name']); ?></h3>
                        <p class="text-gray-600">Grade Level: <?php echo htmlspecialchars($class['grade_level']); ?></p>
                        <p class="text-gray-600">Capacity: <?php echo htmlspecialchars($class['capacity']); ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
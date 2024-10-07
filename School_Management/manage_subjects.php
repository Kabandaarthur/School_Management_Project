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

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_class':
                $name = $conn->real_escape_string($_POST['class_name']);
                $grade_level = intval($_POST['grade_level']);
                $capacity = intval($_POST['capacity']);
                
                 
                $sql = "INSERT INTO classes (name, grade_level, capacity) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $name, $grade_level, $capacity);
                
                if ($stmt->execute()) {
                    $message = "New class added successfully";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
                break;

            case 'add_subject':
                $name = $conn->real_escape_string($_POST['subject_name']);
                $code = $conn->real_escape_string($_POST['subject_code']);
                $description = $conn->real_escape_string($_POST['subject_description']);
                
                $sql = "INSERT INTO subjects (name, code, description) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $name, $code, $description);
                
                if ($stmt->execute()) {
                    $message = "New subject added successfully";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
                break;

            case 'assign_subject':
                $class_id = intval($_POST['class_id']);
                $subject_id = intval($_POST['subject_id']);
                
                $check_sql = "SELECT * FROM class_subjects WHERE class_id = ? AND subject_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $class_id, $subject_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $message = "This subject is already assigned to the selected class.";
                    $messageType = "warning";
                } else {
                    $sql = "INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $class_id, $subject_id);
                    
                    if ($stmt->execute()) {
                        $message = "Subject assigned to class successfully";
                        $messageType = "success";
                    } else {
                        $message = "Error: " . $stmt->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
                $check_stmt->close();
                break;
        }
    }
}

// Fetch all classes and subjects
$classes = $conn->query("SELECT * FROM classes ORDER BY grade_level, name");
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes and Subjects - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Manage Classes and Subjects</h1>
            <a href="admin_dashboard.php" class="bg-blue-500 hover:bg-blue-700 px-4 py-2 rounded">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Add Class Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Add New Class</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_class">
                    <div class="mb-4">
                        <label for="class_name" class="block mb-2">Class Name</label>
                        <input type="text" id="class_name" name="class_name" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="grade_level" class="block mb-2">Grade Level</label>
                        <input type="text" id="grade_level" name="grade_level" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="capacity" class="block mb-2">Capacity</label>
                        <input type="number" id="capacity" name="capacity" class="w-full p-2 border rounded" required>
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Add Class</button>
                </form>
            </div>

            <!-- Add Subject Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Add New Subject</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_subject">
                    <div class="mb-4">
                        <label for="subject_name" class="block mb-2">Subject Name</label>
                        <input type="text" id="subject_name" name="subject_name" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="subject_code" class="block mb-2">Subject Code</label>
                        <input type="text" id="subject_code" name="subject_code" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="subject_description" class="block mb-2">Description</label>
                        <textarea id="subject_description" name="subject_description" class="w-full p-2 border rounded" rows="3"></textarea>
                    </div>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Add Subject</button>
                </form>
            </div>
        </div>

        <!-- Assign Subject to Class Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mt-8">
            <h2 class="text-xl font-semibold mb-4">Assign Subject to Class</h2>
            <form method="POST">
                <input type="hidden" name="action" value="assign_subject">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="class_id" class="block mb-2">Class</label>
                        <select name="class_id" id="class_id" class="w-full p-2 border rounded" required>
                            <?php while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="subject_id" class="block mb-2">Subject</label>
                        <select name="subject_id" id="subject_id" class="w-full p-2 border rounded" required>
                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2">&nbsp;</label>
                        <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">Assign Subject to Class</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md mt-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Classes and Their Subjects</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $classes->data_seek(0); // Reset the classes result pointer
            while ($class = $classes->fetch_assoc()):
                $class_id = $class['id'];
                $class_subjects = $conn->query("SELECT s.name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE cs.class_id = $class_id");
            ?>
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm hover:shadow-md transition duration-300">
                    <h3 class="text-xl font-semibold mb-2 text-blue-600">
                        <?php echo htmlspecialchars($class['name']); ?>
                        <span class="text-sm font-normal text-gray-600">(Grade <?php echo $class['grade_level']; ?>)</span>
                    </h3>
                    <p class="text-gray-600 mb-2">Capacity: <span class="font-medium"><?php echo $class['capacity']; ?> students</span></p>
                    <div class="mt-4">
                        <h4 class="text-lg font-medium mb-2 text-gray-700">Subjects:</h4>
                        <?php if ($class_subjects->num_rows > 0): ?>
                            <ul class="space-y-1">
                                <?php while ($subject = $class_subjects->fetch_assoc()): ?>
                                    <li class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-gray-500 italic">No subjects assigned yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    </div>
</body>
</html>

<?php
$conn->close();
?>
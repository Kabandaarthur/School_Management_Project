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

// Fetch all classes
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

// Add new student
if (isset($_POST['add_student'])) {
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $age = $conn->real_escape_string($_POST['age']);
    $class = $conn->real_escape_string($_POST['class']);

    $sql = "INSERT INTO students (firstname, lastname, gender, age, class) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $firstname, $lastname, $gender, $age, $class);

    if ($stmt->execute()) {
        $message = "Student added successfully.";
    } else {
        $message = "Error adding student: " . $conn->error;
    }
    $stmt->close();
}

// Delete student
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Student deleted successfully.";
    } else {
        $message = "Error deleting student: " . $conn->error;
    }
    $stmt->close();
}

// Update student
if (isset($_POST['update_student'])) {
    $id = (int)$_POST['id'];
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $class = $conn->real_escape_string($_POST['class']);

    $sql = "UPDATE students SET firstname = ?, lastname = ?, class = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $firstname, $lastname, $class, $id);

    if ($stmt->execute()) {
        $message = "Student updated successfully.";
    } else {
        $message = "Error updating student: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all students
$students = $conn->query("SELECT id, firstname, lastname, gender, age, class FROM students ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">Manage Students</h1>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Add Student Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Student</h2>
            <form action="" method="POST">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="firstname" class="block mb-2">Firstname</label>
                        <input type="text" id="firstname" name="firstname" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label for="lastname" class="block mb-2">Lastname</label>
                        <input type="text" id="lastname" name="lastname" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label for="gender" class="block mb-2">Gender</label>
                        <select id="gender" name="gender" required class="w-full px-3 py-2 border rounded">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label for="age" class="block mb-2">Age</label>
                        <input type="number" id="age" name="age" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label for="class" class="block mb-2">Class</label>
                        <select id="class" name="class" required class="w-full px-3 py-2 border rounded">
                            <option value="">Select Class</option>
                            <?php while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_student" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Student</button>
            </form>
        </div>

        <!-- Students List -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Students List</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">FirstName</th>
                            <th class="py-2 px-4 border-b">LastName</th>
                            <th class="py-2 px-4 border-b">Gender</th>
                            <th class="py-2 px-4 border-b">Age</th>
                            <th class="py-2 px-4 border-b">Class</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?php echo $student['id']; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo $student['firstname']; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo $student['lastname']; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo $student['gender']; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo $student['age']; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo $student['class']; ?></td>
                                <td class="py-2 px-4 border-b">
                                    <button onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 mr-2">Update</button>
                                    <a href="?delete=<?php echo $student['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?')" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Student Modal -->
    <div id="updateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Update Student</h3>
            <form id="updateForm" method="POST">
                <input type="hidden" id="update_id" name="id">
                <div class="mb-4">
                    <label for="update_firstname" class="block mb-2">FirstName</label>
                    <input type="text" id="update_firstname" name="firstname" required class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label for="update_lastname" class="block mb-2">Last Name</label>
                    <input type="text" id="update_lastname" name="lastname" required class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label for="update_class" class="block mb-2">Class</label>
                    <select id="update_class" name="class" required class="w-full px-3 py-2 border rounded">
                        <?php 
                        $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mt-4">
                    <button type="submit" name="update_student" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Student</button>
                    <button type="button" onclick="closeUpdateModal()" class="ml-2 bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUpdateModal(student) {
            document.getElementById('update_id').value = student.id;
            document.getElementById('update_firstname').value = student.firstname;
            document.getElementById('update_lastname').value = student.lastname;
            document.getElementById('update_class').value = student.class;
            document.getElementById('updateModal').classList.remove('hidden');
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
        }
    </script>
</body>
</html>
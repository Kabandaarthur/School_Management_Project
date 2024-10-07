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

$notification = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_class_teacher':
                $teacher_id = $_POST['teacher_id'];
                $class_id = $_POST['class_id'];

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Update the class with the new class teacher
                    $stmt = $conn->prepare("UPDATE classes SET class_teacher_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $teacher_id, $class_id);
                    $stmt->execute();

                    // Fetch all subjects for this class
                    $subjects = $conn->query("SELECT id FROM subjects WHERE class_id = $class_id");

                    // Assign the teacher to all subjects of this class
                    $stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, class_id, subject_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)");
                    while ($subject = $subjects->fetch_assoc()) {
                        $subject_id = $subject['id'];
                        $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);
                        $stmt->execute();
                    }

                    // Commit transaction
                    $conn->commit();
                    $notification = "Class teacher assigned successfully and added to all subjects of the class.";
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $notification = "Error assigning class teacher: " . $e->getMessage();
                }
                break;

            case 'assign_subject_teacher':
                $teacher_id = $_POST['teacher_id'];
                $class_id = $_POST['class_id'];
                $subject_id = $_POST['subject_id'];

                $stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, class_id, subject_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)");
                $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);

                if ($stmt->execute()) {
                    $notification = "Subject teacher assigned successfully.";
                } else {
                    $notification = "Error assigning subject teacher: " . $conn->error;
                }
                break;

            case 'remove_subject_teacher':
                $teacher_id = $_POST['teacher_id'];
                $class_id = $_POST['class_id'];
                $subject_id = $_POST['subject_id'];

                $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND class_id = ? AND subject_id = ?");
                $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                exit();
        }
    }
}

// Fetch all teachers
$teachers = $conn->query("SELECT * FROM users WHERE user_type = 'teacher'");

// Fetch classes
$classes = $conn->query("SELECT c.*, u.username AS class_teacher_name 
                         FROM classes c 
                         LEFT JOIN users u ON c.class_teacher_id = u.id");

// Fetch subjects
$subjects = $conn->query("SELECT * FROM subjects");

// Fetch teacher assignments (classes and subjects)
$assignments_query = "
    SELECT ts.teacher_id, u.username AS teacher_name, c.id AS class_id, c.name AS class_name, s.id AS subject_id, s.name AS subject_name
    FROM teacher_subjects ts
    JOIN users u ON ts.teacher_id = u.id
    JOIN classes c ON ts.class_id = c.id
    JOIN subjects s ON ts.subject_id = s.id
    ORDER BY c.name, s.name, u.username
";
$assignments = $conn->query($assignments_query);

$teacher_assignments = [];
while ($row = $assignments->fetch_assoc()) {
    $class_id = $row['class_id'];
    $subject_id = $row['subject_id'];
    if (!isset($teacher_assignments[$class_id])) {
        $teacher_assignments[$class_id] = [
            'name' => $row['class_name'],
            'subjects' => []
        ];
    }
    if (!isset($teacher_assignments[$class_id]['subjects'][$subject_id])) {
        $teacher_assignments[$class_id]['subjects'][$subject_id] = [
            'name' => $row['subject_name'],
            'teacher' => $row['teacher_name'],
            'teacher_id' => $row['teacher_id']
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Manage Teachers</h1>

        <!-- Notification -->
        <?php if ($notification): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $notification; ?></span>
            </div>
        <?php endif; ?>

        <!-- Assign Class Teacher Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4">Assign Class Teacher</h2>
            <form method="POST">
                <input type="hidden" name="action" value="assign_class_teacher">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="teacher_id" class="block mb-2">Teacher</label>
                        <select name="teacher_id" id="teacher_id" class="w-full p-2 border rounded" required>
                            <?php
                            $teachers->data_seek(0);
                            while ($teacher = $teachers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['username']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="class_id" class="block mb-2">Class</label>
                        <select name="class_id" id="class_id" class="w-full p-2 border rounded" required>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Assign Class Teacher</button>
                </div>
            </form>
        </div>

        <!-- Assign Subject Teacher Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4">Assign Subject Teacher</h2>
            <form method="POST">
                <input type="hidden" name="action" value="assign_subject_teacher">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="subject_teacher_id" class="block mb-2">Teacher</label>
                        <select name="teacher_id" id="subject_teacher_id" class="w-full p-2 border rounded" required>
                            <?php
                            $teachers->data_seek(0);
                            while ($teacher = $teachers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['username']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="subject_class_id" class="block mb-2">Class</label>
                        <select name="class_id" id="subject_class_id" class="w-full p-2 border rounded" required>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="subject_id" class="block mb-2">Subject</label>
                        <select name="subject_id" id="subject_id" class="w-full p-2 border rounded" required>
                            <?php
                            $subjects->data_seek(0);
                            while ($subject = $subjects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo $subject['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Assign Subject Teacher</button>
                </div>
            </form>
        </div>

        <!-- Class Teachers and Subject Assignments Section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Class Teachers and Subject Assignments</h2>
            <?php foreach ($teacher_assignments as $class_id => $class_data): ?>
                <div class="mb-6 border-b pb-4">
                    <h3 class="text-lg font-semibold mb-2"><?php echo $class_data['name']; ?></h3>
                    <p class="mb-2">
                        <strong>Class Teacher:</strong> 
                        <?php
                        $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()) {
                            if ($class['id'] == $class_id) {
                                echo $class['class_teacher_name'] ?? 'Not assigned';
                                break;
                            }
                        }
                        ?>
                    </p>
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Subject</th>
                                <th class="py-3 px-6 text-left">Teacher</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php foreach ($class_data['subjects'] as $subject_id => $subject_data): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo $subject_data['name']; ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo $subject_data['teacher']; ?></td>
                                    <td class="py-3 px-6 text-center">
                                        <button class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded remove-subject-teacher"
                                                data-teacher-id="<?php echo $subject_data['teacher_id']; ?>"
                                                data-class-id="<?php echo $class_id; ?>"
                                                data-subject-id="<?php echo $subject_id; ?>">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('.remove-subject-teacher').click(function() {
            var button = $(this);
            var teacherId = button.data('teacher-id');
            var classId = button.data('class-id');
            var subjectId = button.data('subject-id');

            $.ajax({
                url: 'manage_teachers.php',
                method: 'POST',
                data: {
                    action: 'remove_subject_teacher',
                    teacher_id: teacherId,
                    class_id: classId,
                    subject_id: subjectId
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Error removing subject teacher: ' + result.error);
                    }
                },
                error: function() {
                    alert('An error occurred while trying to remove the subject teacher.');
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>
<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// Database connection
function getDbConnection() {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'School_Management';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$teacher_id = $_SESSION['user_id'];
$conn = getDbConnection();

// Fetch classes taught by the teacher
$classes_query = "SELECT DISTINCT c.id, c.name, c.grade_level 
                  FROM classes c
                  JOIN teacher_subjects ts ON c.id = ts.class_id
                  WHERE ts.teacher_id = ?";
$stmt = $conn->prepare($classes_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes_result = $stmt->get_result();

$selected_class = null;
$subjects = [];
$students = [];
$exam_types = [];

// Fetch active exam types
$exam_types_query = "SELECT id, exam_type FROM exams WHERE is_active = 1";
$exam_types_result = $conn->query($exam_types_query);
while ($row = $exam_types_result->fetch_assoc()) {
    $exam_types[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['class_id'])) {
        $selected_class = $_POST['class_id'];
        
        // Fetch subjects taught by the teacher for the selected class
        $subjects_query = "SELECT s.id AS subject_id, s.name AS subject_name 
                           FROM subjects s
                           JOIN teacher_subjects ts ON s.id = ts.subject_id
                           WHERE ts.teacher_id = ? AND ts.class_id = ?";
        $stmt = $conn->prepare($subjects_query);
        $stmt->bind_param("ii", $teacher_id, $selected_class);
        $stmt->execute();
        $subjects_result = $stmt->get_result();
        $subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);

        // Fetch students for the selected class
        $students_query = "SELECT id, firstname, lastname FROM students WHERE class = ?";
        $stmt = $conn->prepare($students_query);
        $stmt->bind_param("i", $selected_class);
        $stmt->execute();
        $students_result = $stmt->get_result();
        $students = $students_result->fetch_all(MYSQLI_ASSOC);
    }
    
    if (isset($_POST['submit_results'])) {
        $subject_id = $_POST['subject_id'];
        $exam_name = $_POST['exam_name'];
        $exam_type = $_POST['exam_type'];
        
        // Insert exam details
        $insert_exam_query = "INSERT INTO exams (exam_name, teacher_id, class_id, subject_id, exam_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_exam_query);
        $stmt->bind_param("siiii", $exam_name, $teacher_id, $selected_class, $subject_id, $exam_type);
        $stmt->execute();
        $exam_id = $stmt->insert_id;
        
        // Insert student results
        $insert_result_query = "INSERT INTO exam_results (exam_id, student_id, marks) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_result_query);
        
        foreach ($_POST['marks'] as $student_id => $marks) {
            $stmt->bind_param("iii", $exam_id, $student_id, $marks);
            $stmt->execute();
        }
        
        echo "<p class='text-green-600 font-bold'>Results uploaded successfully!</p>";
    }

    if (isset($_POST['edit_mark'])) {
        $result_id = $_POST['result_id'];
        $new_mark = $_POST['new_mark'];
        
        $update_query = "UPDATE exam_results SET marks = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $new_mark, $result_id);
        $stmt->execute();
        
        echo "<p class='text-green-600 font-bold'>Mark updated successfully!</p>";
    }

    if (isset($_POST['delete_mark'])) {
        $result_id = $_POST['result_id'];
        
        $delete_query = "DELETE FROM exam_results WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $result_id);
        $stmt->execute();
        
        echo "<p class='text-green-600 font-bold'>Mark deleted successfully!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results - School Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 p-4 text-white">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Upload Results</h1>
            <a href="teacher_dashboard.php" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8">
        <form method="POST" class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Select Class</h2>
            <select name="class_id" onchange="this.form.submit()" class="w-full p-2 border rounded mb-4">
                <option value="">Select a class</option>
                <?php while ($class = $classes_result->fetch_assoc()): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>>
                        <?php echo $class['name'] . ' (Grade ' . $class['grade_level'] . ')'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <?php if ($selected_class && !empty($subjects) && !empty($students) && !empty($exam_types)): ?>
        <form method="POST" class="bg-white p-6 rounded-lg shadow-md mt-6">
            <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
            
            <h2 class="text-xl font-semibold mb-4">Upload Results</h2>
            
            <div class="mb-4">
                <label for="subject_id" class="block mb-2">Select Subject:</label>
                <select name="subject_id" id="subject_id" required class="w-full p-2 border rounded">
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>"><?php echo $subject['subject_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="exam_name" class="block mb-2">Exam Name:</label>
                <input type="text" name="exam_name" id="exam_name" required class="w-full p-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label for="exam_type" class="block mb-2">Exam Type:</label>
                <select name="exam_type" id="exam_type" required class="w-full p-2 border rounded">
                    <?php foreach ($exam_types as $exam_type): ?>
                        <option value="<?php echo $exam_type['id']; ?>"><?php echo $exam_type['exam_type']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h3 class="text-lg font-semibold mb-2">Enter Student Marks:</h3>
            <?php foreach ($students as $student): ?>
                <div class="mb-2 flex items-center">
                    <label for="marks_<?php echo $student['id']; ?>" class="inline-block w-48">
                        <?php echo $student['firstname'] . ' ' . $student['lastname']; ?>:
                    </label>
                    <input type="number" name="marks[<?php echo $student['id']; ?>]" id="marks_<?php echo $student['id']; ?>" 
                           required min="0" max="100" class="w-24 p-2 border rounded">
                    <button type="button" onclick="editMark(<?php echo $student['id']; ?>)" class="ml-2 bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">Edit</button>
                    <button type="button" onclick="deleteMark(<?php echo $student['id']; ?>)" class="ml-2 bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">Delete</button>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" name="submit_results" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded mt-4">Submit Results</button>
        </form>
        <?php elseif ($selected_class): ?>
            <p class="text-red-600 mt-4">No subjects, students, or active exam types found for this class. Please contact the administrator.</p>
        <?php endif; ?>
    </div>

    <script>
    function editMark(studentId) {
        const markInput = document.getElementById(`marks_${studentId}`);
        const newMark = prompt("Enter new mark:", markInput.value);
        if (newMark !== null) {
            markInput.value = newMark;
        }
    }

    function deleteMark(studentId) {
        if (confirm("Are you sure you want to delete this mark?")) {
            document.getElementById(`marks_${studentId}`).value = "";
        }
    }
    </script>
</body>
</html>

<?php
// Close the database connection at the end of the script
$conn->close();
?>
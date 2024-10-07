<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if class_id is provided
if (!isset($_GET['class_id'])) {
    header("Location: view_reports.php");
    exit();
}

$class_id = $_GET['class_id'];

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'School_Management';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get class details
$class_query = "SELECT name, grade_level FROM classes WHERE id = ?";
$class_stmt = $conn->prepare($class_query);
$class_stmt->bind_param("i", $class_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class = $class_result->fetch_assoc();

// Get all subjects assigned to this class
$subjects_query = "SELECT s.id AS subject_id, s.name AS subject_name
                   FROM class_subjects cs
                   JOIN subjects s ON cs.subject_id = s.id
                   WHERE cs.class_id = ?
                   ORDER BY s.name";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $class_id);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();

$subjects = [];
while ($subject = $subjects_result->fetch_assoc()) {
    $subjects[$subject['subject_id']] = $subject['subject_name'];
}

// Get all students and their results
$students_query = "SELECT s.id AS student_id, s.firstname, s.lastname,
                   e.subject_id, er.marks
                   FROM students s
                   LEFT JOIN exam_results er ON s.id = er.student_id
                   LEFT JOIN exams e ON er.exam_id = e.id
                   WHERE s.class = ?
                   ORDER BY s.lastname, s.firstname";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $class_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

$student_results = [];
while ($row = $students_result->fetch_assoc()) {
    $student_id = $row['student_id'];

    // Initialize student if not already in the array
    if (!isset($student_results[$student_id])) {
        $student_results[$student_id] = [
            'name' => $row['firstname'] . ' ' . $row['lastname'],
            'marks' => array_fill_keys(array_keys($subjects), '-'), // Fill subjects with default '-'
            'total' => 0
        ];
    }

    // Assign marks for the subject if available
    if ($row['subject_id'] && isset($subjects[$row['subject_id']])) {
        $marks_obtained = $row['marks'] ?? '-';
        if ($marks_obtained !== '-') { // Only update if marks exist
            $student_results[$student_id]['marks'][$row['subject_id']] = $marks_obtained;
        }
    }
}

// Calculate total marks for each student
foreach ($student_results as &$student) {
    $student['total'] = array_sum(array_map(function($mark) {
        return is_numeric($mark) ? $mark : 0;
    }, $student['marks']));
}

// Sort students by total marks
usort($student_results, function ($a, $b) {
    return $b['total'] - $a['total'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['name']); ?> - Exam Results</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .print-section {
                position: absolute;
                left: 0;
                top: 0;
            }
            .no-print {
                display: none;
            }
            @page {
                size: landscape;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white no-print">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($class['name']); ?> (Grade <?php echo htmlspecialchars($class['grade_level']); ?>) - Exam Results</h1>
            <div>
                <button onclick="window.print()" class="bg-green-500 hover:bg-green-700 px-4 py-2 rounded mr-2">Print Report</button>
                <a href="view_reports.php" class="bg-blue-500 hover:bg-blue-700 px-4 py-2 rounded">Back to Classes</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 overflow-x-auto print-section">
        <h2 class="text-2xl font-bold mb-4 text-center"><?php echo htmlspecialchars($class['name']); ?> (Grade <?php echo htmlspecialchars($class['grade_level']); ?>) - Exam Results</h2>
        <?php if (empty($student_results)): ?>
            <p class="text-center text-xl mt-8">No students found for this class.</p>
        <?php else: ?>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2">Rank</th>
                        <th class="border border-gray-300 px-4 py-2">Name</th>
                        <?php foreach ($subjects as $subject): ?>
                            <th class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($subject); ?></th>
                        <?php endforeach; ?>
                        <th class="border border-gray-300 px-4 py-2">Total</th>
                        <th class="border border-gray-300 px-4 py-2 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($student_results as $student_id => $student): 
                    ?>
                        <tr class="hover:bg-gray-100">
                            <td class="border border-gray-300 px-4 py-2"><?php echo $rank++; ?></td>
                            <td class="border border-gray-300 px-4 py-2 font-medium"><?php echo htmlspecialchars($student['name']); ?></td>
                            <?php foreach ($subjects as $subject_id => $subject): ?>
                                <td class="border border-gray-300 px-4 py-2 text-center"><?php echo htmlspecialchars($student['marks'][$subject_id]); ?></td>
                            <?php endforeach; ?>
                            <td class="border border-gray-300 px-4 py-2 font-bold text-center"><?php echo $student['total']; ?></td>
                            <td class="border border-gray-300 px-4 py-2 no-print text-center">
                            <a href="student_report_card.php?student_id=<?php echo $student_id; ?>&class_id=<?php echo $class_id; ?>" class="text-red-500 hover:text-red-700 font-bold text-sm inline-block">View Report</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php $conn->close(); ?>
</body>
</html>

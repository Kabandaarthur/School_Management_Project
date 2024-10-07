<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if student_id and class_id are provided
if (!isset($_GET['student_id']) || !isset($_GET['class_id'])) {
    header("Location: view_reports.php");
    exit();
}

$student_id = $_GET['student_id'];
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

// Get student details
$student_query = "SELECT firstname, lastname, class FROM students WHERE id = ?";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

// Check if student exists
if (!$student) {
    die("Error: Student not found. Please check the student ID.");
}

// Get class details
$class_query = "SELECT name, grade_level FROM classes WHERE id = ?";
$class_stmt = $conn->prepare($class_query);
$class_stmt->bind_param("i", $class_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class = $class_result->fetch_assoc();

// Check if class exists
if (!$class) {
    die("Error: Class not found. Please check the class ID.");
}

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

// Check if subjects are assigned to the class
if (empty($subjects)) {
    die("Error: No subjects assigned to this class.");
}

// Get student's results with teacher information
$results_query = "SELECT e.subject_id, er.marks, t.username AS username
                  FROM exam_results er
                  JOIN exams e ON er.exam_id = e.id
                  LEFT JOIN users t ON e.teacher_id = t.id
                  WHERE er.student_id = ? AND e.class_id = ?";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("ii", $student_id, $class_id);
$results_stmt->execute();
$results_result = $results_stmt->get_result();

$student_marks = array_fill_keys(array_keys($subjects), [
    'marks' => '-',
    'grade' => '-',
    'teacher' => '-'
]);

while ($row = $results_result->fetch_assoc()) {
    if (isset($subjects[$row['subject_id']])) {
        $marks = $row['marks'] ?? '-';
        $student_marks[$row['subject_id']]['marks'] = $marks;
        $student_marks[$row['subject_id']]['teacher'] = $row['username'];
        if (is_numeric($marks)) {
            $student_marks[$row['subject_id']]['grade'] = getGrade($marks);
        }
    }
}

// Function to calculate grade
function getGrade($marks) {
    if ($marks >= 80) return 'D1';
    if ($marks >= 75) return 'D2';
    if ($marks >= 70) return 'C3';
    if ($marks >= 65) return 'C4';
    if ($marks >= 60) return 'C5';
    if ($marks >= 50) return 'C6';
    if ($marks >= 40) return 'P7';
    if ($marks >= 30) return 'P8';
    return 'F9';
}

// Function to get aggregate points
function getAggregatePoints($grade) {
    $points = ['D1' => 1, 'D2' => 2, 'C3' => 3, 'C4' => 4, 'C5' => 5, 'C6' => 6, 'P7' => 7, 'P8' => 8, 'F9' => 9];
    return isset($points[$grade]) ? $points[$grade] : 10;
}

// Function to get remarks based on grade
function getRemarks($grade) {
    switch ($grade) {
        case 'D1':
        case 'D2':
            return 'Excellent';
        case 'C3':
        case 'C4':
            return 'Very Good';
        case 'C5':
        case 'C6':
            return 'Good';
        case 'P7':
        case 'P8':
            return 'Fair';
        case 'F9':
            return 'Poor';
        default:
            return '-';
    }
}

// Calculate aggregates
$subject_aggregates = [];
foreach ($student_marks as $subject_id => $data) {
    if ($data['grade'] !== '-') {
        $subject_aggregates[$subject_id] = getAggregatePoints($data['grade']);
    }
}

// Sort subjects by aggregate points and take the best 8
asort($subject_aggregates);
$best_subjects = array_slice($subject_aggregates, 0, 8, true);

// Calculate total aggregates
$total_aggregates = array_sum($best_subjects);

// Check for F9 in Mathematics or English
$math_subject_id = array_search('Mathematics', $subjects);
$english_subject_id = array_search('English', $subjects);

$demoted = false;
if (($math_subject_id && isset($student_marks[$math_subject_id]) && $student_marks[$math_subject_id]['grade'] === 'F9') ||
    ($english_subject_id && isset($student_marks[$english_subject_id]) && $student_marks[$english_subject_id]['grade'] === 'F9')) {
    $demoted = true;
}

// Function to determine the division
function getDivision($aggregates, $subject_count) {
    if ($subject_count < 8) return 'U';
    if ($aggregates <= 32) return 'I';
    if ($aggregates <= 48) return 'II';
    if ($aggregates <= 64) return 'III';
    if ($aggregates <= 72) return 'IV';
    return 'U';
}

$division = getDivision($total_aggregates, count($best_subjects));

// Adjust division if demoted
if ($demoted) {
    $divisions = ['I', 'II', 'III', 'IV', 'U'];
    $current_index = array_search($division, $divisions);
    $division = isset($divisions[$current_index + 1]) ? $divisions[$current_index + 1] : 'U';
}

// Get all students' total marks to calculate rank
$all_students_query = "SELECT s.id, SUM(er.marks) as total_marks
                       FROM students s
                       LEFT JOIN exam_results er ON s.id = er.student_id
                       LEFT JOIN exams e ON er.exam_id = e.id
                       WHERE s.class = ?
                       GROUP BY s.id
                       ORDER BY total_marks DESC";
$all_students_stmt = $conn->prepare($all_students_query);
$all_students_stmt->bind_param("i", $class_id);
$all_students_stmt->execute();
$all_students_result = $all_students_stmt->get_result();

$rank = 1;
while ($row = $all_students_result->fetch_assoc()) {
    if ($row['id'] == $student_id) {
        break;
    }
    $rank++;
}

// Count total students in the class
$total_students_query = "SELECT COUNT(*) as total_students FROM students WHERE class = ?";
$total_students_stmt = $conn->prepare($total_students_query);
$total_students_stmt->bind_param("i", $class_id);
$total_students_stmt->execute();
$total_students_result = $total_students_stmt->get_result();
$total_students_row = $total_students_result->fetch_assoc();
$total_students = $total_students_row['total_students'];

// HTML output starts here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report Card</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
                line-height: 1.2;
            }
            .print-section {
                margin: 0;
                padding: 0;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .no-print {
                display: none;
            }
            table {
                font-size: 12px;
            }
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .header p, .header h6, .header h1, .header h5 {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body class="bg-white">
    <div class="container mx-auto mt-1 p-1 bg-white shadow-lg print-section">
        <div class="header">
            <h1 class="text-xl font-bold mb-2 text-center">MPIGI HIGH SCHOOL</h1>
            <h6 class="text-center">School Motto: <strong>"WITH GOD EVERYTHING IS POSSIBLE"</strong></h6>
            <h5 class="text-center">P.O BOX. 212 Mpigi | www.mpigihighschool.com | Tel: +256-706686901</h5>
            <p class="text-center font-bold">LEARNERS END OF TERM REPORT CARD 2024</p> <br>
            <hr style="height: 4px; background-color: #000; border: none;">
        </div>
        <div class="mb-4">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($class['name'] . ' (Grade ' . $class['grade_level'] . ')'); ?></p>
        </div>
        <table class="w-full border-collapse border border-gray-300 mb-4">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2">SUBJECT</th>
                    <th class="border border-gray-300 px-4 py-2">E.O.T</th>
                    <th class="border border-gray-300 px-4 py-2">GRADE</th>
                    <th class="border border-gray-300 px-4 py-2">REMARKS</th>
                    <th class="border border-gray-300 px-4 py-2">TEACHER</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject_id => $subject_name): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($subject_name); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($student_marks[$subject_id]['marks']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($student_marks[$subject_id]['grade']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars(getRemarks($student_marks[$subject_id]['grade'])); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($student_marks[$subject_id]['teacher']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="flex justify-between">
            <div style="width: 200px; border: 2px solid; background-color: #f0f0f0; padding: 10px;">
                <strong>AGGREGATES:</strong> 
                <?php 
                echo (count($best_subjects) == 8) ? $total_aggregates : 'U';
                if ($demoted) echo ' (Demoted)';
                ?>
            </div>
            <div style="width: 200px;  border: 2px solid; background-color: #f0f0f0; padding: 10px;">
                <strong>POSITION: <?php echo $rank . ' out of ' . $total_students; ?></strong>
            </div>
            <div style="width: 200px;  border: 2px solid ; background-color: #f0f0f0; padding: 10px;">
                <strong>DIVISION:</strong> <?php echo $division; ?>
            </div>
        </div>
        <div class="mt-4">
            <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p> <br>
            <p><strong>Class Teacher's Signature:</strong> _________________________</p> 
            <p><strong>Head Teacher's Signature:</strong> _________________________</p>
            <p><strong>Next Term Begins:</strong> _________________________</p>
        </div> <br>
        <hr style="height: 4px; background-color: #000; border: none;">
        <p class="text-center font-bold">"With God Everything Is Possible"</p>
    </div>
    <div class="container mx-auto mt-2 text-center no-print">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Print Report Card
        </button>
    </div>
    <?php $conn->close(); ?>
</body>
</html>

<?php
function getOverallDivision($student_marks) {
    $total_points = 0;
    $subject_count = 0;
    foreach ($student_marks as $subject) {
        if ($subject['grade'] !== '-') {
            $total_points += getPoints($subject['grade']);
            $subject_count++;
        }
    }
    if ($subject_count === 0) return '-';
    $average_points = $total_points / $subject_count;
    if ($average_points <= 2) return 'I';
    if ($average_points <= 3) return 'II';
    if ($average_points <= 6) return 'III';
    if ($average_points <= 7) return 'IV';
    return 'U';
}

function getPoints($grade) {
    switch ($grade) {
        case 'D1': return 1;
        case 'D2': return 2;
        case 'C3': return 3;
        case 'C4': return 4;
        case 'C5': return 5;
        case 'C6': return 6;
        case 'P7': return 7;
        case 'P8': return 8;
        case 'F9': return 9;
        default: return 0;
    }
}
?>
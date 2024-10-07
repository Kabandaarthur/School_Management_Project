<?php
// Include database connection
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

// Initialize variables
$exam_type = '';
$exam_date = '';
$is_active = 1; // Active by default

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    $exam_type = $_POST['exam_type'];
    $exam_date = $_POST['exam_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Insert into database
    $sql = "INSERT INTO exams (exam_type, exam_date,  is_active) VALUES ( ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $exam_type,  $exam_date, $is_active);
    $stmt->execute();
    $stmt->close();
}

// Toggle status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $id = $_POST['id'];
    $is_active = $_POST['is_active'] == 1 ? 0 : 1;

    // Update status
    $sql = "UPDATE exams SET is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $is_active, $id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_exam'])){
   $id = $_POST['id'];

   //delete the exam type
   $sql = "DELETE FROM exams WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt-> bind_param("i", $id);
   $stmt->execute();
    $stmt->close();
}
// Fetch existing exams
$result = $conn->query("SELECT * FROM exams");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams</title>
    <style>
        /* CSS Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: #333;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"], input[type="date"], input[type="time"], input[type="number"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }
        .action-buttons button{
            margin-right: 4px;
        }
        .action-buttons button.delete {
            background-color: #dc3545;
        }
        .action-buttons button.delete:hover{
            background-color: #c82333;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Manage Exams</h1>

    <!-- Form to add new exam -->
    <form method="POST" action="">
        <div>
            <label for="exam_type">Exam Type:</label>
            <input type="text" id="exam_type" name="exam_type" required>
        </div>
        
        <div>
            <label for="exam_date">Exam Date:</label>
            <input type="date" id="exam_date" name="exam_date" required>
        </div>
        
        <div>
            <label for="is_active">Active:</label>
            <input type="checkbox" id="is_active" name="is_active" checked>
        </div>
        <button type="submit" name="add_exam">Add Exam</button>
    </form>

    <!-- List of existing exams -->
    <h2>Existing Exams</h2>
    <table>
        <thead>
            <tr>
                <th>Exam Type</th>
                <th>Exam Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
                <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="is_active" value="<?php echo $row['is_active']; ?>">
                        <button type="submit" name="toggle_status">
                            <?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                    <form action="" method="POST" style="display: inline-block;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_exam", class="delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    // JavaScript if needed for additional interactivity
    // For now, the JS handles no specific functionality
</script>

</body>
</html>


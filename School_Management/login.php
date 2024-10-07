<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'School_Management';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        // Registration logic
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $user_type = $conn->real_escape_string($_POST['user_type']);

        if ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Username already exists";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $insert_stmt = $conn->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $username, $hashed_password, $user_type);

                if ($insert_stmt->execute()) {
                    $success = "Registration successful. You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }

                $insert_stmt->close();
            }

            $check_stmt->close();
        }
    } else {
        // Login logic (unchanged)
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, user_type FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];

                $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                logLoginAttempt($user['id'], true, $conn);

                if ($user['user_type'] == 'teacher') {
                    header("Location: teacher_dashboard.php");
                } else {
                    header("Location: admin_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }

        logLoginAttempt(null, false, $conn);

        $stmt->close();
    }
}

function logLoginAttempt($user_id, $success, $conn) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO login_attempts (user_id, ip_address, success) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $user_id, $ip_address, $success);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Exam Management System - Login/Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, button, select {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }
        .toggle-form {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>School Exam Management System</h1>
        <?php
        if (!empty($error)) {
            echo "<p class='error'>$error</p>";
        }
        if (!empty($success)) {
            echo "<p class='success'>$success</p>";
        }
        ?>
        <div id="loginForm">
            <h2>Login</h2>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <div class="toggle-form">
                <p>Don't have an account? <a href="#" onclick="toggleForm('registerForm')">Register</a></p>
            </div>
        </div>
        <div id="registerForm" style="display: none;">
            <h2>Register</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <select name="user_type" required>
                    <option value="">Select User Type</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit">Register</button>
            </form>
            <div class="toggle-form">
                <p>Already have an account? <a href="#" onclick="toggleForm('loginForm')">Login</a></p>
            </div>
        </div>
    </div>
    <script>
        function toggleForm(formId) {
            document.getElementById('loginForm').style.display = formId === 'loginForm' ? 'block' : 'none';
            document.getElementById('registerForm').style.display = formId === 'registerForm' ? 'block' : 'none';
        }
    </script>
</body>
</html>
<?php
session_start();

// Show errors for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
$host = "localhost";
$user = "root";
$password = "";
$database = "trackables";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate random user ID
function random_num($length = 7) {
    $text = "";
    if ($length < 5) $length = 5;
    $len = rand(4, $length);
    for ($i = 0; $i < $len; $i++) {
        $text .= rand(0, 9);
    }
    return $text;
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (!empty($email) && !empty($phone) && !empty($password)) {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo "<script>alert('This email is already registered. Please log in or use another email.'); window.history.back();</script>";
        } else {
            $user_id = random_num(7);
            $password = $_POST['password'] ?? '';
            $HashedPass = password_hash($password, PASSWORD_DEFAULT);


            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (User_id, Email, Password, Phone_No) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_id, $email, $HashedPass, $phone);

            if ($stmt->execute()) {
                echo "<script>alert('Sign-up successful! Please log in.'); window.location.href='../signIn.html';</script>";
                exit();
            } else {
                echo "<script>alert('Error saving user: " . $stmt->error . "'); window.history.back();</script>";
            }

            $stmt->close();
        }

        $check_stmt->close();
    } else {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
    }
}

$conn->close();
?>

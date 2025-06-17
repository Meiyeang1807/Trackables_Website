<?php
session_start();

// Connect to database
$host = "localhost";
$user = "root";
$password = "";
$database = "trackables";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted and required fields exist
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query user by email
    $sql = "SELECT * FROM users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify hashed password
            if (password_verify($password, $row['Password'])) {
            $_SESSION['user_id'] = $row['User_id'];
            $_SESSION['email'] = $row['Email'];
            $_SESSION['phone'] = $row['Phone_No'];
            $_SESSION['profile_pic'] = $row['profile_pic'] ?? null;

            // Redirect to homepage
            header("Location: ../homepage.php");
            exit();
        } else {
            // Show incorrect password popup
            echo "<script>alert('Incorrect password.'); window.history.back();</script>";
        }
    } else {
        // Show email not registered popup
        echo "<script>alert('Email not registered.'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Please enter both email and password.'); window.history.back();</script>";
}

$conn->close();
?>
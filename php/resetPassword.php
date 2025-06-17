<?php

session_start();

$host = "localhost";
$user = "root";
$password = "";
$database = "trackables";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

echo "<script>console.log('Email: $email, Phone: $phone, New Password: $newPassword, Confirm Password: $confirmPassword');</script>";

// if (empty($email) || empty($phone) || empty($newPassword) || empty($confirmPassword)) {
//     echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
//     exit();
// }

if ($newPassword !== $confirmPassword) {
    echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
    exit();
}

$sql = "SELECT * FROM users WHERE Email = ? AND Phone_No = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
print_r($user);
if (!$result) {
    die("Query failed: " . $conn->error);
}

if ($result->num_rows === 1) {
    $Password = $newPassword;
    $updateSql = "UPDATE users SET Password = ? WHERE Email = ? AND Phone_No = ?";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        die("Update prepare failed: " . $conn->error);
    }
    
    $HashedPass = password_hash($Password, PASSWORD_DEFAULT);
    $updateStmt->bind_param("sss", $HashedPass, $email, $phone);

    if ($updateStmt->execute()) {
        echo "<script>alert('Password reset successfully! Please log in.'); window.location.href = '../signIn.html';</script>";
    } else {
        echo "<script>alert('Error updating password: " . $updateStmt->error . "'); window.history.back();</script>";

    }
    $updateStmt->close();
} else {
    echo "<script>alert('No matching user found.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>
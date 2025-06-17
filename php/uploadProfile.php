<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../signIn.html");
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$database = "trackables";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for building update query
$fields = [];
$params = [];
$types = "";

$user_id = $_SESSION['user_id'];
$newEmail = isset($_POST['newEmail']) ? trim($_POST['newEmail']) : '';
$newPhone = isset($_POST['newPhone']) ? trim($_POST['newPhone']) : '';
$profilePicFilename = null;

// ======== HANDLE PROFILE PICTURE =========
if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid("profile_", true) . '.' . $extension;
    $targetPath = $uploadDir . $uniqueName;

    if (move_uploaded_file($_FILES['profilePic']['tmp_name'], $targetPath)) {
        $profilePicFilename = $uniqueName;
    } else {
        echo "<script>alert('Failed to upload profile picture.'); window.history.back();</script>";
        exit();
    }
}

// ======== CHECK FOR DUPLICATE EMAIL =========
if (!empty($newEmail)) {
    $checkEmail = $conn->prepare("SELECT * FROM user WHERE Email = ? AND User_id != ?");
    $checkEmail->bind_param("si", $newEmail, $user_id);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists.'); window.history.back();</script>";
        exit();
    }
    $checkEmail->close();
}

// ======== BUILD UPDATE QUERY =========
if (!empty($newEmail)) {
    $fields[] = "Email = ?";
    $params[] = $newEmail;
    $types .= "s";
}
if (!empty($newPhone)) {
    $fields[] = "Phone_No = ?";
    $params[] = $newPhone;
    $types .= "s";
}
if ($profilePicFilename !== null) {
    $fields[] = "Profile_Pic = ?";
    $params[] = $profilePicFilename;
    $types .= "s";
}

if (count($fields) > 0) {
    $params[] = $user_id;
    $types .= "i";

    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE User_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Update session variables
        if (!empty($newEmail)) $_SESSION['email'] = $newEmail;
        if (!empty($newPhone)) $_SESSION['phone'] = $newPhone;
        if ($profilePicFilename !== null) $_SESSION['profile_pic'] = $profilePicFilename;

        // Set message and redirect
        $_SESSION['redirect_msg'] = "Profile updated successfully.";
        header("Location: ../profilesection.php");
        exit();
    } else {
        echo "<script>alert('Update failed.'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('No changes made.'); window.history.back();</script>";
}

$conn->close();
?>

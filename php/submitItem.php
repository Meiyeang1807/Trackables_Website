<?php
session_start();
if (!isset($_SESSION['email'])) {
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

// Determine item type from the button pressed
if (isset($_POST['lost'])) {
    $item_type = 'lost';
} elseif (isset($_POST['found'])) {
    $item_type = 'found';
} else {
    die("Invalid submission.");
}

// Get current user ID based on email
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['User_id'];

// Get form data
$item_name = $_POST['item_name'];
$date = $_POST['date'];
$location = $_POST['location'];
$description = $_POST['description'];
$image_path = "";

// Handle image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "../uploads/posts/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $image_path = "uploads/posts/" . basename($_FILES["image"]["name"]);
    }
}

// Prepare insert query based on item type
if ($item_type === 'lost') {
    $query = "INSERT INTO lost_items (user_id, item_name, date, location, description, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
} else {
    $query = "INSERT INTO found_items (user_id, item_name, date, location, description, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("isssss", $user_id, $item_name, $date, $location, $description, $image_path);

if ($stmt->execute()) {
    header("Location: ../" . $item_type . ".php");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>

<?php
session_start();
include("db_connection.php");

if (!isset($_SESSION['email'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$senderEmail = $_SESSION['email'];
$receiverEmail = $_POST['receiver_email'] ?? '';
$message = trim($_POST['message'] ?? '');

if ($receiverEmail && $message) {
    // Get sender and receiver IDs
    $stmt1 = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
    $stmt1->bind_param("s", $senderEmail);
    $stmt1->execute();
    $senderId = $stmt1->get_result()->fetch_assoc()['user_id'] ?? null;

    $stmt2 = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
    $stmt2->bind_param("s", $receiverEmail);
    $stmt2->execute();
    $receiverId = $stmt2->get_result()->fetch_assoc()['User_id'] ?? null;

    if ($senderId && $receiverId) {
        $insert = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, NOW())");
        $insert->bind_param("iis", $senderId, $receiverId, $message);
        $insert->execute();
    }
}
?>

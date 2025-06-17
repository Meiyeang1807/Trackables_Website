<?php
session_start();
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $found_id = $_POST['found_id'] ?? null;
    $claimer_email = $_SESSION['email'] ?? null;
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $matric_number = $_POST['matric_number'] ?? '';
    $receiver_email = $_POST['receiver_email'] ?? null;

    if (!$claimer_email || !$found_id || !$receiver_email) {
        die("Missing required data.");
    }

    // Get claimer user_id
    $stmt = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
    $stmt->bind_param("s", $claimer_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $claimer_id = $userData['User_id'] ?? null;

    if (!$claimer_id) {
        die("User not found.");
    }

    // Get receiver user_id
    $stmt2 = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
    $stmt2->bind_param("s", $receiver_email);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $receiverData = $result2->fetch_assoc();
    $receiver_id = $receiverData['User_id'] ?? null;

    if (!$receiver_id) {
        die("Receiver user not found.");
    }

    // Prevent duplicate claims
    $dupCheck = $conn->prepare("SELECT claim_id FROM claim WHERE found_id = ? AND claimer_id = ?");
    $dupCheck->bind_param("ii", $found_id, $claimer_id);
    $dupCheck->execute();
    $dupResult = $dupCheck->get_result();

    if ($dupResult->num_rows > 0) {
    echo "<script>
        alert('⚠️ You’ve already submitted a claim for this item.');
        window.location.href = '../chatSection.php?receiver=" . urlencode($receiver_email) . "&found_id=" . intval($found_id) . "';
    </script>";
    exit();
    }


    // Insert into claim table
    $insertStmt = $conn->prepare("
        INSERT INTO claim (found_id, claimer_id, full_name, email, phone_number, matric_number)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->bind_param("iissss", $found_id, $claimer_id, $full_name, $email, $phone_number, $matric_number);

    if ($insertStmt->execute()) {
        // Build system message string
        // Get item details
        $itemStmt = $conn->prepare("SELECT item_name, location FROM found_items WHERE found_id = ?");
        $itemStmt->bind_param("i", $found_id);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result()->fetch_assoc();
        $itemName = $itemResult['item_name'] ?? 'Unknown Item';
        $itemLocation = $itemResult['location'] ?? 'Unknown Location';

        $systemMessage = "CLAIM_SUBMITTED|$full_name|$email|$phone_number|$matric_number|$itemName|$itemLocation|$found_id";


        // Insert system message into chat_messages
        $msgStmt = $conn->prepare("
            INSERT INTO chat_messages (sender_id, receiver_id, message)
            VALUES (?, ?, ?)
        ");
        $msgStmt->bind_param("iis", $claimer_id, $receiver_id, $systemMessage);
        $msgStmt->execute();

        // Redirect back to chatSection.php with success flag
        $receiver = urlencode($receiver_email);
        $found_id = intval($found_id);
        $full_name = urlencode($full_name);
        $phone = urlencode($phone_number);
        $matric = urlencode($matric_number);

        header("Location: ../chatSection.php?receiver=$receiver&found_id=$found_id&review=1&name=$full_name&phone=$phone&matric=$matric");
        exit();

    } else {
        die("Error inserting claim: " . $conn->error);
    }
} else {
    // Invalid access
    header("Location: ../chatSection.php");
    exit();
}
?>

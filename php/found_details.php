<?php
session_start();
include('db_connection.php');

$found_id = $_GET['found_id'] ?? null;
$receiver = isset($_GET['receiver']) ? urlencode($_GET['receiver']) : '';
$found_id = isset($_GET['found_id']) ? intval($_GET['found_id']) : 0;

if (!$found_id) {
    echo "Item not found.";
    exit;
}

$stmt = $conn->prepare("
    SELECT fi.item_name, fi.location, fi.description, fi.image_path, u.email 
    FROM found_items fi
    JOIN users u ON fi.User_id = u.User_id
    WHERE fi.found_id = ?
");
$stmt->bind_param("i", $found_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    echo "No item details available.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .item-container {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .item-container img {
            max-width: 100%;
            border-radius: 6px;
        }
        .item-container h2 {
            margin-top: 0;
        }
        .item-container p {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <a href="../chatSection.php?receiver=<?php echo $receiver; ?>&found_id=<?php echo $found_id; ?>" 
   style="display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #102b3d; color: white; text-decoration: none; border-radius: 5px;">
   ← Back to Chat
    </a>
    <div class="item-container">
        <h2><?php echo htmlspecialchars($item['email']); ?></h2>
        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Item Image" width="300">
        <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['item_name']); ?></p>
        <p><strong>Location Found:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
        <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
    </div>
</body>
</html>

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

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!in_array($type, ['lost', 'found']) || $id <= 0) {
    die("Invalid request.");
}

$table = $type === 'lost' ? 'lost_items' : 'found_items';
$id_column = $type === 'lost' ? 'lost_id' : 'found_id';

$stmt = $conn->prepare("SELECT * FROM $table WHERE $id_column = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Post not found or permission denied.");
}

$post = $result->fetch_assoc();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Update even if fields are blank
    $update_stmt = $conn->prepare("UPDATE $table SET item_name = ?, location = ?, description = ? WHERE $id_column = ? AND user_id = ?");
    $update_stmt->bind_param("sssii", $item_name, $location, $description, $id, $user_id);

    if ($update_stmt->execute()) {
        $_SESSION['redirect_msg'] = "Post updated successfully!";
        header("Location: ../profilesection.php");
        exit();
    } else {
        $error = "Failed to update post. Please try again.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit <?php echo ucfirst($type); ?> Post</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        .form-buttons {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
        }

        button, .cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button {
            background-color: #102b3d;
            color: white;
        }

        .cancel {
            background-color: #ccc;
            color: #333;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit <?php echo htmlspecialchars(ucfirst($type)); ?> Post</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="editPost.php?type=<?php echo urlencode($type); ?>&id=<?php echo urlencode($id); ?>">
        <label for="item_name">Item Name</label>
        <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($post['item_name']); ?>"/>

        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($post['location']); ?>" />

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($post['description']); ?></textarea>

        <div class="form-buttons">
            <button type="submit">Update Post</button>
            <a class="cancel" href="../profilesection.php">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
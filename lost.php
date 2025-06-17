<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: signIn.html");
    exit();
}
include("php/db_connection.php");

// --- Search logic ---
$searchTerm = $_GET['search'] ?? '';

$categoryKeywords = [
    'Card' => ['card', 'cards', 'id', 'matric', 'student', 'lesen', 'bank', 'credit', 'debit'],
    'Accessories' => ['accessory', 'accessories', 'watch', 'ring', 'cincin', 'gelang', 'rantai', 'jam'],
    'Keys' => ['key', 'keys', 'kunci', 'motor', 'kereta', 'house', 'rumah'],
    'Devices' => ['device', 'devices', 'phone', 'tablet', 'laptop', 'telephone', 'iphone', 'ipad', 'computer', 'telefon']
];

$conn = new mysqli("localhost", "root", "", "trackables");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$keywordsToSearch = [];
if (!empty($searchTerm)) {
    foreach ($categoryKeywords as $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($searchTerm, $keyword) !== false) {
                $keywordsToSearch = $keywords;
                break 2;
            }
        }
    }

    if (!empty($keywordsToSearch)) {
        $sql = "SELECT li.*, u.Email, u.Profile_pic 
                FROM lost_items li 
                JOIN users u ON li.User_id = u.User_id 
                WHERE ";
        $conditions = [];
        foreach ($keywordsToSearch as $kw) {
            $safe_kw = '%' . $conn->real_escape_string($kw) . '%';
            $conditions[] = "li.item_name LIKE '$safe_kw'";
        }
        $sql .= implode(" OR ", $conditions) . " ORDER BY li.created_at DESC";
    } else {
        $safeSearch = '%' . $conn->real_escape_string($searchTerm) . '%';
        $sql = "SELECT li.*, u.Email, u.Profile_pic 
                FROM lost_items li 
                JOIN users u ON li.User_id = u.User_id 
                WHERE li.item_name LIKE '$safeSearch' 
                   OR li.location LIKE '$safeSearch' 
                   OR li.description LIKE '$safeSearch'
                ORDER BY li.created_at DESC";
    }
} else {
    $sql = "SELECT li.*, u.Email, u.Profile_pic 
            FROM lost_items li 
            JOIN users u ON li.User_id = u.User_id 
            ORDER BY li.created_at DESC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost</title>
    <link rel="stylesheet" href="css/lost.css">
</head>
<body>
    <header>
        <nav class="top-nav">
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="create.php">Create</a></li>
                <li><a href="chatSection.php">Messages</a></li>
            </ul>
            <div class="profile">
                <span class="username"><?php echo $_SESSION['email']; ?></span>
                <a href="profilesection.php"><img src="image/profile.png" alt="Profile Icon" width="35"></a>
            </div>
        </nav>

        <div class="main-header">
            <a href="lost.php" class="logo-lost" style="text-decoration: none; color: inherit;">
                <img src="image/lost.png" alt="Logo" class="logo">
                <h2>LOST</h2>
            </a>
            <div class="search-actions">
                <form method="GET" action="lost.php" class="search-actions">
                    <input type="text" name="search" placeholder="Search by item or category" class="search-bar" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </div>
    </header>

    <main>
    <?php
    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
    ?>
    <div class="post-container">
        <div class="postprofile">
            <img src="<?php echo htmlspecialchars(!empty($row['profile_pic']) ? 'uploads/' . $row['profile_pic'] : 'image/postprofile.png'); ?>" 
                 alt="Post Profile" width="35" style="border-radius: 50%;">

            <span class="username"><strong><?php echo htmlspecialchars($row['Email']); ?></strong></span><br/>
            <span class="date"><?php echo date('M d Y, h:i A', strtotime($row['created_at'] ?? $row['date'])); ?></span><br/>
            <span class="desc"><strong>Item:</strong> <?php echo htmlspecialchars($row['item_name']); ?><br/></span>
            <span class="desc"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br/></span>
            <span class="desc"><?php echo htmlspecialchars($row['description']); ?></span>
        </div>

        <?php if (!empty($row['image_path'])): ?>
        <div class="lostpost">
            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="lost-image" alt="Lost Item">
        </div>
        <?php endif; ?>

        <?php if ($_SESSION['email'] !== $row['Email']): ?>
            <div class="claim-button-container">
                <form method="GET" action="chatSection.php">
                    <input type="hidden" name="receiver" value="<?php echo urlencode($row['Email']); ?>">
                    <button type="submit" class="claim-button">Message Owner</button>
                </form>
            </div>
        <?php endif; ?>

        <hr class="divider">
    </div>
    <?php endwhile;
    else:
        echo "<p style='text-align:center'>No lost items posted yet.</p>";
    endif;

    $conn->close();
    ?>
    </main>
</body>
</html>
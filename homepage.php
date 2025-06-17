<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: signIn.html");
    exit();
}

include("php/db_connection.php");

$selectedCategory = $_GET['category'] ?? null;
$searchQuery = $_GET['search'] ?? null;

$categoryKeywords = [
    'Card' => ['card', 'cards', 'id', 'matric', 'student', 'lesen', 'bank', 'credit', 'debit'],
    'Accessories' => ['accessory', 'accessories', 'watch', 'ring', 'cincin', 'gelang', 'rantai', 'jam'],
    'Keys' => ['key', 'keys', 'kunci', 'motor', 'kereta', 'house', 'rumah', 'bag'],
    'Devices' => ['device', 'devices', 'phone', 'tablet', 'laptop', 'telephone', 'iphone', 'ipad', 'computer', 'telefon']
];

$conditions = [];
$params = [];
$types = '';

if ($selectedCategory && isset($categoryKeywords[$selectedCategory])) {
    $keywords = $categoryKeywords[$selectedCategory];
    foreach ($keywords as $keyword) {
        $conditions[] = "fi.item_name LIKE ?";
        $params[] = '%' . $keyword . '%';
        $types .= 's';
    }
} elseif ($searchQuery) {
    $conditions[] = "fi.item_name LIKE ? OR fi.description LIKE ?";
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
    $types .= 'ss';
}

$sql = "SELECT fi.*, u.Email, u.Profile_pic 
        FROM found_items fi 
        JOIN users u ON fi.User_id = u.User_id";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" OR ", $conditions);
}

$sql .= " ORDER BY fi.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$posts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="css/homepage.css">
</head>
<body>
    <header>
        <nav class="top-nav">
            <ul class="nav-links">
                <li><a href="homepage.php" class="active">Home</a></li>
                <li><a href="create.php">Create</a></li>
                <li><a href="chatSection.php">Messages</a></li>
            </ul>
            <div class="profile">
                <span class="username"><?php echo $_SESSION['email']; ?></span>
                <a href="profilesection.php"><img src="image/profile.png" alt="Profile Icon" width="35"></a>
            </div>
        </nav>
    
        <div class="main-header">
            <div class="logo-title">
                <img src="image/logo-Trackables.png" alt="Logo" class="logo">
                <h2>Home</h2>
            </div>
    
            <div class="search-actions">
                <form method="GET" action="homepage.php" class="search-form">
                    <input type="text" name="search" placeholder="Search by item or category" class="search-bar" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit" class="search-btn">Search</button>
                    <a href="lost.php" class="btn lost-btn">
                    <img src="image/lost.png" alt="Lost" />
                    LOST
                    </a>
                    <a href="found.php" class="btn found-btn">
                    <img src="image/found.png" alt="Found" />
                    FOUND
                    </a>
                </form>
            </div>

        </div>
    </header>

    <main>
        <section class="categories">
            <h3>Categories</h3>
            <div class="category-list">
                <a href="homepage.php?category=Card" class="category">
                    <img src="image/card.png" alt="Card">
                    <span>CARDS</span>
                </a>
                <a href="homepage.php?category=Accessories" class="category">
                    <img src="image/accessories.png" alt="Accessories">
                    <span>ACCESSORIES</span>
                </a>
                <a href="homepage.php?category=Keys" class="category">
                    <img src="image/keyss.png" alt="Keys">
                    <span>KEYS</span>
                </a>
                <a href="homepage.php?category=Devices" class="category">
                    <img src="image/device.png" alt="Devices">
                    <span>DEVICES</span>
                </a>
            </div>
        </section>

        <hr class="divider">

        <section class="most-recent">
            <h2>
                <?php 
                    if ($selectedCategory) {
                        echo "Showing: " . htmlspecialchars($selectedCategory);
                    } elseif (!empty($searchQuery)) {
                        echo "Showing results for: " . htmlspecialchars($searchQuery);
                    } else {
                        echo "Most Recent Found Items";
                    }
                ?>
            </h2>
            <div class="post-container">
                <?php if ($posts->num_rows > 0): ?>
                    <?php while ($row = $posts->fetch_assoc()): ?>
                        <a href="found.php?search=<?php echo urlencode($row['item_name']); ?>" style="text-decoration: none; color: inherit;">
                            <div class="post">
                                <div class="postprofile">
                                    <img src="<?php echo htmlspecialchars(!empty($row['profile_pic']) ? 'uploads/' . $row['profile_pic'] : 'image/postprofile.png'); ?>" 
                                    alt="Post Profile" width="35" style="border-radius: 100%;">
                                    <span class="username"><strong><?php echo htmlspecialchars($row['email']); ?></strong></span><br/>
                                    <span class="date"><?php echo date('M d Y, h:i A', strtotime($row['created_at'] ?? $row['date'])); ?></span><br/>
                                    <span class="desc"><strong>Item:</strong> <?php echo htmlspecialchars($row['item_name']); ?><br/></span>
                                    <span class="desc"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br/></span>
                                    <span class="desc"><?php echo htmlspecialchars($row['description']); ?></span>
                                </div>
                                <div class="postimage">
                                    <?php if (!empty($row['image_path'])): ?>
                                        <br/><img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Item Image" style="max-width: 200px; margin-top: 10px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No posts found for this category.</p>
                <?php endif; ?>


            </div>
        </section>

    </main>
</body>
<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = [
        'y' => 'y',
        'm' => 'mo',
        'w' => 'w',
        'd' => 'd',
        'h' => 'h',
        'i' => 'm',
        's' => 's',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . $v;
        } else {
            unset($string[$k]);
        }
    }

    return $string ? implode(', ', array_slice($string, 0, 1)) . " ago" : 'just now';
}
?>

</html>

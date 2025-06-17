<?php
session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header("Location: signIn.html");
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

$sql = "SELECT Profile_Pic, Email FROM users WHERE User_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$profilePic = "image/profile.png";
$email = $_SESSION['email'];

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (!empty($row['Profile_Pic'])) {
        $profilePic = "uploads/" . $row['Profile_Pic'];
    }
    $email = $row['Email'];
}
$stmt->close();

// Fetch lost items
$lost_sql = "SELECT * FROM lost_items WHERE User_id = ? ORDER BY created_at DESC";
$lost_stmt = $conn->prepare($lost_sql);
$lost_stmt->bind_param("i", $user_id);
$lost_stmt->execute();
$lost_result = $lost_stmt->get_result();

// Fetch found items
$found_sql = "SELECT * FROM found_items WHERE User_id = ? ORDER BY created_at DESC";
$found_stmt = $conn->prepare($found_sql);
$found_stmt->bind_param("i", $user_id);
$found_stmt->execute();
$found_result = $found_stmt->get_result();

$conn->close();

$_SESSION['profile_pic'] = $profilePic;
$_SESSION['email'] = $email;

if (isset($_SESSION['redirect_msg'])) {
    echo "<script>alert('" . $_SESSION['redirect_msg'] . "');</script>";
    unset($_SESSION['redirect_msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profile Section</title>
    <link rel="stylesheet" href="css/profilesection.css" />
    <style>
        .tab-button {
            padding: 10px 20px;
            border: none;
            background: #eee;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        .tab-button.active {
            background-color: #ccc;
        }
        .post-tab {
            display: none;
        }
        .post-tab.active-tab {
            display: block;
        }
    </style>
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
                <span class="username"><?php echo htmlspecialchars($email); ?></span>
                <a href="profilesection.php"><img src="image/profile.png" alt="Profile Icon" width="35" /></a>
            </div>
        </nav>
    </header>

    <section class="profile-header">
        <div class="cover-banner">
            <img src="image/cover.jpg" alt="Cover" class="cover-photo" />
        </div>

        <div class="profile-container">
            <div class="profile-left">
                <div class="profile-pic-container">
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile" class="profile-pic" />
                </div>
                <div class="description">
                    <span><h2><?php echo htmlspecialchars($email); ?></h2></span>
                </div>
            </div>

            <div class="profile-right">
                <button class="edit-btn"><a href="editProfile.php">Edit Profile</a></button>
                <button class="logout-btn"><a href="signIn.html">Log Out</a></button>
            </div>
        </div>
    </section>

    <nav class="tabs">
        <button class="tab-button active" onclick="showTab('all')">All</button>
        <button class="tab-button" onclick="showTab('lost')">Lost</button>
        <button class="tab-button" onclick="showTab('found')">Found</button>
    </nav>

    <main class="post">
        <!-- ALL POSTS -->
        <div id="all" class="post-tab active-tab">
            <?php
            // Re-fetch lost and found results since original ones are exhausted
            $conn = new mysqli($host, $user, $password, $database);
            $lost_stmt = $conn->prepare("SELECT *, 'lost' as type FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
            $lost_stmt->bind_param("i", $user_id);
            $lost_stmt->execute();
            $lost_all = $lost_stmt->get_result();

            $found_stmt = $conn->prepare("SELECT *, 'found' as type FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
            $found_stmt->bind_param("i", $user_id);
            $found_stmt->execute();
            $found_all = $found_stmt->get_result();

            // Merge results
            $all_posts = [];
            while ($row = $lost_all->fetch_assoc()) $all_posts[] = $row;
            while ($row = $found_all->fetch_assoc()) $all_posts[] = $row;

            // Sort all by date (most recent first)
            usort($all_posts, function ($a, $b) {
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });

            foreach ($all_posts as $row):
            ?>
                <div class="user-post">
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="SmallProfile" class="profile-pic" />
                    <span>
                        <p>
                            <strong><?php echo htmlspecialchars($email); ?></strong><br />
                            <span class="date"><?php echo date('M d Y, h:i A', strtotime($row['created_at'] ?? $row['date'])); ?></span><br/>
                            <span class="desc"><strong>Item:</strong> <?php echo htmlspecialchars($row['item_name']); ?><br/></span>
                            <span class="desc"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br/></span>
                            <span class="desc"><?php echo htmlspecialchars($row['description']); ?></span>
                        </p>
                    </span>
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Item Image" class="bag-photo" />
                    <?php endif; ?>
                    <?php
                        $editLink = "";
                        if (isset($row['type']) && $row['type'] === 'lost') {
                            $editLink = "php/editPost.php?type=lost&id=" . $row['lost_id'];
                        } elseif (isset($row['type']) && $row['type'] === 'found') {
                            $editLink = "php/editPost.php?type=found&id=" . $row['found_id'];
                        }
                    ?>
                    <a href="<?php echo htmlspecialchars($editLink); ?>" 
                        style="display: inline-block; padding: 10px 20px; background-color: #102b3d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px;">
                        Edit
                    </a>


                </div>
            <?php endforeach; ?>
        </div>

        <!-- LOST POSTS -->
        <div id="lost" class="post-tab">
            <?php
            $lost_stmt->execute();
            $lost_result = $lost_stmt->get_result();
            while ($row = $lost_result->fetch_assoc()):
            ?>
                <div class="user-post">
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="SmallProfile" class="profile-pic" />
                    <span>
                        <p>
                            <strong><?php echo htmlspecialchars($email); ?></strong><br />
                            <span class="date"><?php echo date('M d Y, h:i A', strtotime($row['created_at'] ?? $row['date'])); ?></span><br/>
                            <span class="desc"><strong>Item:</strong> <?php echo htmlspecialchars($row['item_name']); ?><br/></span>
                            <span class="desc"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br/></span>
                            <span class="desc"><?php echo htmlspecialchars($row['description']); ?></span>
                        </p>
                    </span>
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Lost Item" class="bag-photo" />
                    <?php endif; ?>

                    <a href="php/editPost.php?type=lost&id=<?php echo $row['lost_id']; ?>"
                    style="display: inline-block; padding: 10px 20px; background-color: #102b3d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px;">
                        Edit
                    </a>

                </div>
            <?php endwhile; ?>
        </div>

        <!-- FOUND POSTS -->
        <div id="found" class="post-tab">
            <?php
            $found_stmt->execute();
            $found_result = $found_stmt->get_result();
            while ($row = $found_result->fetch_assoc()):
            ?>
                <div class="user-post">
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="SmallProfile" class="profile-pic" />
                    <span>
                        <p>
                            <strong><?php echo htmlspecialchars($email); ?></strong><br />
                            <span class="date"><?php echo date('M d Y, h:i A', strtotime($row['created_at'] ?? $row['date'])); ?></span><br/>
                            <span class="desc"><strong>Item:</strong> <?php echo htmlspecialchars($row['item_name']); ?><br/></span>
                            <span class="desc"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br/></span>
                            <span class="desc"><?php echo htmlspecialchars($row['description']); ?></span>
                        </p>
                    </span>
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Found Item" class="bag-photo" />
                    <?php endif; ?>

                    <a href="php/editPost.php?type=found&id=<?php echo $row['found_id']; ?>"
                       style="display: inline-block; padding: 10px 20px; background-color: #102b3d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px;">
                        Edit
                    </a>

                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <script>
    function showTab(tab) {
        const tabs = ['all', 'lost', 'found'];
        tabs.forEach(t => {
            document.getElementById(t).classList.remove('active-tab');
            document.querySelector(`[onclick="showTab('${t}')"]`).classList.remove('active');
        });
        document.getElementById(tab).classList.add('active-tab');
        document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
    }
    </script>

</body>
</html>
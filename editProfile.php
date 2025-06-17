<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signIn.html"); // Redirect to login if not signed in
    exit();
}

$profilePic = isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic']) 
    ? "uploads/" . htmlspecialchars($_SESSION['profile_pic']) 
    : "image/default.png";  // put your default image path here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: white;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #102B3Dff;
            padding-bottom: 20px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 30px;
            color: white;
            font-size: 14px;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
        }

        .profile img {
            width: 28px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        main {
            padding: 60px;
        }

        .user-info-title {
            text-align: center;
            color: white;
            font-size: 32px;
            margin-top: 30px;
        }

        .info-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 100px;
            margin-top: 20px;
        }

        .current-info {
            background-color: #102B3Dff;
            color: white;
            padding: 30px;
            border-radius: 16px;
            width: 380px;
        }

        .current-info p {
            margin: 20px 0;
            font-size: 16px;
        }

        .form-section {
            width: 500px;
        }

        .form-input {
            margin-bottom: 20px;
        }

        .form-input input {
            width: 100%;
            padding: 12px 5px;
            border: none;
            outline: none;
            background: transparent;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ccc;
        }

        .form-input input::placeholder {
            color: #bbb;
        }

        .form-input button {
            background-color: white;
            color: #102B3Dff;
            padding: 10px 20px;
            border: 1px solid;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-input button:hover {
            background-color: #102B3Dff;
            color: white;
        }

        .form-input a {
            text-decoration: none;
            color: inherit;
        }

        label {
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="homepage.php">Home</a></li>
                <li><a href="create.php">Create</a></li>
                <li><a href="chatSection.php">Messages</a></li>
            </ul>
            <div class="profile">
                <span class="username"><?php echo $_SESSION['email']; ?></span>
                <a href="profilesection.php"><img src="image/profile.png" alt="Profile Icon" width="35"></a>
            </div>
        </nav>
        <h1 class="user-info-title">User Information</h1>
    </header>
    <main>
        <section class="user-info">
            <div class="info-container">
                <div class="current-info">
                    <p><strong>Email:</strong> <?php echo $_SESSION['email']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $_SESSION['phone']; ?></p>
                </div>


                <form action="php/uploadProfile.php" method="POST" enctype="multipart/form-data">
                    <div class="form-input">
                        <input type="email" name="newEmail" placeholder="New Email">
                    </div>
                    <div class="form-input">
                        <input type="tel" name="newPhone" placeholder="New Phone Number">
                    </div>
                    <div class="form-input">
                        <label for="profilePic">Upload Profile Picture:</label><br>

                        <?php
                        $profilePic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'uploads/default.png';
                        ?>

                        <!-- Upload input -->
                        <input type="file" id="profilePic" name="profilePic" accept="image/*">
                    </div>

                    <div class="form-input">
                        <button type="submit">Update Information</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>

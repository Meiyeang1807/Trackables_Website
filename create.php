<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: signIn.html"); // Redirect to login if not signed in
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Lost and Found Items</title>
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
       position: relative;
    }

.profile img {
    width: 28px;
}

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile .username {
    font-size: 16px;
}

h1 {
    color: white;
    text-align: center;
    font-size: 40px;
    margin-top: 20px;
}

main {
    padding: 60px;
}

.lost-found-form {
    display: flex;
    flex-direction: column;
    gap: 40px;
    width: 500px;
    margin: auto;
    font-size: 18px;
    color: #0F2B3D;
}

.form-items {
    display: flex;
    align-items: center;
    gap: 20px;
}

.form-items label {
    width: 235px;
    padding-left: -100px;
    font-weight: bold;
}

.form-items input {
    padding: 10px;
    width: 320px;
    border-radius: 15px;
    border: 2px solid #0F2B3D;
}

.form-items textarea {
    width: 320px;
    height: 100px;
    padding: 10px;
    border-radius: 15px;
    border: 2px solid #0F2B3D;
}

.button-group {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 10px;
}

.submit-btn {
    background-color: #0F2B3D;
    color: white;
    padding: 10px 20px;
    width: 180px;
    border: none;
    text-align: center;
    border-radius: 15px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.submit-btn:hover {
    background-color: #15445e;
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
        <h1>Submit Lost and Found Items</h1>
    </header>

    <main>
        <form class="lost-found-form" action="php/submitItem.php" method="POST" enctype="multipart/form-data">
            <div class="form-items">
                <label for="item_name">What was lost/found?</label>
                <input type="text" id="item_name" name="item_name" required>
            </div>

            <div class="form-items">
                <label for="date">Date lost/found?</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-items">
                <label for="location">Where lost/found?</label>
                <input type="text" id="location" name="location">
            </div>

            <div class="form-items">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>

            <div class="form-items">
                <label for="upload">Upload picture</label>
                <input type="file" id="upload" name="image" accept="image/*">
            </div>

            <div class="button-group">
                <button type="submit" name="lost" class="submit-btn lost">Submit Lost Items</button>
                <button type="submit" name="found" class="submit-btn found">Submit Found Items</button>
            </div>
        </form>

    </main>
</body>
</html>

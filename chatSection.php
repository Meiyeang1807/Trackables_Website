<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signIn.html");
    exit();
}
include("php/db_connection.php");

// Get current logged-in user email and ID
$currentUserEmail = $_SESSION['email'];
$chatWithEmail = $_GET['users'] ?? null;

$chatMessages = [];

if ($chatWithEmail) {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages 
                           WHERE (sender_id = :user1 AND receiver_id = :user2)
                              OR (sender_id = :user2 AND receiver_id = :user1)
                           ORDER BY timestamp ASC");
    $stmt->execute(['user1' => $currentUserEmail, 'user2' => $chatWithEmail]);
    $chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
$stmt->bind_param("s", $currentUserEmail);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$currentUserId = $userData['User_id'] ?? null;

// Initialize values
$claimReceiver = isset($_GET['receiver']) ? urldecode($_GET['receiver']) : null;
$claimedFoundId = isset($_GET['found_id']) ? intval($_GET['found_id']) : null;
$receiverId = null;

if ($claimReceiver && !$claimedFoundId) {
    $stmt = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
    $stmt->bind_param("s", $claimReceiver);
    $stmt->execute();
    $stmt->bind_result($receiverId);
    $stmt->fetch();
    $stmt->close();
}

// Fetch inbox users related to this user (claimers or owners of found items)
$inboxStmt = $conn->prepare("
    SELECT DISTINCT u.email, u.profile_pic
    FROM users u
    WHERE u.User_id != ?
    AND (
        u.user_id IN (
            SELECT receiver_id FROM chat_messages WHERE sender_id = ?
            UNION
            SELECT sender_id FROM chat_messages WHERE receiver_id = ?
        )
        OR u.user_id IN (
            SELECT claimer_id FROM claim WHERE found_id IN (
                SELECT found_id FROM found_items WHERE user_id = ?
            )
            UNION
            SELECT user_id FROM found_items WHERE found_id IN (
                SELECT found_id FROM claim WHERE claimer_id = ?
            )
        )
    )
");
$inboxStmt->bind_param("iiiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
$inboxStmt->execute();
$inboxResult = $inboxStmt->get_result();

// Fetch details of the found item being claimed (if any)
$itemDetails = null;
if ($claimedFoundId) {
    $itemStmt = $conn->prepare("SELECT item_name, description, location, image_path FROM found_items WHERE found_id = ?");
    $itemStmt->bind_param("i", $claimedFoundId);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();
    $itemDetails = $itemResult->fetch_assoc();
}

$isReviewMode = isset($_GET['review']) && $_GET['review'] == '1';
$reviewData = [
    'name' => $_GET['name'] ?? '',
    'phone' => $_GET['phone'] ?? '',
    'matric' => $_GET['matric'] ?? ''
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inbox</title>
  <link rel="stylesheet" href="css/chatSection.css" />
  <style>
    .chat-message.system {
    background-color: #102b3d;
    color: white;
    border: 1px solid #102b3d;
    font-style: italic;
    padding: 10px;
    border-radius: 10px;
    }

    .unread-badge {
    background: #102b3d;
    color: white;
    padding: 2px 6px;
    font-size: 12px;
    border-radius: 12px;
    margin-left: 5px;
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
</header>

<main>
  <div class="chat-layout">
    <!-- Inbox List -->
    <div class="inbox-list">
      <div class="inbox-header">
        <h2>Messages</h2>
      </div>
      <?php while ($row = $inboxResult->fetch_assoc()): 
          $email = $row['email'];
          $image = $row['profile_pic'] ? 'uploads/' . $row['profile_pic'] : 'image/profile.png';

          // Get user_id for the current inbox user
          $userIdStmt = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
          $userIdStmt->bind_param("s", $email);
          $userIdStmt->execute();
          $userIdResult = $userIdStmt->get_result();
          $userIdRow = $userIdResult->fetch_assoc();
          $otherUserId = $userIdRow['user_id'] ?? 0;

          // Check if there are unread messages from this user to current user
          $unreadStmt = $conn->prepare("SELECT COUNT(*) AS unread FROM chat_messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
          $unreadStmt->bind_param("ii", $otherUserId, $currentUserId);
          $unreadStmt->execute();
          $unreadResult = $unreadStmt->get_result();
          $unreadRow = $unreadResult->fetch_assoc();
          $unreadCount = $unreadRow['unread'];
      ?>
      <div class="message-item" data-user="<?php echo htmlspecialchars($email); ?>">
          <img src="<?php echo $image; ?>" alt="User Picture" />
          <div class="message-text">
              <p class="message-name">
                  <?php echo htmlspecialchars($email); ?>
                  <?php if ($unreadCount > 0): ?>
                      <span class="unread-badge"><?php echo $unreadCount; ?></span>
                  <?php endif; ?>
              </p>
              <p class="message-subtext">Tap to see chat</p>
          </div>
      </div>
      <?php endwhile; ?>
      
    </div>

    <!-- Chat Area -->
    <div class="chat-wrapper">
      <div class="header-right">
        <div class="chat-header">
          <img id="chat-user-image" src="image/profile.png" alt="User Picture">
          <span id="chat-username">
            <?php echo $claimReceiver ? htmlspecialchars($claimReceiver) : 'Select a user'; ?>
          </span>
        </div>
      </div>

      <div class="chat-area">
        <div class="chat-container">
          <div class="chat-messages" id="chat-messages">
            <?php
              if ($claimReceiver) {
                  // Get receiver user ID
                  $receiverQuery = $conn->prepare("SELECT User_id FROM users WHERE Email = ?");
                  $receiverQuery->bind_param("s", $claimReceiver);
                  $receiverQuery->execute();
                  $receiverResult = $receiverQuery->get_result();
                  $receiverData = $receiverResult->fetch_assoc();
                  $receiverId = $receiverData['User_id'] ?? null;

                  if ($receiverId && $currentUserId) {
                      // Fetch full chat history
                      $chatQuery = $conn->prepare("
                          SELECT sender_id, message, timestamp 
                          FROM chat_messages 
                          WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                          ORDER BY timestamp ASC
                      ");
                      $chatQuery->bind_param("iiii", $currentUserId, $receiverId, $receiverId, $currentUserId);
                      $chatQuery->execute();
                      $chatResult = $chatQuery->get_result();

                      // Mark all received messages from this user as read
                      $markRead = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
                      $markRead->bind_param("ii", $receiverId, $currentUserId);
                      $markRead->execute();

                      while ($chatRow = $chatResult->fetch_assoc()) {
                          $msgClass = ($chatRow['sender_id'] == $currentUserId) ? 'sent' : 'received';

                          // Optional: highlight claim messages
                          if (strpos($chatRow['message'], '📌 Claim Submitted:') === 0) {
                              $msgClass = 'system';
                          }

                          $messageText = $chatRow['message'];

                          if (strpos($messageText, 'CLAIM_SUBMITTED|') === 0) {
                              $parts = explode('|', $messageText);
                              $formatted = "<div class='chat-message system'>
                                  <strong>📌 Claim Submitted</strong><br>
                                  Name: " . htmlspecialchars($parts[1]) . "<br>
                                  Email: " . htmlspecialchars($parts[2]) . "<br>
                                  Phone: " . htmlspecialchars($parts[3]) . "<br>
                                  Matric No: " . htmlspecialchars($parts[4]) . "<br>
                                  <strong>Item:</strong> 
                                  <a href='php/found_details.php?found_id=" . htmlspecialchars($parts[7]) . "' target='_blank' style='color: #007bff; text-decoration: underline;'>
                                      " . htmlspecialchars($parts[5]) . " (View Post)
                                  </a><br>
                                  <strong>Location:</strong> " . htmlspecialchars($parts[6]) . "<br>
                                  <small class='text-muted'>This message was automatically generated</small>
                              </div>";
                              echo $formatted;
                          } else {
                              echo "<div class='chat-message $msgClass'>" . nl2br(htmlspecialchars($messageText)) . "</div>";
                          }
                      }
                  }
              }
            ?>

            <?php if ($claimReceiver && $claimedFoundId && !$isReviewMode): ?>
              <!-- Show item and ask for claim confirmation -->
              <div class="chat-message received">
                <?php if ($itemDetails): ?>
                  <?php if (!empty($itemDetails['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($itemDetails['image_path']); ?>" alt="Item Image" width="150" />
                  <?php endif; ?>
                  <p>Item: <?php echo htmlspecialchars($itemDetails['item_name']); ?></p>
                  <p>Location: <?php echo htmlspecialchars($itemDetails['location']); ?></p>
                  <p>Description: <?php echo htmlspecialchars($itemDetails['description']); ?></p>
                  <p>Are you sure you want to claim this item?</p>
                  <div class="button-group">
                    <button onclick="showValidationForm()">Yes</button>
                    <button onclick="window.location.href='found.php'">Cancel</button>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($isReviewMode): ?>
              <!-- Show confirmation after claim -->
              <div class="review-form">
                <h2>Claim Submitted</h2>
                <p>Thank you for submitting your claim. Please review your details below:</p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($reviewData['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($currentUserEmail); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($reviewData['phone']); ?></p>
                <p><strong>Matric Number:</strong> <?php echo htmlspecialchars($reviewData['matric']); ?></p>
                <p class="success-msg">You may now continue chatting with the owner below.</p>
              </div>
            <?php else: ?>
              <!-- Hidden identity validation form -->
              <div id="identity-form" style="display: none;">
                <form method="POST" action="php/submit_claims.php" class="validation-form">
                  <input type="hidden" name="found_id" value="<?php echo htmlspecialchars($claimedFoundId); ?>">
                  <input type="hidden" name="receiver_email" value="<?php echo htmlspecialchars($claimReceiver); ?>">
                  <h2>Identity Validation</h2>

                  <label for="name">Full Name</label>
                  <input type="text" id="name" name="full_name" required>

                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>

                  <label for="phone">Phone Number</label>
                  <input type="text" id="phone" name="phone_number" required>

                  <label for="matric">Matric Number</label>
                  <input type="text" id="matric" name="matric_number" required>

                  <button type="submit">Submit Claim</button>
                </form>
              </div>
            <?php endif; ?>
          </div>

          <!-- Chat input field -->
          <?php if ($claimReceiver): ?>
            <form class="chat-input" id="chat-form">
              <textarea placeholder="Type a message" id="chat-input" required></textarea>
              <button class="icon-button" type="submit">
                <img src="image/sent.png" alt="Send Icon" />
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

  <script>
    function showValidationForm() {
      document.getElementById('identity-form').style.display = 'block';
    }

    document.getElementById('chat-form').addEventListener('submit', function (e) {
      e.preventDefault();
      const input = document.getElementById('chat-input');
      const message = input.value.trim();
      const receiverEmail = "<?php echo $claimReceiver ?? ''; ?>";

      if (message !== '' && receiverEmail !== '') {
        // Send message to PHP via AJAX
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "php/send_message.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
          if (xhr.status === 200) {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-message sent';
            msgDiv.innerHTML = message;
            document.getElementById('chat-messages').appendChild(msgDiv);
            input.value = '';
          }
        };
        xhr.send("receiver_email=" + encodeURIComponent(receiverEmail) + "&message=" + encodeURIComponent(message));
      }
    });

  </script>

  <?php if (isset($_GET['success'])): ?>
  <script>
    alert('Validation form submitted successfully!');
  </script>
  <?php endif; ?>


    <script>
      // When a message item is clicked, load chat with that user
      document.querySelectorAll('.message-item').forEach(item => {
        item.addEventListener('click', function() {
          const userEmail = this.getAttribute('data-user');

          // Option 1: Redirect to the same page with ?receiver=email
          // Use encodeURIComponent to safely encode email
          window.location.href = `chatSection.php?receiver=${encodeURIComponent(userEmail)}`;

          // Option 2: (Optional) Use AJAX here to load chat dynamically without page reload
        });
      });
    </script>

</body>
</html>

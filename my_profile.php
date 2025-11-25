<?php
require 'includes/db.php';
session_start();

$user_id = $_GET['id'] ?? $_SESSION['user_id'];

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found!');
}

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];

// Calculate total points based on posts, likes, comments, and views
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS total_posts,
        (SELECT COUNT(*) FROM likes WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)) AS total_likes,
        (SELECT COUNT(*) FROM comments WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)) AS total_comments,
        (SELECT COUNT(*) FROM post_views WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)) AS total_views,
        (SELECT COUNT(*) FROM likes WHERE user_id = ?) AS post_likes,
        (SELECT COUNT(*) FROM comments WHERE user_id = ?) AS post_comments,
        (SELECT COUNT(*) FROM post_views WHERE user_id = ?) AS post_views
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate total points
$totalPoints = ($userStats['total_posts'] * 3) + 
               ($userStats['total_likes'] * 0.5) + 
               ($userStats['total_comments'] * 1) + 
               ($userStats['total_views'] * 0.25) +
               ($userStats['post_likes'] * 0.5) + 
               ($userStats['post_comments'] * 1) + 
               ($userStats['post_views'] * 0.25);
               if ($totalPoints <= 50) {
                $level = 1;
                $title = "Newbie";
            } elseif ($totalPoints <= 150) {
                $level = 2;
                $title = "Contributor";
            } elseif ($totalPoints <= 300) {
                $level = 3;
                $title = "Influencer";
            } elseif ($totalPoints <= 500) {
                $level = 4;
                $title = "Expert";
            } else  {
                $level = 5;
                $title = "Guru";
            }
            $user_id =  $_SESSION['user_id'];
// Update the user's level and title in the database
$updateStmt = $pdo->prepare("UPDATE users SET level = ?, title = ? WHERE id = ?");
$updateStmt->execute([$level, $title, $user_id]);

// Fetch the user's level and title
$stmt = $pdo->prepare("SELECT level, title FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userProfile = $stmt->fetch();

// Calculate total points (reuse from level and title logic)
$totalPoints = ($userStats['total_posts'] * 3) + 
                ($userStats['total_likes'] * 0.5) + 
                ($userStats['total_comments'] * 1) + 
                ($userStats['total_views'] * 0.25) + 
                ($userStats['post_likes'] * 0.5) + 
                ($userStats['post_comments'] * 1) + 
                ($userStats['post_views'] * 0.25);

// Define thresholds for levels
$levelThresholds = [
    1 => 50,
    2 => 150,
    3 => 300,
    4 => 500,
];

// Determine current level and next threshold
$currentLevel = 1;
$nextThreshold = $levelThresholds[$currentLevel];

foreach ($levelThresholds as $level => $threshold) {
    if ($totalPoints > $threshold) {
        $currentLevel = $level;
    } else {
        $nextThreshold = $threshold;
        break;
    }
}

// Calculate points for the current level
$pointsInCurrentLevel = $totalPoints - ($levelThresholds[$currentLevel - 1] ?? 0);
$pointsToNextLevel = $nextThreshold - ($levelThresholds[$currentLevel - 1] ?? 0);

// Prevent division by zero
if ($pointsToNextLevel > 0) {
    $progress = ($pointsInCurrentLevel / $pointsToNextLevel) * 100;
} else {
    $progress = 100; // Full progress if there's no next level
}

// Ensure the progress is between 0 and 100
$progress = max(0, min(100, $progress));
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($user['name']); ?>'s Profile</title>
        <link rel="stylesheet" href="assets/css/styles.css">
        <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
/* 🌟 Profile Header */
.profile-header {
    text-align: center;
    padding: 40px;
    background: linear-gradient(to right, #6a11cb, #2575fc);
    color: white;
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
}

.profile-header img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    margin-bottom: 10px;
    transition: transform 0.3s ease;
}

.profile-header img:hover {
    transform: scale(1.05);
}

.profile-header h1 {
    font-size: 22px;
    font-weight: bold;
    margin: 5px 0;
}

.profile-header p {
    font-size: 14px;
    opacity: 0.9;
}

/* ✨ Profile Action Buttons */
.btn {
    display: inline-block;
    background: white;
    color: #6a11cb;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s ease-in-out;
    border: 2px solid #6a11cb;
    margin-top: 10px;
}

.btn:hover {
    background: #6a11cb;
    color: white;
}

/* 🎖️ Level Progress Section */
.progress-container {
    text-align: center;
    margin: 20px auto;
    width: 80%;
}

.progress-container h3 {
    font-size: 16px;
    font-weight: bold;
    color: #333;
}

/* 🎯 Progress Bar */
.progress-bar {
    background-color: #ddd;
    border-radius: 20px;
    overflow: hidden;
    height: 20px;
    width: 100%;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(to right, #6a11cb, #2575fc);
    width: 0; /* Initial state */
    border-radius: 20px;
    transition: width 1s ease-in-out; /* Smooth transition */
}

/* 📜 Follower & Following Lists */
.following,
.followers {
    text-align: center;
    margin: 20px auto;
    width: 80%;
}

.following ul, .followers ul {
    list-style: none;
    padding: 0;
}

.following li, .followers li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    border-radius: 8px;
    background: white;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    color: black;
}

.following li:hover, .followers li:hover {
    transform: translateY(-3px);
}

.following img, .followers img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.view-profile-btn {
    background: transparent;
    color: #2575fc;
    font-size: 14px;
    padding: 6px 10px;
    border: 1px solid #2575fc;
    border-radius: 5px;
    transition: all 0.3s;
}

.view-profile-btn:hover {
    background: #2575fc;
    color: white;
}

/* 🎉 Level-Up Popup */
.level-up-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

.level-up-container.show {
    opacity: 1;
    visibility: visible;
}

.confetti {
    position: fixed;
    top: -100px;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 999;
    overflow: hidden;
}

.confetti-piece {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #6a11cb;
    animation: confetti-fall 2s linear infinite;
}
@keyframes confetti-fall {
    0% {
        transform: translateY(-100px) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}
/* 🔲 Modal Background */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    overflow-y: auto;
    color: black;
}

/* 📦 Modal Content */
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 12px;
    width: 40%;
    margin: 10% auto;
    text-align: center;
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
    position: relative;
}

/* ❌ Close Button */
.close-modal {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 20px;
    cursor: pointer;
    background: none;
    border: none;
    color: #555;
}

.close-modal:hover {
    color: #ff5f5f;
}

/* 🖼️ Profile Picture in Modal */
#modalProfilePicture {
    width: 100%;
    height: auto;
    border-radius: 10px;
    border: 4px solid #ddd;
    margin: 15px 0;
}

/* 📂 File Input */
#profilePictureInput {
    margin: 15px 0;
    color: gray;
}

/* ✅ Upload Button */
button {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

button:hover {
    background: linear-gradient(135deg, #4a4aad, #1b5fd1);
}
@media screen and (max-width: 600px){
    .modal-content {
    width: 80%;
}
}
        </style>
    </head>
    <body class="<?php echo htmlspecialchars($theme); ?>">
          <!-- Sidebar -->
          <aside style="height: 100%;  overflow-y: auto" class="sidebar">
            <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i> Bookmarks</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                </ul>
            </nav>
        </aside><!-- Main Content -->
        <main class="content">

        <section>
            <div class="profile-header">
                <img src="<?php echo $user['profile_picture'] ?: 'default-avatar.png'; ?>" id="profilePicture" alt="Profile Picture">
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <p><?php echo htmlspecialchars($userProfile['title']) . " (Level " . $userProfile['level'] . ")"; ?></p>
                <p><?php echo htmlspecialchars($user['bio']); ?></p>
                <a class="btn" href="settings.php"><i class="fas fa-pencil"></i> Edit Profile</a>
                <a href="chat.php?user_id=<?php echo $user_id; ?>#message-input" class="btn"><i class="fas fa-message"></i> Message</a>
            </div>
                <!-- Profile Picture Modal -->
                <div id="profilePictureModal" class="modal">
                    <div class="modal-content">
                        <span class="close-modal">&times;</span>
                        <h2>Profile Picture</h2>
                        <img id="modalProfilePicture" src="<?= htmlspecialchars($user['profile_picture']) ?: 'default-avatar.png' ?>" alt="Profile Picture">
                        
                        <!-- Change Picture Form -->
                        <h4> Change Profile Picture </h4>
                        <form id="changeProfilePictureForm" enctype="multipart/form-data">
                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                            <button type="submit">Upload</button>
                        </form>
                    </div>
                </div>
            <br>
                <ul class="nav">
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <?php if ($_SESSION['role'] === 'User'): ?>
              <a href="#"><li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
            <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
            <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>

            <div class="level-up-container" id="levelUpPopup">
                <h1>🎉 Level Up! 🎉</h1>
                <p>You’ve reached Level <span id="newLevel"></span>!</p>
            </div>
            <div class="confetti" id="confettiContainer"></div>

            <div class="progress-container">
                <h3>Level: <?= htmlspecialchars($level) ?> - <?= htmlspecialchars($title) ?></h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <p><?= $pointsInCurrentLevel ?> / <?= $pointsToNextLevel ?> points to the next level</p>
            </div>

                <h2 class="animate-on-scroll" id="profile-name"><?php echo htmlspecialchars($user['role']); ?>'s Profile</h2>

                <div class="followers">
                    <ul id="followers-list">
                        <li>Loading...</li>
                    </ul>
                </div>

                <div class="following">
                    <ul id="following-list">
                        <li>Loading...</li>
                    </ul>
                </div>
                <ul class="nav">
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <?php if ($_SESSION['role'] === 'User'): ?>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
            <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
            <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br><br><br>
            </div>
            
        <main>
        <script>
                 document.addEventListener("DOMContentLoaded", function () {
  const elements = document.querySelectorAll(".animate-on-scroll");

  function handleScroll() {
    elements.forEach((el) => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight - 100) {
        el.classList.add("visible");
      }
    });
  }

  window.addEventListener("scroll", handleScroll);
  handleScroll(); // Trigger once on page load
});
            document.addEventListener('DOMContentLoaded', function () {
            fetch('fetch_followers_following.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display followers count
                        const followersCount = document.createElement('h3');
                        followersCount.innerText = `Followers: ${data.followers_count}`;
                        document.querySelector('.followers').prepend(followersCount);

                        // Display following count
                        const followingCount = document.createElement('h3');
                        followingCount.innerText = `Following: ${data.following_count}`;
                        document.querySelector('.following').prepend(followingCount);

                        // Populate followers list
                        const followersList = document.getElementById('followers-list');
                        followersList.innerHTML = '';
                        if (data.followers.length > 0) {
                            data.followers.forEach(follower => {
                                const li = document.createElement('li');
                                li.innerHTML = `
                                <img src="${follower.profile_picture || 'default-profile.png'}" width=' 100px ' alt="${follower.name}">
                                <span">${follower.name}</span>
                                <a href="user_profile.php?id=${follower.id}" class="view-profile-btn">View Profile</a>`;
                                followersList.appendChild(li);
                            });
                        } else {
                            followersList.innerHTML = '<li>No followers yet.</li>';
                        }

                        // Populate following list
                        const followingList = document.getElementById('following-list');
                        followingList.innerHTML = '';
                        if (data.following.length > 0) {
                            data.following.forEach(following => {
                                const li = document.createElement('li');
                                li.innerHTML = `
                                <img src="${following.profile_picture || 'default-profile.png'}" alt="${following.name}">
                                <span>${following.name}</span>
                                <a href="user_profile.php?id=${following.id}" class="view-profile-btn">View Profile</a>`;
                                followingList.appendChild(li);
                            });
                        } else {
                            followingList.innerHTML = '<li>Not following anyone yet.</li>';
                        }
                    }
                });
            });
            document.addEventListener('DOMContentLoaded', function () {
    const notificationCountElement = document.getElementById('notification-count');
    const unreadMessageCountElement = document.getElementById('unread-message-count');
    const notificationsList = document.getElementById('notifications-list');
    const unreadMessagesList = document.getElementById('unread-messages-list');

    // Fetch unread notifications
    function fetchNotifications() {
        fetch('fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notifications = data.notifications;
                notificationCountElement.textContent = notifications.length;

                notificationsList.innerHTML = ''; // Clear previous list
                if (notifications.length > 0) {
                    notifications.forEach(notification => {
                        const listItem = document.createElement('li');
                        listItem.className = 'notification-item';
                        listItem.innerHTML = `
                            <img src="${notification.profile_picture || 'default-profile.png'}" alt="${notification.sender_name}" class="sender-pic">
                            <span>${notification.message} - <a href="user_profile.php?id=${notification.sender_id}">${notification.sender_name}</a></span>
                            <button class="mark-as-read-btn" data-notification-id="${notification.id}">Mark as Read</button>
                        `;
                        notificationsList.appendChild(listItem);
                    });
                } else {
                    notificationsList.innerHTML = '<li>No new notifications</li>';
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Fetch unread messages
    function fetchUnreadMessages() {
        fetch('fetch_unread_messages.php')
            .then(response => response.json())
            .then(data => {
                const unreadMessages = data.unread_messages;
                unreadMessageCountElement.textContent = unreadMessages.length;

                unreadMessagesList.innerHTML = ''; // Clear previous list
                if (unreadMessages.length > 0) {
                    unreadMessages.forEach(msg => {
                        const listItem = document.createElement('li');
                        listItem.className = 'unread-message-item';
                        listItem.innerHTML = `
                            <img src="${msg.profile_picture || 'default-profile.png'}" alt="${msg.sender_name}" class="sender-pic">
                            <span>${msg.sender_name} (${msg.unread_count} unread)</span>
                            <a href="chat.php?user_id=${msg.sender_id}" class="view-chat-link">View Chat</a>
                        `;
                        unreadMessagesList.appendChild(listItem);
                    });
                } else {
                    unreadMessagesList.innerHTML = '<li>No unread messages</li>';
                }
            })
            .catch(error => console.error('Error fetching unread messages:', error));
    }

    // Initial fetch
    fetchNotifications();
    fetchUnreadMessages();

    // Optionally refresh the lists periodically
    setInterval(() => {
        fetchNotifications();
        fetchUnreadMessages();
    }, 2000); // Refresh every 60 seconds
});
document.addEventListener("DOMContentLoaded", function () {
    const currentLevel = <?= $currentLevel ?>;
    const totalPoints = <?= $totalPoints ?>;
    const levelThresholds = <?= json_encode($levelThresholds) ?>;

    // Check if the user has leveled up
    const previousLevel = parseInt(localStorage.getItem("previousLevel")) || currentLevel;

    if (currentLevel > previousLevel) {
        // Show level-up animation
        showLevelUpAnimation(currentLevel);

        // Update previous level in localStorage
        localStorage.setItem("previousLevel", currentLevel);
    }

    function showLevelUpAnimation(newLevel) {
        // Show confetti effect
        const confettiContainer = document.getElementById("confettiContainer");
        for (let i = 0; i < 100; i++) {
            const confettiPiece = document.createElement("div");
            confettiPiece.classList.add("confetti-piece");
            confettiPiece.style.left = Math.random() * 100 + "vw";
            confettiPiece.style.backgroundColor =
                `hsl(${Math.random() * 360}, 100%, 50%)`;
            confettiPiece.style.animationDelay = Math.random() * 2 + "s";
            confettiContainer.appendChild(confettiPiece);
        }

        // Show level-up popup
        const levelUpPopup = document.getElementById("levelUpPopup");
        document.getElementById("newLevel").textContent = newLevel;
        levelUpPopup.classList.add("show");

        // Remove confetti after animation
        setTimeout(() => {
            confettiContainer.innerHTML = "";
            levelUpPopup.classList.remove("show");
        }, 3000);
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const profilePicture = document.getElementById('profilePicture');
    const modal = document.getElementById('profilePictureModal');
    const closeModal = document.querySelector('.close-modal');
    const modalProfilePicture = document.getElementById('modalProfilePicture');
    const profilePictureInput = document.getElementById('profilePictureInput');
    const changeProfilePictureForm = document.getElementById('changeProfilePictureForm');

    // Open Modal on Click
    profilePicture.addEventListener('click', function () {
        modalProfilePicture.src = this.src;
        modal.style.display = 'block';
    });

    // Close Modal
    closeModal.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    // Handle Profile Picture Change
    changeProfilePictureForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('update_profile_picture.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalProfilePicture.src = data.new_picture;
                profilePicture.src = data.new_picture;
                alert('Profile picture updated successfully!');
                modal.style.display = 'none';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
function updateUserActivity() {
    fetch("update_user_status.php", { method: "POST" })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                console.log("User activity updated");
            }
        });
}
// Call this function every few seconds
setInterval(updateUserActivity, 3000);

document.addEventListener('DOMContentLoaded', function () {
    const progressFill = document.querySelector('.progress-fill');
    const progress = <?= $progress ?>;

    // Apply progress with animation
    setTimeout(() => {
        progressFill.style.width = progress + '%';
    }, 300); // Delay for smooth effect
});
        </script>
    </body>
</html>
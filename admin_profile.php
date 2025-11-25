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

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin'){
    header("Location: dashboard.php");
    exit;
}

// Fetch the count of unresolved notifications
$stmt = $pdo->prepare("SELECT COUNT(*) AS notification_count FROM notifications WHERE is_read = 0 AND user_id = ?");
$stmt->execute([$user_id]);
$notification = $stmt->fetch();
$notification_count = $notification['notification_count'];

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];

// Calculate total points based on posts, likes, comments, and views
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS total_posts,
        (SELECT COUNT(*) FROM likes WHERE user_id = ?) AS total_likes,
        (SELECT COUNT(*) FROM comments WHERE user_id = ?) AS total_comments,
        (SELECT COUNT(*) FROM post_views WHERE user_id = ?) AS total_views
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate total points
$totalPoints = ($userStats['total_posts'] * 5) + 
               ($userStats['total_likes'] * 1) + 
               ($userStats['total_comments'] * 2) + 
               ($userStats['total_views'] * 0.5);
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
            } else {
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
$totalPoints = ($userStats['total_posts'] * 5) + 
               ($userStats['total_likes'] * 1) + 
               ($userStats['total_comments'] * 2) + 
               ($userStats['total_views'] * 0.5);

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

// Calculate progress percentage
$pointsInCurrentLevel = $totalPoints - ($levelThresholds[$currentLevel - 1] ?? 0);
$pointsToNextLevel = $nextThreshold - ($levelThresholds[$currentLevel - 1] ?? 0);
$progress = ($pointsInCurrentLevel / $pointsToNextLevel) * 100;
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
            
            .profile-header {
       text-align: center;
       padding: 20px;
       background: rgba(0, 0, 0, 0.3);
       border-bottom: 5px solid #2575fc;
   }

.followers ul:hover, .following ul:hover {
    transform: translateX(5px); /* Slight movement on hover */
}


.profile {
    display: flex;
    justify-content: space-between;
    background-color: #fff;
margin-bottom: 10px;
    color: black;
}

#follow-flex {
    display: flex;
    justify-content: space-between;
    background-color: #fff;
}

@media screen and (max-width: 600px){
    .profile {
    display: block;
}
}

.following  ul ,
.followers  ul 
{
    list-style: none;
    padding: 0;
    color: black;
    margin-left: 20px;
    margin-right: 20px;
}

.following  li,
.followers  li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    border-radius: 5px;
    
}

.following li img,
.followers li img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 10px;
}

.view-profile-btn {
    text-decoration: none;
    color: #007BFF;
    font-size: 14px;
    padding: 5px 10px;
    border: 1px solid #007BFF;
    border-radius: 4px;
    transition: all 0.3s;
}

.view-profile-btn:hover {
    background-color: #007BFF;
    color: #fff;
}
.progress-container {
    margin: 20px 0;
    text-align: center;
    font-family: 'Poppins', sans-serif;
}

.progress-bar {
    position: relative;
    background-color: #f3f3f3;
    border-radius: 20px;
    overflow: hidden;
    height: 25px;
    width: 80%;
    margin: 0 auto;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

.progress-fill {
    height: 100%;
    background-color: #6c63ff;
    transition: width 0.5s ease-in-out;
    border-radius: 20px;
}
.level-up-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(0, 0, 0, 0.8);
    color: #fff;
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
    background: #6c63ff;
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
        </style>
    </head>
    <body class="<?php echo htmlspecialchars($theme); ?>">
          <!-- Sidebar -->
          <aside style="overflow-y: scroll;" class="sidebar">
            <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
            <nav>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i>Home</a></li>
                <li><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="active"><i class="fas fa-user"></i>My Profile</a></li>
                <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                <li><a href="admin_users.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                <li><a href="admin_groups.php"><i class="fas fa-users"></i>Manage Groups</a></li>
                <li><a href="admin_posts.php"><i class="fas fa-file-alt"></i>Manage Posts</a></li>
                <li><a href="admin_comments.php"><i class="fas fa-comments"></i>Manage Comments</a></li>
                <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i>View Reports </a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-eye"></i>View Analytics</a></li>
                <li><a href="admin_filters.php"><i class="fas fa-folder-open"></i>Manage Filters</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i> All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
            </nav>
        </aside><!-- Main Content -->
        <main class="content">

        <header>
            <div class="profile-header">
                    <img src="<?php echo $user['profile_picture'] ?: 'default-avatar.png'; ?>" alt="Profile Picture" class="animate-on-scroll profile-picture">
                    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="animate-on-scroll bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <p class="animate-on-scroll bio"><?php echo htmlspecialchars($userProfile['title']) . " (" . $userProfile['level'] . ")"; ?></p><?php if ($user['social_links']): ?>
                        <p class="animate-on-scroll">Connect: <?php echo htmlspecialchars($user['social_links']); ?></p>
                    <?php endif; ?>
                    <a class="btn animate-on-scroll" href="settings.php"><i class="fas fa-pencil"></i> Edit Profile</a>
                    <a href="chat.php?user_id=<?php echo $user_id; ?>" class="animate-on-scroll btn "><i class="fas fa-message"></i> Message Yourself</a>
                </div><br>
                <ul class="nav">
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
              <a href=""><li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
              <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
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
                    <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                </div>
                <p><?= $pointsInCurrentLevel ?> / <?= $pointsToNextLevel ?> points to the next level</p>
            </div>
                <h2 class="animate-on-scroll" id="profile-name"><?php echo htmlspecialchars($user['role']); ?>'s Profile</h2>

                <div class="animate-on-scroll profile">
                    
                        <div class="followers animate-on-scroll">
                            <ul class="animate-on-scroll" id="followers-list">
                                <li>Loading...</li>
                            </ul>
                        </div>

                        <div class="following animate-on-scroll">
                            <ul class="animate-on-scroll" id="following-list">
                                <li>Loading...</li>
                            </ul>
                        </div>
                   
                </div>
                <ul class="nav">
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
            <br>
            <br>
            <br>
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
                        const followersCount = document.createElement('h4');
                        followersCount.innerText = `Followers: ${data.followers_count}`;
                        document.querySelector('.followers').prepend(followersCount);

                        // Display following count
                        const followingCount = document.createElement('h4');
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
        </script>
    </body>
</html>
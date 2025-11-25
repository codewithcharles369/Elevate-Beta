<?php
require 'includes/db.php';
session_start();
$user = $_SESSION['user_id'];
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Check if the user is logged in
if ($_SESSION['user_id'] == $user_id) {
    // Redirect to the login page
    header("Location: my_profile.php");
    exit;
}

    // Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];

    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if logged-in user is following this user
    $stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $is_following = $stmt->fetch(PDO::FETCH_ASSOC);

     // Fetch user's posts
     $stmt = $pdo->prepare("SELECT 
     posts.id,
     posts.title,
     posts.content,
     posts.media,
     posts.user_id,
     posts.created_at,
     categories.name AS category_name,
     users.name AS name,
     users.profile_picture AS user_image,
     (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
     (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS liked
 FROM posts
 LEFT JOIN categories ON posts.category_id = categories.id
 LEFT JOIN users ON posts.user_id = users.id
  WHERE user_id = ? ORDER BY posts.created_at DESC");
     $stmt->execute([$user_id]);
     $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM follows WHERE following_id = ?) AS followers_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ?) AS following_count
");
$stmt->execute([$user_id, $user_id]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "Invalid user.";
    exit;
}
// Fetch the count of unresolved message
$stmt = $pdo->prepare("SELECT COUNT(*) AS message_count FROM messages WHERE receiver_id = ? AND  sender_id = ?  ANd  is_read = 0");
$stmt->execute([$_SESSION['user_id'], $user_id]);
$message = $stmt->fetch();
$message_count = $message['message_count'];

// Fetch the user's level and title
$stmt = $pdo->prepare("SELECT level, title FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userProfile = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?>'s Profile</title>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
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
    transition: width 0.5s ease-in-out;
    border-radius: 20px;
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
  #user-status {
    font-size: 14px;
    font-weight: bold;
}

#user-status.online {
    color: #6a11cb;
}


.blog-card {
  background-color: #ffffff;
  border-radius: 8px ;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  text-align: center;
}

.blog-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.blog-card img,.blog-card video {
  width: 100%;
  height: 200px;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.blog-card:hover img, .blog-card:hover video {
  transform: scale(1.1);
}

.blog-content {
  padding: 1.5rem;
}

.blog-content h3 {
  font-size: 1.6rem;
  margin-bottom: 1rem;
  font-weight: 700;
  color: initial;
}

.blog-content p {
  font-size: 1rem;
  color: #666;
  margin-bottom: 1.5rem;
}

.blog-content .btn {
  padding: 0.8rem 1.5rem;
  background-color: #6a0dad;
  color: #ffffff;
  border: none;
  border-radius: 5px;
  text-decoration: none;
  font-size: 1rem;
  transition: background 0.3s ease, transform 0.3s ease;
}

.blog-content .btn:hover {
  background-color: #520a7e;
  transform: scale(1.1);
}

/* View All Button */
.view-all {
  margin-top: 2rem;
}

.view-all .btn-large {
  padding: 1rem 2rem;
  background-color: #6a0dad;
  color: #ffffff;
  border-radius: 5px;
  font-size: 1.2rem;
  text-decoration: none;
  transition: background 0.3s ease, transform 0.3s ease;
}

.view-all .btn-large:hover {
  background-color: #520a7e;
  transform: scale(1.1);
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
/* 🔲 Image Modal */
.image-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* 📦 Modal Content */
.image-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    overflow-y: auto;
    border-radius: 10px;
}

/* 🖼️ Zoomable Image */
#modalImage {
    max-width: 100%;
    max-height: 100%;
    transition: transform 0.2s ease-in-out;
    cursor: grab;
}
/* ❌ Close Button */
.close-modal {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 25px;
    font-weight: bold;
    color: white;
    cursor: pointer;
    background: none;
    border: none;
}

.close-modal:hover {
    color: #ff5f5f;
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
      <!-- Sidebar -->
      <?php if ($_SESSION['role'] === 'User'): ?>
    <aside style="height: 100%" class="sidebar">
            <img class=" count" src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class=" count"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
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
            <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                       <!-- Sidebar -->
        <aside class="sidebar" style="overflow-y: scroll;">
        <img class="" src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2 class=""><?php echo $_SESSION['name']; ?></h2>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i>Home</a></li>
                <li><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i>My Profile</a></li>
                <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                <li><a href="admin_users.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                <li><a href="admin_posts.php"><i class="fas fa-file-alt"></i>Manage Posts</a></li>
                <li><a href="admin_comments.php"><i class="fas fa-comments"></i>Manage Comments</a></li>
                <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i>View Reports </a></li>
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
       
        </aside>
            <?php endif; ?><!-- Main Content -->
        <main class="content">
   
    
        <header>
            <div class="profile-header">
                <img src="<?php echo $user['profile_picture'] ?: 'default-avatar.png'; ?>" alt="Profile Picture" >
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <p><?php echo htmlspecialchars($userProfile['title']) . " (Level " . $userProfile['level'] . ")"; ?></p>
                <p><?php echo htmlspecialchars($user['bio']); ?></p>
                <h4 class=""><span id="user-status" class="offline">Offline</span></h4>
                <?php if ($_SESSION['user_id'] != $user_id): ?>
                <button id="follow-btn" class=" btn" data-user-id="<?php echo $user_id; ?>">
                    <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                </button>
                <?php endif; ?>
                    <a href="chat.php?user_id=<?php echo $user_id; ?>#message-input" class=" btn "><i class="fas fa-message"></i> Message <?php if ($message_count > 0): ?><span class="count-badge"><?= $message_count ?></span><?php endif; ?></a>
            </div>
        </header>
        <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
                <li class=" icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class=" icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class=" icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class=" icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class=" icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class=" icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class=" icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class=" icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
                <li  class=" icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class=" icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class=" icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class=" icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class=" icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                <li class=" icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class=" icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class=" icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class=" icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class=" icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class=" icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class=" icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class=" icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class=" icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class=" icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
        <div class="user-profile">
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
        </div><br><br>


            <h3 class="">Posts by <?php echo htmlspecialchars($user['name']); ?>:</h3><br><br>
        <?php foreach ($posts as $post): ?>
            <div class="blog-card">

            <?php if (!empty($post['media'])): ?>
                <?php 
                $mediaFiles = json_decode($post['media'], true);
                if (is_array($mediaFiles) && count($mediaFiles) > 1): 
                    $slideshowId = "slideshow-" . $post['id'];
                ?>
                    <div class="slideshow-container" id="<?= $slideshowId; ?>">
                        <?php foreach ($mediaFiles as $index => $media): ?>
                            <?php $extension = strtolower(pathinfo($media, PATHINFO_EXTENSION)); ?>
                            <div class="mySlides-<?= $post['id']; ?>" style="display: <?= $index === 0 ? 'block' : 'none'; ?>">
                                <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($media); ?>" style="width:100%; max-height:400px; object-fit:cover;">
                                <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                                    <video autoplay muted loop style="width:100%; max-height:400px; object-fit:cover;">
                                        <source src="uploads/<?= htmlspecialchars($media); ?>" type="video/<?= $extension; ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php $extension = strtolower(pathinfo($mediaFiles[0], PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <img src="uploads/<?= htmlspecialchars($mediaFiles[0]); ?>" style="width:100%; max-height:400px; object-fit:cover;">
                    <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                        <video autoplay muted loop style="width:100%; max-height:400px; object-fit:cover;">
                            <source src="uploads/<?= htmlspecialchars($mediaFiles[0]); ?>" type="video/<?= $extension; ?>">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        <div class="blog-content">
        <h3 class=""><?php echo htmlspecialchars($post['title']); ?></h3>
        <p class=""><?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...</p>
        
        <a href="view_post2.php?id=<?php echo $post['id']; ?>" class=" btn">Read More</a>
            </div>
            </div><br>
            
            <!-- Image Modal -->
      <div id="imageModal" style="display: none" class="image-modal">
        <span class="close-modal">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Post Image">
        </div>
    </div>
            
    <?php endforeach; ?>
    <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
              <li class=" icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class=" icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class=" icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class=" icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
              <li class=" icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class=" icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class=" icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class=" icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
                <li  class=" icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class=" icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class=" icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class=" icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class=" icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
                <li class=" icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class=" icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class=" icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class=" icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class=" icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class=" icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class=" icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class=" icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class=" icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class=" icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br><br><br>
    </div><br>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
    const slideshows = document.querySelectorAll('.slideshow-container');

    slideshows.forEach(slideshow => {
        const postId = slideshow.id.split('-')[1];
        const slides = document.querySelectorAll(`.mySlides-${postId}`);
        let slideIndex = 0;

        function showSlides() {
            slides.forEach(slide => {
                slide.style.display = 'none'; // Hide all slides
                const video = slide.querySelector('video');
                if (video) video.pause(); // Pause any playing video
            });

            slideIndex++;
            if (slideIndex > slides.length) {
                slideIndex = 1;
            }

            const currentSlide = slides[slideIndex - 1];
            currentSlide.style.display = 'block'; // Show current slide
            const video = currentSlide.querySelector('video');
            if (video) video.play(); // Play the video if it's a video slide

            setTimeout(showSlides, 3000); // Change slide every 3 seconds
        }

        showSlides(); // Start slideshow
    });
});
        document.querySelector('#follow-btn').addEventListener('click', function () {
        const userId = this.dataset.userId;
        const action = this.innerText.toLowerCase();

        fetch('follow_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.innerText = action === 'follow' ? 'Unfollow' : 'Follow';
            } else {
                alert(data.message || 'An error occurred.');
            }
        });
    });

        function fetchUserStatus(userId) {
    fetch(`get_user_status.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById("user-status");
            if (data.status === "online") {
                statusElement.textContent = "Online";
                statusElement.classList.add("online");
                statusElement.classList.remove("offline");
            } else if (data.status === "offline" && data.last_online) {
                const lastOnlineTime = new Date(data.last_online).toLocaleString();
                statusElement.textContent = 'Offline';
                statusElement.classList.add("offline");
                statusElement.classList.remove("online");
            } else {
                statusElement.textContent = "Offline";
                statusElement.classList.add("offline");
                statusElement.classList.remove("online");
            }
        });
}

// Call the function periodically to update the status
const userId = new URLSearchParams(window.location.search).get('id');
setInterval(() => fetchUserStatus(userId), 1000);
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

document.addEventListener("DOMContentLoaded", function () {
  const elements = document.querySelectorAll(".");

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
    const userId = new URLSearchParams(window.location.search).get('id');
    const followersList = document.getElementById('followers-list');
    const followingList = document.getElementById('following-list');

    // Fetch followers and following
    function fetchFollowData() {
        fetch(`fetch_follow_data.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {

                // Display followers count
                const followersCount = document.createElement('h3');
                followersCount.innerText = `Followers: ${data.followers_count}`;
                document.querySelector('.followers').prepend(followersCount);

                // Display following count
                const followingCount = document.createElement('h3');
                followingCount.innerText = `Following: ${data.following_count}`;
                document.querySelector('.following').prepend(followingCount);

                // Display followers
                followersList.innerHTML = '';
                data.followers.forEach(follower => {
                    const listItem = document.createElement('li');
                    listItem.className = 'follower-item';
                    listItem.innerHTML = `
                        <img src="${follower.profile_picture || 'default-profile.png'}" alt="${follower.name}">
                        <span>${follower.name}</span>
                        <a href="user_profile.php?id=${follower.id}" class="view-profile-btn">View Profile</a>
                    `;
                    followersList.appendChild(listItem);
                });

                // Display following
                followingList.innerHTML = '';
                data.following.forEach(following => {
                    const listItem = document.createElement('li');
                    listItem.className = 'following-item';
                    listItem.innerHTML = `
                        <img src="${following.profile_picture || 'default-profile.png'}" alt="${following.name}">
                        <span>${following.name}</span>
                        <a href="user_profile.php?id=${following.id}" class="view-profile-btn">View Profile</a>
                    `;
                    followingList.appendChild(listItem);
                });
            })
            .catch(error => console.error('Error fetching follow data:', error));
    }

    // Initial fetch
    fetchFollowData();
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
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const closeModal = document.querySelector(".close-modal");

    // Open Image Modal
    document.querySelectorAll(".profile-header img").forEach(image => {
        image.addEventListener("click", function () {
            modalImg.src = this.src;
            modal.style.display = "flex";
            modalImg.style.transform = "scale(1)"; // Reset zoom
        });
    });

    // Close Modal
    closeModal.addEventListener("click", function () {
        modal.style.display = "none";
    });

    // Enable Zoom In and Out
    let scale = 1;
    modalImg.addEventListener("wheel", function (event) {
        event.preventDefault();
        scale += event.deltaY * -0.01;
        scale = Math.min(Math.max(1, scale), 3); // Zoom range (1x to 3x)
        modalImg.style.transform = `scale(${scale})`;
    });
});

    </script>
</body>
</html>
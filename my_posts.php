<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/db.php'; // Include database connection file

$user_id = $_SESSION['user_id'];

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

try {
    // Fetch posts made by the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching posts: " . $e->getMessage());
}
?>

<?php


$user_id = $_SESSION['user_id'];

// Fetch posts created by the logged-in user with counts for likes and comments & Fetch posts with categories and tags
$stmt = $pdo->prepare("
    SELECT 
        posts.id, 
        posts.title, 
        posts.content, 
        posts.media, 
        posts.views, 
        posts.created_at,
        categories.name AS category_name,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
    FROM posts
    LEFT JOIN categories ON posts.category_id = categories.id
    WHERE posts.user_id = ?
    ORDER BY posts.created_at DESC
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$tag_stmt = $pdo->query("
    SELECT post_tags.post_id, tags.name 
    FROM post_tags
    INNER JOIN tags ON post_tags.tag_id = tags.id
");
$tags = $tag_stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        blog-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.blog-card {
  background-color: #ffffff;
  border-radius: 8px;
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
  height: 300px;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.blog-card:hover img, .blog-card:hover video {
  transform: scale(1.1);
}

.blog-flex {
    position: relative;
}

.blog-flex p {
    position: absolute;
    top: 8px;
    right: 16px;
    font-size: 18px;
}

.blog-content {
  padding: 1.2rem;
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
small {
    color: purple;
    padding: 10px;
    font-style: italic;
    border-radius: 5px;
    margin-left: 40px;
    margin-right: auto;
    float: right;
}
.slideshow-container {
    position: relative;
    max-height: 400px;
    overflow: hidden;
    margin: auto;
}

.mySlides {
    display: none; /* Hide all slides by default */
    position: relative;
    animation: fade 1s ease-in-out; /* Add fade animation */
}

@keyframes fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
       <!-- Sidebar -->
       <?php if ($user['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
            <img class="animate-on-scroll count" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll count"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php" class="active"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i> Bookmarks</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                </ul>
            </nav>
        </aside><!-- Main Content -->
            <?php elseif ($user['role'] === 'Admin'): ?>
                       <!-- Sidebar -->
        <aside class="sidebar" style="overflow-y: scroll;">
        <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i>Home</a></li>
                <li><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i>My Profile</a></li>
                <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                <li><a href="admin_users.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                <li><a href="admin_groups.php"><i class="fas fa-users"></i>Manage Groups</a></li>
                <li><a href="admin_posts.php"><i class="fas fa-file-alt"></i>Manage Posts</a></li>
                <li><a href="admin_comments.php"><i class="fas fa-comments"></i>Manage Comments</a></li>
                <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i>View Reports </a></li>
                <li><a href="admin_filters.php"><i class="fas fa-folder-open"></i>Manage Filters</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i> All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php" class="active"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
       
        </aside>
            <?php endif; ?><!-- Main Content -->
        <main class="content">
    
        <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <a href="#"><li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
        <h2 class="animate-on-scroll"><i class="fas fa-file"></i> My Posts</h2><br>
        

        <?php if (isset($_SESSION['success'])): ?>
            <p class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <?php if (count($posts) > 0): ?>
            <ul class="blog-cards">
                <?php foreach ($posts as $post): ?>
                    <li class="blog-card" id="<?php echo $post['id']; ?>">
                        <div class="blog-flex">
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
                       
                        <p class="animate-on-scroll" style="float: right;">
                            <button class="trends-btn"><span class="views" id="views-<?php echo $post['id']; ?>"><?php echo $post['views']; ?>
                            </span><i class="fas fa-eye"></i>
                            View(s)
                            </button>
                            <button class="trends-btn">
                            <span class="like-count" id="like-count-<?php echo $post['id']; ?>">
                                <?php echo $post['like_count']; ?>
                            </span><i class="fas fa-heart"></i>
                            Like(s)
                            </button>
                            <button class="trends-btn">
                            <span class="comment-count" id="comment-count-<?php echo $post['id']; ?>">
                                <?php echo $post['comment_count']; ?>
                            </span><i class="fas fa-comments"></i>
                            Comment(s)
                            </button>
                            <button class="trends-btn">
                            Category: 
                            <?php echo htmlspecialchars($post['category_name'] ?: 'Uncategorized'); ?>
                            </button>
                            <!--<button class="trends-btn">
                            Tags: 
                                <?php 
                                $post_tags = $tags[$post['id']] ?? [];
                                echo $post_tags ? implode(', ', $post_tags) : 'No Tags';
                                ?>
                            </button>-->
                            </p><br>
                            </div> <br>
                            <div class="blog-content">
                            <h3 class="animate-on-scroll"> <?php echo htmlspecialchars($post['title']); ?></h3>
                        <?php echo substr($post['content'], 0, 100); ?>...
                        <!--<small>Posted on: <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></small><br>-->
                           
                        
                        <a href="view_post2.php?id=<?php echo $post['id']; ?>" class="btn">Read More...</a>
                        <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn"><i class="fas fa-pen"></i> Edit</a>
                        <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this post?');"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </li><br><br>
                <?php endforeach; ?>
                <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br><br><br>
            </ul>
        <?php else: ?>
            <p>You haven't created any posts yet.</p>
        <?php endif; ?>
    </div>
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
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', () => {
            const postId = button.getAttribute('data-post-id');
            const likeCountSpan = document.getElementById(`like-count-${postId}`);

            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    likeCountSpan.textContent = data.like_count; // Update like count
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    </script>
    <script>
    function updateCommentCount(postId) {
        const commentCountSpan = document.getElementById(`comment-count-${postId}`);
        fetch('comment_count.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                commentCountSpan.textContent = data.comment_count; // Update comment count
            } else {
                console.error(data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
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
    </script>
</body>
</html>
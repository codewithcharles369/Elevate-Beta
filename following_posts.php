<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;


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

if (!$user_id) {
    header("Location: login.php");
    exit;
}

$filter_sql = ""; // Initialize filter condition
$params = [$user_id]; // Start with the user ID for filtering follows

if (isset($_GET['category_id'])) {
    $filter_sql = "AND posts.category_id = ?";
    $params[] = $_GET['category_id'];
} elseif (isset($_GET['tag_id'])) {
    $filter_sql = "
        INNER JOIN post_tags ON posts.id = post_tags.post_id
        WHERE post_tags.tag_id = ?
    ";
    $params[] = $_GET['tag_id'];
}

// Query to fetch posts by followed users
$stmt = $pdo->prepare("
    SELECT 
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
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS liked
    FROM posts
    LEFT JOIN categories ON posts.category_id = categories.id
    LEFT JOIN users ON posts.user_id = users.id
    INNER JOIN follows ON follows.following_id = posts.user_id
    WHERE follows.follower_id = ?
    $filter_sql
    ORDER BY posts.created_at DESC
");
array_unshift($params, $user_id); // Add the logged-in user ID for the 'liked' column
$stmt->execute($params);

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Following Posts</title>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

.blog-card img {
  width: 100%;
  height: 250px;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.blog-card:hover img {
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
small {
    color: purple;
    padding: 10px;
    border-radius: 5px;
    margin-left: 40px;
    margin-right: auto;
    float: right;
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
     <!-- Sidebar -->
     <?php if ($_SESSION['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
            <img class="animate-on-scroll count" src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll count"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php"  class="active"><i class="fas fa-file-alt"></i>  All Posts</a></li>
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
        <img class="animate-on-scroll" src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
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
                    <li><a href="public_posts.php"  class="active"><i class="fas fa-file-alt"></i> All Posts</a></li>
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
              <a href=""><li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
    <h2>Posts by Users You Follow <i class="fas fa-users"></i></h2>
    <a class="btn animate-on-scroll" style="background-color: #6a11cb;" href="public_posts.php"><i class="fas fa-file-alt"></i> All posts</a>
        <a class="btn animate-on-scroll" style="background-color: #6a11cb;" href="following_posts.php"><i class="fas fa-users"></i> Following</a>
        <a class="btn animate-on-scroll" style="background-color: #6a11cb;" href="trending_posts.php"><i class="fas fa-chart-line"></i> Trends</a>
        
    <!-- Display categories -->
    <?php
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categories as $category):
    ?>
        <a class="btn" style="background-color: #dc3545;" href="public_posts.php?category_id=<?php echo $category['id']; ?>">
            <?php echo htmlspecialchars($category['name']); ?>
        </a>
    <?php endforeach; ?>

    <!-- Display tags -->
    <?php
    $tags = $pdo->query("SELECT * FROM tags")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tags as $tag):
    ?>
        <a class="btn" href="public_posts.php?tag_id=<?php echo $tag['id']; ?>">
            <?php echo htmlspecialchars($tag['name']); ?>
        </a>
    <?php endforeach; ?>
    <input type="search"  name="search" id="search-bar" placeholder="Search posts..." onkeyup="liveSearch()" />
    <div id="search-results"></div>

    
        <?php if ($posts): ?>
            <ul class="blog-cards">
                <?php foreach ($posts as $post): ?>
                    <li class="blog-card">
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
                                            <img class="animate-on-scroll" src="uploads/<?= htmlspecialchars($media); ?>" style="width:100%; max-height:400px; object-fit:cover;">
                                        <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                                            <video class="animate-on-scroll" autoplay muted loop style="width:100%; max-height:400px; object-fit:cover;">
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
                                <img class="animate-on-scroll" src="uploads/<?= htmlspecialchars($mediaFiles[0]); ?>" style="width:100%; max-height:400px; object-fit:cover;">
                            <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                                <video class="animate-on-scroll" autoplay muted loop style="width:100%; max-height:400px; object-fit:cover;">
                                    <source src="uploads/<?= htmlspecialchars($mediaFiles[0]); ?>" type="video/<?= $extension; ?>">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                        <div class="blog-content">
                        <h3 class="animate-on-scroll"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p style="text-align: center; margin: 0 50px;" class="animate-on-scroll"><?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...</p>
                        <a href="view_post2.php?id=<?php echo $post['id']; ?>" class="btn">Read More</a>
                    </li><br><br>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No posts available from users you follow.</p>
        <?php endif; ?>
        <ul class="nav">
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <?php if ($_SESSION['role'] === 'User'): ?>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
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
              <li  class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
            <br>
            <br>
            <br>
            <br>
    </main>
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

        function liveSearch() {
        const query = document.getElementById('search-bar').value.trim();

        if (query === "") {
            document.getElementById('search-results').innerHTML = "";
            return;
        }

        console.log("Sending query: ", query);

        fetch('search_posts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ query: query }),
        })
        .then(response => response.json())
        .then(data => {
            console.log("Response received: ", data);
            const resultsDiv = document.getElementById('search-results');
            resultsDiv.innerHTML = "";

            if (data.length > 0) {
                data.forEach(post => {
                    const postDiv = document.createElement('div');
                    postDiv.className = 'search-result';
                    postDiv.innerHTML = `
                        <h3>${post.title}</h3>
                        <p>${post.content.substring(0, 100)}...</p>
                        <a href="view_post2.php?id=${post.id}">Read more</a>
                    `;
                    resultsDiv.appendChild(postDiv);
                });
            } else {
                resultsDiv.innerHTML = "<p>No results found.</p>";
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
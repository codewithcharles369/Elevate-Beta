<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: login.php");
    exit;
}

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];

$user_id = $_SESSION['user_id'];
$params = [$user_id, $user_id, $user_id, $user_id, $user_id]; // For audience conditions

$filter_conditions = [];
$joins = '';

if (isset($_GET['category_id'])) {
    $filter_conditions[] = 'posts.category_id = ?';
    $params[] = $_GET['category_id'];
}

if (isset($_GET['tag_id'])) {
    $joins .= 'INNER JOIN post_tags ON posts.id = post_tags.post_id ';
    $filter_conditions[] = 'post_tags.tag_id = ?';
    $params[] = $_GET['tag_id'];
}

$filter_sql = '';
if (!empty($filter_conditions)) {
    $filter_sql = 'AND ' . implode(' AND ', $filter_conditions);
}

$stmt = $pdo->prepare("
    SELECT 
        posts.id,
        posts.title,
        posts.content,
        posts.media,
        posts.user_id,
        posts.created_at,
        posts.last_edited_at,
        users.name AS name,
        users.profile_picture AS user_image,
        categories.name AS category_name,
        posts.views AS view_count,
        posts.share_count AS share_count,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
        (SELECT comment FROM comments WHERE comments.post_id = posts.id ORDER BY created_at DESC LIMIT 1) AS latest_comment,
        (SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS user_liked,
        (SELECT 1 FROM bookmarks WHERE bookmarks.post_id = posts.id AND bookmarks.user_id = ?) AS user_bookmarked
    FROM posts
    LEFT JOIN categories ON posts.category_id = categories.id
    LEFT JOIN users ON posts.user_id = users.id
    $joins
    WHERE (scheduled_at IS NULL OR scheduled_at <= NOW())
      AND (
            audience = 'public' 
            OR (audience = 'followers' AND (posts.user_id = ? OR EXISTS (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = posts.user_id)))
            OR (audience = 'private' AND posts.user_id = ?)
      )
    $filter_sql
    ORDER BY posts.created_at DESC
");

$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($filter_sql)) {
    $stmt = $pdo->prepare("
        SELECT 
        posts.id,
        posts.title,
        posts.content,
        posts.media,
        posts.user_id,
        posts.created_at,
        posts.last_edited_at,
        users.name AS name,
        users.id AS user_id,
        categories.name AS category_name,
        users.profile_picture AS user_image,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
           posts.views AS view_count,
           posts.share_count AS share_count,
           (SELECT comment FROM comments WHERE comments.post_id = posts.id ORDER BY created_at DESC LIMIT 1) AS latest_comment,
           (SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS user_liked,
           (SELECT 1 FROM bookmarks WHERE bookmarks.post_id = posts.id AND bookmarks.user_id = ?) AS user_bookmarked
    FROM posts
        LEFT JOIN categories ON posts.category_id = categories.id
        LEFT JOIN users ON posts.user_id = users.id
        WHERE scheduled_at IS NULL OR scheduled_at <= NOW() AND audience = 'public' 
        OR (audience = 'followers' AND (posts.user_id = ? OR EXISTS (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = posts.user_id)))
        OR (audience = 'private' AND posts.user_id = ?)
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $user_id, $user_id, $user_id, $user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Posts</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>


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
  height: 250px;
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
small {
    color: purple;
    padding: 10px;
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
/* ⏰ Time & Author */
.post-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #999;
    margin-top: 10px;
}

.post-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 10px;
}

.post-actions button, .post-actions a {
    background-color: #f4f4f4;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    position: relative;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.post-actions button:hover, .post-actions a:hover {
    background-color: #e0e0e0;
    transform: scale(1.1);
}

.like-icon, .bookmark-icon, .share-icon, .comment-icon {
    font-size: 18px;
    color: #555;
    transition: color 0.3s ease;
}

.like-icon.liked {
    color: #4CAF50; /* Green */
}

.bookmark-icon.bookmarked {
    color: #FFA500; /* Orange */
}

.share-icon {
    color: #6a0dad; /* Purple */
}

/* Tooltip Style */
.post-actions button::after, .post-actions a::after {
    content: attr(aria-label);
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease;
}

.post-actions button:hover::after, .post-actions a:hover::after {
    opacity: 1;
    visibility: visible;
}
/* Share Modal - Light Mode (Keep as Default) */
.share-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.share-modal-content {
    background-color: #fff;
    color: #333;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    position: relative;
    transition: transform 0.4s ease, opacity 0.4s ease;
}

.close-share-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    transition: color 0.3s;
}

.share-buttons button {
    display: block;
    width: 100%;
    margin-top: 10px;
    padding: 10px;
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.share-buttons button:hover {
    background-color: #8e44ad;
}

/* Dark Mode */
body.dark-mode .share-modal-content {
    background-color: #1E1E1E;
    color: #f5f5f5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
}

body.dark-mode .close-share-modal {
    color: #f5f5f5;
}

body.dark-mode .share-buttons button {
    background-color: #6a0dad;
    color: #fff;
}



/* Platform-Specific Button Colors (Optional) */
.share-buttons button#shareFacebook { background-color: #3b5998; color: white; }
.share-buttons button#shareWhatsApp { background-color: #25D366; color: white; }
.share-buttons button#shareTwitter { background-color: #1da1f2; color: white; }
.share-buttons button#shareInstagram { background-color: #833AB4; color: white; }

.share-buttons button:hover {
    opacity: 0.9;
}

.post-placeholder-image {
    background-color: #f3f3f3;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 250px;
    border-bottom: 1px solid #ddd;
}
.post-latest-comment {
    background-color: #f9f9f9;
    border-left: 4px solid #6a0dad;
    padding: 8px 12px;
    margin-top: 10px;
    font-size: 14px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 6px;
}

.post-latest-comment i {
    color: #6a0dad;
}

.post-latest-comment.no-comments {
    background-color: #f3f3f3;
    color: #888;
}
.post-author-info {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 8px;
}

.post-author-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #6a0dad;
}

.post-author-name {
    font-size: 14px;
    font-weight: bold;
    color: #333;
}
.post-card-media {
    position: relative;
    overflow: hidden;
}
.post-category-badge-over-image {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: rgba(106, 13, 173, 0.9); /* Purple */
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 13px;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 12px;
    font-weight: bold;
    z-index: 2;
}
.post-updated-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #FFA500;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 5px;
}
.slideshow-container {
    position: relative;
    max-height: 400px;
    overflow: hidden;
}

.fade-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    transition: opacity 1s ease-in-out;
    opacity: 0;
}

.fade-slide img, .fade-slide video {
    display: block;
    width: 100%;
    height: auto;
}

.fade-slide.active {
    opacity: 1;
    position: relative; /* Ensures the active slide stacks properly */
}
.like-btn {
    position: relative;
    overflow: visible; /* Allow particles to spill outside */
}

.burst-animation {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    pointer-events: none;
    transform: translate(-50%, -50%);
}

.particle {
    position: absolute;
    width: 8px;
    height: 8px;
    background-color: #4CAF50;
    border-radius: 50%;
    opacity: 0;
    transform: scale(0);
    animation: particle-burst 0.6s ease-out forwards;
}

@keyframes particle-burst {
    0% {
        transform: scale(0);
        opacity: 1;
    }
    50% {
        transform: translate(var(--x), var(--y)) scale(1);
        opacity: 0.8;
    }
    100% {
        transform: translate(var(--x), var(--y)) scale(0);
        opacity: 0;
    }
}
.bookmark-icon.flip {
    animation: flipBookmark 0.4s ease;
}

@keyframes flipBookmark {
    0% { transform: rotateY(0deg); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(0deg); }
}
.share-btn {
    position: relative;
    overflow: hidden;
}

.ripple-wave {
    position: absolute;
    width: 0;
    height: 0;
    background: rgba(106, 13, 173, 0.5); /* Purple */
    border-radius: 50%;
    transform: scale(0);
    opacity: 0;
    pointer-events: none;
    transition: transform 0.6s ease-out, opacity 0.6s ease-out;
}

.ripple-wave.active {
    transform: scale(3);
    opacity: 0;
}
.dark-mode-switch-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #333;
}

.dark-mode-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.dark-mode-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #6a0dad;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

body.dark-mode .dark-mode-switch-wrapper {
    color: #f5f5f5;
}
/* Default Light Mode */
.post-card {
    background: #fff;
    color: #333;
    transition: background-color 0.4s ease, color 0.4s ease;
}

/* Dark Mode */
body.dark-mode {
    background-color: #121212;
    color: #f5f5f5;
}

body.dark-mode .blog-card {
    background: #1E1E1E;
    color: #f5f5f5;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.6);
}

body.dark-mode .blog-card h3 {
    color: whitesmoke;
}

body.dark-mode .post-meta span,
body.dark-mode .post-latest-comment {
    color: #bbb;
}

body.dark-mode .post-category-badge,
body.dark-mode .post-category-badge-over-image {
    background-color: #8e44ad;
}
/* Post Author Info */
body.dark-mode .post-author-name {
    color: #f5f5f5;
}
body.dark-mode .post-author-avatar {
    border-color: #6a0dad;
}

/* Post Actions */
body.dark-mode .post-actions button,
body.dark-mode .post-actions a {
    background-color: #2c2c2c;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
}
body.dark-mode .post-actions button:hover,
body.dark-mode .post-actions a:hover {
    background-color: #3a3a3a;
}
body.dark-mode .like-icon,
body.dark-mode .comment-icon,
body.dark-mode .bookmark-icon,
body.dark-mode .share-icon {
    color: #bbb;
}
body.dark-mode .like-icon.liked {
    color: #4CAF50;
}
body.dark-mode .bookmark-icon.bookmarked {
    color: #FFA500;
}
body.dark-mode .share-icon {
    color: #8e44ad;
}

/* Latest Comment */
body.dark-mode .post-latest-comment {
    background-color: #2c2c2c;
    border-left-color: #6a0dad;
    color: #ddd;
}
body.dark-mode .post-latest-comment.no-comments {
    background-color: #262626;
    color: #999;
}
body.dark-mode .post-latest-comment i {
    color: #8e44ad;
}

/* Post Meta Info */
body.dark-mode .post-meta span {
    color: #bbb;
}
body.dark-mode .post-meta i {
    color: #8e44ad;
}
body {
    transition: background-color 0.4s ease, color 0.4s ease;
}
/* Search Bar & Inputs */
body.dark-mode input[type="text"],
body.dark-mode input[type="search"],
body.dark-mode select {
    background-color: #2c2c2c;
    color: #f5f5f5;
    border: 1px solid #444;
}

body.dark-mode input::placeholder {
    color: #bbb;
}

body.dark-mode input:focus,
body.dark-mode select:focus {
    border-color: #8e44ad;
    outline: none;
}



/* Headers */
body.dark-mode h1, body.dark-mode h2, body.dark-mode h3 {
    color: #f5f5f5;
}


.share-modal-content {
    color: black
}

.share-modal-content {
    color: black
}
.copy-notification {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #6a0dad;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, transform 0.3s ease;
    z-index: 9999;
}

.copy-notification.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-10px);
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>" >
<?php if ($user['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
            <img class="animate-on-scroll count" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll count"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php" class="active"><i class="fas fa-file-alt"></i>  All Posts</a></li>
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
                    <li><a href="public_posts.php" class="active"><i class="fas fa-file-alt"></i> All Posts</a></li>
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
            <?php endif; ?>
         <!-- Sidebar -->
         
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
              <a href="#"><li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
            
        <h2><i class="fas fa-file-alt"></i> All Posts</h2>
        <div class="dark-mode-switch-wrapper">
            <label class="dark-mode-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider round"></span>
            </label>
            <span id="darkModeText">Light Mode</span>
        </div>
        <!--All posts-->
        <a class="btn animate-on-scroll" style="background-color: #6a11cb;" href="public_posts.php"><i class="fas fa-file-alt"></i> All posts</a>
        <a class="btn animate-on-scroll" style="background-color: #6a11cb;" href="following_posts.php"><i class="fas fa-users"></i> Following</a>
        <a class="btn animate-on-scroll" style="background-color: #6a11cb;" href="trending_posts.php"><i class="fas fa-chart-line"></i> Trends</a>
        


        <!-- Display categories -->
        <?php
        $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categories as $category):
        ?>
            <a class="btn animate-on-scroll" style="background-color: #dc3545;" href="public_posts.php?category_id=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a>
        <?php endforeach; ?>

        <!-- Display tags -->
        <?php
        $tags = $pdo->query("SELECT * FROM tags")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tags as $tag):
        ?>
            <a class="btn animate-on-scroll" href="public_posts.php?tag_id=<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></a>
        <?php endforeach; ?>
        <input type="search" class="animate-on-scroll"  name="search" id="search-bar"  placeholder="Search posts..." onkeyup="liveSearch()" />
        <div id="search-results"></div><br><br>

    
        <?php if ($posts): ?>
            
            
            <ul class="blog-cards">
            <?php foreach ($posts as $post): ?>
    <li class="blog-card" id="<?= htmlspecialchars($post['id']); ?>">
    <div class="post-card-media">
    <?php 
$mediaFiles = !empty($post['media']) ? json_decode($post['media'], true) : [];

if (!empty($mediaFiles) && is_array($mediaFiles)): 
    if (count($mediaFiles) > 1):
        $slideshowId = "slideshow-" . $post['id'];
?>
        <div class="slideshow-container" id="slideshow-<?= $post['id']; ?>">
    <?php foreach ($mediaFiles as $index => $media): ?>
        <?php $extension = strtolower(pathinfo($media, PATHINFO_EXTENSION)); ?>
        <div class="mySlides-<?= $post['id']; ?> fade-slide" style="opacity: <?= $index === 0 ? '1' : '0'; ?>;">
            <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <img src="uploads/<?= htmlspecialchars($media); ?>" style="width:100%; max-height:300px; object-fit:cover;">
            <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                <video autoplay muted loop style="width:100%; max-height:300px; object-fit:cover;">
                    <source src="uploads/<?= htmlspecialchars($media); ?>" type="video/<?= $extension; ?>">
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
        </div>
    <?php else: ?>
        <?php 
        $extension = strtolower(pathinfo($mediaFiles[0], PATHINFO_EXTENSION));
        ?>
        <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <img class="animate-on-scroll" src="uploads/<?= htmlspecialchars($mediaFiles[0]); ?>" style="width:100%; max-height:400px; object-fit:cover;">
        <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
            <video class="animate-on-scroll" autoplay muted loop style="width:100%; max-height:400px; object-fit:cover;">
                <source src="uploads/<?= htmlspecialchars($mediaFiles[0]); ?>" type="video/<?= $extension; ?>">
                Your browser does not support the video tag.
            </video>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <!-- Fallback Image (Optional) -->
    <div class="post-placeholder-image">
        <img src="assets/no_media_placeholder.png" alt="No Media" style="width:100%; max-height:400px; object-fit:cover; opacity: 0.5;">
    </div>
<?php endif; ?>
<div class="post-category-badge-over-image">
    <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
</div>
    <?php if (!empty($post['last_edited_at'])): ?>
        <span class="post-updated-indicator">Updated</span>
    <?php endif; ?>
</div>
<?php
$userLiked = $post['user_liked'] == 1;
$userBookmarked = $post['user_bookmarked'] == 1;
?>
        
        <div class="blog-content">
        <div class="post-author-info">
                <img style="width: 40px;height: 40px;border-radius: 50%;object-fit: cover;border: 2px solid #6a0dad;" src="<?= $post['user_image'] ?: 'default_avatar.png'; ?>" alt="Avatar">
                <a href="user_profile.php?id=<?= urlencode($post['user_id']); ?>" class="post-author-name">
                    <?= htmlspecialchars($post['name']); ?>
                </a>
        </div>
            <h3 class="animate-on-scroll"><?= htmlspecialchars($post['title']); ?></h3>
            <span style="color: rgb(127, 140, 141);"><?= substr($post['content'], 0, 100); ?></span>
            <div class="post-meta">
                <span><i class="fas fa-eye"></i> <?= $post['view_count'] ?? 0; ?> Views</span>
                <span><i class="fas fa-thumbs-up"></i> <span id="like-count-<?= $post['id']; ?>"><?= $post['like_count']; ?></span> Likes</span>
                <span><i class="fas fa-comment"></i> <?= $post['comment_count'] ?? 0; ?> Comments</span>
                <span><i class="fas fa-share"></i> <span id="share-count-<?= $post['id']; ?>"><?= $post['share_count']; ?></span> Shares</span>
            </div>
            <div class="post-actions">
            <button class="like-btn" data-post-id="<?= $post['id']; ?>" aria-label="Like this post">
                <i class="fas fa-thumbs-up like-icon <?= $post['user_liked'] ? 'liked' : ''; ?>"></i>
                <div class="burst-animation"></div>
            </button>

            <a class="comment-btn" style="text-decoration: none" href="view_post2.php?id=<?= $post['id']; ?>#comment-link" class="animate-on-scroll btn"><i class="fas fa-comment comment-icon"></i></a>

            <button class="bookmark-btn" data-post-id="<?= $post['id']; ?>" aria-label="Bookmark this post">
                <i class="fas fa-bookmark bookmark-icon <?= $post['user_bookmarked'] ? 'bookmarked' : ''; ?>"></i>
                <div class="bookmark-flip-animation"></div>
            </button>

            <button class="share-btn" data-post-id="<?= $post['id']; ?>" data-title="<?= htmlspecialchars($post['title']); ?>" aria-label="Share this post">
                <i class="fas fa-share-alt share-icon"></i>
                <span class="ripple-wave"></span>
            </button>
            </div>
            <?php if (!empty($post['latest_comment'])): ?>
                <div class="post-latest-comment">
                    <i class="fas fa-comment-alt"></i>
                    <span><?= substr($post['latest_comment'], 0, 100); ?>...</span>
                </div>
            <?php else: ?>
                <div class="post-latest-comment no-comments">
                    <i class="fas fa-comment-slash"></i>
                    <span>No comments yet</span>
                </div>
            <?php endif; ?>
            <a  href="view_post2.php?id=<?= $post['id']; ?>" class="animate-on-scroll btn">Read More...</a>
           
        </div>
    </li><br><br>
<?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No posts available.</p>
        <?php endif; ?>

        <div id="shareModal" class="share-modal" style="display:none;">
    <div class="share-modal-content animate-modal">
        <span id="closeShareModal" class="close-share-modal">&times;</span>
        <h3>Share this Post</h3>
        <div class="share-buttons">
            <button id="shareFacebook"><i class="fab fa-facebook-f"></i> Share on Facebook</button>
            <button id="shareWhatsApp"><i class="fab fa-whatsapp"></i> Share on WhatsApp</button>
            <button id="shareTwitter"><i class="fab fa-x-twitter"></i> Share on X (Twitter)</button>
            <button id="shareInstagram"><i class="fab fa-instagram"></i> Copy Link for Instagram</button>
        </div>
    </div>
</div>

<div id="copyNotification" class="copy-notification">Link Copied!</div>

<!-- Sound Effects -->
<audio id="likeSound" src="assets/sounds/like_click.mp3" preload="auto"></audio>
<audio id="bookmarkSound" src="assets/sounds/bookmark_click.mp3" preload="auto"></audio>
<audio id="shareSound" src="assets/sounds/share_click.mp3" preload="auto"></audio>
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
    </main>
        </div>

        <script>
document.addEventListener('DOMContentLoaded', function () {
    const postSlideshowContainers = document.querySelectorAll('.slideshow-container');

    postSlideshowContainers.forEach(container => {
        const slides = container.querySelectorAll('.fade-slide');
        let slideIndex = 0;

        function showSlides() {
            slides.forEach((slide, index) => {
                slide.classList.remove('active');
                slide.style.opacity = '0';
            });

            slides[slideIndex].classList.add('active');
            slides[slideIndex].style.opacity = '1';

            slideIndex++;
            if (slideIndex >= slides.length) {
                slideIndex = 0;
            }
        }

        // Initial display
        showSlides();

        // Change slide every 5 seconds
        setInterval(showSlides, 5000);
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

document.addEventListener('DOMContentLoaded', function () {
    const likeSound = document.getElementById('likeSound');

    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            const postId = this.getAttribute('data-post-id');
            const likeIcon = this.querySelector('.like-icon');
            const likeCountElement = document.getElementById(`like-count-${postId}`);
            const burstContainer = this.querySelector('.burst-animation');

            // Play sound
            likeSound.currentTime = 0;
            likeSound.play().catch(err => console.warn('Like sound play failed:', err));

            // Perform AJAX like request
            fetch('toggle_like.php', {
                method: 'POST',
                body: new URLSearchParams({ post_id: postId }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                likeCountElement.textContent = data.like_count;

                // Handle icon color
                if (data.liked) {
                    likeIcon.classList.add('liked');
                    triggerBurstAnimation(burstContainer);
                } else {
                    likeIcon.classList.remove('liked');
                }
            })
            .catch(error => console.error('Like error:', error));
        });
    });

    function triggerBurstAnimation(container) {
        container.innerHTML = ''; // Clear previous particles

        for (let i = 0; i < 8; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');

            // Random burst position
            const angle = Math.random() * 2 * Math.PI;
            const distance = Math.random() * 40 + 10; // 10-50px distance
            particle.style.setProperty('--x', `${Math.cos(angle) * distance}px`);
            particle.style.setProperty('--y', `${Math.sin(angle) * distance}px`);

            container.appendChild(particle);
        }
    }
});
document.querySelectorAll('.bookmark-btn').forEach(button => {
    button.addEventListener('click', function () {
        const postId = this.getAttribute('data-post-id');
        const bookmarkIcon = this.querySelector('.bookmark-icon');

        // Play sound
        bookmarkSound.currentTime = 0;
        bookmarkSound.play().catch(err => console.warn('Bookmark sound play failed:', err));

        // Perform AJAX toggle
        fetch('toggle_bookmark.php', {
            method: 'POST',
            body: new URLSearchParams({ post_id: postId }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.bookmarked) {
                bookmarkIcon.classList.add('bookmarked');
            } else {
                bookmarkIcon.classList.remove('bookmarked');
            }

            // Trigger flip animation
            bookmarkIcon.classList.add('flip');
            setTimeout(() => {
                bookmarkIcon.classList.remove('flip');
            }, 400);
        })
        .catch(error => console.error('Bookmark error:', error));
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const shareModal = document.getElementById('shareModal');
    const shareModalContent = shareModal.querySelector('.share-modal-content');
    const closeShareModal = document.getElementById('closeShareModal');
    const shareFacebook = document.getElementById('shareFacebook');
    const shareWhatsApp = document.getElementById('shareWhatsApp');
    const shareTwitter = document.getElementById('shareTwitter');
    const shareInstagram = document.getElementById('shareInstagram');

    let currentPostId = null;
    let currentPostTitle = '';

    document.querySelectorAll('.share-btn').forEach(button => {
        button.addEventListener('click', function () {
            currentPostId = this.dataset.postId;
            currentPostTitle = this.dataset.title;
            shareModal.style.display = 'flex';

            // Trigger Animation In
            setTimeout(() => {
                shareModalContent.classList.add('show');
                shareModalContent.classList.remove('hide');
            }, 50);
        });
    });

    closeShareModal.addEventListener('click', function () {
        // Trigger Animation Out
        shareModalContent.classList.add('hide');
        shareModalContent.classList.remove('show');

        // Delay closing modal until animation is complete
        setTimeout(() => {
            shareModal.style.display = 'none';
        }, 300);
    });

    function updateShareCount() {
        if (!currentPostId) return;

        fetch('update_share_count.php', {
            method: 'POST',
            body: new URLSearchParams({ post_id: currentPostId }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(response => response.json())
        .then(data => {
            const shareCountElement = document.getElementById(`share-count-${currentPostId}`);
            if (shareCountElement) {
                shareCountElement.textContent = data.share_count;
            }
        })
        .catch(error => console.error('Share count update failed:', error));
    }

    const baseUrl = window.location.origin;
    const getPostUrl = () => `${baseUrl}/view_post2.php?id=${currentPostId}`;

    shareFacebook.addEventListener('click', () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${getPostUrl()}`, '_blank');
        updateShareCount();
    });

    shareWhatsApp.addEventListener('click', () => {
        window.open(`https://api.whatsapp.com/send?text=Check this post: ${getPostUrl()}`, '_blank');
        updateShareCount();
    });

    shareTwitter.addEventListener('click', () => {
        window.open(`https://twitter.com/intent/tweet?text=${currentPostTitle}&url=${getPostUrl()}`, '_blank');
        updateShareCount();
    });

    shareInstagram.addEventListener('click', () => {
        navigator.clipboard.writeText(getPostUrl())
            .then(() => showCopyNotification())
            .catch(err => console.error('Clipboard error:', err));

        updateShareCount();
    });
});
function showCopyNotification() {
    const notification = document.getElementById('copyNotification');
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, 2000);
}
document.querySelectorAll('.share-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        const ripple = this.querySelector('.ripple-wave');

        // Play sound
        shareSound.currentTime = 0;
        shareSound.play().catch(err => console.warn('Share sound play failed:', err));

        // Position ripple effect at click position
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        ripple.style.width = ripple.style.height = '10px';

        ripple.classList.remove('active'); // Reset animation
        void ripple.offsetWidth; // Force reflow
        ripple.classList.add('active');
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const toggleCheckbox = document.getElementById('darkModeToggle');
    const modeText = document.getElementById('darkModeText');

    // Check manual user preference first
    const userPreference = localStorage.getItem('darkMode');
    const systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (userPreference === 'enabled' || (userPreference === null && systemPreference)) {
        document.body.classList.add('dark-mode');
        toggleCheckbox.checked = true;
        modeText.textContent = 'Dark Mode';
    } else {
        document.body.classList.remove('dark-mode');
        toggleCheckbox.checked = false;
        modeText.textContent = 'Light Mode';
    }

    // Manual Toggle Handler (Overrides system preference)
    toggleCheckbox.addEventListener('change', function () {
        if (this.checked) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('darkMode', 'enabled');
            modeText.textContent = 'Dark Mode';
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('darkMode', 'disabled');
            modeText.textContent = 'Light Mode';
        }
    });
});


</script>
</body>
</html>
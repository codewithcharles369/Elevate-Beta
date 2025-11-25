<?php
include 'includes/db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

// Fetch bookmarked posts
$stmt = $pdo->prepare("
    SELECT posts.id, posts.title, posts.content, posts.media, posts.created_at, users.name AS name,
    posts.share_count AS share_count,
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
    (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.post_id = posts.id) AS bookmark_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comment_count,
    posts.views AS view_count
    FROM bookmarks 
    LEFT JOIN posts ON bookmarks.post_id = posts.id 
    LEFT JOIN users ON posts.user_id = users.id
    WHERE bookmarks.user_id = ? 
    ORDER BY bookmarks.created_at DESC
");
$stmt->execute([$user_id]);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bookmarks</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        
/* Bookmark Card */
.bookmark-card {
    background-color: #ffffff;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 24px;
    position: relative;
    text-align: left;
}

.bookmark-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

/* Post Image/Video */
.bookmark-card img, .bookmark-card video {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.4s ease;
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
}

.bookmark-card img:hover, .bookmark-card video:hover {
    transform: scale(1.05);
}

/* Post Content */
.bookmark-content {
    padding: 16px;
    font-family: 'Roboto', sans-serif;
}

.bookmark-content h3 {
    font-size: 1.4rem;
    color: #333;
    margin-bottom: 10px;
    font-weight: 700;
    text-align: center;
}

.bookmark-content p {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 12px;
    line-height: 1.6;
    text-align: center;
}

/* Read More Button */
.bookmark-content .btn {
    display: inline-block;
    padding: 10px 18px;
    background-color: #6a0dad;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.bookmark-content .btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

/* Remove Bookmark Button */
.remove-bookmark-btn {
    background-color: #dc3545;
    color: white;
    padding: 10px 18px;
    font-size: 0.9rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.remove-bookmark-btn:hover {
    background-color: #c82333;
    transform: scale(1.05);
}

.bookmark-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}


/* Meta Data */
.bookmark-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #777;
    margin-top: 10px;
}

/* Responsive Adjustment */
@media (max-width: 768px) {
    .bookmark-card {
        margin-bottom: 16px;
    }
}

.bookmark-card {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInBookmarkCard 0.6s ease-out forwards;
}

@keyframes fadeInBookmarkCard {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

.bookmark-content .btn i,
.remove-bookmark-btn i {
    margin-right: 6px;
}

.bookmark-content .btn:hover i,
.remove-bookmark-btn:hover i {
    transform: translateX(2px);
    transition: transform 0.3s ease;
}
    /* 🔍 Search Bar Styles */
.search-bar {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    transition: box-shadow 0.3s;
}

.search-bar:focus {
    outline: none;
    box-shadow: 0 0 5px rgba(108, 99, 255, 0.6);
    border-color: #6c63ff;
}



.sort-container {
    margin-bottom: 12px;
    font-size: 0.9rem;
}

.sort-container label {
    margin-right: 8px;
    color: #555;
}

.sort-container select {
    padding: 12px 20px 12px 40px;
    border-radius: 6px;
    border: 1px solid #ddd;
    font-size: 0.9rem;
    cursor: pointer;
    transition: border-color 0.3s;
    margin: 10px;
}

.sort-container select:focus {
    outline: none;
    border-color: #6a0dad;
}
/* Toggle Switch */
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 26px;
}

.switch input {
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
  transition: 0.4s;
  border-radius: 26px;
}

.slider::before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: 0.4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #7b61ff;
}

input:checked + .slider::before {
  transform: translateX(24px);
}

/* Slideshow Media Container */
.author-post-thumb {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background-image: url('assets/Elevate -Your Chance to be more-.jpg');
    background-repeat: no-repeat;
    background-size: cover;
}

.author-post-thumb img,
.author-post-thumb video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 1s ease-in-out;
}

.author-post-thumb img.active,
.author-post-thumb video.active {
    opacity: 1;
}

/* Media Items */
.slideshow-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 180px;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.8s ease-in-out;
}

/* Active Media */
.slideshow-item.active {
    opacity: 1;
}

.bookmark-media-slideshow {
    opacity: 0;
    animation: fadeInSlideshow 0.8s ease-out forwards;
}

@keyframes fadeInSlideshow {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

/* Dark Mode Styles */
body.dark-mode .bookmark-card {
    background-color: #1e1e1e;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.5);
}

body.dark-mode .bookmark-content h3 {
    color: #f5f5f5;
}

body.dark-mode .bookmark-content p {
    color: #d1d1d1;
}

body.dark-mode .bookmark-meta {
    color: #a0a0a0;
}

body.dark-mode .bookmark-content .btn {
    background-color: #bb86fc;
    color: #1e1e1e;
}

body.dark-mode .bookmark-content .btn:hover {
    background-color: #d8b4fe;
    color: #1e1e1e;
}

body.dark-mode .remove-bookmark-btn {
    background-color: #c82333;
}

body.dark-mode .remove-bookmark-btn:hover {
    background-color: #a71d2a;
}

body.dark-mode .search-bar {
    background-color: #1e1e1e;
    color: white;
}
 .slideshow-container {
    position: relative;
    width: 100%;
    height: 140px;
    overflow: hidden;
    background-color: #f5f5f5;
}

.slideshow-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 140px;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.8s ease;
}

.slideshow-item.active {
    opacity: 1;
}
.suggested-post-thumb img.slideshow-item {
    object-fit: cover;
    width: 100%;
    height: 140px;
    display: block;
}
/* ⏰ Time & Author */
.post-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #999;
    margin-top: 10px;
}

/* ===============================
   🔥 ELEVATED BOOKMARK CARD UI 🔥
   =============================== */

.bookmark-card {
    background: rgba(255, 255, 255, 0.75);
    border-radius: 20px;
    overflow: hidden;
    padding-bottom: 8px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);

    /* Modern Shadow */
    box-shadow:
        0 8px 28px rgba(0, 0, 0, 0.15),
        inset 0 0 8px rgba(255, 255, 255, 0.2);

    transition: transform 0.35s ease, box-shadow 0.35s ease;
    margin-bottom: 28px;
    border: 1px solid rgba(255, 255, 255, 0.55);
}

/* Hover Glow */
.bookmark-card:hover {
    transform: translateY(-8px) scale(1.015);
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.22),
        inset 0 0 12px rgba(255, 255, 255, 0.25);
}

/* Media Slideshow Top Section */
.author-post-thumb.slideshow-container {
    height: 210px !important;
    border-radius: 20px 20px 0 0;
    overflow: hidden;
    position: relative;
}

.slideshow-item {
    height: 210px !important;
}

/* Add subtle fade gradient on media */
.author-post-thumb::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 85px;
    background: linear-gradient(transparent, rgba(0,0,0,0.45));
}

/* Content Area */
.bookmark-content {
    padding: 18px;
    text-align: left;
}

/* Title */
.bookmark-content h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #222;
    text-align: center;
    margin-bottom: 10px;
}

/* Description */
.bookmark-content p {
    font-size: 0.97rem;
    line-height: 1.55;
    color: #333;
    text-align: center;
    margin-bottom: 15px;
}

/* Metadata Row */
.post-meta {
    background: rgba(0, 0, 0, 0.05);
    padding: 10px 12px;
    border-radius: 12px;
    margin-bottom: 15px;
    display: flex;
    flex-wrap: wrap;
    font-size: 0.85rem;
    gap: 10px;
    justify-content: space-between;
}

.post-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #555;
}

/* Dark Mode Metadata */
body.dark-mode .post-meta {
    background: rgba(255, 255, 255, 0.06);
    color: #ccc;
}

body.dark-mode .post-meta span {
    color: #ccc;
}

/* Actions Row */
.bookmark-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
}

/* Buttons */
.bookmark-content .btn {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    padding: 10px 18px;
    font-weight: 600;
    font-size: 0.95rem;
    border-radius: 10px;
    transition: 0.3s;
    color: #fff;
}

.bookmark-content .btn:hover {
    transform: translateY(-3px);
    background: linear-gradient(135deg, #560faa, #1c5ad6);
}

/* Trash button */
.remove-bookmark-btn {
    background: #e63946;
    border-radius: 10px;
    padding: 10px 18px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: 0.3s ease;
}

.remove-bookmark-btn:hover {
    background: #d62839;
    transform: translateY(-3px) scale(1.05);
}

/* Animate cards one-by-one */
.bookmark-card {
    opacity: 0;
    transform: translateY(20px);
    animation: smoothCardFade 0.6s ease forwards;
}

@keyframes smoothCardFade {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode Complete Support */
body.dark-mode .bookmark-card {
    background: rgba(25, 25, 35, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.07);
    box-shadow:
        0 8px 25px rgba(0, 0, 0, 0.6),
        inset 0 0 8px rgba(255, 255, 255, 0.05);
}

body.dark-mode .bookmark-content h3 {
    color: #f7f7f7;
}

body.dark-mode .bookmark-content p {
    color: #ddd;
}
</style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
   <!-- Sidebar -->
   <?php if ($user['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: auto" class="sidebar">
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
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php" class="active"><i class="fas fa-bookmark"></i> Bookmarks</a></li>
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
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php" class="active"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
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
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <a href="#"><li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>

     

                <h2 style="text-align: center"><i class="fas fa-bookmark"></i> Bookmarks 
                    <div class="mode-toggle">
                    <label class="switch">
                        <input type="checkbox" id="mode-toggle">
                        <span class="slider round"></span>
                    </label>
                    </div>
                </h2>
            <!-- 🔍 Bookmark Search Bar -->
<input type="search" style="border-radius: 10px" id="bookmarkSearch" placeholder="Search bookmarks..." class="search-bar animate-on-scroll">
<div class="sort-container">
<select id="sortBookmarks">
    <option value="date_desc">Newest First</option>
    <option value="date_asc">Oldest First</option>
    <option value="title_asc">Title (A-Z)</option>
    <option value="title_desc">Title (Z-A)</option>
</select>
</div>
              

<div id="bookmarksContainer">
    <?php
    while ($post = $stmt->fetch()) {
        $created_at = new DateTime($post['created_at']);
        $now = new DateTime();
        $interval = $created_at->diff($now);
        $time_ago = $interval->d > 0 ? $interval->d . ' days ago' : 
                    ($interval->h > 0 ? $interval->h . ' hours ago' : 
                    ($interval->i > 0 ? $interval->i . ' mins ago' : 'Just now'));

        echo "<div class='animate-on-scroll bookmark-card bookmark-item'>";
        echo "<div class='author-post-thumb slideshow-container' data-media=". htmlspecialchars($post['media']) .">
        <!-- Images/Videos will be injected via JS -->
        </div>";
        echo "<div class='bookmark-content'>";
        echo "<h3 class='bookmark-title'>{$post['title']}</h3>";
        echo "<p>" . nl2br(substr($post['content'], 0, 100)) . "...</p>";
        echo "
        <div class='post-meta'>
            <span><i class='fas fa-eye'></i> {$post['view_count']}</span>
            <span><i class='fas fa-thumbs-up'></i> <span id='like-count-{$post['id']}'>{$post['like_count']}</span></span>
            <span><i class='fas fa-comment'></i> {$post['comment_count']}</span>
            <span><i class='fas fa-share'></i> <span id='share-count-{$post['id']}'>{$post['share_count']}</span></span>
            <span><i class='fas fa-user'></i> {$post['name']}</span>
            <span><i class='fas fa-clock'></i> $time_ago</span></div>
        ";
        echo "<div class='bookmark-actions'>";
        echo "<a href='view_post2.php?id={$post['id']}' class=' btn'>Read More...</a>";
        echo "<button class='remove-bookmark-btn ' data-post-id='{$post['id']}'><i class='fas fa-trash'></i> Remove</button>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    ?>
</div>
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
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
            <br>
            <br>
            <br>
<script>
   document.getElementById("sortBookmarks").addEventListener("change", function () {
    const sortBy = this.value;

    fetch(`fetch_bookmarks.php?sort=${sortBy}`)
        .then(response => response.json())
        .then(data => {
            const bookmarksContainer = document.getElementById("bookmarksContainer");
            bookmarksContainer.innerHTML = '';

            if (data.success) {
                data.bookmarks.forEach(bookmark => {
                    const timeAgo = new Date(bookmark.created_at).toLocaleDateString();
                    const bookmarkHtml = `
                        <div class="bookmark-card bookmark-item">
                        <div class='author-post-thumb slideshow-container' data-media="${bookmark.media}">
                        <!-- Images/Videos will be injected via JS -->
                        </div>
                            <div class="bookmark-content">
                                <h3 class="bookmark-title">${bookmark.title}</h3>
                                <p>${bookmark.content.substring(0, 100)}...</p>
                                <div class="bookmark-meta">
                                    <span><i class='fas fa-user'></i> ${bookmark.name}</span>
                                    <span><i class="fas fa-clock"></i> ${timeAgo}</span>
                                </div>
                                <a class="btn" href="view_post2.php?id=${bookmark.id}">Read more...</a>
                            </div>
                        </div>
                    `;
                    bookmarksContainer.insertAdjacentHTML('beforeend', bookmarkHtml);
                });
            } else {
                bookmarksContainer.innerHTML = '<p>No bookmarks found.</p>';
            }
        })
        .catch(error => console.error('Error:', error));
});
    document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("bookmarkSearch");
    const bookmarks = document.querySelectorAll(".bookmark-item"); // Make sure bookmark items have this class

    searchInput.addEventListener("input", function () {
        const searchText = this.value.toLowerCase();
        bookmarks.forEach(bookmark => {
            const title = bookmark.querySelector(".bookmark-title").textContent.toLowerCase();
            bookmark.style.display = title.includes(searchText) ? "block" : "none";
        });
    });
});
    document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.remove-bookmark-btn').forEach(button => {
        button.addEventListener('click', function () {
            const postId = this.dataset.postId;
            if (confirm("Are you sure you want to remove this bookmark?")) {
                fetch('remove_bookmark.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.bookmark-card').remove();
                    } else {
                        alert('Failed to remove bookmark.');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
});
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

document.addEventListener('DOMContentLoaded', function () {
    const toggleSwitch = document.getElementById('mode-toggle');
    const currentMode = localStorage.getItem('theme');

    if (currentMode === 'dark') {
        document.body.classList.add('dark-mode');
        toggleSwitch.checked = true;
    } else {
        document.body.classList.add('light-mode');
    }

    toggleSwitch.addEventListener('change', function () {
        if (toggleSwitch.checked) {
            document.body.classList.remove('light-mode');
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
        } else {
            document.body.classList.remove('dark-mode');
            document.body.classList.add('light-mode');
            localStorage.setItem('theme', 'light');
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const slideshowContainers = document.querySelectorAll('.author-post-thumb.slideshow-container');

    slideshowContainers.forEach(container => {
        const mediaData = JSON.parse(container.getAttribute('data-media').replace(/&quot;/g, '"'));
        if (!mediaData || mediaData.length === 0) return;

        container.innerHTML = ''; // Clear placeholder

        mediaData.forEach((mediaSrc, index) => {
            const extension = mediaSrc.split('.').pop().toLowerCase();
            let mediaElement;

            if (['mp4', 'webm', 'ogg'].includes(extension)) {
                mediaElement = document.createElement('video');
                mediaElement.src = `uploads/${mediaSrc}`;
                mediaElement.muted = true;
                mediaElement.loop = true;
                mediaElement.playsInline = true;
                mediaElement.setAttribute('autoplay', 'true');
            } else {
                mediaElement = document.createElement('img');
                mediaElement.src = `uploads/${mediaSrc}`;
                mediaElement.alt = 'Post Media';
            }

            mediaElement.classList.add('slideshow-item');
            if (index === 0) mediaElement.classList.add('active');

            container.appendChild(mediaElement);
        });

        const mediaElements = container.querySelectorAll('.slideshow-item');

        if (mediaElements.length <= 1) {
            if (mediaElements[0]?.tagName === 'VIDEO') mediaElements[0].play();
            return;
        }

        let currentIndex = 0;
        let interval;

        function showMedia(index) {
            mediaElements.forEach((media, i) => {
                media.classList.toggle('active', i === index);
                if (media.tagName === 'VIDEO') {
                    i === index ? media.play() : media.pause();
                }
            });
        }

        function startSlideshow() {
            interval = setInterval(() => {
                currentIndex = (currentIndex + 1) % mediaElements.length;
                showMedia(currentIndex);
            }, 3500);
        }

        container.addEventListener('mouseenter', () => clearInterval(interval));
        container.addEventListener('mouseleave', startSlideshow);

        startSlideshow();
    });
});
</script>
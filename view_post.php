<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


require 'includes/db.php'; // Include database connection file

$user_id =  $_SESSION['user_id'];


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

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT title, content, created_at FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            die("Post not found or you do not have permission to view it.");
        }
    } catch (PDOException $e) {
        die("Error fetching post: " . $e->getMessage());
    }
} else {
    header("Location: my_posts.php");
    exit;
}
?>

<?php
require 'includes/db.php';

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];


    // Fetch the post details
    $stmt = $pdo->prepare("
        SELECT posts.*, 
               categories.name AS category_name, 
               users.name AS name,
               users.profile_picture AS user_image,
               (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comment_count
        FROM posts
        LEFT JOIN categories ON posts.category_id = categories.id
        LEFT JOIN users ON posts.user_id = users.id
        WHERE posts.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo "Post not found.";
        exit;
    }
} else {
    echo "Invalid request.";
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        comments.id AS comment_id, 
        comments.comment, 
        comments.user_id, 
        comments.created_at, 
        users.name, 
        users.profile_picture, 
        (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id) AS like_count,
        EXISTS(SELECT 1 FROM comment_likes WHERE comment_likes.comment_id = comments.id AND comment_likes.user_id = ?) AS user_liked
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ? 
    ORDER BY comments.created_at DESC
");
$stmt->execute([$user_id, $post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($comments as &$comment) {
    $stmt = $pdo->prepare("
        SELECT 
            comment_replies.reply, 
            comment_replies.user_id, 
            comment_replies.id, 
            comment_replies.created_at, 
            users.name, 
            users.profile_picture 
        FROM comment_replies 
        JOIN users ON comment_replies.user_id = users.id 
        WHERE comment_replies.comment_id = ? 
        ORDER BY comment_replies.created_at ASC
    ");
    $stmt->execute([$comment['comment_id']]);
    $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_views, COUNT(DISTINCT user_id) AS unique_views
    FROM post_views
    WHERE post_id = ?
");
$stmt->execute([$post_id]);
$analytics = $stmt->fetch(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

.container {
  width: 80%;
  margin: 0 auto;
}
        /* Blog Header Section with Parallax */
.blog-header {
  background: url('assets/default-group-image.jpg') no-repeat center center/cover;
  height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #fff;
  position: relative;
  background-attachment: fixed; /* Key for Parallax */
  background-size: cover;
}

.blog-header .container {
  z-index: 2;
}

.blog-header::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4); /* Overlay */
  z-index: 1;
}

.hero-content {
  z-index: 3;
  position: relative;
}

.blog-header .blog-title {
  font-size: 3rem;
  font-weight: bold;
  margin: 0 0 1rem;
}

.blog-header .blog-meta {
  font-size: 1rem;
  margin-bottom: 2rem;
  font-style: italic;
}

.blog-header .btn {
  padding: 0.8rem 1.5rem;
  background-color: #6a0dad;
  color: #ffffff;
  text-decoration: none;
  border-radius: 5px;
  font-size: 1.1rem;
  transition: background 0.3s ease, transform 0.3s ease;
}

.blog-header .btn:hover {
  background-color: #520a7e;
  transform: scale(1.1);
}
        .comment, .reply {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

/* Blog Content Section */
.blog-content {
  padding: 3rem 1rem;
  
  margin: 2rem auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
}

.blog-content .container {
  width: 80%;
  margin: 0 auto;
}

.blog-content .blog-image {
  width: 100%;
  height: auto;
  margin-bottom: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.blog-content .blog-body {
  font-size: 1.1rem;
  line-height: 1.8;
}

.blog-content h2 {
  font-size: 1.8rem;
 
  color: whitesmoke;
}

.blog-content p {
  
  text-align: justify;
}

.blog-content blockquote {
  margin: 2rem 0;
  padding: 1rem 2rem;
  font-style: italic;
  background: #f9f9f9;
  border-left: 5px solid #6a0dad;
  color: #555;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
}

.replies {
    margin-left: 150px;
    text-align: right;
}


button {
    margin-right: 10px;
    cursor: pointer;
}
.reply-comment-btn,
.like-comment-btn{
    text-decoration: none;
    color: #fff;
    background-color: #007bff;
    padding: 14px;
    border-radius: 5px;
    font-size: 14px;
    margin-right: 5px;
}
/* Progress Container */
.progress-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 8px;
  background: #e0e0e0;
  z-index: 9999;
}

/* Progress Bar */
.progress-bar {
  height: 8px;
  width: 0%;
  background: #6c63ff;
  transition: width 0.2s ease;
}
.scroll-percentage {
     position: fixed;
     top: 12px;
     right: 16px;
     font-size: 14px;
     color: #6c63ff;
     display: none;
     z-index: 9999;
   }


.share-modal.visible {
  opacity: 1;
  visibility: visible;
}

.share-modal-content {
  padding: 2rem;
  border-radius: 10px;
  text-align: center;
  max-width: 600px;
  width: 90%;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}
#share-btns {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 30px;
 
}

#share{
    display: none;
}

.share-btn {
  background-color: transparent;
  border: 2px solid #ddd;
  color: #555;
  font-size: 16px;
  padding: 12px 20px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  font-weight: bold;
}

.share-btn i {
  margin-right: 10px;
  font-size: 18px;
}

.share-btn:hover {
  background-color: #f1f1f1;
  color: #000;
  border-color: #ccc;
}

#share-facebook:hover  {
  color: #3b5998;
  border-color: #3b5998;
}

#share-facebook {
  background-color: #3b5998;
  color: white;
}

#share-twitter:hover  {
  color: #00acee;
  border-color: #00acee;
}

#share-twitter{
  background-color: #00acee;
  color: white;
}

#share-whatsapp:hover  {
  color: #25d366;
  border-color: #25d366;
}

#share-whatsapp{
  background-color: #25d366;
  color: white;
}

#share-linkedin:hover  {
  color: #0077b5;
  border-color: #0077b5;
}

#share-linkedin{
  background-color: #0077b5;
  color: white;
}
        /* Form Container */
form {
    width: 100%;
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Form Header */
h2 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: 700;
    color: #fff;
}

/* Input Fields */
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    font-size: 14px;
}

form input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
}

form select {
    width: 100%;
    padding: 16px 20px;
    border: none;
    border-radius: 4px;
    background-color: #f1f1f1;
}

 
form button {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    background: #2575fc;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}



form button:hover {
    background: #1e63d4;
}

/* Links */
form a {
    display: block;
    margin-top: 15px;
    text-align: center;
    color: #ddd;
    font-size: 14px;
    text-decoration: none;
}

form a:hover {
    color: white;
    text-decoration: underline;
}

/* Error Message */
.error {
    color: red;
    font-size: 14px;
    margin-bottom: 15px;
    text-align: center;
}

/* Textarea */
form textarea, textarea {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 15px;
}
.blog-content blockquote {
  margin: 2rem 0;
  padding: 1rem 2rem;
  font-style: italic;
  background: #f9f9f9;
  border-left: 5px solid #6a0dad;
  color: #555;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
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
        <img class="animate-on-scroll" src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
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
        <main class="content ">

        <header class="blog-header">
        <div class="container"><br><br><br><br><br>
        <div class="post-analytics">
                <p><button class="trends-btn"><?php echo $analytics['total_views']; ?> Total Views</button> <button class="trends-btn"><?php echo $analytics['unique_views']; ?> Unique Views</button>  <button class="trends-btn"><?php echo $post['views']; ?> Views</button> <button class="trends-btn"><?php echo $post['like_count']; ?> Likes</button><button class="trends-btn"><?php echo $post['comment_count']; ?> Comments</button></p></div>
                <br><br>
                <br><br>
                <div class="hero-content">
                <h1 class="animate-on-scroll blog-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <p class="blog-meta">Published on <span><?php echo date('F j, Y · g:i a', strtotime($post['created_at'])); ?></span> by <a href="user_profile.php?id=<?php echo htmlspecialchars($post['user_id']); ?>"><?php echo htmlspecialchars($post['name']); ?> </a></p>
                <a href="#content" class="btn">Read Blog</a>
            </div>
        </div>
        </header><br><br><br><br><br><br><br><br>
    
        <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> </a></li>
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
            </ul><br>
            <div class="blog-content" id="content">
    
      <!-- Blog Image -->
        <?php if ($post['image']): ?>
            <img style=" width: 100%" src="uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image animate-on-scroll">
        <?php endif; ?>
        <header>
            <h2 class="animate-on-scroll"><?php echo htmlspecialchars($post['title']); ?></h2>
        </header>
        <blockquote class="animate-on-scroll"><?php echo nl2br(htmlspecialchars($post['content'])); ?></blockquote><br>
                <br><br><br>

<form id="comment-form" class="animate-on-scroll" method="POST">
            <h2 class="animate-on-scroll">Comment On Post...</h2>
    <textarea class="animate-on-scroll" name="comment" id="comment" placeholder="Write a comment..." required></textarea>
    <input class="animate-on-scroll" type="hidden" id="post-id" value="<?php echo $post['id']; ?>">
    <input type='hidden' id='poster-id' value='<?php echo $post['user_id']; ?>'>
    <button class="animate-on-scroll" type="button" id="submit-comment"><i class="fas fa-comment"></i>Comment</button>
</form><br><br>

<section id="comments-section">
    <?php foreach ($comments as $comment): ?>
        <div id="<?php echo $comment['comment_id']; ?>" class="animate-on-scroll comment" style="; padding: 20px;">
            <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" class="animate-on-scroll" width="70px" style="border-radius: 50%;">
            <div class="animate-on-scroll" style="display: block; padding: 5px;">
                <strong class="animate-on-scroll"><?php echo htmlspecialchars($comment['name']); ?>:</strong>
                <p class="comment-content" style="font-family: Comic Sans MS" class="animate-on-scroll"><?php echo htmlspecialchars($comment['comment']); ?></p>
                <small class="animate-on-scroll" style="font-size: 13px;"><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></small>
                <div>
                <input type="hidden" id="post-id" value="<?php echo $post['id']; ?>">
                <button class="animate-on-scroll like-comment-btn" data-comment-id="<?php echo $comment['comment_id']; ?>">
                    <i class="<?php echo $comment['user_liked'] ? 'fas fa-thumbs-up' : 'far fa-thumbs-up'; ?>"></i> 
                    (<span id="comment-like-count-<?php echo $comment['comment_id']; ?>"><?php echo $comment['like_count']; ?></span>)
                </button>
                    <input type="hidden" id="post-id" value="<?php echo $post['id']; ?>">
                    <button class="animate-on-scroll reply-comment-btn" data-comment-id="<?php echo $comment['comment_id']; ?>"><i class="fas fa-reply"></i> Reply</button>
                    <!--<?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a class="btn delete-btn" href="admin_delete_comment.php?id=<?php echo $comment['comment_id']; ?>; post_id=<?php echo $post_id; ?>" onclick="return confirm('Delete this comment?');"><i class="fas fa-trash"></i> Delete</a>
                        <?php endif; ?>-->
                    <input class="animate-on-scroll" type="hidden" id="post-id" value="<?php echo $post['id']; ?>">
                    <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
                        <button class="btn edit-comment-btn animate-on-scroll" data-comment-id="<?= $comment['comment_id'] ?>"><i class="fas fa-pen"></i> Edit</button>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] == 'Admin' || $post['user_id'] == $_SESSION['user_id']): ?>
                        <button class="delete-comment-btn btn delete-btn animate-on-scroll" data-comment-id="<?= $comment['comment_id'] ?>"><i class="fas fa-trash"></i> Delete</button>
                    <?php endif; ?>
                </div><br>
                <div id="replies-<?php echo $comment['comment_id']; ?>" class="animate-on-scroll replies">
                    <?php foreach ($comment['replies'] as $reply): ?>
                        <div class="animate-on-scroll reply">
                            <strong class="animate-on-scroll"><?php echo htmlspecialchars($reply['name']); ?>:</strong>
                            <img class="animate-on-scroll" src="<?php echo htmlspecialchars($reply['profile_picture']); ?>" width="50px" style="border-radius: 50%;">
                            <p style="font-family: Comic Sans MS" class="reply-content animate-on-scroll" class="animate-on-scroll"><?php echo htmlspecialchars($reply['reply']); ?></p>
                            <?php if ($_SESSION['user_id'] == $reply['user_id']): ?>
                                <button class="edit-reply-btn animate-on-scroll btn" data-reply-id="<?= $reply['id'] ?>"><i class="fas fa-pencil"></i></button>
                            <?php endif; ?><?php if ($_SESSION['user_id'] == $reply['user_id'] || $post['user_id'] == $_SESSION['user_id']): ?>
                                <button class="delete-reply-btn btn delete-btn animate-on-scroll" data-reply-id="<?= $reply['id'] ?>"><i class="fas fa-trash"></i></button><br>
                            <?php endif; ?>
                            <small class="animate-on-scroll"><?php echo date('F j, Y, g:i a', strtotime($reply['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<a href="my_posts.php" class="btn">Back to My Posts</a><br>
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
            </ul><br>
    </main>
    
    </div>
    <script>
document.getElementById('submit-comment').addEventListener('click', () => {
    const postId = document.getElementById('post-id').value;
    const posterId = document.getElementById('poster-id').value;
    const comment = document.getElementById('comment').value;

    fetch('add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&poster_id=${posterId}&comment=${encodeURIComponent(comment)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const commentsSection = document.getElementById('comments-section');
            const newComment = document.createElement('div');
            newComment.classList.add('comment');
            newComment.innerHTML = `
            <div class="comment" style="; padding: 20px;">
            <img src="<?php echo htmlspecialchars($_SESSION['image']); ?> " width="70px" style="border-radius: 50%;">
              <div style="display: block; padding: 5px;">
              <div style="display: block; padding: 5px"><strong>You:</strong>
              <p >${data.comment}</p>
            <small style="font-size: 13px;">Just now</small>
            </div>
            </div>
            `;
            commentsSection.prepend(newComment);

            document.getElementById('comment').value = ''; // Clear textarea
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
});
document.querySelectorAll('.like-comment-btn').forEach(button => {
    button.addEventListener('click', () => {
        const commentId = button.dataset.commentId;
        const likeCountSpan = document.getElementById(`comment-like-count-${commentId}`);
        const likeIcon = button.querySelector('i');
        const postId = document.getElementById('post-id').value;

        fetch('like_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `comment_id=${commentId}&post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                likeCountSpan.textContent = data.like_count;
                if (data.liked) {
                    likeIcon.classList.remove('far');
                    likeIcon.classList.add('fas');
                } else {
                    likeIcon.classList.remove('fas');
                    likeIcon.classList.add('far');
                }
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
document.querySelectorAll('.reply-comment-btn').forEach(button => {
    button.addEventListener('click', () => {
        const commentId = button.dataset.commentId;
        const postId = document.getElementById('post-id').value;
        const replyText = prompt('Enter your reply:');

        if (replyText) {
            fetch('reply_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `comment_id=${commentId}&post_id=${postId}&reply=${encodeURIComponent(replyText)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const repliesDiv = document.getElementById(`replies-${commentId}`);
                    const newReply = document.createElement('div');
                    newReply.classList.add('reply');
                    newReply.innerHTML = `
                        <img src="${data.reply.profile_picture}" width="50px" style="border-radius: 50%;">
                        <strong>${data.reply.name}:</strong> <p>${data.reply.reply}</p>
                        <small>${new Date(data.reply.created_at).toLocaleString()}</small>
                    `;
                    repliesDiv.appendChild(newReply);
                }
            })
            .catch(error => console.error('Error:', error));
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
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const postId = document.getElementById('post-id').value;

            if (confirm('Are you sure you want to delete this comment?')) {
                fetch('delete_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `comment_id=${commentId}&post_id=${postId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Remove the comment from the DOM
                        this.closest('.comment').remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
});
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.edit-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentDiv = this.closest('.comment');
            const commentId = this.dataset.commentId;
            const commentContent = commentDiv.querySelector('.comment-content');
            const originalText = commentContent.textContent;

            // Create an input field for editing
            const input = document.createElement('textarea');
            input.value = originalText;
            input.style.width = '100%';
            input.style.marginTop = '5px';

            const saveButton = document.createElement('button');
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
            saveButton.className = 'btn ';
            saveButton.style.marginRight = '10px';

            const cancelButton = document.createElement('button');
            cancelButton.innerHTML = '<i class="fas fa-times"></i> Cancel';
            cancelButton.className = 'btn delete-btn ';
            

            // Hide the current content and add editing controls
            commentContent.style.display = 'none';
            commentDiv.appendChild(input);
            commentDiv.appendChild(saveButton);
            commentDiv.appendChild(cancelButton);

            // Handle save action
            saveButton.addEventListener('click', () => {
                const updatedComment = input.value.trim();
                if (updatedComment === '') {
                    alert('Comment cannot be empty.');
                    return;
                }

                fetch('edit_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `comment_id=${commentId}&comment=${encodeURIComponent(updatedComment)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            commentContent.textContent = updatedComment;
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        // Remove editing controls
                        input.remove();
                        saveButton.remove();
                        cancelButton.remove();
                        commentContent.style.display = '';
                    });
            });

            // Handle cancel action
            cancelButton.addEventListener('click', () => {
                input.remove();
                saveButton.remove();
                cancelButton.remove();
                commentContent.style.display = '';
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.delete-reply-btn').forEach(button => {
        button.addEventListener('click', function () {
            const replyId = this.dataset.replyId;

            if (confirm('Are you sure you want to delete this reply?')) {
                fetch('delete_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reply_id=${replyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Remove the reply from the DOM
                        this.closest('.reply').remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
});
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.edit-reply-btn').forEach(button => {
        button.addEventListener('click', function () {
            const replyDiv = this.closest('.reply');
            const replyId = this.dataset.replyId;
            const replyContent = replyDiv.querySelector('.reply-content');
            const originalText = replyContent.textContent;

            // Create an input field for editing
            const input = document.createElement('textarea');
            input.value = originalText;
            input.style.width = '100%';
            input.style.marginTop = '5px';

            const saveButton = document.createElement('button');
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
            saveButton.className = 'btn ';
            saveButton.style.marginRight = '10px';

            const cancelButton = document.createElement('button');
            cancelButton.innerHTML = '<i class="fas fa-times"></i> Cancel';
            cancelButton.className = 'btn delete-btn ';

            // Clear the current content and add editing controls
            replyContent.style.display = 'none';
            replyDiv.appendChild(input);
            replyDiv.appendChild(saveButton);
            replyDiv.appendChild(cancelButton);

            // Handle save action
            saveButton.addEventListener('click', () => {
                const updatedReply = input.value.trim();
                if (updatedReply === '') {
                    alert('Reply cannot be empty.');
                    return;
                }

                fetch('edit_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reply_id=${replyId}&reply=${encodeURIComponent(updatedReply)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            replyContent.textContent = updatedReply;
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        // Remove editing controls
                        input.remove();
                        saveButton.remove();
                        cancelButton.remove();
                        replyContent.style.display = '';
                    });
            });

            // Handle cancel action
            cancelButton.addEventListener('click', () => {
                input.remove();
                saveButton.remove();
                cancelButton.remove();
                replyContent.style.display = '';
            });
        });
    });
});
</script>
    </div>
</body>
</html>
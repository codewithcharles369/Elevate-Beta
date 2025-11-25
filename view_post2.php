<?php
require 'includes/db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: login.php");
    exit;
}

$user_id = $_GET['id'] ?? $_SESSION['user_id'];

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
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
    // Increment the views count
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);

    // Fetch the post details
    $stmt = $pdo->prepare("
        SELECT posts.*, 
               categories.name AS category_name, 
               users.name AS name,
               users.profile_picture AS user_image,
               posts.share_count AS share_count,
               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
               (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.post_id = posts.id) AS bookmark_count,
               (SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS user_liked,
               (SELECT 1 FROM bookmarks WHERE bookmarks.post_id = posts.id AND bookmarks.user_id = ?) AS user_bookmarked,
               (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comment_count
        FROM posts
        LEFT JOIN categories ON posts.category_id = categories.id
        LEFT JOIN users ON posts.user_id = users.id
        WHERE posts.id = ?
    ");
    $stmt->execute([$user_id, $user_id,  $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);


    $authorPosts = [];
$stmt_author_posts = $pdo->prepare("
    SELECT id, title, media, created_at, SUBSTRING(content, 1, 100) AS excerpt
    FROM posts
    WHERE user_id = ? AND id != ?
    ORDER BY created_at DESC
    LIMIT 3
");
$stmt_author_posts->execute([$post['user_id'], $post['id']]);
$authorPosts = $stmt_author_posts->fetchAll(PDO::FETCH_ASSOC);

  
$post_word_count = str_word_count(strip_tags($post['content']));
$reading_time = ceil($post_word_count / 200); // Assuming average reading speed is 200 words per minute


    if (!$post) {
        echo "Post not found.";
        exit;
    }
} else {
    echo "Invalid request.";
    exit;
}

$commentsPerPage = 5; // You can adjust this
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $commentsPerPage;

$stmt = $pdo->prepare("
    SELECT 
        comments.id AS comment_id, 
        comments.comment, 
        comments.user_id, 
        comments.created_at, 
        comments.updated_at, 
        users.name, 
        users.profile_picture, 
        (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id) AS like_count,
        EXISTS(SELECT 1 FROM comment_likes WHERE comment_likes.comment_id = comments.id AND comment_likes.user_id = ?) AS user_liked
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ? 
    ORDER BY comments.created_at DESC
    LIMIT $commentsPerPage OFFSET $offset
");
$stmt->execute([$user_id, $post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$stmtCount->execute([$post_id]);
$totalComments = $stmtCount->fetchColumn();

$totalPages = ceil($totalComments / $commentsPerPage);

foreach ($comments as &$comment) {
    $stmt = $pdo->prepare("
        SELECT 
            comment_replies.id, 
            comment_replies.reply, 
            comment_replies.user_id, 
            comment_replies.created_at, 
            comment_replies.updated_at, 
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
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$stmt = $pdo->prepare("INSERT INTO post_views (post_id, user_id, ip_address) VALUES (?, ?, ?)");
$stmt->execute([$post_id, $user_id, $ip_address]);
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
<?php
$is_following = false;

if (isset($user_id) && $user_id != $post['user_id']) {
    $stmt_follow_check = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt_follow_check->execute([$user_id, $post['user_id']]);
    $is_following = $stmt_follow_check->fetch() !== false;
}

$user_liked = $post['user_liked'] ? true : false;
$user_bookmarked = $post['user_bookmarked'] ? true : false;

if (isset($user_id)) {
    $stmt_like_check = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt_like_check->execute([$user_id, $post['id']]);
    $user_liked = $stmt_like_check->fetch() !== false;

    $stmt_bookmark_check = $pdo->prepare("SELECT * FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt_bookmark_check->execute([$user_id, $post['id']]);
    $user_bookmarked = $stmt_bookmark_check->fetch() !== false;
}

$stmt_next = $pdo->prepare("
    SELECT id, title
    FROM posts
    WHERE created_at > ? 
    ORDER BY created_at ASC
    LIMIT 1
");
$stmt_next->execute([$post['created_at']]);
$nextPost = $stmt_next->fetch(PDO::FETCH_ASSOC);

$stmt_prev = $pdo->prepare("
    SELECT id, title
    FROM posts
    WHERE created_at < ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt_prev->execute([$post['created_at']]);
$prevPost = $stmt_prev->fetch(PDO::FETCH_ASSOC);

$suggestedPosts = [];

$stmt_suggested = $pdo->prepare("
    SELECT id, title, media, created_at, SUBSTRING(content, 1, 100) AS excerpt
    FROM posts
    WHERE category_id = ? AND id != ?
    ORDER BY created_at DESC
    LIMIT 3
");

$stmt_suggested->execute([$post['category_id'], $post['id']]);
$suggestedPosts = $stmt_suggested->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
.container {
  width: 90%;
  margin: 0 auto;
}
        /* Blog Header Section with Parallax */
        .blog-header {
    background-color: #ffffff;
    padding: 24px 28px;
    border-radius: 14px;
    margin-bottom: 24px;
    margin-top: 50px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    transition: background-color 0.3s ease, color 0.3s ease;
}

.blog-title {
    font-size: 2.8rem;
    font-weight: 800;
    color: #1e1e1e;
    margin: 12px 0;
    line-height: 1.3;
    transition: color 0.3s ease;
}

.blog-title::after {
    content: '';
    display: block;
    width: 80px;
    height: 4px;
    background: linear-gradient(45deg, #6a0dad, #9b59b6);
    margin-top: 8px;
    border-radius: 6px;
}

.blog-meta-info {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 12px;
}

.author-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
}

.meta-details {
    display: flex;
    flex-direction: column;
    font-size: 14px;
    color: #555;
    transition: color 0.3s ease;
}

.author-name {
    font-weight: 600;
    font-size: 15px;
    color: #6a0dad;
    text-decoration: none;
    transition: color 0.3s ease;
}

.author-name:hover {
    color: #8e44ad;
    text-decoration: underline;
}

.post-date, .reading-time {
    color: #777;
    font-size: 13px;
    margin-top: 2px;
    transition: color 0.3s ease;
}
.follow-btn {
    background-color: transparent;
    color: #6a0dad;
    border: 2px solid #6a0dad;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.follow-btn:hover {
    background-color: #6a0dad;
    color: white;
    transform: translateY(-2px);
}

.post-category-badge {
    display: inline-block;
    background-color: #6a0dad;
    color: #ffffff;
    font-size: 13px;
    font-weight: bold;
    padding: 5px 14px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(106, 13, 173, 0.2);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}
.blog-header {
    opacity: 0;
    transform: translateY(-10px);
    animation: fadeInHeader 0.6s ease-out forwards;
}

@keyframes fadeInHeader {
    0% { opacity: 0; transform: translateY(-10px); }
    100% { opacity: 1; transform: translateY(0); }
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
  margin-top: 2rem;
  color: whitesmoke;
}

.blog-content p {
  margin: 1rem 0;
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
.share-btn {
    padding: 14px;
    display: inline-block;
    color: white;
    background-color: #007bff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.share-btn:hover {
    background-color: #0056b3;
    color: white;
}
.share-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    width: 300px;
    position: relative;
}

.modal-content h3 {
    margin-bottom: 15px;
    font-size: 1.5rem;
    color: #34495e;
}

.modal-content #shareLinks a {
    display: block;
    margin: 10px 0;
    padding: 10px 15px;
    background-color: #6c63ff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 1rem;
}

.modal-content #shareLinks a:hover {
    background-color: #4a4aad;
}

.close-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 1.5rem;
    color: #333;
    cursor: pointer;
}

.slideshow-container {
    position: relative;
    max-width: 100%;
    margin: auto;
    border-radius: 6px;
    overflow: hidden;
    background: #000; /* Optional background color */
}

.mySlides {
    display: none;
    border-radius: 6px;
    position: relative;
    animation: fade 1s ease-in-out;
}


@keyframes fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    width: auto;
    padding: 16px;
    margin-top: -22px;
    color: white;
    font-weight: bold;
    font-size: 18px;
    border-radius: 0 3px 3px 0;
    user-select: none;
    z-index: 10;
    background: rgba(0, 0, 0, 0.5);
}

.next {
    right: 0;
    border-radius: 3px 0 0 3px;
}

.prev:hover, .next:hover {
    background: rgba(0, 0, 0, 0.8);
}
.dots-container {
    text-align: center;
    margin-top: 15px;
}

.dot {
    height: 12px;
    width: 12px;
    margin: 0 5px;
    background-color: #bbb;
    border-radius: 50%;
    display: inline-block;
    transition: background-color 0.3s ease;
    cursor: pointer;
}

.dot.active {
    background-color: #6a0dad;
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
.close-image-modal {
    position: absolute;
    top: 30px;
    right: 20px;
    font-size: 25px;
    font-weight: bold;
    color: white;
    cursor: pointer;
    background: none;
    border: none;
}

.close-image-modal:hover {
    color: #ff5f5f;
}

.post-hashtags .hashtag {
    background-color: #6A0DAD;
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    margin-right: 5px;
    font-size: 12px;
}
.user-info-card {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 1.5rem;
}

.user-info-card img {
    border-radius: 50%;
    width: 60px;
    height: 60px;
    object-fit: cover;
}

.user-info-card a {
    font-weight: bold;
    text-decoration: none;
    color: #6a0dad;
}

.user-info-card a:hover {
    text-decoration: underline;
}

.follow-btn {
    background-color: #6a0dad;
    color: white;
    padding: 8px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: background 0.3s ease;
}

.follow-btn:hover {
    background-color: #520a7e;
}
.post-content {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 24px;
    line-height: 1.7;
    font-size: 1rem;
    color: #333;
    word-wrap: break-word;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: background-color 0.3s ease, color 0.3s ease;
}
.post-content p {
    margin-bottom: 1.5rem;
    line-height: 1.8;
    font-size: 1.05rem;
    color: #444;
}

.post-content h2, .post-content h3, .post-content h4 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
    color: #6a0dad;
}

.post-content h2 { font-size: 2rem; }
.post-content h3 { font-size: 1.6rem; }
.post-content h4 { font-size: 1.3rem; }

.post-content img, .post-content video {
    max-width: 100%;
    height: auto;
    margin-top: 15px;
    margin-bottom: 15px;
    display: block;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.post-content img:hover, .post-content video:hover {
    transform: scale(1.03);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.post-content blockquote {
    position: relative;
    background: linear-gradient(135deg, #faf7ff, #f4f0ff);
    padding: 18px 24px;
    margin: 1.8rem 0;
    border-left: 5px solid #6a0dad;
    border-radius: 8px;
    font-size: 1.2rem;
    font-style: italic;
    color: #333;
    line-height: 1.8;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: background-color 0.3s ease, color 0.3s ease;
}

.post-content blockquote::before {
    content: "“";
    position: absolute;
    top: -10px;
    left: 12px;
    font-size: 60px;
    font-family: Georgia, serif;
    color: #6a0dad;
    opacity: 0.2;
}

.post-content blockquote p {
    margin: 0;
    color: #444;
    font-size: 1.1rem;
}
.post-content pre {
    background-color: #f5f5f5;
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    color: #333;
}

.post-content {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInPostContent 0.8s ease-out forwards;
}

@keyframes fadeInPostContent {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}
.post-actions {
    display: flex;
    gap: 12px;
    margin-top: 15px;
}

.post-actions button, .other-actions {
    background-color: #f5f5f5;
    border: none;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-size: 14px;
    color: #333;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.post-actions button:hover {
    background-color: #e9e9e9;
    transform: translateY(-2px);
}

.post-actions button:active {
    transform: translateY(1px);
}

.post-actions i {
    font-size: 18px;
    transition: color 0.3s ease;
}

.post-actions .like-icon.liked {
    color: #4caf50;
}

.post-actions .bookmark-icon.bookmarked {
    color: #ffa500;
}

.post-actions .share-icon {
    color: #6a0dad;
}

/* Optional - Tooltip Styling */
.post-actions button[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: -24px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 5px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, transform 0.3s;
}

.post-actions button:hover[data-tooltip]::after {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-4px);
}
.like-icon {
    transition: transform 0.3s ease, color 0.3s ease;
}

.like-icon.liked {
    animation: likeBurst 0.4s ease;
    color: #4caf50;
}

@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.5); }
    100% { transform: scale(1); }
}
.bookmark-icon {
    transition: transform 0.3s ease, color 0.3s ease;
}

.bookmark-icon.bookmarked {
    animation: bookmarkFlip 0.4s ease;
    color: #ffa500;
}

@keyframes bookmarkFlip {
    0% { transform: rotateY(0); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(360deg); }
}
.share-btn {
    position: relative;
    overflow: hidden;
}

.share-btn::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 10px;
    height: 10px;
    background: rgba(106, 13, 173, 0.3);
    border-radius: 50%;
    transform: scale(0);
    transition: transform 0.5s ease, opacity 0.5s ease;
    opacity: 0;
    pointer-events: none;
}

.share-btn:active::after {
    transform: scale(12);
    opacity: 0;
    transition: transform 0.5s ease, opacity 0.5s ease;
}
.sticky-actions {
    position: fixed;
    top: 10px;
    left: 40px;
    right: 40px;
    transform: translateX(-50%);
    background-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 50px;
    padding: 10px 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    transition: bottom 0.4s ease, opacity 0.3s ease;
    z-index: 1000;
    opacity: 0;
    justify-content: center;
    pointer-events: none;
}

.sticky-actions.show {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}

@media screen and (min-width: 600px){
    .sticky-actions{
        margin-left: 25%;
    }
}

.sticky-actions button {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #333;
}

.sticky-actions i {
    font-size: 20px;
    transition: color 0.3s ease;
}

.sticky-actions .like-icon.liked {
    color: #4caf50;
}

.sticky-actions .bookmark-icon.bookmarked {
    color: #ffa500;
}

.sticky-actions .share-icon {
    color: #6a0dad;
}
.suggested-posts {
    margin-top: 40px;
}

.suggested-posts h3 {
    font-size: 1.8rem;
    margin-bottom: 16px;
    color: #333;
}

.suggested-posts-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.suggested-post-card {
    display: flex;
    flex-direction: column;
    background-color: #fff;
    border-radius: 12px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: calc(33.333% - 10px);
    min-height: 250px;
}

.suggested-post-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.suggested-post-thumb img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}

.suggested-post-info {
    padding: 12px;
}

.suggested-post-info h4 {
    font-size: 1.2rem;
    margin-bottom: 6px;
    color: #6a0dad;
}

.suggested-post-info p {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 8px;
}

.suggested-post-info span {
    font-size: 0.85rem;
    color: #777;
}
@media (max-width: 768px) {
    .suggested-post-card {
        width: 100%; /* Full width on mobile */
    }
}


.suggested-posts .slideshow-container {
    position: relative;
    width: 100%;
    height: 140px;
    overflow: hidden;
    background-color: #f5f5f5;
}

.suggested-posts .slideshow-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 140px;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.8s ease;
}

.suggested-posts .slideshow-item.active {
    opacity: 1;
}
.suggested-post-thumb img.slideshow-item {
    object-fit: cover;
    width: 100%;
    height: 140px;
    display: block;
}
/* Section Styling */
.author-other-posts {
    margin-top: 50px;
    padding-top: 10px;
}

.author-other-posts h3 {
    font-size: 1.8rem;
    margin-bottom: 20px;
    color: #333;
}

/* Grid Layout */
.author-posts-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

/* Post Card */
.author-post-card {
    position: relative;
    display: block;
    background-color: #fff;
    border-radius: 14px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: calc(33.333% - 12px);
    height: 180px;
    cursor: pointer;
}

.author-post-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
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

/* Overlay Text */
.author-post-info-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 10px 14px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
    color: #fff;
    z-index: 2;
}

.author-post-info-overlay h4 {
    font-size: 1.1rem;
    margin: 0;
    font-weight: 600;
    line-height: 1.3;
    color: #fff;
    max-height: 48px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.author-post-info-overlay span {
    font-size: 0.85rem;
    color: #ddd;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .author-post-card {
        width: 100%;
        height: 200px;
    }
}
.read-aloud-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #333;
    transition: color 0.3s ease;
}

.read-aloud-btn:hover {
    color: #6a0dad;
}

.sticky-actions .read-aloud-btn {
    font-size: 20px;
    color: #333;
}
/* Post Navigation Section */
.post-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    gap: 12px;
}

.nav-link {
    background-color: #f8f9fd;
    text-decoration: none;
    display: flex;
    flex: 1;
    padding: 14px 18px;
    border-radius: 12px;
    transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
    align-items: center;
    color: #4a0072;
    font-size: 0.95rem;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.nav-link:hover {
    background-color: #e7e4fa;
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
}

.nav-link i {
    font-size: 1.3rem;
    color: #6a0dad;
    transition: transform 0.3s ease;
}

.nav-link:hover i {
    transform: scale(1.1);
    color: #4a0072;
}

.nav-link.prev-post i {
    margin-right: 12px;
}

.nav-link.next-post i {
    margin-left: 12px;
}

.nav-content {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    justify-content: space-between;
}

/* Text Alignment */
.nav-text {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
}

.nav-label {
    font-size: 0.85rem;
    color: #999;
}

.nav-title {
    font-size: 1rem;
    font-weight: 600;
    color: #4a0072;
    max-width: 220px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

/* Responsive Adjustment */
@media (max-width: 768px) {
    .post-navigation {
        flex-direction: column;
        gap: 10px;
    }

    .nav-title {
        max-width: 100%;
    }

    .nav-link i {
        font-size: 1.2rem;
    }
}
.sticky-post-nav {
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.04);
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 10px 16px;
    z-index: 1000;
    transform: translateY(100%);
    transition: transform 0.4s ease;
    margin-left: 25%;
}

@media screen and (max-width: 600px){
    .sticky-post-nav {margin-left: 0;}
}


.sticky-post-nav.show {
    transform: translateY(0);
}

.sticky-post-nav a {
    text-decoration: none;
    color: #6a0dad;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
    text-align: center;
}

.sticky-post-nav a:hover {
    color: #4a0072;
}
/* Comment Container */
.comment {
    background-color: #ffffff;
    padding: 16px;
    margin-bottom: 14px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.comment-header,
.reply-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.comment-avatar,
.reply-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.comment-avatar:hover,
.reply-avatar:hover {
    transform: scale(1.1);
}

.comment-author,
.reply-author {
    font-weight: bold;
    color: #6a0dad;
    text-decoration: none;
    transition: color 0.3s ease;
}

.comment-author:hover,
.reply-author:hover {
    color: #4a0072;
}

.comment-timestamp,
.reply-timestamp {
    font-size: 0.85rem;
    color: #777;
}

.comment-content,
.reply-content {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 8px;
    color: #333;
    font-family: 'Comic Sans MS';
}

.comment-actions,
.reply-actions {
    display: flex;
    gap: 8px;
}

.comment-actions button,
.reply-actions button, .reply-edits, .comment-edits {
    background-color: #f3f3f3;
    border: none;
    border-radius: 8px;
    padding: 6px 10px;
    cursor: pointer;
    color: black;
    font-size: 0.9rem;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.comment-actions button:hover,
.reply-actions button:hover {
    background-color: #e1e1e1;
    transform: translateY(-1px);
}

.comment-actions i,
.reply-actions i {
    margin-right: 6px;
    color: #6a0dad;
}

.replies {
    margin-top: 10px;
    padding-left: 20px;
    border-left: 3px solid #f0f0f0;
    display: none;
}

.reply {
    background-color: #fafafa;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

button.liked i {
    color: #4caf50;
}
/* Comment Form */
#comment-form {
    background: rgba(255, 255, 255, 0.9);
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

#comment-form h2 {
    font-size: 20px;
    color: #4a0072;
    margin-bottom: 12px;
}

#comment-form textarea {
    width: 100%;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    background-color: #fafafa;
    transition: border-color 0.3s ease;
}

#comment-form textarea:focus {
    outline: none;
    border-color: #6a0dad;
}

#submit-comment {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#submit-comment:hover {
    background-color: #4a0072;
}

.no-comments {
    text-align: center;
    font-size: 14px;
    color: #999;
}
.toggle-replies-btn {
    background-color: #f4f4f4;
    border: none;
    color: #6a0dad;
    padding: 6px 12px;
    font-size: 0.9rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.toggle-replies-btn:hover {
    background-color: #e4e4e4;
    transform: translateY(-1px);
}
.like-comment-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    transition: transform 0.2s ease, color 0.3s ease;
}

.like-comment-btn i {
    font-size: 1.2rem;
    color: #666;
    transition: color 0.3s ease, transform 0.2s ease;
}

.like-comment-btn i.liked {
    color: #4caf50;
}

.like-comment-btn i.like-burst {
    transform: scale(1.4);
    transition: transform 0.2s ease;
}
.edited-indicator{
    font-size: 0.8rem;
    color: #888;
    margin-left: 6px;
    font-style: italic;
}
.pagination {
    margin-top: 20px;
    text-align: center;
}

.pagination a {
    padding: 8px 12px;
    margin: 0 4px 4px 0;
    background-color: #f3f3f3;
    color: #6a0dad;
    text-decoration: none;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.pagination a:hover {
    background-color: #e4e4e4;
}

.pagination a.active {
    background-color: #6a0dad;
    color: white;
    font-weight: bold;
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
/* Blog Header Section – Dark Mode */
body.dark-mode .blog-header {
    background-color: #1e1e1e;
    color: #ffffff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

body.dark-mode .blog-title {
    color: #ffffff;
}

body.dark-mode .blog-title::after {
    background: linear-gradient(45deg, #bb86fc, #9b59b6);
}

body.dark-mode .meta-details {
    color: #b3b3b3;
}

body.dark-mode .post-date,
body.dark-mode .reading-time {
    color: #a0a0a0;
}

body.dark-mode .author-name {
    color: #bb86fc;
}

body.dark-mode .author-name:hover {
    color: #d0a1ff;
}

body.dark-mode .author-avatar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

body.dark-mode .follow-btn {
    background-color: transparent;
    color: #bb86fc;
    border-color: #bb86fc;
}

body.dark-mode .follow-btn:hover {
    background-color: #bb86fc;
    color: #1e1e1e;
}

body.dark-mode .post-category-badge {
    background-color: #bb86fc;
    color: #1e1e1e;
    box-shadow: 0 2px 8px rgba(187, 134, 252, 0.3);
}
/* Progress Container */
body.dark-mode .progress-container {
  background: #333;
}

/* Progress Bar */
body.dark-mode .progress-bar {
  background: #9b59b6;
}

/* Share Modal */
body.dark-mode .share-modal-content {
  background-color: #1e1e1e;
  color: #f5f5f5;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
}



body.dark-mode #share-facebook:hover {
  background-color: #3b5998;
  color: white;
  border-color: #3b5998;
}

body.dark-mode #share-facebook {
  background-color: #3b5998;
  color: white;
}

body.dark-mode #share-twitter:hover {
  background-color: #00acee;
  color: white;
  border-color: #00acee;
}

body.dark-mode #share-twitter {
  background-color: #00acee;
  color: white;
}

body.dark-mode #share-whatsapp:hover {
  background-color: #25d366;
  color: white;
  border-color: #25d366;
}

body.dark-mode #share-whatsapp {
  background-color: #25d366;
  color: white;
}

body.dark-mode #share-linkedin:hover {
  background-color: #0077b5;
  color: white;
  border-color: #0077b5;
}

body.dark-mode #share-linkedin {
  background-color: #0077b5;
  color: white;
}
body.dark-mode .post-content {
    background-color: #1e1e1e;
    border-radius: 12px;
    padding: 24px;
    line-height: 1.7;
    font-size: 1rem;
    color: #e0e0e0;
    word-wrap: break-word;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: background-color 0.3s ease, color 0.3s ease;
}
body.dark-mode  .post-content p {
    margin-bottom: 1.5rem;
    line-height: 1.8;
    font-size: 1.05rem;
    color: #d0d0d0;
}

body.dark-mode  .post-content h2, .post-content h3, .post-content h4 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
    color: #a855f7;
}

body.dark-mode  .post-content h2 { font-size: 2rem; }
body.dark-mode  .post-content h3 { font-size: 1.6rem; }
body.dark-mode .post-content h4 { font-size: 1.3rem; }

body.dark-mode .post-content img, .post-content video {
    max-width: 100%;
    height: auto;
    margin-top: 15px;
    margin-bottom: 15px;
    display: block;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

body.dark-mode .post-content img:hover, .post-content video:hover {
    transform: scale(1.03);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
}

body.dark-mode .post-content blockquote {
    position: relative;
    background: linear-gradient(135deg, #2a2a2a, #1e1e1e);
    padding: 18px 24px;
    margin: 1.8rem 0;
    border-left: 5px solid #a855f7;
    border-radius: 8px;
    font-size: 1.2rem;
    font-style: italic;
    color: #e0e0e0;
    line-height: 1.8;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: background-color 0.3s ease, color 0.3s ease;
}

body.dark-mode .post-content blockquote::before {
    content: "“";
    position: absolute;
    top: -10px;
    left: 12px;
    font-size: 60px;
    font-family: Georgia, serif;
    color: #a855f7;
    opacity: 0.2;
}

body.dark-mode .post-content blockquote p {
    margin: 0;
    color: #cccccc;
    font-size: 1.1rem;
}

body.dark-mode .post-content pre {
    background-color: #2d2d2d;
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    color: #e0e0e0;
}

body.dark-mode .post-content {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInPostContent 0.8s ease-out forwards;
}

@keyframes fadeInPostContent {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

body.dark-mode .post-actions {
    display: flex;
    gap: 12px;
    margin-top: 15px;
}

body.dark-mode .post-actions button, .other-actions {
    background-color: #2d2d2d;
    border: none;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-size: 14px;
    color: #e0e0e0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

body.dark-mode .post-actions button:hover {
    background-color: #3a3a3a;
    transform: translateY(-2px);
}

body.dark-mode .post-actions button:active {
    transform: translateY(1px);
}

body.dark-mode .post-actions i {
    font-size: 18px;
    transition: color 0.3s ease;
}

body.dark-mode .post-actions .like-icon.liked {
    color: #4caf50;
}

body.dark-mode .post-actions .bookmark-icon.bookmarked {
    color: #ffa500;
}

body.dark-mode .post-actions .share-icon {
    color: #a855f7;
}

/* Optional - Tooltip Styling */
body.dark-mode .post-actions button[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: -24px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 5px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, transform 0.3s;
}

body.dark-mode .post-actions button:hover[data-tooltip]::after {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-4px);
}

body.dark-mode .like-icon {
    transition: transform 0.3s ease, color 0.3s ease;
}

body.dark-mode .like-icon.liked {
    animation: likeBurst 0.4s ease;
    color: #4caf50;
}

@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.5); }
    100% { transform: scale(1); }
}

body.dark-mode .bookmark-icon {
    transition: transform 0.3s ease, color 0.3s ease;
}

body.dark-mode .bookmark-icon.bookmarked {
    animation: bookmarkFlip 0.4s ease;
    color: #ffa500;
}

@keyframes bookmarkFlip {
    0% { transform: rotateY(0); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(360deg); }
}

body.dark-mode .share-btn {
    position: relative;
    overflow: hidden;
}

body.dark-mode .share-btn::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 10px;
    height: 10px;
    background: rgba(168, 85, 247, 0.3);
    border-radius: 50%;
    transform: scale(0);
    transition: transform 0.5s ease, opacity 0.5s ease;
    opacity: 0;
    pointer-events: none;
}

body.dark-mode .share-btn:active::after {
    transform: scale(12);
    opacity: 0;
    transition: transform 0.5s ease, opacity 0.5s ease;
}
/* Sticky Actions - Dark Mode */
body.dark-mode .sticky-actions {
    background-color: #1e1e1e;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

body.dark-mode .sticky-actions button {
    color: #e0e0e0;
}

body.dark-mode .sticky-actions i {
    color: #d1d1d1;
    transition: color 0.3s ease;
}

body.dark-mode .sticky-actions .like-icon.liked {
    color: #4caf50;
}

body.dark-mode .sticky-actions .bookmark-icon.bookmarked {
    color: #ffa500;
}

body.dark-mode .sticky-actions .share-icon {
    color: #bb86fc;
}

/* Suggested Posts - Dark Mode */
body.dark-mode .suggested-posts h3 {
    color: #f5f5f5;
}

body.dark-mode .suggested-post-card {
    background-color: #1e1e1e;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    color: #f5f5f5;
}

body.dark-mode .suggested-post-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
}

body.dark-mode .suggested-post-info h4 {
    color: #bb86fc;
}

body.dark-mode .suggested-post-info p {
    color: #c7c7c7;
}

body.dark-mode .suggested-post-info span {
    color: #a0a0a0;
}

body.dark-mode .suggested-posts .slideshow-container {
    background-color: #2a2a2a;
}
/* Author Other Posts - Dark Mode */
body.dark-mode .author-other-posts h3 {
    color: #f5f5f5;
}

body.dark-mode .author-post-card {
    background-color: #1e1e1e;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
    color: #f5f5f5;
}

body.dark-mode .author-post-card:hover {
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.6);
}

body.dark-mode .author-post-thumb {
    background-color: #2a2a2a;
    background-image: url('assets/Elevate -Your Chance to be more-.jpg'); /* Optional */
    background-size: cover;
    background-repeat: no-repeat;
}

body.dark-mode .author-post-info-overlay {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
    color: #f5f5f5;
}

body.dark-mode .author-post-info-overlay h4 {
    color: #ffffff;
}

body.dark-mode .author-post-info-overlay span {
    color: #c7c7c7;
}

body.dark-mode .read-aloud-btn {
    color: #d1d1d1;
}

body.dark-mode .read-aloud-btn:hover {
    color: #bb86fc;
}

body.dark-mode .sticky-actions .read-aloud-btn {
    color: #d1d1d1;
}

/* Post Navigation - Dark Mode */
body.dark-mode .nav-link {
    background-color: #1e1e1e;
    color: #d1d1d1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

body.dark-mode .nav-link:hover {
    background-color: #292929;
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5);
}

body.dark-mode .nav-link i {
    color: #bb86fc;
}

body.dark-mode .nav-link:hover i {
    color: #d8b4fe;
}

body.dark-mode .nav-label {
    color: #aaaaaa;
}

body.dark-mode .nav-title {
    color: #d1d1d1;
}

/* Sticky Post Navigation - Dark Mode */
body.dark-mode .sticky-post-nav {
   /* background-color: #1e1e1e; */
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.4);
}

body.dark-mode .sticky-post-nav a {
    color: #bb86fc;
}

body.dark-mode .sticky-post-nav a:hover {
    color: #d8b4fe;
}
/* Comment Container - Dark Mode */
body.dark-mode .comment {
    background-color: #1e1e1e;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
}

body.dark-mode .reply {
    background-color: #1e1e1e;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
}

body.dark-mode .comment-timestamp,
body.dark-mode .reply-timestamp {
    color: #aaaaaa;
}

body.dark-mode .comment-content,
body.dark-mode .reply-content {
    color: #e0e0e0;
}

body.dark-mode .comment-author,
body.dark-mode .reply-author {
    color: #bb86fc;
}

body.dark-mode .comment-author:hover,
body.dark-mode .reply-author:hover {
    color: #d8b4fe;
}

body.dark-mode .comment-actions button,
body.dark-mode .reply-actions button,
body.dark-mode .comment-edits,
body.dark-mode .reply-edits {
    background-color: #2a2a2a;
    color: #e0e0e0;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

body.dark-mode .comment-actions button:hover,
body.dark-mode .reply-actions button:hover {
    background-color: #3a3a3a;
}

body.dark-mode .comment-actions i,
body.dark-mode .reply-actions i {
    color: #bb86fc;
}

body.dark-mode button.liked i {
    color: #4caf50;
}

body.dark-mode .replies {
    border-left: 3px solid #2a2a2a;
}

/* Comment Form - Dark Mode */
body.dark-mode #comment-form {
    background: rgba(30, 30, 30, 0.95);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

body.dark-mode #comment-form h2 {
    color: #bb86fc;
}

body.dark-mode #comment-form textarea {
    background-color: #2a2a2a;
    color: #e0e0e0;
    border-color: #444;
}

body.dark-mode #comment-form textarea:focus {
    border-color: #bb86fc;
}

body.dark-mode #submit-comment {
    background-color: #bb86fc;
    color: #1e1e1e;
}

body.dark-mode #submit-comment:hover {
    background-color: #d8b4fe;
}

/* No Comments Placeholder */
body.dark-mode .no-comments {
    color: #bbbbbb;
}

/* Toggle Replies Button - Dark Mode */
body.dark-mode .toggle-replies-btn {
    background-color: #2a2a2a;
    color: #bb86fc;
}

body.dark-mode .toggle-replies-btn:hover {
    background-color: #3a3a3a;
}

/* Like Comment Button - Dark Mode */
body.dark-mode .like-comment-btn i {
    color: #b3b3b3;
}

body.dark-mode .like-comment-btn i.liked {
    color: #4caf50;
}

body.dark-mode .like-comment-btn i.like-burst {
    transform: scale(1.4);
}

/* Edited Indicator - Dark Mode */
body.dark-mode .edited-indicator {
    color: #b0b0b0;
}

/* Pagination - Dark Mode */
body.dark-mode .pagination a {
    background-color: #2a2a2a;
    color: #bb86fc;
}

body.dark-mode .pagination a:hover {
    background-color: #3a3a3a;
}

body.dark-mode .pagination a.active {
    background-color: #bb86fc;
    color: #1e1e1e;
}
/* Share Modal - Dark Mode */
body.dark-mode .modal-content {
    background: #1e1e1e;
    color: #f5f5f5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

body.dark-mode .modal-content h3 {
    color: #f5f5f5;
}

body.dark-mode .modal-content #shareLinks a {
    background-color: #bb86fc;
    color: #1e1e1e;
}

body.dark-mode .modal-content #shareLinks a:hover {
    background-color: #d8b4fe;
}

body.dark-mode .close-modal {
    color: #f5f5f5;
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">

  <!-- Sidebar -->
        <!-- Sidebar -->
        <?php if ($_SESSION['role'] === 'User'): ?>
    <aside style="height: 100%; overflow-y: scroll" class="sidebar">
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
     
<!-- Main Content -->
        <div class="progress-container">
            <div class="progress-bar"></div>
            <div style="display: none;" class="scroll-percentage">0%</div>
        </div>
        <main class="content">

        
<div class="sticky-actions" id="stickyActions">
    <button class="like-btn sticky-like-btn" data-post-id="<?= $post['id']; ?>" aria-label="Like this post">
        <i class="fas fa-thumbs-up like-icon <?= $user_liked ? 'liked' : ''; ?>"></i>
        <span class="like-count sticky-like-count"><?= $post['like_count'] ?? 0; ?></span>
    </button>

    <button class="bookmark-btn sticky-bookmark-btn" data-post-id="<?= $post['id']; ?>" aria-label="Bookmark this post">
        <i class="fas fa-bookmark bookmark-icon <?= $user_bookmarked ? 'bookmarked' : ''; ?>"></i>
        <span class="bookmark-count sticky-bookmark-count"><?= $post['bookmark_count'] ?? 0; ?></span>
    </button>

    <button class="share-btn sticky-share-btn" data-post-id="<?= $post['id']; ?>" data-title="<?= htmlspecialchars($post['title']); ?>" aria-label="Share this post">
        <i class="fas fa-share-alt share-icon"></i>
        <span class="share-count sticky-share-count"><?= $post['share_count'] ?? 0; ?></span>
    </button>

    <button class="read-aloud-btn" aria-label="Read post aloud">
        <i class="fas fa-volume-up"></i>
    </button>
</div>

    
        <div class="blog-header">
        <a href="public_posts.php?category_id=<?php echo $post['category_id']; ?>">
            <span class="post-category-badge"><?= htmlspecialchars($post['category_name'] ?? 'uncategorized');   ?></span>
        </a>
    <h1 class="blog-title"><?= htmlspecialchars($post['title']); ?></h1>

    <div class="blog-meta-info">
        <img class="author-avatar" src="<?= htmlspecialchars($post['user_image'] ?: 'default_avatar.png'); ?>" alt="Author">
        <div class="meta-details">
            <a href="user_profile.php?id=<?= htmlspecialchars($post['user_id']); ?>" class="author-name"><?= htmlspecialchars($post['name']); ?></a>
            <span class="post-date"><?= date('F j, Y', strtotime($post['created_at'])); ?></span>
            <span class="reading-time"><?= $reading_time; ?> min read</span>
        </div>
        <?php if (isset($user_id) && $user_id != $post['user_id']): ?>
            <button 
                class="follow-btn" 
                data-user-id="<?= $post['user_id']; ?>" 
                data-following="<?= $is_following ? 'true' : 'false'; ?>"
            >
                <?= $is_following ? 'Following' : 'Follow'; ?>
            </button>
        <?php endif; ?>
    </div>
</div>


<div class="mode-toggle">
  <label class="switch">
    <input type="checkbox" id="mode-toggle">
    <span class="slider round"></span>
  </label>
</div>
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
                <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
    <div class="post-content" id="content">
    
      <!-- Blog Image -->
      <?php if (!empty($post['media'])): ?>
    <div class="slideshow-container">
        <?php 
        $mediaFiles = json_decode($post['media'], true);
        foreach ($mediaFiles as $index => $media): 
            $extension = strtolower(pathinfo($media, PATHINFO_EXTENSION));
        ?>
            <div class="mySlides" style="display: <?= $index === 0 ? 'block' : 'none'; ?>;">
                <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <img src="uploads/<?= htmlspecialchars($media); ?>" alt="Post Media" style="width:100%; max-height:400px; object-fit:cover;">
                <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                    <video controls style="width:100%; max-height:400px; object-fit:cover;">
                        <source src="uploads/<?= htmlspecialchars($media); ?>" type="video/<?= $extension; ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <!-- Navigation arrows -->
        <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
        <a class="next" onclick="plusSlides(1)">&#10095;</a>
    </div>
    <!-- Slide indicators -->
    <div class="dots-container">
        <?php foreach ($mediaFiles as $index => $media): ?>
            <span class="dot" onclick="currentSlide(<?= $index + 1; ?>)"></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
        <!--<header>
            <h2 class="animate-on-scroll"><?php echo htmlspecialchars($post['title']); ?></h2>
        </header>-->
        <blockquote class="animate-on-scroll"><?php echo nl2br($post['content']); ?></blockquote><br>
        <?php
            if (!empty($post['hashtags'])) {
                $tags = explode(' ', $post['hashtags']);
                echo "<div class='post-hashtags'>";
                foreach ($tags as $tag) {
                    echo "<span class='hashtag'>$tag</span> ";
                }
                echo "</div>";
            }
        ?>



<div class="post-actions main-actions">
    <button class="like-btn main-like-btn" data-post-id="<?= $post['id']; ?>" aria-label="Like this post">
        <i class="fas fa-thumbs-up like-icon <?= $user_liked ? 'liked' : ''; ?>"></i>
        <span class="like-count main-like-count"><?= $post['like_count'] ?? 0; ?></span>
    </button>

    <button class="bookmark-btn main-bookmark-btn" data-post-id="<?= $post['id']; ?>" aria-label="Bookmark this post">
        <i class="fas fa-bookmark bookmark-icon <?= $user_bookmarked ? 'bookmarked' : ''; ?>"></i>
        <span class="bookmark-count main-bookmark-count"><?= $post['bookmark_count'] ?? 0; ?></span>
    </button>

    <button class="share-btn main-share-btn" data-post-id="<?= $post['id']; ?>" data-title="<?= htmlspecialchars($post['title']); ?>" aria-label="Share this post">
        <i class="fas fa-share-alt share-icon"></i>
        <span class="share-count main-share-count"><?= $post['share_count'] ?? 0; ?></span>
    </button>

    <button class="read-aloud-btn" aria-label="Read post aloud">
        <i class="fas fa-volume-up"></i>
    </button>
    
</div>

    <?php if ($_SESSION['role'] === 'Admin'): ?>
        <a class="animate-on-scroll btn delete-btn" href="admin_delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this post?');"><i class="fas fa-trash"></i> Delete</a>
    <?php endif; ?>



</div>
<!-- Share Modal -->
<div id="shareModal" class="share-modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Share this Post</h3>
        <div id="shareLinks"></div>
    </div>
</div>

<div class="post-navigation">
    <?php if ($prevPost): ?>
        <a href="view_post2.php?id=<?= $prevPost['id']; ?>" class="nav-link prev-post">
            <div class="nav-content">
                <i class="fas fa-arrow-left"></i>
                <div class="nav-text">
                    <span class="nav-label">Previous Post</span>
                    <span class="nav-title"><?= htmlspecialchars($prevPost['title']); ?></span>
                </div>
            </div>
        </a>
    <?php endif; ?>

    <?php if ($nextPost): ?>
        <a href="view_post2.php?id=<?= $nextPost['id']; ?>" class="nav-link next-post">
            <div class="nav-content">
                <div class="nav-text">
                    <span class="nav-label">Next Post</span>
                    <span class="nav-title"><?= htmlspecialchars($nextPost['title']); ?></span>
                </div>
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
    <?php endif; ?>
</div>
<div id="comment-link"></div>   
    <br><br><br>

    

        <form id="comment-form" class="animate-on-scroll" method="POST">
            <h2 class="animate-on-scroll">Comment On Post...</h2>
    <textarea class="animate-on-scroll" name="comment" id="comment" placeholder="Write a comment..." required></textarea>
    <input class="animate-on-scroll" type="hidden" id="post-id" value="<?php echo $post['id']; ?>">
    <input type='hidden' id='poster-id' value='<?php echo $post['user_id']; ?>'>
    <button class="animate-on-scroll" type="button" id="submit-comment" style="display: none;"><i class="fas fa-comment"></i>Comment</button>
</form><br><br>



<section id="comments-section">
    <?php foreach ($comments as $comment): ?>
        <div id="comment-<?= $comment['comment_id']; ?>" class="comment animate-on-scroll">
            <div class="comment-header">
                <a href="user_profile.php?id=<?= htmlspecialchars($comment['user_id']); ?>">
                    <img src="<?= htmlspecialchars($comment['profile_picture']); ?>" alt="User Avatar" class="comment-avatar animate-on-scroll">
                </a>
                <div>
                    <a href="user_profile.php?id=<?= htmlspecialchars($comment['user_id']); ?>" class="comment-author"><?= htmlspecialchars($comment['name']); ?></a>
                    <div class="comment-timestamp" data-time="<?= $comment['created_at']; ?>"><?= date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></div>
                </div>
            </div>

            <p class="comment-content animate-on-scroll"><?= htmlspecialchars($comment['comment']); ?></p>
           
            <?php if (!empty($comment['updated_at'])): ?>
                <span class="edited-indicator">(Edited)</span>
            <?php endif; ?>
            
            <div class="comment-actions">
                <button class="like-comment-btn animate-on-scroll" data-comment-id="<?= $comment['comment_id']; ?>">
                    <i class="<?= $comment['user_liked'] ? 'fas fa-thumbs-up liked' : 'far fa-thumbs-up'; ?>"></i>
                    (<span id="comment-like-count-<?= $comment['comment_id']; ?>"><?= $comment['like_count']; ?></span>)
                </button>
                <button class="reply-comment-btn animate-on-scroll" data-comment-id="<?= $comment['comment_id']; ?>">
                    <i class="fas fa-reply"></i> Reply
                </button>

                <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
                    <button class="edit-comment-btn animate-on-scroll" data-comment-id="<?= $comment['comment_id']; ?>">
                        <i class="fas fa-pen"></i> Edit
                    </button>
                <?php endif; ?>

                <?php if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] === 'Admin' || $post['user_id'] == $_SESSION['user_id']): ?>
                    <button class="delete-comment-btn animate-on-scroll" data-comment-id="<?= $comment['comment_id']; ?>">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                <?php endif; ?>
            </div>

            <button class="toggle-replies-btn" data-comment-id="<?= $comment['comment_id']; ?>">
                Show Replies (<?= count($comment['replies']); ?>)
            </button>

            <div id="replies-<?= $comment['comment_id']; ?>" class="replies animate-on-scroll">
                <?php foreach ($comment['replies'] as $reply): ?>
                    <div class="reply animate-on-scroll">
                        <div class="reply-header">
                            <a href="user_profile.php?id=<?= htmlspecialchars($reply['user_id']); ?>">
                                <img src="<?= htmlspecialchars($reply['profile_picture']); ?>" alt="Reply Avatar" class="reply-avatar">
                            </a>
                            <div>
                                <a href="user_profile.php?id=<?= htmlspecialchars($reply['user_id']); ?>" class="reply-author"><?= htmlspecialchars($reply['name']); ?></a>
                                <div data-time="<?= $reply['created_at']; ?>" class="reply-timestamp"><?= date('F j, Y, g:i a', strtotime($reply['created_at'])); ?></div>
                            </div>
                        </div>

                        <p class="reply-content animate-on-scroll"><?= htmlspecialchars($reply['reply']); ?></p>

                        <?php if (!empty($reply['updated_at'])): ?>
                                <span class="edited-indicator">(Edited)</span>
                            <?php endif; ?>
                        

                        <div class="reply-actions">
                            <?php if ($_SESSION['user_id'] == $reply['user_id']): ?>
                                <button class="edit-reply-btn animate-on-scroll" data-reply-id="<?= $reply['id']; ?>">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            <?php endif; ?>
                            <?php if ($_SESSION['user_id'] == $reply['user_id'] || $post['user_id'] == $_SESSION['user_id']): ?>
                                <button class="delete-reply-btn animate-on-scroll" data-reply-id="<?= $reply['id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<div class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="?id=<?= $post_id; ?>&page=<?= $currentPage - 1; ?>#comment-form">Previous</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?id=<?= $post_id; ?>&page=<?= $i; ?>#comment-form" class="<?= $i === $currentPage ? 'active' : ''; ?>">
            <?= $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="?id=<?= $post_id; ?>&page=<?= $currentPage + 1; ?>#comment-form">Next</a>
    <?php endif; ?>
</div>

    <section class="suggested-posts">
    <h3>Suggested Posts</h3>
    <div class="suggested-posts-grid">
        <?php foreach ($suggestedPosts as $sPost): ?>
            <a href="view_post2.php?id=<?= $sPost['id']; ?>" class="suggested-post-card">
            <?php
                $mediaData = json_decode($sPost['media'], true);
                $hasMedia = !empty($mediaData);
                ?>
                <div class="suggested-post-thumb slideshow-container"
                    data-media='<?php echo htmlspecialchars($sPost['media']); ?>'
                    data-has-media="<?= $hasMedia ? 'true' : 'false'; ?>">
                    <?php if (!$hasMedia): ?>
                        <img src="assets/Elevate -Your Chance to be more-.jpg" alt="Placeholder Image" class="slideshow-item active">
                    <?php endif; ?>
                    <!-- Actual images/videos will be injected here by JS if media exists -->
                </div>
                <div class="suggested-post-info">
                    <h4><?= htmlspecialchars($sPost['title']); ?></h4>
                    <p><?= $sPost['excerpt']; ?>...</p>
                    <span><?= date('F j, Y', strtotime($sPost['created_at'])); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($authorPosts)): ?>
    <section class="author-other-posts">
    <h3>More from <?= htmlspecialchars($post['name']); ?></h3>
    <div class="author-posts-grid">
        <?php foreach ($authorPosts as $aPost): ?>
            <a href="view_post2.php?id=<?= $aPost['id']; ?>" class="author-post-card">
                <div class="author-post-thumb slideshow-container" data-media='<?= htmlspecialchars($aPost['media']); ?>'>
                    <!-- Images/Videos will be injected via JS -->
                </div>
                <div class="author-post-info-overlay">
                    <h4><?= htmlspecialchars($aPost['title']); ?></h4>
                    <span><?= date('F j, Y', strtotime($aPost['created_at'])); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

         <!-- Image Modal -->
         <div id="imageModal" style="display: none" class="image-modal">
        <span class="close-image-modal">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Post Image">
        </div>
    </div>

    <small id="comment-error" style="color: red; display: none;">Comment cannot be empty!</small>
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
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
            <br>
            <br>
            <br>
</section>

    </div>
    <div class="sticky-post-nav" id="stickyPostNav">
    <?php if ($prevPost): ?>
        <a href="view_post2.php?id=<?= $prevPost['id']; ?>" class="nav-link">
            <i class="fas fa-arrow-left"></i> <?= htmlspecialchars($prevPost['title']); ?>
        </a>
    <?php endif; ?>
    <?php if ($nextPost): ?>
        <a style="margin-left: 30px; text-align: right" href="view_post2.php?id=<?= $nextPost['id']; ?>" class="nav-link">
            <?= htmlspecialchars($nextPost['title']); ?> <i class="fas fa-arrow-right"></i>
        </a>
    <?php endif; ?>
</div>
 


    <script>
        document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const closeModal = document.querySelector(".close-image-modal");

    // Open Image Modal
    document.querySelectorAll(".mySlides img").forEach(image => {
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
 document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute("href")).scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    });
});
        
    document.addEventListener('DOMContentLoaded', () => {
    let slideIndex = 0;
    const slides = document.querySelectorAll(".mySlides");
    const dots = document.querySelectorAll(".dot");

    function showSlides(index) {
        if (index >= slides.length) slideIndex = 0;
        if (index < 0) slideIndex = slides.length - 1;

        slides.forEach((slide, idx) => {
            slide.style.display = idx === slideIndex ? "block" : "none";
        });

        dots.forEach((dot, idx) => {
            dot.classList.remove("active");
            if (idx === slideIndex) {
                dot.classList.add("active");
            }
        });
    }

    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

    function currentSlide(n) {
        slideIndex = n - 1;
        showSlides(slideIndex);
    }

    document.querySelector(".prev").addEventListener("click", () => plusSlides(-1));
    document.querySelector(".next").addEventListener("click", () => plusSlides(1));

    dots.forEach((dot, idx) => {
        dot.addEventListener("click", () => currentSlide(idx + 1));
    });

    showSlides(slideIndex);
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
    const commentTextarea = document.getElementById('comment');
    const submitCommentBtn = document.getElementById('submit-comment');

    // Listen for input in the textarea
    commentTextarea.addEventListener('input', () => {
        if (commentTextarea.value.trim() === '') {
            submitCommentBtn.style.display = 'none'; // Hide button if empty
        } else {
            submitCommentBtn.style.display = 'inline-block'; // Show button if not empty
        }
    });
});
document.getElementById('submit-comment').addEventListener('click', () => {
    const postId = document.getElementById('post-id').value;
    const posterId = document.getElementById('poster-id').value;
    const comment = document.getElementById('comment').value.trim(); // Trim whitespace

    if (comment === '') {
        alert('Comment cannot be empty!');
        return;
    }

    const errorText = document.getElementById('comment-error');

if (comment === '') {
    errorText.style.display = 'block';
    return;
} else {
    errorText.style.display = 'none';
}

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
                    <div class="comment-header">
                        <a href="user_profile.php?id=<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                            <img src="<?php echo htmlspecialchars($_SESSION['image']); ?>" alt="User Avatar" class="comment-avatar">

                        </a>
                    <div>
                        <a href="user_profile.php?id=<?= htmlspecialchars($_SESSION['user_id']); ?>" class="comment-author">You:</a>
                        <div class="comment-timestamp">Just Now</div>
                    </div>
                </div>

                <p class="comment-content">${data.comment}</p>
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
        const likeSound = new Audio('assets/sounds/like_click.mp3'); // Adjust path if needed
        

        fetch('like_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `comment_id=${commentId}&post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                likeSound.play();
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
                    <div class="reply-header">
                        <a href="user_profile.php?id=<?= htmlspecialchars($_SESSION['user_id']); ?>">
                            <img src="${data.reply.profile_picture}" alt="Reply Avatar" class="reply-avatar">
                        </a>
                        <div>
                            <a href="user_profile.php?id=<?= htmlspecialchars($_SESSION['user_id']); ?>" class="reply-author">${data.reply.name}:</a>
                            <div class="reply-timestamp">Just now</div>
                        </div>
                    </div>
                    
                    <p class="reply-content">${data.reply.reply}</p>
                    `;
                    repliesDiv.appendChild(newReply);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});
document.addEventListener("scroll", function () {
  const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
  const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
  const scrollPercentage = (scrollTop / scrollHeight) * 100;
  
  const progressBar = document.querySelector(".progress-bar");
  progressBar.style.width = scrollPercentage + "%";
  if (scrollPercentage === 100) {
     progressBar.style.background = "purple"; // Green color on completion
   } else {
     progressBar.style.background = "#6c63ff"; // Default color
   }
   const scrollPercentageText = document.querySelector(".scroll-percentage");
   scrollPercentageText.textContent = Math.round(scrollPercentage) + "%";
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

document.addEventListener("DOMContentLoaded", function () {
        const shareModal = document.getElementById("shareModal");
        const closeModal = document.querySelector(".close-modal");
        const shareLinksContainer = document.getElementById("shareLinks");

        document.querySelectorAll(".share-btn").forEach(button => {
            button.addEventListener("click", function () {
                const postId = this.dataset.postId;
                const postUrl = `${window.location.origin}${window.location.pathname}?id=${postId}`;

                // Increment share count in the database
                fetch('share_post.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error("Error updating share count:", data.message);
                    }
                });

                // Populate share links
                shareLinksContainer.innerHTML = `
                    <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}" target="_blank">Share on <i class="fab fa-facebook-f"></i> Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(postUrl)}" target="_blank">Share on <i class="fab fa-twitter"></i> Twitter</a>
                    <a href="https://wa.me/?text=${encodeURIComponent('Check out this post: ' + postUrl)}" target="_blank">Share on <i class="fab fa-whatsapp"></i> WhatsApp</a>
                `;

                // Show modal
                shareModal.style.display = "flex";
            });
        });

        // Close modal
        closeModal.addEventListener("click", () => {
            shareModal.style.display = "none";
        });

        // Close modal when clicking outside the content
        window.addEventListener("click", (e) => {
            if (e.target === shareModal) {
                shareModal.style.display = "none";
            }
        });
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
            saveButton.className = 'reply-edits';
            saveButton.style.marginRight = '10px';

            const cancelButton = document.createElement('button');
            cancelButton.innerHTML = '<i class="fas fa-times"></i> Cancel';
            cancelButton.className = 'reply-edits';

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
            saveButton.className = 'comment-edits';
            saveButton.style.marginRight = '10px';

            const cancelButton = document.createElement('button');
            cancelButton.innerHTML = '<i class="fas fa-times"></i> Cancel';
            cancelButton.className = 'comment-edits';
            

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
document.addEventListener('DOMContentLoaded', function () {
    const followBtn = document.querySelector('.follow-btn');

    if (followBtn) {
        followBtn.addEventListener('click', function () {
            const userId = this.getAttribute('data-user-id');
            const isFollowing = this.getAttribute('data-following') === 'true';

            fetch('follow_author.php', {
                method: 'POST',
                body: new URLSearchParams({ user_id: userId }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isFollowing) {
                        this.textContent = 'Follow';
                        this.setAttribute('data-following', 'false');
                    } else {
                        this.textContent = 'Following';
                        this.setAttribute('data-following', 'true');
                    }
                } else {
                    console.error('Follow action failed:', data.message);
                }
            })
            .catch(error => console.error('Follow AJAX error:', error));
        });
    }
    if (followBtn) {
    followBtn.addEventListener('mouseover', function () {
        if (this.getAttribute('data-following') === 'true') {
            this.textContent = 'Unfollow';
        }
    });

    followBtn.addEventListener('mouseout', function () {
        if (this.getAttribute('data-following') === 'true') {
            this.textContent = 'Following';
        }
    });
}
});



document.addEventListener('DOMContentLoaded', function () {
    const mainActions = document.querySelector('.main-actions');
    const stickyActions = document.getElementById('stickyActions');

    if (!mainActions || !stickyActions) return;

    const observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                // Main actions visible → Hide sticky actions
                stickyActions.classList.remove('show');
            } else {
                // Main actions hidden → Show sticky actions fixed at top
                stickyActions.classList.add('show');
            }
        },
        {
            root: null,
            threshold: 0.1, // Trigger when at least 10% of main actions is visible
        }
    );

    observer.observe(mainActions);
});

    document.addEventListener('DOMContentLoaded', function () {
    const likeBtns = document.querySelectorAll('.like-btn');
    const bookmarkBtns = document.querySelectorAll('.bookmark-btn');
    const shareBtns = document.querySelectorAll('.share-btn');

    likeBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');

            fetch('toggle_like.php', {
                method: 'POST',
                body: new URLSearchParams({ post_id: postId })
            })
                .then(response => response.json())
                .then(data => {
                    syncActionCounts('like', data.like_count, data.liked);
                });
        });
    });

    bookmarkBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');

            fetch('toggle_bookmark.php', {
                method: 'POST',
                body: new URLSearchParams({ post_id: postId })
            })
                .then(response => response.json())
                .then(data => {
                    syncActionCounts('bookmark', data.bookmark_count, data.bookmarked);
                });
        });
    });

    shareBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');

            fetch('update_share_count.php', {
                method: 'POST',
                body: new URLSearchParams({ post_id: postId })
            })
                .then(response => response.json())
                .then(data => {
                    syncActionCounts('share', data.share_count);
                });
        });
    });

    /**
     * Syncs counts and icon states for both Main and Sticky action buttons.
     * @param {string} actionType - 'like', 'bookmark', or 'share'
     * @param {int} count - Updated count from server
     * @param {bool} [isActive] - Optional (for like/bookmark), whether the action is active (liked/bookmarked)
     */
    function syncActionCounts(actionType, count, isActive = null) {
        const mainCountSpan = document.querySelector(`.main-${actionType}-count`);
        const stickyCountSpan = document.querySelector(`.sticky-${actionType}-count`);
        const mainIcon = document.querySelector(`.main-${actionType}-btn i`);
        const stickyIcon = document.querySelector(`.sticky-${actionType}-btn i`);

        // Update counts
        if (mainCountSpan) mainCountSpan.textContent = count;
        if (stickyCountSpan) stickyCountSpan.textContent = count;

        // Update icon states (for like and bookmark only)
        if (isActive !== null) {
            if (mainIcon) mainIcon.classList.toggle(actionType === 'like' ? 'liked' : 'bookmarked', isActive);
            if (stickyIcon) stickyIcon.classList.toggle(actionType === 'like' ? 'liked' : 'bookmarked', isActive);
        }
    }
});
const likeSound = new Audio('assets/sounds/like_click.mp3');
const bookmarkSound = new Audio('assets/sounds/bookmark_click.mp3');
const shareSound = new Audio('assets/sounds/share_click.mp3');

// Like Button Sound Trigger
document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function () {
        likeSound.play();
    });
});

// Bookmark Button Sound Trigger
document.querySelectorAll('.bookmark-btn').forEach(button => {
    button.addEventListener('click', function () {
        bookmarkSound.play();
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const slideshowContainers = document.querySelectorAll('.suggested-posts .slideshow-container');

    slideshowContainers.forEach(container => {
        const hasMedia = container.getAttribute('data-has-media') === 'true';
        if (!hasMedia) return; // Skip slideshow logic if no media

        const mediaData = JSON.parse(container.getAttribute('data-media').replace(/&quot;/g, '"'));
        if (!mediaData || mediaData.length === 0) return;

        // Remove placeholder if exists
        const placeholder = container.querySelector('.slideshow-item.active');
        if (placeholder) {
            container.removeChild(placeholder);
        }

        // Inject media
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
        if (mediaElements.length <= 1) return; // Single media doesn't need slideshow

        let currentIndex = 0;
        let interval;

        function showMedia(index) {
            mediaElements.forEach((media, i) => {
                media.classList.toggle('active', i === index);
                if (media.tagName === 'VIDEO') {
                    if (i === index) media.play();
                    else media.pause();
                }
            });
        }

        function startSlideshow() {
            interval = setInterval(() => {
                currentIndex = (currentIndex + 1) % mediaElements.length;
                showMedia(currentIndex);
            }, 3000);
        }

        container.addEventListener('mouseenter', () => clearInterval(interval));
        container.addEventListener('mouseleave', startSlideshow);

        startSlideshow();
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


    document.addEventListener('DOMContentLoaded', function () {
    const synth = window.speechSynthesis;
    let isSpeaking = false;
    let utterance = null;
    const pageId = `page-${Date.now()}`; // Unique ID for this page instance

    // Check for ongoing speech from other tabs/pages
    window.addEventListener('storage', function (e) {
        if (e.key === 'readingPage' && e.newValue !== pageId) {
            // Another page started reading → Stop this page's speech
            if (isSpeaking) {
                synth.cancel();
                isSpeaking = false;
                updateSpeakerIcons(false);
            }
        }
    });

    function readPostAloud() {
        const title = document.querySelector('.blog-title')?.innerText.trim();
        const content = document.querySelector('.post-content')?.innerText.trim();

        if (!title || !content) return;

        // Stop current speech if already speaking
        if (isSpeaking) {
            synth.cancel();
            isSpeaking = false;
            updateSpeakerIcons(false);
            sessionStorage.removeItem('readingPage');
            return;
        }

        // Set this page as the active reader (stops other pages)
        sessionStorage.setItem('readingPage', pageId);

        // Start reading this page's content
        const fullText = `${title}. ${content}`;
        utterance = new SpeechSynthesisUtterance(fullText);
        utterance.lang = 'en-US';
        utterance.rate = 1;
        utterance.pitch = 1;

        utterance.onstart = function () {
            isSpeaking = true;
            updateSpeakerIcons(true);
        };

        utterance.onend = function () {
            isSpeaking = false;
            updateSpeakerIcons(false);
            sessionStorage.removeItem('readingPage');
        };

        synth.speak(utterance);
    }

    function updateSpeakerIcons(isSpeaking) {
        const speakerIcons = document.querySelectorAll('.read-aloud-btn i');
        speakerIcons.forEach(icon => {
            if (isSpeaking) {
                icon.classList.remove('fa-volume-up');
                icon.classList.add('fa-stop');
            } else {
                icon.classList.remove('fa-stop');
                icon.classList.add('fa-volume-up');
            }
        });
    }

    // Attach to all read-aloud buttons (both main and sticky)
    document.querySelectorAll('.read-aloud-btn').forEach(button => {
        button.addEventListener('click', readPostAloud);
    });

    // Optional: Stop speech when leaving the page (e.g., closing or refreshing)
    window.addEventListener('beforeunload', function () {
        synth.cancel();
        sessionStorage.removeItem('readingPage');
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const stickyNav = document.getElementById('stickyPostNav');
    let lastScrollY = window.scrollY;

    window.addEventListener('scroll', function () {
        if (window.scrollY > lastScrollY) {
            // Scrolling down → Show
            stickyNav.classList.add('show');
        } else {
            // Scrolling up → Hide
            stickyNav.classList.remove('show');
        }
        lastScrollY = window.scrollY;
    });
});
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-replies-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.getAttribute('data-comment-id');
            const repliesContainer = document.getElementById(`replies-${commentId}`);

            if (repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
                repliesContainer.style.display = 'block';
                this.textContent = 'Hide Replies';
            } else {
                repliesContainer.style.display = 'none';
                const replyCount = repliesContainer.querySelectorAll('.reply').length;
                this.textContent = `Show Replies (${replyCount})`;
            }
        });
    });
});
function timeAgo(dateString) {
    const now = new Date();
    const pastDate = new Date(dateString);
    const seconds = Math.floor((now - pastDate) / 1000);

    if (seconds < 60) {
        return `${seconds} sec${seconds !== 1 ? 's' : ''} ago`;
    } else if (seconds < 3600) {
        const minutes = Math.floor(seconds / 60);
        return `${minutes} min${minutes !== 1 ? 's' : ''} ago`;
    } else if (seconds < 86400) {
        const hours = Math.floor(seconds / 3600);
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    } else if (seconds < 2592000) {
        const days = Math.floor(seconds / 86400);
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    } else if (seconds < 31536000) {
        const months = Math.floor(seconds / 2592000);
        return `${months} month${months !== 1 ? 's' : ''} ago`;
    } else {
        const years = Math.floor(seconds / 31536000);
        return `${years} year${years !== 1 ? 's' : ''} ago`;
    }
}

function updateTimestamps() {
    document.querySelectorAll('.comment-timestamp, .reply-timestamp').forEach(element => {
        const timeValue = element.getAttribute('data-time');
        if (timeValue) {
            element.textContent = timeAgo(timeValue);
        }
    });
}

document.addEventListener('DOMContentLoaded', updateTimestamps);

setInterval(updateTimestamps, 60000);


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

</script>
</body>
</html>


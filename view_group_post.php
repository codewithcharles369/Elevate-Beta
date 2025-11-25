<?php
include 'includes/db.php'; // Database connection
session_start();
$userId = $_SESSION['user_id'];

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);



// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];


$userId = $_SESSION['user_id'] ?? null;
$groupId = $_GET['group_id'] ?? null;
$postId = $_GET['post_id'] ?? null;

if (!$groupId || !$postId) {
    die("Invalid request.");
}

// Fetch group post details
$stmt = $pdo->prepare("
    SELECT gp.*, u.name AS author_name, u.profile_picture,
        (SELECT COUNT(*) FROM group_post_likes WHERE post_id = gp.id) AS like_count,
        (SELECT COUNT(*) FROM group_post_comments WHERE post_id = gp.id) AS comment_count
    FROM group_posts gp
    JOIN users u ON gp.user_id = u.id
    WHERE gp.id = ? AND gp.group_id = ?
");
$stmt->execute([$postId, $groupId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

$post_word_count = str_word_count(strip_tags($post['content']));
$reading_time = ceil($post_word_count / 200); // Assuming average reading speed is 200 words per minute


// Check if the current user is following the post author
if (isset($userId) && $userId != $post['user_id']) {
    $stmt_follow_check = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt_follow_check->execute([$userId, $post['user_id']]);
    $is_following = $stmt_follow_check->fetch() !== false;
}

// Check if the current user has liked this post
$userLikedStmt = $pdo->prepare("SELECT COUNT(*) FROM group_post_likes WHERE post_id = ? AND user_id = ?");
$userLikedStmt->execute([$postId, $userId]);
$userLiked = $userLikedStmt->fetchColumn() > 0;

// Check if the current user has bookmarked this post
$userBookmarkedStmt = $pdo->prepare("SELECT COUNT(*) FROM group_post_bookmarks WHERE post_id = ? AND user_id = ?");
$userBookmarkedStmt->execute([$postId, $userId]);
$userBookmarked = $userBookmarkedStmt->fetchColumn() > 0;

if (!$post) {
    die("Post not found.");
}

// Fetch previous post
$prevPostStmt = $pdo->prepare("
    SELECT id, title
    FROM group_posts
    WHERE group_id = ? AND id < ?
    ORDER BY id DESC
    LIMIT 1
");
$prevPostStmt->execute([$groupId, $postId]);
$prevPost = $prevPostStmt->fetch(PDO::FETCH_ASSOC);

// Fetch next post
$nextPostStmt = $pdo->prepare("
    SELECT id, title
    FROM group_posts
    WHERE group_id = ? AND id > ?
    ORDER BY id ASC
    LIMIT 1
");
$nextPostStmt->execute([$groupId, $postId]);
$nextPost = $nextPostStmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title><?= htmlspecialchars($post['title']) ?> - Group Post</title>
    <style>
        *{
            color: blck
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #333;
        }

        .blog-header {
    background-color: #ffffff;
    padding: 24px 28px;
    border-radius: 14px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    text-align: center;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.blog-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e1e1e;
    margin-bottom: 8px;
    position: relative;
}

.blog-title::after {
    content: '';
    display: block;
    width: 100px;
    height: 4px;
    background: linear-gradient(45deg, #6a0dad, #9b59b6);
    margin: 10px auto 0;
    border-radius: 8px;
}

.blog-meta-info {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 12px;
    gap: 16px;
}

.author-avatar {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.meta-details a {
    color: #6a0dad;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
}

.meta-details a:hover {
    text-decoration: underline;
    color: #9b59b6;
}

.post-date, .reading-time {
    color: #777;
    font-size: 13px;
    margin-top: 2px;
    transition: color 0.3s ease;
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

.follow-btn:active {
    transform: translateY(1px);
}
/* Post Content Section */
.post-content {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

/* Blockquote Styling */
.post-quote {
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

.post-quote::before {
    content: "“";
    position: absolute;
    top: -10px;
    left: 12px;
    font-size: 60px;
    font-family: Georgia, serif;
    color: #6a0dad;
    opacity: 0.2;
}

/* Slideshow */
.slideshow-container {
    position: relative;
    max-width: 100%;
    margin: auto;
    overflow: hidden;
    border-radius: 10px;
}

.slide {
    display: none;
    width: 100%;
    transition: transform 0.5s ease-in-out;
}

.slide img, .slide video {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Navigation Buttons */
.prev, .next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    font-size: 20px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

.prev { left: 10px; }
.next { right: 10px; }

.prev:hover, .next:hover {
    background-color: rgba(0, 0, 0, 0.8);
}

/* Dot Indicators */
.dot-container {
    text-align: center;
    margin-top: 10px;
}

.dot {
    height: 10px;
    width: 10px;
    margin: 5px;
    background-color: #bbb;
    display: inline-block;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.dot.active {
    background-color: #6a0dad;
}


/* Post Actions */
.post-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 15px;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 22px;
    padding: 8px;
    border-radius: 50%;
    transition: transform 0.2s ease, color 0.3s ease;
}

.action-btn:hover {
    transform: scale(1.1);
}

/* Like Button */
.like-icon {
    color: #777;
    transition: transform 0.3s ease, color 0.3s ease;
}

.like-icon.liked {
    color: #4caf50;
    animation: likeBurst 0.4s ease;
}

@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.4); }
    100% { transform: scale(1); }
}

/* Bookmark Button */
.bookmark-icon {
    color: #777;
    transition: transform 0.3s ease, color 0.3s ease;
}

.bookmark-icon.bookmarked {
    color: #ffa500;
    animation: bookmarkFlip 0.4s ease;
}

@keyframes bookmarkFlip {
    0% { transform: rotateY(0); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(360deg); }
}

/* Share Button */
.share-icon {
    color: #6a0dad;
}

.share-btn:active {
    transform: scale(1.2);
}

/* Share Modal Styles */
.share-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.share-modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    width: 300px;
    position: relative;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    animation: fadeIn 0.3s ease-out;
}

/* Close Button */
.close-share-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 1.5rem;
    color: #333;
    cursor: pointer;
}

/* Share Buttons */
.share-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.share-buttons a {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.2s ease;
}

.share-buttons a i {
    margin-right: 8px;
}

/* Social Media Colors */
#share-whatsapp { background: #25d366; }
#share-facebook { background: #3b5998; }
#share-twitter { background: #1da1f2; }
#share-linkedin { background: #0077b5; }

#share-whatsapp:hover { background: #1ebd55; }
#share-facebook:hover { background: #2d4373; }
#share-twitter:hover { background: #0c85d0; }
#share-linkedin:hover { background: #005582; }

/* Copy Link Section */
.copy-link {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.copy-link input {
    flex-grow: 1;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

.copy-link button {
    background: #6a0dad;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease;
}

.copy-link button:hover {
    background: #5a0cb5;
}

/* Success Message */
#copy-success {
    margin-top: 8px;
    color: #4caf50;
    font-size: 14px;
    display: none;
}

/* Animation */
@keyframes fadeIn {
    0% { opacity: 0; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1); }
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
/* Tooltip Styling */
.action-btn[data-tooltip]::after {
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

.action-btn:hover[data-tooltip]::after {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-4px);
}
/* Comment Container */
.comment {
    background-color: #ffffff;
    padding: 16px;
    margin-bottom: 14px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    animation: fadeIn 0.5s ease-in-out;
}

/* Comment Header */
.comment-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

/* User Avatars */
.comment-avatar, .reply-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

/* Comment Author & Timestamp */
.comment-author {
    font-weight: bold;
    color: #6a0dad;
    text-decoration: none;
}

.comment-timestamp {
    font-size: 0.85rem;
    color: #777;
}

/* Comment Content */
.comment-content, .reply-content {
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

/* Like Button */
.like-comment-btn i {
    color: #666;
}

.like-comment-btn i.liked {
    color: #4caf50;
    animation: likeBurst 0.3s ease;
}

@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* Replies Section */
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
}

/* Animations */
@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

.blog-header, .post-content, .comments-section {
    animation: fadeIn 0.8s ease-out;
}

/* Floating Post Actions */
.floating-post-actions {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.9);
    padding: 12px;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    display: flex;
    gap: 12px;
    transition: opacity 0.3s ease, transform 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    z-index: 1000;
}

.floating-post-actions.visible {
    opacity: 1;
    transform: translateY(0);
}

.floating-post-actions button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 22px;
    padding: 8px;
    border-radius: 50%;
    transition: transform 0.2s ease, color 0.3s ease;
}

.floating-post-actions button:hover {
    transform: scale(1.1);
}

/* Like Button */
.floating-post-actions .like-icon {
    color: #777;
    transition: color 0.3s ease;
}

.floating-post-actions .like-icon.liked {
    color: #4caf50;
}

/* Bookmark Button */
.floating-post-actions .bookmark-icon {
    color: #777;
    transition: color 0.3s ease;
}

.floating-post-actions .bookmark-icon.bookmarked {
    color: #ffa500;
}

/* Share Button */
.floating-post-actions .share-icon {
    color: #6a0dad;
}

/* Like Button */
.like-comment-btn i {
    color: #777;
    transition: transform 0.3s ease, color 0.3s ease;
}

.like-comment-btn i.liked {
    color: #4caf50;
    animation: likeBurst 0.4s ease;
}

@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.4); }
    100% { transform: scale(1); }
}
/* Comment Section */
.comment {
    background-color: #ffffff;
    padding: 16px;
    margin-bottom: 14px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.comment-avatar, .reply-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-author {
    font-weight: bold;
    color: #6a0dad;
}

.comment-timestamp {
    font-size: 0.85rem;
    color: #777;
}

.comment-content {
    font-size: 1rem;
    margin-bottom: 8px;
}



.like-comment-btn i.liked {
    color: #4caf50;
}

.replies {
    margin-top: 10px;
    padding-left: 20px;
    border-left: 3px solid #ddd;
    display: none;
}

.reply {
    background-color: #f9f9f9;
    padding: 8px;
    margin-bottom: 8px;
    border-radius: 8px;
}

.no-comments, .no-replies {
    text-align: center;
    color: #777;
}

.edit-comment-form, .edit-reply-form {
    margin-top: 8px;
}

textarea {
    width: 100%;
    border: 1px solid #ddd;
    padding: 8px;
    border-radius: 8px;
}

button {
    margin-right: 8px;
}

.reply {
    background: #f9f9f9;
    padding: 12px;
    margin-bottom: 8px;
    border-radius: 10px;
}

.reply-header {
    display: flex;
    align-items: center;
    gap: 8px;
}

.reply-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.reply-author {
    font-weight: bold;
    color: #6a0dad;
}

.reply-timestamp {
    font-size: 0.85rem;
    color: #777;
}

.reply-actions button {
    background-color: #f3f3f3;
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.reply-actions button:hover {
    background-color: #e1e1e1;
}

.edit-reply-form {
    margin-top: 8px;
}

.edit-reply-form textarea {
    width: 100%;
    padding: 8px;
    border-radius: 8px;
}

.edited-badge {
    font-size: 0.8rem;
    color: #888;
    margin-left: 6px;
    font-style: italic;
}

.reply-form {
    margin-top: 10px;
    padding: 8px;
    background: #f9f9f9;
    border-radius: 8px;
}

.reply-form textarea {
    width: 100%;
    padding: 8px;
    border-radius: 8px;
    resize: none;
}

.reply-form button, .edit-comment-form button {
    margin-top: 5px;
    padding: 6px 12px;
    background: #6a0dad;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 10px;
}

.reply-form button:hover, .edit-comment-form button {
    background: #5a0bac;
}

#toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
}

.toast {
    background-color: #6a0dad;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    font-weight: 600;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.4s ease-out, transform 0.4s ease-out;
}

.toast.show {
    opacity: 1;
    transform: translateY(0);
}

.highlight {
    background-color: #d1ffd1;
    transition: background-color 2s ease-out;
}

#group-comment-form {
    background: #f9f9f9;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

#group-comment-text {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
    background-color: #fafafa;
    transition: border-color 0.3s ease;
}

#group-comment-text:focus {
    outline: none;
    border-color: #6a0dad;
}

#group-comment-form button {
    background-color: #6a0dad;
    color: white;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#group-comment-form button:hover {
    background-color: #4a0072;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.pagination-btn {
    background-color: #6a0dad;
    color: #fff;
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.pagination-btn:hover {
    background-color: #4a0072;
}

.pagination span {
    font-weight: bold;
    color: #6a0dad;
}

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

/* Media Container */
.author-post-thumb {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.author-post-thumb img,
.author-post-thumb video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.author-post-card:hover .author-post-thumb img,
.author-post-card:hover .author-post-thumb video {
    transform: scale(1.05);
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

    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">

<?php if ($user['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
            <img class="animate-on-scroll count" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll count"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php" ><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php" class="active"><i class="fas fa-users"></i>Groups</a></li>
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
                    <li><a href="groups.php" class="active"><i class="fas fa-users"></i>Groups</a></li>
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

         <div class="progress-container">
            <div class="progress-bar"></div>
            <div style="display: none;" class="scroll-percentage">0%</div>
        </div>
         
        <main class="content">
          <!--  <ul class="nav">
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
              <a href="#"><li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>-->
          
            <section class="blog-header">
    <h1 class="blog-title"><?= htmlspecialchars($post['title']) ?></h1>
    <div class="blog-meta-info">
        <img src="<?= htmlspecialchars($post['profile_picture']) ?>" class="author-avatar" alt="Author">
        <div class="meta-details">
            <a href="user_profile.php?id=<?= $post['user_id'] ?>" class="author-name"><?= htmlspecialchars($post['author_name']) ?></a>
            <span class="post-date"><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
            <span class="reading-time"><?= $reading_time; ?> min read</span>
        </div>

        <!-- Follow Button -->
        <?php if (isset($userId) && $userId != $post['user_id']): ?>
            <button 
                class="follow-btn" 
                data-user-id="<?= $post['user_id']; ?>" 
                data-following="<?= $is_following ? 'true' : 'false'; ?>"
            >
                <?= $is_following ? 'Following' : 'Follow'; ?>
            </button>
        <?php endif; ?>
    </div>
</section>

<div class="post-content">
    <?php if (!empty($post['media'])): ?>
        <div class="slideshow-container">
            <?php foreach (json_decode($post['media'], true) as $index => $media): ?>
                <div class="slide fade" <?= $index === 0 ? 'style="display: block;"' : '' ?>>
                    <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $media)): ?>
                        <img src="<?= $media ?>" alt="Post Image">
                    <?php elseif (preg_match('/\.(mp4|webm|ogg)$/i', $media)): ?>
                        <video controls>
                            <source src="<?= $media ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Navigation Buttons -->
            <button class="prev" onclick="changeSlide(-1)">❮</button>
            <button class="next" onclick="changeSlide(1)">❯</button>
        </div>

        <!-- Dots for Navigation -->
        <div class="dot-container">
            <?php foreach (json_decode($post['media'], true) as $index => $media): ?>
                <span class="dot" onclick="setSlide(<?= $index ?>)"></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Content in a Styled Blockquote -->
    <blockquote class="post-quote">
        <p><?= nl2br($post['content']) ?></p>
    </blockquote>

    <!-- Post Actions -->
<div class="post-actions">
    <!-- Like Button -->
    <button class="action-btn like-btn <?= $userLiked ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
        <i class="fas fa-thumbs-up like-icon <?= $userLiked ? 'liked' : '' ?>"></i> 
        <span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>
    </button>

    <!-- Bookmark Button -->
    <button class="action-btn bookmark-btn <?= $userBookmarked ? 'bookmarked' : '' ?>" data-post-id="<?= $post['id'] ?>">
        <i class="fas fa-bookmark bookmark-icon <?= $userBookmarked ? 'bookmarked' : '' ?>"></i>
    </button>

   <!-- Share Button -->
    <button class="action-btn share-btn" onclick="openShareModal('<?= $post['id'] ?>')">
        <i class="fas fa-share-alt share-icon"></i>
    </button>

    <button class="read-aloud-btn" aria-label="Read post aloud">
        <i class="fas fa-volume-up"></i>
    </button>
</div>
</div>


<!-- Floating Post Actions -->
<div class="floating-post-actions">
    <button class="action-btn like-btn <?= $userLiked ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
        <i class="fas fa-thumbs-up like-icon"></i>
    </button>

    <button class="action-btn bookmark-btn <?= $userBookmarked ? 'bookmarked' : '' ?>" data-post-id="<?= $post['id'] ?>">
        <i class="fas fa-bookmark bookmark-icon"></i>
    </button>

    <button class="action-btn share-btn" onclick="openShareModal('<?= $post['id'] ?>')">
        <i class="fas fa-share-alt share-icon"></i>
    </button>

    <button class="read-aloud-btn" aria-label="Read post aloud">
        <i class="fas fa-volume-up"></i>
    </button>
</div>

<?php

// Pagination settings
$commentsPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $commentsPerPage;

$groupId = $_GET['group_id'] ?? null;
$postId = $_GET['post_id'] ?? null;

if (!$groupId || !$postId) {
    die('Invalid group or post');
}

// Fetch comments with pagination and like counts
$stmt = $pdo->prepare("
    SELECT c.*, u.name, u.profile_picture,
        (SELECT COUNT(*) FROM group_comment_likes WHERE comment_id = c.id) AS like_count,
        (SELECT COUNT(*) FROM group_comment_likes WHERE comment_id = c.id AND user_id = ?) AS user_liked
    FROM group_post_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at DESC
    LIMIT $commentsPerPage OFFSET $offset
");
$stmt->execute([$userId, $postId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total comments count for pagination
$totalCommentsStmt = $pdo->prepare("SELECT COUNT(*) FROM group_post_comments WHERE post_id = ?");
$totalCommentsStmt->execute([$postId]);
$totalComments = $totalCommentsStmt->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalComments / $commentsPerPage);

// Fetch and attach replies for each comment
foreach ($comments as &$comment) {
    $stmtReplies = $pdo->prepare("
        SELECT r.*, u.name, u.profile_picture
        FROM group_comment_replies r
        JOIN users u ON r.user_id = u.id
        WHERE r.comment_id = ?
        ORDER BY r.created_at ASC
    ");
    $stmtReplies->execute([$comment['id']]);
    $comment['replies'] = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);
}
unset($comment);

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' days ago';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' months ago';
    } else {
        return floor($diff / 31536000) . ' years ago';
    }
}

// Fetch more posts by the same author in this group
$morePostsStmt = $pdo->prepare("
    SELECT id, title, media, created_at
    FROM group_posts
    WHERE group_id = ? AND user_id = ? AND id != ?
    ORDER BY created_at DESC
    LIMIT 5
");
$morePostsStmt->execute([$groupId, $post['user_id'], $postId]);
$morePosts = $morePostsStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<h3>Comments</h3>

<!-- Group Comment Form -->
<form id="group-comment-form" onsubmit="addGroupComment(event)">
<textarea id="group-comment-text" placeholder="Write a comment..." required></textarea>
<input type="hidden" id="post-id" value="<?= htmlspecialchars($post['id']); ?>">
<button type="submit" class="btn">💬 Add Comment</button>
</form>

 <!-- Comment Section -->
 <section id="comments-section">

    <div id="comment-list">
        <?php if (!empty($comments)): ?>
            <?php foreach ($comments as $comment): ?>
                <div id="comment-<?= $comment['id']; ?>" class="comment">
                        <div class="comment-header">
                            <a href="user_profile.php?id=<?= htmlspecialchars($comment['user_id']); ?>">
                                <img src="<?= htmlspecialchars($comment['profile_picture']); ?>" class="comment-avatar" alt="User Avatar">
                            </a>
                            <div>
                                <a href="user_profile.php?id=<?= htmlspecialchars($comment['user_id']); ?>" class="comment-author">
                                    <?= htmlspecialchars($comment['name']); ?>
                                </a>
                                <span class="comment-timestamp"><?= timeAgo($comment['created_at']); ?></span>
                                <?php if (!empty($comment['updated_at'])): ?>
                                    <span class="edited-badge">(Edited)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Comment Content -->
                        <p id="comment-content-<?= $comment['id']; ?>" class="comment-content"><?= nl2br(htmlspecialchars($comment['comment'])); ?></p>

                        <!-- Inline Edit Form (Hidden by Default) -->
                        <form id="edit-comment-form-<?= $comment['id']; ?>" class="edit-comment-form" style="display: none;" onsubmit="updateComment(event, <?= $comment['id']; ?>)">
                            <textarea id="edit-comment-text-<?= $comment['id']; ?>"><?= htmlspecialchars($comment['comment']); ?></textarea>
                            <button type="submit">Save</button>
                            <button type="button" onclick="cancelEdit(<?= $comment['id']; ?>)">Cancel</button>
                        </form>

                        <div class="comment-actions">
                        <button class="like-comment-btn" data-comment-id="<?= $comment['id']; ?>">
                            <i class="fas fa-thumbs-up <?= $comment['user_liked'] ? 'liked' : ''; ?>"></i>
                            (<span id="comment-like-count-<?= $comment['id']; ?>"><?= $comment['like_count']; ?></span>)
                        </button>
                        <button onclick="toggleReplyForm(<?= $comment['id']; ?>)"><i class="fas fa-reply"></i> Reply</button>
                        <button class="toggle-replies-btn" data-comment-id="<?= $comment['id']; ?>">
                            View Replies (<?= count($comment['replies']); ?>)
                        </button>

                        <?php if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] == 'Admin'): ?>
                            <button onclick="enableEdit(<?= $comment['id']; ?>)"><i class="fas fa-pen"></i> Edit</button>
                            <button class="delete-comment-btn" onclick="deleteComment(<?= $comment['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                        <?php endif; ?>
                    </div>

                    <!-- Reply Form (Hidden by Default) -->
                    <form id="reply-form-<?= $comment['id']; ?>" class="reply-form" style="display: none;" onsubmit="submitReply(event, <?= $comment['id']; ?>)">
                        <textarea id="reply-text-<?= $comment['id']; ?>" placeholder="Write a reply..."></textarea>
                        <button type="submit">Send Reply</button>
                        <button type="button" onclick="toggleReplyForm(<?= $comment['id']; ?>)">Cancel</button>
                    </form>

                   <!-- Replies Section (Initially Hidden) -->
                <div id="replies-<?= $comment['id']; ?>" class="replies" style="display: none;">
                    <?php if (!empty($comment['replies'])): ?>
                        <?php foreach ($comment['replies'] as $reply): ?>
                            <div class="reply" id="reply-<?= $reply['id']; ?>">
                                <!-- Reply Header -->
                                <div class="reply-header">
                                    <a href="user_profile.php?id=<?= htmlspecialchars($reply['user_id']); ?>">
                                        <img src="<?= htmlspecialchars($reply['profile_picture']); ?>" alt="Reply Avatar" class="reply-avatar">
                                    </a>
                                    <div>
                                        <strong class="reply-author"><?= htmlspecialchars($reply['name']); ?></strong>
                                        <small class="reply-timestamp"><?= timeAgo($reply['created_at']); ?></small>
                                        <?php if (!empty($reply['updated_at'])): ?>
                                            <span class="edited-badge">(Edited)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Reply Content -->
                                <p class="reply-content" id="reply-content-<?= $reply['id']; ?>"><?= nl2br(htmlspecialchars($reply['reply'])); ?></p>

                                <!-- Inline Edit Form (Hidden by Default) -->
                                <form id="edit-reply-form-<?= $reply['id']; ?>" style="display: none;" onsubmit="updateReply(event, <?= $reply['id']; ?>)">
                                    <textarea id="edit-reply-text-<?= $reply['id']; ?>"><?= htmlspecialchars($reply['reply']); ?></textarea>
                                    <button type="submit">Save</button>
                                    <button type="button" onclick="cancelReplyEdit(<?= $reply['id']; ?>)">Cancel</button>
                                </form>

                                <!-- Reply Actions -->
                                <div class="reply-actions">
                                    <?php if ($_SESSION['user_id'] == $reply['user_id'] || $_SESSION['role'] === 'Admin'): ?>
                                        <button onclick="enableReplyEdit(<?= $reply['id']; ?>)"><i class="fas fa-pen"></i> Edit</button>
                                        <button class="delete-reply-btn" onclick="deleteReply(<?= $reply['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-replies">No replies yet.</p>
                    <?php endif; ?>
                </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-comments">No comments yet. Be the first to comment!</p>
        <?php endif; ?>
    </div>
</section>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?group_id=<?= $groupId; ?>&post_id=<?= $postId; ?>&page=<?= $page - 1; ?>#group-comment-form" class="pagination-btn">⬅️ Previous</a>
    <?php endif; ?>

    <span>Page <?= $page; ?> of <?= $totalPages; ?></span>

    <?php if ($page < $totalPages): ?>
        <a href="?group_id=<?= $groupId; ?>&post_id=<?= $postId; ?>&page=<?= $page + 1; ?>#group-comment-form" class="pagination-btn">Next ➡️</a>
    <?php endif; ?>
</div>

<section class="author-other-posts">
    <h3>More Posts by <?= htmlspecialchars($post['author_name']); ?></h3>

    <div class="author-posts-grid">
        <?php if (!empty($morePosts)): ?>
            <?php foreach ($morePosts as $post): ?>
                <a href="view_group_post.php?group_id=<?= $groupId; ?>&post_id=<?= $post['id']; ?>" class="author-post-card">
                    <div class="author-post-thumb">
                        <?php
                        $media = json_decode($post['media'], true);
                        $firstMedia = $media[0] ?? 'assets/Elevate -Your Chance to be more-.jpg'; // Placeholder if no media
                        ?>
                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $firstMedia)): ?>
                            <img src="<?= htmlspecialchars($firstMedia); ?>" alt="Post Image">
                        <?php elseif (preg_match('/\.(mp4|webm|mov)$/i', $firstMedia)): ?>
                            <video src="<?= htmlspecialchars($firstMedia); ?>" autoplay muted loop></video>
                        <?php else: ?>
                            <img src="assets/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                    </div>
                    <div class="author-post-info-overlay">
                        <h4><?= htmlspecialchars($post['title']); ?></h4>
                        <span><?= date('F j, Y', strtotime($post['created_at'])); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No other posts by this author yet.</p>
        <?php endif; ?>
    </div>
</section>

<div class="post-navigation">
    <?php if ($prevPost): ?>
        <a href="view_group_post.php?group_id=<?= $groupId; ?>&post_id=<?= $prevPost['id']; ?>" class="nav-link prev-post">
            <i class="fas fa-arrow-left"></i>
            <div class="nav-text">
                <span class="nav-label">Previous Post</span>
                <span class="nav-title"><?= htmlspecialchars($prevPost['title']); ?></span>
            </div>
        </a>
    <?php endif; ?>

    <?php if ($nextPost): ?>
        <a href="view_group_post.php?group_id=<?= $groupId; ?>&post_id=<?= $nextPost['id']; ?>" class="nav-link next-post">
            <div class="nav-text">
                <span class="nav-label">Next Post</span>
                <span class="nav-title"><?= htmlspecialchars($nextPost['title']); ?></span>
            </div>
            <i class="fas fa-arrow-right"></i>
        </a>
    <?php endif; ?>
</div>

<!-- Toast Notification Container -->
<div id="toast-container"></div>

      <!-- Image Modal -->
      <div id="imageModal" style="display: none" class="image-modal">
        <span class="close-image-modal">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Post Image">
        </div>
    </div>


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

  <!-- Share Modal -->
<div id="share-modal" class="share-modal">
    <div class="share-modal-content">
        <span class="close-share-modal" onclick="closeShareModal()">&times;</span>
        <h3>Share This Post</h3>
        <div class="share-buttons">
            <a id="share-whatsapp" href="#" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            <a id="share-facebook" href="#" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
            <a id="share-twitter" href="#" target="_blank"><i class="fab fa-x-twitter"></i> Twitter</a>
            <a id="share-linkedin" href="#" target="_blank"><i class="fab fa-linkedin"></i> LinkedIn</a>
        </div>
        <div class="copy-link">
            <input type="text" id="post-link" readonly>
            <button onclick="copyPostLink()">Copy Link</button>
        </div>
        <p id="copy-success" style="display: none;">Copied to clipboard!</p>
    </div>
</div>

<!-- Comment Sound Effect -->
<audio id="comment-sound" src="assets/sounds/comment-added.mp3" preload="auto"></audio>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle Replies
    document.querySelectorAll('.toggle-replies-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const repliesContainer = document.getElementById(`replies-${commentId}`);

            if (repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
                repliesContainer.style.display = 'block';
                this.textContent = 'Hide Replies';
            } else {
                repliesContainer.style.display = 'none';
                this.textContent = `View Replies (${repliesContainer.children.length})`;
            }
        });
    });

    // Like Comment
    document.querySelectorAll('.like-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const likeCount = document.getElementById(`comment-like-count-${commentId}`);
            const likeIcon = this.querySelector('i');

            fetch('like_group_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `comment_id=${commentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    likeIcon.classList.toggle('liked', data.liked);
                    likeCount.textContent = data.like_count;
                }
            });
        });
    });
});

        // Preload Sound Effects
const likeSound = new Audio("assets/sounds/like_click.mp3");
const bookmarkSound = new Audio("assets/sounds/bookmark_click.mp3");
const shareSound = new Audio("assets/sounds/share_click.mp3");
const clickSound = new Audio("assets/sounds/click.mp3");
const successSound = new Audio("assets/sounds/success.mp3");

// Function to Play Sound
function playSound(audio) {
    audio.currentTime = 0;
    audio.play();
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
document.addEventListener("DOMContentLoaded", function () {
    let slideIndex = 0;
    showSlides(slideIndex);

    function showSlides(n) {
        let slides = document.querySelectorAll(".slide");
        let dots = document.querySelectorAll(".dot");

        if (n >= slides.length) slideIndex = 0;
        if (n < 0) slideIndex = slides.length - 1;

        slides.forEach((slide) => slide.style.display = "none");
        dots.forEach((dot) => dot.classList.remove("active"));

        slides[slideIndex].style.display = "block";
        dots[slideIndex].classList.add("active");
    }

    window.changeSlide = function(n) {
        showSlides(slideIndex += n);
    };

    window.setSlide = function(n) {
        slideIndex = n;
        showSlides(slideIndex);
    };
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
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function () {
            playSound(likeSound);
             const postId = this.dataset.postId;
            const likeIcon = this.querySelector('.like-icon');
            const likeCount = document.getElementById(`like-count-${postId}`);

            fetch('like_group_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.liked) {
                        likeIcon.classList.add('liked');
                    } else {
                        likeIcon.classList.remove('liked');
                    }
                    likeCount.textContent = data.like_count;
                }
            });
        });
    });

    // Bookmark Button Click
    document.querySelectorAll('.bookmark-btn').forEach(button => {
        button.addEventListener('click', function () {
            playSound(bookmarkSound);
            const postId = this.dataset.postId;
            const bookmarkIcon = this.querySelector('.bookmark-icon');

            fetch('bookmark_group_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.bookmarked) {
                        bookmarkIcon.classList.add('bookmarked');
                    } else {
                        bookmarkIcon.classList.remove('bookmarked');
                    }
                }
            });
        });
    });
});

function openShareModal(postId) {
    const modal = document.getElementById('share-modal');
    const postUrl = window.location.origin + '/view_group_post.php?id=' + postId;

    // Update Social Media Share Links
    document.getElementById('share-whatsapp').href = `https://api.whatsapp.com/send?text=${encodeURIComponent(postUrl)}`;
    document.getElementById('share-facebook').href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}`;
    document.getElementById('share-twitter').href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(postUrl)}&text=Check this out!`;
    document.getElementById('share-linkedin').href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(postUrl)}`;

    // Set Link in Input Field
    document.getElementById('post-link').value = postUrl;

    // Show Modal
    modal.style.display = 'flex';
}

function closeShareModal() {
    document.getElementById('share-modal').style.display = 'none';
}

function copyPostLink() {
    const postLink = document.getElementById('post-link');
    postLink.select();
    document.execCommand('copy')

    playSound(successSound);


    // Show Success Message
    const successMessage = document.getElementById('copy-success');
    successMessage.style.display = 'block';
    setTimeout(() => { successMessage.style.display = 'none'; }, 2000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('share-modal');
    if (event.target === modal) {
        closeShareModal();
    }
};

document.addEventListener("DOMContentLoaded", function () {
    const floatingActions = document.querySelector(".floating-post-actions");

    window.addEventListener("scroll", function () {
        if (window.scrollY > 10) {
            floatingActions.classList.add("visible");
        } else {
            floatingActions.classList.remove("visible");
        }
    });

    // Play Sound on Click
    document.querySelectorAll(".floating-post-actions button").forEach(button => {
        button.addEventListener("click", function () {
            playSound(clickSound);
        });
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

document.addEventListener("DOMContentLoaded", function () {
    // Like Comment Button
    document.querySelectorAll(".like-comment-btn").forEach(button => {
        button.addEventListener("click", function () {
            playSound(likeSound);
            const commentId = this.dataset.commentId;
            const likeCount = document.getElementById(`comment-like-count-${commentId}`);
            const likeIcon = this.querySelector("i");

            fetch("like_comment.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `comment_id=${commentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    if (data.liked) {
                        likeIcon.classList.add("liked");
                    } else {
                        likeIcon.classList.remove("liked");
                    }
                    likeCount.textContent = data.like_count;
                }
            });
        });
    });

   // Toggle Replies
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-replies-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.dataset.commentId;
            const repliesContainer = document.getElementById(`replies-${commentId}`);
            if (repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
                repliesContainer.style.display = 'block';
                this.textContent = 'Hide Replies';
            } else {
                repliesContainer.style.display = 'none';
                this.textContent = 'View Replies';
            }
        });
    });
});
});

// Show Toast Notification
function showToast(message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast');
    toast.innerText = message;

    toastContainer.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Enable Comment Editing
function enableEdit(commentId) {
    document.getElementById(`comment-content-${commentId}`).style.display = 'none';
    document.getElementById(`edit-comment-form-${commentId}`).style.display = 'block';
}

// Cancel Editing
function cancelEdit(commentId) {
    document.getElementById(`comment-content-${commentId}`).style.display = 'block';
    document.getElementById(`edit-comment-form-${commentId}`).style.display = 'none';
}

// Update Comment via AJAX
function updateComment(event, commentId) {
    event.preventDefault();
    const updatedComment = document.getElementById(`edit-comment-text-${commentId}`).value;

    fetch('update_group_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `comment_id=${commentId}&comment=${encodeURIComponent(updatedComment)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById(`comment-content-${commentId}`).innerHTML = updatedComment.replace(/\n/g, '<br>');
            cancelEdit(commentId);

             // Add highlight effect
             document.getElementById(`comment-content-${commentId}`).classList.add('highlight');
            setTimeout(() => document.getElementById(`comment-content-${commentId}`).classList.remove('highlight'), 2000);


            // Show toast notification
            showToast('✏️ Comment updated successfully');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Show Toast Notification
function showToast(message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast');
    toast.innerText = message;

    toastContainer.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Enable Inline Editing for Replies
function enableReplyEdit(replyId) {
    document.getElementById(`reply-content-${replyId}`).style.display = 'none';
    document.getElementById(`edit-reply-form-${replyId}`).style.display = 'block';
}

// Cancel Editing
function cancelReplyEdit(replyId) {
    document.getElementById(`reply-content-${replyId}`).style.display = 'block';
    document.getElementById(`edit-reply-form-${replyId}`).style.display = 'none';
}

// Update Reply with AJAX and Highlight
function updateReply(event, replyId) {
    event.preventDefault();
    const updatedReply = document.getElementById(`edit-reply-text-${replyId}`).value;

    if (!updatedReply.trim()) return alert('Reply cannot be empty!');

    fetch('update_group_reply.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `reply_id=${replyId}&reply=${encodeURIComponent(updatedReply)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const replyContent = document.getElementById(`reply-content-${replyId}`);
            replyContent.innerHTML = updatedReply.replace(/\n/g, '<br>');

            cancelReplyEdit(replyId);

            // Add highlight effect
            replyContent.classList.add('highlight');
            setTimeout(() => replyContent.classList.remove('highlight'), 2000);

            // Show "Reply Updated" toast notification
            showToast('✏️ Reply updated successfully');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}


// Show Toast Notification
function showToast(message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast');
    toast.innerText = message;

    toastContainer.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Fade Out and Delete Comment
function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    fetch('delete_group_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `comment_id=${commentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const comment = document.getElementById(`comment-${commentId}`);
            comment.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            comment.style.opacity = '0';
            comment.style.transform = 'translateY(-10px)';
            setTimeout(() => comment.remove(), 500);

            // Show the toast notification
            showToast('🗑️ Comment deleted successfully');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Fade Out and Delete Reply
function deleteReply(replyId) {
    if (!confirm('Are you sure you want to delete this reply?')) return;

    fetch('delete_group_reply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `reply_id=${replyId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const reply = document.getElementById(`reply-${replyId}`);
            reply.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            reply.style.opacity = '0';
            reply.style.transform = 'translateY(-10px)';
            setTimeout(() => reply.remove(), 500);

            // Show the toast notification
            showToast('🗑️ Reply deleted successfully');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Toggle Reply Form
function toggleReplyForm(commentId) {
    const form = document.getElementById(`reply-form-${commentId}`);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// Show Toast Notification
function showToast(message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast');
    toast.innerText = message;

    toastContainer.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Submit Reply via AJAX
function submitReply(event, commentId) {
    event.preventDefault();
    const replyText = document.getElementById(`reply-text-${commentId}`).value;

    if (!replyText.trim()) return alert('Reply cannot be empty!');

    fetch('add_group_reply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `comment_id=${commentId}&reply=${encodeURIComponent(replyText)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const repliesContainer = document.getElementById(`replies-${commentId}`);
            const newReply = `
                <div id="reply-${data.reply.id}" class="reply">
                    <div class="reply-header">
                        <a href="user_profile.php?id=${data.reply.user_id}">
                            <img src="${data.reply.profile_picture}" alt="Reply Avatar" class="reply-avatar">
                        </a>
                        <div>
                            <strong class="reply-author">${data.reply.name}</strong>
                            <small class="reply-timestamp">Just now</small>
                        </div>
                    </div>
                    <p class="reply-content" id="reply-content-${data.reply.id}">${data.reply.content}</p>
                    <div class="reply-actions">
                        <button class="delete-reply-btn" onclick="deleteReply(${data.reply.id})"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            `;
            repliesContainer.innerHTML += newReply;

            document.getElementById(`reply-form-${commentId}`).reset();
            toggleReplyForm(commentId);

            // Show the toast notification
            showToast('✅ Reply sent successfully!');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function addGroupComment(event) {
    event.preventDefault();

    const commentText = document.getElementById('group-comment-text').value;
    const postId = document.getElementById('post-id').value;

    if (!commentText.trim()) return alert('Comment cannot be empty!');

    fetch('add_group_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&comment=${encodeURIComponent(commentText)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const commentsSection = document.getElementById('comments-section');
            const newComment = `
                <div id="comment-${data.comment.id}" class="comment">
                    <div class="comment-header">
                        <a href="user_profile.php?id=${data.comment.user_id}">
                            <img src="${data.comment.profile_picture}" alt="User Avatar" class="comment-avatar">
                        </a>
                        <div>
                            <strong class="comment-author">${data.comment.name}</strong>
                            <small class="comment-timestamp">Just now</small>
                        </div>
                    </div>
                    <p class="comment-content">${data.comment.content}</p>
                    <div class="comment-actions">
                        <button class="delete-comment-btn" onclick="deleteComment(${data.comment.id})"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            `;
            commentsSection.innerHTML = newComment + commentsSection.innerHTML;

            // Clear the form
            document.getElementById('group-comment-form').reset();

            // Play sound effect
            document.getElementById('comment-sound').play();

            // Show toast notification
            showToast('💬 Comment added successfully');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const closeModal = document.querySelector(".close-image-modal");

    // Open Image Modal
    document.querySelectorAll(".slideshow-container img").forEach(image => {
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
</script>
</body>
</html>
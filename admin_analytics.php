<?php
require 'includes/db.php';
session_start();

$user_id = $_GET['id'] ?? $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];

// Fetch the count of unresolved notifications
$stmt = $pdo->prepare("SELECT COUNT(*) AS notification_count FROM notifications WHERE is_read = 0 AND user_id = ?");
$stmt->execute([$user_id]);
$notification = $stmt->fetch();
$notification_count = $notification['notification_count'];

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin'){
    header("Location: dashboard.php");
    exit;
}
$stmt = $pdo->query("SELECT 
                     (SELECT COUNT(*) FROM users) AS total_users,
                     (SELECT COUNT(*) FROM posts) AS total_posts,
                     (SELECT COUNT(*) FROM comments) AS total_comments,
                     (SELECT COUNT(*) FROM likes) AS total_likes");
$analytics = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage comments</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body style="height: 100vh;">
<!-- Sidebar -->
<aside class="sidebar" style="overflow-y: scroll;">
        <img src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2><?php echo $_SESSION['name']; ?></h2>
        <nav>
            <ul>
            <li><a href="admin_dashboard.php">Home</a></li>
                <li><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>">My Profile</a></li>
                <li><a href="admin_notifications.php">Notification
                    <?php if ($notification_count > 0): ?>
                        <span class="report-count">(<?= $notification_count ?>)</span>
                        <?php endif; ?>
                </a></li>
                <li><a href="admin_users.php">Manage Users</a></li>
                <li><a href="admin_posts.php">Manage Posts</a></li>
                <li><a href="admin_comments.php">Manage Comments</a></li>
                <li><a href="admin_reports.php">View Reports
                    <?php if ($report_count > 0): ?>
                        <span class="report-count">(<?= $report_count ?>)</span>
                        <?php endif; ?>
                </a></li>
                <li><a href="admin_analytics.php">View Analytics</a></li>
                <li><a href="admin_filters.php">Manage Filters</a></li>
                    <li><a href="public_posts.php">All Posts</a></li>
                    <li><a href="create_post.php">Create Post</a></li>
                    <li><a href="my_posts.php">My Posts</a></li>
                    <li><a href="bookmarked_posts.php">Bookmarked Posts</a></li>
                    <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
       
        </aside>
        <!-- Main Content -->
        <main class="content">
    <div class="container">
        <div>
            <h2 style="text-align: center">Blog analysis</h2>
            <p>Total Users: <?php echo $analytics['total_users']; ?></p><br>
            <p>Total Posts: <?php echo $analytics['total_posts']; ?></p><br>
            <p>Total Comments: <?php echo $analytics['total_comments']; ?></p><br>
            <p>Total Likes: <?php echo $analytics['total_likes']; ?></p>
        </div>
    </div>
</body>
</html>
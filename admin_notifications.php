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
$stmt = $pdo->query("SELECT notifications.id, notifications.sender_id, notifications.message, notifications.is_read, notifications.created_at,  users.name AS sender 
                     FROM notifications 
                     JOIN users ON notifications.sender_id = users.id where is_read = 0 AND notifications.user_id = 3 ORDER BY notifications.created_at DESC ");
                     
$notifications= $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage notifications</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
       

.table-container {
  width: 100%;
  margin: 50px auto;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h2 {
  text-align: center;
  color: #fff; /* Purple */
  margin-bottom: 20px;
}

/* Table styles */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 16px;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

th {
  background: rgba(0, 0, 0, 0.5);
  color: #ffffff;
}

td {
  background-color: #f9f9f9;
  color: black;
}

tr:nth-child(even) td {
  background-color: #f1f1f1;
}

.profile-img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}
  </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
    <!-- Sidebar -->
    <aside style="overflow-y: scroll;" class="sidebar">
            <img src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2><?php echo $_SESSION['name']; ?></h2>
            <nav>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i>Home</a></li>
                <li><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i>My Profile</a></li>
                <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                <li><a href="admin_users.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                <li><a href="admin_posts.php"><i class="fas fa-file-alt"></i>Manage Posts</a></li>
                <li><a href="admin_comments.php"><i class="fas fa-comments"></i>Manage Comments</a></li>
                <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i>View Reports
                    <?php if ($report_count > 0): ?>
                        <span class="report-count">(<?= $report_count ?>)</span>
                        <?php endif; ?>
                </a></li>
                <li><a href="admin_filters.php"><i class="fas fa-folder-open"></i>Manage Filters</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i> All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="content">

        <ul class="nav">
                <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
    <a class="btn delete-btn" href="admin_mark_all_read.php" ?>Mark all as Read</a>
        <table class="admin-table">
    <thead>
        <tr class="animate-on-scroll">
            <th>Message</th>
            <th>Sender</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    
    <tbody>
    <?php foreach ($notifications as $notification): ?>
            <tr class="animate-on-scroll">
                <td class="animate-on-scroll"><?php echo htmlspecialchars($notification['message']); ?></td>
                <td class="animate-on-scroll center-text"><?php echo htmlspecialchars($notification['sender']); ?></td>
                <td class="animate-on-scroll"><?php echo date('F j, Y · g:i a', strtotime($notification['created_at'])); ?></td>
                <td class="animate-on-scroll">
                    <a class="btn" href="chat.php?user_id=<?php echo $notification['sender_id']; ?>">View</a>
                    <?php if ($notification['is_read'] == 0): ?>
                        <a class="btn delete-btn" href="admin_mark_read.php?id=<?php echo $notification['id']; ?>">Mark as read</a>
                    <?php endif; ?>
                </td>
            </tr>
        
    </tbody>
    <?php endforeach; ?>
</table>
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
            </script>
</body>
</html>
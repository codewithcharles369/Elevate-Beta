<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];


// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin'){
    header("Location: dashboard.php");
    exit;
}

// Fetch the count of unresolved notifications
$stmt = $pdo->prepare("SELECT COUNT(*) AS notification_count FROM notifications WHERE is_read = 0 AND user_id = ?");
$stmt->execute([$user_id]);
$notification = $stmt->fetch();
$notification_count = $notification['notification_count'];

// Fetch the count of unresolved reports
$stmt = $pdo->prepare("SELECT COUNT(*) AS report_count FROM reports WHERE status = 'unresolved'");
$stmt->execute();
$report = $stmt->fetch();
$report_count = $report['report_count'];

$stmt = $pdo->query("SELECT 
                     (SELECT COUNT(*) FROM users) AS total_users,
                     (SELECT COUNT(*) FROM posts) AS total_posts,
                     (SELECT COUNT(*) FROM comments) AS total_comments,
                     (SELECT COUNT(*) FROM likes) AS total_likes,
                     (SELECT COUNT(*) FROM groups) AS total_groups,
                     (SELECT COUNT(*) FROM group_members) AS total_group_members,
                     (SELECT COUNT(*) FROM post_views) AS total_views");
$analytics = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT COUNT(*) AS post_count FROM posts WHERE  user_id = ?");
$stmt->execute([$user_id]);
$post = $stmt->fetch();
$post_count = $post['post_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS likes_count FROM likes WHERE  user_id = ?");
$stmt->execute([$user_id]);
$likes = $stmt->fetch();
$likes_count = $likes['likes_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS comments_count FROM comments WHERE  user_id = ?");
$stmt->execute([$user_id]);
$comments = $stmt->fetch();
$comments_count = $comments['comments_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS views_count FROM post_views WHERE  user_id = ?");
$stmt->execute([$user_id]);
$views = $stmt->fetch();
$views_count = $views['views_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS followers_count FROM follows WHERE  following_id = ?");
$stmt->execute([$user_id]);
$followers = $stmt->fetch();
$followers_count = $followers['followers_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS following_count FROM follows WHERE  follower_id = ?");
$stmt->execute([$user_id]);
$following = $stmt->fetch();
$following_count = $following['following_count'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>

        


.header, .user {
  background-color:rgba(0, 0, 0, 0.5);
  color: #fff;
  padding: 20px;
  border-radius: 5px;
}

.user {
  background-color:rgba(0, 0, 0, 0.5);
  color: #fff;
  margin-left: auto;
  margin-right: auto;
  display: none;
  text-align: center;
}

@media screen and (max-width: 600px){
  .user {
    display: block;
  }
}

.header {
background: rgba(0, 0, 0, 0.5);
color: #fff;
padding: 20px;
border-radius: 5px;
}

.content {
margin-top: 20px;
background-color: #fff;
color: black;
padding: 20px;
border-radius: 5px;
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
/* Stats Section */
.stats {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
gap: 20px;
margin-top: 20px;
}

/* Stat Card Icons */
.stat-card i {
font-size: 40px;
margin-bottom: 10px;
color: rgba(255, 255, 255, 0.8);
transition: color 0.3s ease;
}

/* Icon hover effect */
.stat-card:hover i {
color: #fff;
}


.stat-card {
background: rgba(0, 0, 0, 0.5);
color: #fff;
padding: 20px;
border-radius: 10px;
text-align: center;
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card h3 {
margin: 0;
font-size: 18px;
font-weight: bold;
}

.stat-card p {
margin: 10px 0 0;
font-size: 24px;
font-weight: bold;
}

.stat-card:hover {
transform: translateY(-5px);
box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}
/* Animation for stat cards */
.stat-card {
opacity: 0;
transform: translateY(20px);
animation: fadeInUp 0.8s ease-out forwards;
}

/* Delay for each card */
.stat-card:nth-child(1) { animation-delay: 2.1s; }
.stat-card:nth-child(2) { animation-delay: 2.2s; }
.stat-card:nth-child(3) { animation-delay: 2.4s; }
.stat-card:nth-child(4) { animation-delay: 2.6s; }
.stat-card:nth-child(5) { animation-delay: 2.8s; }
.stat-card:nth-child(6) { animation-delay: 3.0s; }
.stat-card:nth-child(7) { animation-delay: 3.2s; }
.stat-card:nth-child(8) { animation-delay: 3.4s; }
.stat-card:nth-child(9) { animation-delay: 3.6s; }
.stat-card:nth-child(10) { animation-delay: 3.8s; }
.stat-card:nth-child(11) { animation-delay: 4.0s; }
.stat-card:nth-child(12) { animation-delay: 4.2s; }
.stat-card:nth-child(13) { animation-delay: 4.4s; }
.stat-card:nth-child(14) { animation-delay: 4.6s; }
.stat-card:nth-child(15) { animation-delay: 4.8s; }

@keyframes fadeInUp {
to {
opacity: 1;
transform: translateY(0);
}
}
.recent-activities {
margin-top: 20px;
padding: 20px;
background-color: #ffffff;
border-radius: 10px;
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.recent-activities h2 {
font-size: 18px;
margin-bottom: 10px;
}

.recent-activities ul {
list-style: none;
padding: 0;
}

.recent-activities li {
margin-bottom: 10px;
font-size: 14px;
display: flex;
align-items: center;
}

.recent-activities li span {
margin-right: 10px;
}
.user-stats {
background: rgba(0, 0, 0, 0.5);
color: #fff;
padding: 20px;
border-radius: 10px;
margin-top: 20px;
}

.user-stats h2 {
font-size: 18px;
margin-bottom: 10px;
}

.user-stats ul {
list-style: none;
padding: 0;
}

.user-stats li {
margin-bottom: 8px;
}
.task-manager {
background-color: #ffffff;
border-radius: 10px;
padding: 20px;
margin-top: 20px;
}

.task-manager ul {
list-style: none;
padding: 0;
}

.task-manager li {
margin-bottom: 10px;
}

.task-manager input {
padding: 8px;
width: calc(100% - 100px);
margin-right: 10px;
border: 1px solid #ddd;
border-radius: 5px;
}

.task-manager button {
padding: 8px 20px;
border: none;
background-color: #6c63ff;
color: #fff;
border-radius: 5px;
cursor: pointer;
}

.task-manager button:hover {
background-color: #5548d1;
}
.weather-widget {
background: rgba(0, 0, 0, 0.5);
color: #fff;
padding: 20px;
border-radius: 10px;
margin-top: 20px;
text-align: center;
}

.weather-widget h2 {
font-size: 18px;
}

.weather-widget p {
font-size: 16px;
}
.calendar-widget {
  background-color: #ffffff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 20px;
}

.calendar-widget h2 {
  font-size: 18px;
  margin-bottom: 10px;
}

#calendar {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 5px;
  text-align: center;
}

#calendar div {
  padding: 10px;
  background-color: #f4f4f4;
  border-radius: 5px;
  font-size: 14px;
  color: black;
}
#calendar .header {
  font-weight: bold;
  background-color: #4a3fb8;
  color: #ffffff;
}

#calendar .today {
  background-color: #ff6f61;
  color: white;
  font-weight: bold;
}
#todo-container {
    margin: 20px 0;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #ffffff;
}

#todo-form {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

#todo-input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

#todo-list {
    list-style: none;
    padding: 0;
}

#todo-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px;
    border-bottom: 1px solid #eee;
    color: black;
}

#todo-list li:last-child {
    border-bottom: none;
}

.todo-task.completed {
    text-decoration: line-through;
    color: gray;
}
.task-manager {
  background-color: #ffffff;
  border-radius: 10px;
  padding: 20px;
  margin-top: 20px;
}

.task-manager ul {
  list-style: none;
  padding: 0;
}

.task-manager li {
  margin-bottom: 10px;
}

.task-manager input {
  padding: 8px;
  width: calc(100% - 100px);
  margin-right: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

.task-manager button {
  padding: 8px 20px;
  border: none;
  color: #fff;
  border-radius: 5px;
  cursor: pointer;
}
#todo-form button, #todo-list button{
  text-decoration: none;
    color: #fff;
    background-color: #007bff;
    padding: 14px;
    border-radius: 5px;
    font-size: 14px;
    margin-right: 5px;
    transition: background 0.3s ease, transform 0.3s ease;
}
#todo-list button{
  background-color: #dc3545;
}


.task-manager button:hover {
  background-color: #5548d1;
}
#daily-signups-container {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-align: center;
}

#daily-signups-container h3 {
    font-size: 20px;
    color: #333;
}

#daily-signups {
    color: #28a745;
    font-weight: bold;
}
/* General Dashboard Styling */
#admin-dashboard {
    max-width: 800px;
    margin: 20px auto;
    font-family: Arial, sans-serif;
    color: #333;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
}

#admin-dashboard h1 {
    font-size: 24px;
    margin-bottom: 20px;
    text-align: center;
    color: #007BFF;
}

/* Online Users Section */
#online-users-container {
    text-align: center;
    margin-bottom: 20px;
}

#online-users-container h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

#online-users-count {
    color: #28a745;
    font-weight: bold;
}

/* Dropdown Styles */
.dropdown {
    display: inline-block;
    position: fixed;
    top: 0;
    right: 0;
}

.dropdown-btn {
    background-color: #007BFF;
    color: white;
    font-size: 16px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dropdown-btn:hover {
    background-color: #0056b3;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: white;
    min-width: 250px;
    max-height: 300px;
    overflow-y: auto;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    padding: 10px;
    border-radius: 5px;
    z-index: 1;
}

/* Show dropdown content on button hover */
.dropdown:hover .dropdown-content {
    display: block;
}

/* User Item Styling */
.user-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s ease;
}

.user-item:last-child {
    border-bottom: none;
}

.user-item img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #007BFF;
}

.user-item span {
    font-size: 16px;
    color: #333;
    flex-grow: 1;
}

.user-item:hover {
    background-color: #f1f1f1;
}

/* No Users Online Message */
.dropdown-content p {
    text-align: center;
    color: #888;
    margin: 0;
    padding: 10px;
    font-size: 14px;
}
#unread-messages-container {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
}

#unread-messages-container h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 15px;
}

#unread-messages-list {
    list-style: none;
    padding: 0;
    margin: 0;
    color: black;
}

.unread-message-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.unread-message-item:last-child {
    border-bottom: none;
}

.sender-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #007BFF;
}

.view-chat-link {
    text-decoration: none;
    color: #007BFF;
    font-size: 14px;
    padding: 5px 10px;
    border: 1px solid #007BFF;
    border-radius: 4px;
    transition: all 0.3s;
}

.view-chat-link:hover {
    background-color: #007BFF;
    color: #fff;
}
#notifications-container {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
}

#notifications-container h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 15px;
}

#notifications-list {
    list-style: none;
    padding: 0;
    margin: 0;
    color: black;

}

.notification-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.notification-item:last-child {
    border-bottom: none;
}

.sender-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #007BFF;
}

.mark-as-read-btn {
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.mark-as-read-btn:hover {
    background-color: #218838;
}

/* Notification and Unread Messages Section */
#notifications-container, #unread-messages-container {
    margin-top: 20px;
    padding: 15px;
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#notifications-container h3, #unread-messages-container h3 {
    font-size: 20px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #007BFF;
}

#notifications-list, #unread-messages-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sender-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #007BFF;
}

.sender-name {
  color: #007bff;
  text-decoration: none;
}

.mark-as-read-btn, .view-chat-link {
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 8px 15px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.message{
    
}

.mark-as-read-btn:hover, .view-chat-link:hover {
    background-color: #218838;
    transform: scale(1.05);
}
#loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #ffffff; /* Adjust background color */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Ensure it appears above other elements */
}

#loading-screen img {
    width: 100%; /* Adjust size */
    animation: fadeIn 2s ease-in-out infinite;
}

@keyframes fadeIn {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}
#loading-screen.fade-out {
    animation: fadeOut 1s ease-in-out forwards;
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
        visibility: hidden;
    }
}
</style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">

<!-- Loading Screen -->
<div id="loading-screen">
    <img src="assets/Elevate4 -Your Chance to be more-.jpg" alt="Elevate Logo" />
</div>

        <!-- Sidebar -->
        <aside class="sidebar" style="overflow-y: scroll;">
        <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2 class="animate-on-scroll">Welcome, <?php echo $_SESSION['name']; ?>!</h2>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i>Home</a></li>
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
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
       
        </aside>
                <!-- Main Content -->
                <main class="content">
                <header class="animate-on-scroll user">
        <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
            <p class="animate-on-scroll"><?php echo $user['bio']; ?></p>
            </header><br>
           <header class="animate-on-scroll header">
           <h1 class="animate-on-scroll">Admin Dashboard</h1>
            <p class="animate-on-scroll">Welcome to your blog dashboard! Here, you can create and manage your posts.</p>
            </header>
            <ul class="nav">
             <a href=""> <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
             <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
             <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
              <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
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
            <section class="content"> 
        <!-- Dashboard content goes here -->
        <p>Start managing your blog effectively!</p>
      </section>

      
      
      <section class="stats">
      <div class="stat-card">
          <i class="fas fa-user"></i>
          <h3 class="animate-on-scroll">Total Users</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_users']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-user"></i>
          <h3 class="animate-on-scroll">Active Users</h3>
          <p class="animate-on-scroll count" id="online-users-count">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-sign-in-alt"></i>
          <h3 class="animate-on-scroll">Daily Signup</h3>
          <p class="animate-on-scroll count" id="daily-signups">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-pencil-alt"></i>
          <h3 class="animate-on-scroll">Total Posts</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_posts']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-heart"></i>
          <h3 class="animate-on-scroll">Total Likes</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_likes']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-comments"></i>
          <h3 class="animate-on-scroll">Total Comment</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_comments']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-eye"></i>
          <h3 class="animate-on-scroll">Total Views</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_views']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-users"></i>
          <h3 class="animate-on-scroll">Total Groups</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_groups']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-users"></i>
          <h3 class="animate-on-scroll">Total Groups Members</h3>
          <p class="animate-on-scroll count" data-count="<?php echo $analytics['total_group_members']; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-pen"></i>
          <h3 class="animate-on-scroll">My Published Posts</h3>
          <p class="animate-on-scroll count" data-count="<?= $post_count ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-check"></i>
          <h3 class="animate-on-scroll">Post Likes</h3>
          <p class="animate-on-scroll count" data-count="<?= $likes_count ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-comment"></i>
          <h3 class="animate-on-scroll">Post Comments</h3>
          <p class="animate-on-scroll count" data-count="<?= $comments_count ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-eye"></i>
          <h3 class="animate-on-scroll">Posts Views</h3>
          <p class="animate-on-scroll count"id="total-views">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-users"></i>
          <h3 class="animate-on-scroll">Total Followers</h3>
          <p class="animate-on-scroll count" data-count="<?php if ($followers_count > 0): ?><?= $followers_count ?><?php endif; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-user-plus"></i>
          <h3 class="animate-on-scroll">Total Following</h3>
          <p class="animate-on-scroll count" data-count="<?php if ($following_count > 0): ?><?= $following_count ?><?php endif; ?>">0</p>
        </div>
      </section>
      <br>
      <div class="animate-on-scroll" id="notifications-container">
          <h3 class="animate-on-scroll" >Notifications</h3>
          <ul class="animate-on-scroll" id="notifications-list"></ul>
      </div>

      <div class="animate-on-scroll" id="unread-messages-container">
        <h3 class="animate-on-scroll">Unread Messages</h3>
        <ul class="animate-on-scroll" id="unread-messages-list"></ul>
    </div>
      <div class="animate-on-scroll" id="todo-container">
      <h2 style="color: black" class="animate-on-scroll">Task Manager</h2><br>
    <form class="animate-on-scroll" id="todo-form">
        <input class="animate-on-scroll" type="text" id="todo-input" placeholder="Add a new task..." required>
        <button class="animate-on-scroll btn" type="submit">Add Task</button>
    </form>
    <ul class="animate-on-scroll animate-on-scroll" id="todo-list"></ul>
</div>
      
      
      <!--<canvas style="background-color: rgba(0, 0, 0, 0.7); border-radius: 5px;" id="traffic-chart" width="400" height="200"></canvas>-->
      <section class="animate-on-scroll calendar-widget">
            <h2 class="animate-on-scroll" style="color: initial">Calendar</h2>
            <div class="animate-on-scroll" id="calendar"></div>
          </section>
      <div class="animate-on-scroll weather-widget">
        <h2>Weather</h2>
        <p>🌤️ Sunny, 25°C</p>
      </div>
      
          <div class="dropdown">
            <button class="dropdown-btn" id="online-users-btn">
                View Online Users <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-content" id="online-users-list"></div>
        </div>
        
      <ul class="nav">
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
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
        </main>
    </div>
    <script>
      
window.addEventListener("load", () => {
    const loadingScreen = document.getElementById("loading-screen");

    // Ensure loading screen stays for at least 3 seconds
    setTimeout(() => {
        loadingScreen.classList.add("fade-out");
    }, 2000); // 2 seconds
});

        document.addEventListener('DOMContentLoaded', function () {
    const totalViewsElement = document.getElementById('total-views');

    // Fetch total views
    function fetchTotalViews() {
        fetch('fetch_user_views.php')
            .then(response => response.json())
            .then(data => {
                totalViewsElement.textContent = data.total_views;
            })
            .catch(error => console.error('Error fetching total views:', error));
    }

    // Initial fetch
    fetchTotalViews();
});
    document.addEventListener('DOMContentLoaded', () => {
  const menuItems = document.querySelectorAll('.menu-item');

  menuItems.forEach(item => {
    item.addEventListener('click', () => {
      // Remove active class from all
      menuItems.forEach(menu => menu.classList.remove('active'));

      // Add active class to the clicked menu item
      item.classList.add('active');
    });
  });
});
document.addEventListener('DOMContentLoaded', () => {
  const stats = {
    post: 150,
    categorie: 12,
    comment: 45,
    view: 10543
  };

  const statCards = document.querySelectorAll('.stat-card');
  
  // Update each stat card dynamically
  statCards.forEach(card => {
    const title = card.querySelector('h3').textContent.toLowerCase();
    const value = stats[title.replace(' ', '')];
    if (value) {
      card.querySelector('p').textContent = value.toLocaleString();
    }
  });
});
document.addEventListener('DOMContentLoaded', () => {
  const counters = document.querySelectorAll('.count');

  counters.forEach(counter => {
    const updateCount = () => {
      const target = +counter.getAttribute('data-count');
      const current = +counter.textContent;
      const increment = Math.ceil(target / 100); // Speed of the count-up

      if (current < target) {
        counter.textContent = current + increment;
        setTimeout(updateCount, 20); // Adjust speed here
      } else {
        counter.textContent = target.toLocaleString(); // Add commas for large numbers
      }
    };

    updateCount();
  });
});
function addTask() {
  const taskInput = document.getElementById('new-task');
  const taskList = document.getElementById('task-list');

  if (taskInput.value.trim() !== '') {
    const li = document.createElement('li');
    li.textContent = taskInput.value;
    taskList.appendChild(li);
    taskInput.value = '';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const calendar = document.getElementById('calendar');
  const today = new Date();
  const currentMonth = today.getMonth();
  const currentYear = today.getFullYear();
  const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  function renderCalendar(month, year) {
    // Clear the calendar
    calendar.innerHTML = '';

    // Add day headers
    daysOfWeek.forEach(day => {
      const header = document.createElement('div');
      header.textContent = day;
      header.className = 'header';
      calendar.appendChild(header);
    });

    // Get the first day of the month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Add blank spaces for days before the first day
    for (let i = 0; i < firstDay; i++) {
      const empty = document.createElement('div');
      calendar.appendChild(empty);
    }

    // Add the days of the month
    for (let day = 1; day <= daysInMonth; day++) {
      const dayElement = document.createElement('div');
      dayElement.textContent = day;

      // Highlight today
      if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
        dayElement.className = 'today';
      }

      calendar.appendChild(dayElement);
    }
  }

  // Render the current month's calendar
  renderCalendar(currentMonth, currentYear);
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
    const todoForm = document.getElementById("todo-form");
    const todoInput = document.getElementById("todo-input");
    const todoList = document.getElementById("todo-list");

    // Fetch tasks
    function fetchTodos() {
        fetch("fetch_todos.php")
            .then(response => response.json())
            .then(todos => {
                todoList.innerHTML = "";
                todos.forEach(todo => {
                    const listItem = document.createElement("li");
                    listItem.innerHTML = `
                        <input type="checkbox" class="todo-checkbox" data-id="${todo.id}" ${todo.is_completed == 1 ? "checked" : ""}>
                        <span class="todo-task ${todo.is_completed == 1 ? "completed" : ""}">${todo.task}</span>
                        <button class="delete-todo-btn" data-id="${todo.id}">Delete</button>
                    `;
                    todoList.appendChild(listItem);
                });
            })
            .catch(error => console.error("Error fetching todos:", error));
    }

    // Add task
    todoForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const task = todoInput.value.trim();
        if (task) {
            fetch("add_todo.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task=${encodeURIComponent(task)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTodos();
                        todoInput.value = "";
                    }
                })
                .catch(error => console.error("Error adding todo:", error));
        }
    });

    // Update task
    todoList.addEventListener("change", function (e) {
        if (e.target.classList.contains("todo-checkbox")) {
            const taskId = e.target.dataset.id;
            const isCompleted = e.target.checked ? 1 : 0;

            fetch("update_todo.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}&is_completed=${isCompleted}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTodos();
                    }
                })
                .catch(error => console.error("Error updating todo:", error));
        }
    });

    // Delete task
    todoList.addEventListener("click", function (e) {
        if (e.target.classList.contains("delete-todo-btn")) {
            const taskId = e.target.dataset.id;

            fetch("delete_todo.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTodos();
                    }
                })
                .catch(error => console.error("Error deleting todo:", error));
        }
    });

    // Initial fetch
    fetchTodos();
});
document.addEventListener('DOMContentLoaded', function () {
    const dailySignupsElement = document.getElementById('daily-signups');

    // Fetch daily sign-ups
    function fetchDailySignups() {
        fetch('fetch_daily_signups.php')
            .then(response => response.json())
            .then(data => {
                dailySignupsElement.textContent = data.daily_signups;
            })
            .catch(error => console.error('Error fetching daily sign-ups:', error));
    }

    // Initial fetch
    fetchDailySignups();

    // Optionally, refresh the count periodically (e.g., every 5 minutes)
    setInterval(fetchDailySignups, 300000);
});
document.addEventListener('DOMContentLoaded', function () {
    const onlineUsersCountElement = document.getElementById('online-users-count');
    const onlineUsersListElement = document.getElementById('online-users-list');

    // Fetch online users
    function fetchOnlineUsers() {
        fetch('fetch_online_users.php')
            .then(response => response.json())
            .then(data => {
                // Update the online users count
                const onlineUsers = data.online_users;
                onlineUsersCountElement.textContent = onlineUsers.length;

                // Populate the dropdown with the list of online users
                onlineUsersListElement.innerHTML = '';
                if (onlineUsers.length > 0) {
                    onlineUsers.forEach(user => {
                        const userItem = document.createElement('div');
                        userItem.className = 'user-item';
                        userItem.innerHTML = `
                            <a href="user_profile.php?id=${user.id}"><img src="${user.profile_picture || 'default-profile.png'}" alt="${user.name}" class="user-pic"></a>
                            <span>${user.name}</span>
                        `;
                        onlineUsersListElement.appendChild(userItem);
                    });
                } else {
                    onlineUsersListElement.innerHTML = '<p>No users online</p>';
                }
            })
            .catch(error => console.error('Error fetching online users:', error));
    }

    // Initial fetch
    fetchOnlineUsers();

    // Refresh the list every minute
    setInterval(fetchOnlineUsers, 60000);
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

                const markBtn = document.createElement('button');
                markBtn.textContent = '✔✔Mark all as Read';
                markBtn.className = 'mark-all-as-read btn';
                notificationsList.appendChild(markBtn);

                if (notifications.length > 0) {
                    notifications.forEach(notification => {
                        const listItem = document.createElement('li');
                        listItem.className = 'notification-item';
                        listItem.innerHTML = `
                            <img src="${notification.profile_picture || 'default-profile.png'}" alt="${notification.sender_name}" class="sender-pic">
                            <strong style="font-size: 15px"><a class="sender-name" href="user_profile.php?id=${notification.sender_id}">${notification.sender_name}</a><span style="color: grey;"> - ${notification.message}</span></strong>
                            <button class="mark-as-read-btn" data-notification-id="${notification.id}"><i class="fas fa-check"></i></button>
                        `;
                        if(notification.post_id != 0){
                        const postLi = document.createElement('span');
                        postLi.innerHTML = `<a class="btn" href=view_post2.php?id=${notification.post_id}#${notification.comment_id}><i class="fas fa-eye"></i></a>`;
                        listItem.appendChild(postLi);}
                        if(notification.post_id == 0){
                        const profileLi = document.createElement('span');
                        profileLi.innerHTML = ``;
                        listItem.appendChild(profileLi);}
                        notificationsList.appendChild(listItem);
                    });
                } else {
                    notificationsList.innerHTML = '<li>No new notifications</li>';
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

      // Mark notification as read
      notificationsList.addEventListener('click', function (event) {
        if (event.target.classList.contains('mark-as-read-btn')) {
            const notificationId = event.target.dataset.notificationId;

            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications(); // Refresh the notifications list
                } else {
                    alert(data.message || 'Failed to mark notification as read');
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        
    });

    // Mark All notification as read
    notificationsList.addEventListener('click', function (event) {
        if (event.target.classList.contains('mark-all-as-read')) {
            const notificationId = event.target.dataset.notificationId;

            fetch('mark_all_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications(); // Refresh the notifications list
                } else {
                    alert(data.message || 'Failed to mark notification as read');
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        
    });

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
                            <strong style="font-size: 15px"><a a class="sender-name" href="user_profile.php?id=${msg.sender_id}">${msg.sender_name}</a><span style="color: grey;"> (${msg.unread_count} unread)</span></strong>
                            <a href="chat.php?user_id=${msg.sender_id}" class="view-chat-link"><i class="fas fa-envelope-open"></i></a>
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
  </script>
</body>
</html>
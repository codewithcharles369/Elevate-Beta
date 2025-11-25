<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Messages</title>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

/* Light and Dark Themes */
body.light {
    background: linear-gradient(to bottom right,#2575fc, #6a11cb);
    color: #fff;
}

body.dark {
    background: linear-gradient(to bottom right, #6a11cb, #2575fc);
    color: #fff;
}

    
body {
    margin: 0;
    font-family: 'Arial', sans-serif;
    display: flex;
   
}

main{
    margin-left: 25%;
    font-family: 'Arial', sans-serif;
    justify-content: center;
    align-items: center;
}

body.dark main{
    background: linear-gradient(to bottom right, #6a11cb, #2575fc);
    color: #fff;
}

body.light main{
    background: linear-gradient(to bottom right,#2575fc, #6a11cb);
}


/* Main Content */
.content {
    flex: 1;
    padding: 10px;
}

.content h1 {
    font-size: 36px;
    margin-bottom: 20px;
}

.content p {
    font-size: 18px;
    line-height: 1.6;
}

/* Sidebar */
.sidebar {
    overflow-y: auto;
    width: 25%;
    background: rgba(0, 0, 0, 0.7);
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
    height: 100vh;
    position: fixed;
}

.sidebar h2 {
    margin-bottom: 20px;
    font-size: 22px;
}

.sidebar nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
}

.sidebar nav ul li {
    margin-bottom: 10px;
}

.sidebar nav ul li a {
    text-decoration: none;
    color: #fff;
    padding: 10px 15px;
    display: block;
    text-align: center;
    border-radius: 5px;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.sidebar nav ul li a:hover,
.sidebar nav ul li a.active
 {
    background: #2575fc;
    color: white;
}
.sidebar nav ul li i {
    margin-right: 10px;
    color: #6c63ff; /* Adjust based on your theme */
    font-size: 18px;
    transition: color 0.3s ease;
  }
  
  .sidebar nav ul li:hover i {
    color: #4a3fb8; /* Adjust hover effect */
  }

@media screen and (max-width: 600px){
    main{
        margin-left: auto;
        margin-right: auto;
        width: 100%
    }
    .sidebar{
        display: none;
    }
    #back-to-bottom {
    bottom: 300px;}
}



    #messages {
    max-height: 450px;
    overflow-y: auto;
    padding: 10px;
    display: flex;
    flex-direction: column;
    /*background-color: #f7f7f7;*/
    border-radius: 8px;
    margin: 20px 0;
    font-family: Arial, sans-serif;
    transition: height 0.2s ease;
}



.message {
    max-width: 70%;
    margin-bottom: 10px;
    padding: 12px 16px;
    border-radius: 18px;
    display: inline-block;
    position: relative;
    transition: all 0.3s ease;
}

.message.sender {
    align-self: flex-end;
    background-color: #d1e7dd;
    text-align: right;
    color: green;
    border-bottom-right-radius: 5px;
}

.message.receiver {
    align-self: flex-start;
    background-color: #f8d7da;
    text-align: left;
    color: purple;
}

.message .content {
    margin-bottom: 5px;
}

.message .time {
    font-size: 12px;
    color: #6c757d;
}

#chat-form {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: #e9ecef;
    border-top: 1px solid #ddd;
    border-radius: 10px;
    bottom: 0;
    width: 100%;
}

#message-input {
    flex: 1;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-right: 10px;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

#message-input:focus {
    outline: none;
    border-color: #6c757d;
}

#send-button {
    background-color: #0d6efd;
    color: #fff;
    font-size: 14px;
    border: none;
    border-radius: 4px;
    padding: 10px 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#send-button:hover {
    background-color: #0056b3;
}

#send-button:active {
    background-color: #004494;
}
span{
    font-size: 20px;
    font-weight: bold;
    font-family: Arial;
    
}
#notifications {
    position: relative;
    display: inline-block;
}

#notification-list {
    position: absolute;
    top: 40px;
    left: 0;
    width: 100%;
    border: 1px solid #ccc;
    background: #fff;
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    color: black;
    border-radius: 5px;
}

#notification-count {
    color: red;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.9em;
    margin-left: 5px;
}
.follow-btn{
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
    text-decoration: none;
}
.read-status {
    color: gray;
    font-size: 0.9em;
    margin-left: 5px;
}

.read-status.read {
    color: blue;
}
#user-status {
    font-size: 14px;
    font-weight: bold;
    margin-left: 20px;
}

#user-status.online {
    color: purple;
}

#user-status.offline {
    color: gray;
}
.actions{
    display: flex;
    justify-content: space-between;
    
}
.edit-btn, .delete-btn {
    margin-left: 10px;
    font-size: 12px;
    padding:10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.edit-btn {
    background-color: #ffc107;
    color: #fff;
}

.delete-btn {
    background-color: #dc3545;
    color: #fff;
}

.edit-btn:hover {
    background-color: #e0a800;
}

.delete-btn:hover {
    background-color: #c82333;
}
.message img, .message video {
    max-width: 200px;
    border-radius: 5px;
    margin-top: 5px;
    cursor: pointer;
    margin-right: 10px;
}
.upload-icon {
    cursor: pointer;
    font-size: 20px;
    color: #007bff;
    margin-right: 10px;
    transition: color 0.2s ease, transform 0.2s ease;
}

.upload-icon:hover {
    color: #0056b3;
    transform: scale(1.2); /* Slight zoom effect */
}
.chat-input-container {
    width:100px;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 10px;
    position: relative; /* Needed for the emoji picker to position correctly */
}

#emoji-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    margin-right: 8px;
}

#emoji-btn:hover {
    color: #007BFF;
}

.emoji-picker {
    display: none; /* Initially hidden */
    position: absolute;
    bottom: 50px; /* Adjust based on your chat input placement */
    left: 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 10px;
    z-index: 1000;
    max-width: 200px;
    flex-wrap: wrap;
}

.emoji-picker .emoji {
    font-size: 20px;
    margin: 5px;
    cursor: pointer;
}

.emoji-picker .emoji:hover {
    transform: scale(1.2);
}
/* Count Badge */
.count-badge {
    background-color: #dc3545;
    color: white;
    font-size: 14px;
    font-weight: bold;
    border-radius: 50%;
    padding: 4px 8px;
    margin-left: 10px;
    vertical-align: middle;
    display: inline-block;
    min-width: 24px;
    text-align: center;
}
#image-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    overflow: hidden; /* Prevent scrolling during modal view */
}

#image-modal img {
    max-width: 90%;
    max-height: 90%;
    border-radius: 8px;
    transition: transform 0.3s ease; /* Smooth zoom effect */
}

#close-modal {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
}

#close-modal:hover {
    color: red;
}
.nav {
    list-style-type: none;
    margin-left: auto;
    margin-right: auto;
    padding: 0;
    display: none;
    width: 90%;
    overflow: hidden;
  }

  @media screen and (max-width: 1260px){
  .nav {
    width: 100%;
  }
}
@media screen and (max-width: 600px){
  .nav {
    display: block;
    margin-bottom: 10px;
  }
}



.icon {
    float: left;
    background: rgba(0, 0, 0, 0.6);
    margin-left: 10px;
    margin-top: 10px;
    border-radius: 10px;
}

.icon:last-child {
    border-right: none;
}

.icon a {
    display: block;
    color: #fff;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
    height: 50px;
    transition: height 1s;
}

.icon a:hover {
    color: #4a3fb8; /* Adjust hover effect */
    height: 100px;
  }
.video-link {
    display: inline-block;
    color: white;
    background-color: #6a0dad;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 10px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.video-link:hover {
    background-color: #520a7e;
    transform: scale(1.05);
}

.video-link:before {
   /* content: '🎥';  Add a video icon */
    margin-right: 5px;
}
.document-link {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
    margin-top: 5px;
    display: inline-block;
}

.document-link:hover {
    text-decoration: underline;
}
#upload-progress {
    width: 100%;
    background: #f3f3f3;
    border: 1px solid #ccc;
    border-radius: 5px;
    overflow: hidden;
    margin-top: 10px;
}

#progress-bar {
    height: 10px;
    background: #4caf50;
    border-radius: 5px;
    transition: width 0.3s ease;
}
.reaction-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 14px;
    margin-top: 5px;
    float: left;
}

.reaction-picker {
    position: absolute;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 5px;
    padding: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 10px;
    z-index: 1000;
}

.reaction-picker .reaction {
    font-size: 18px;
    cursor: pointer;
}

.reaction-picker .reaction:hover {
    transform: scale(1.2);
}

/* Reaction Display */
.reaction-display {
    margin-top: 5px;
    font-size: 14px;
    color: #666;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

/* Animation: Fade-In and Pop */
.reaction-added {
    animation: pop-in 0.5s ease;
}

@keyframes pop-in {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
        opacity: 1;
    }
    100% {
        transform: scale(1);
    }
}
.reply {
    background: #f1f1f1;
    border-left: 4px solid #007bff;
    padding: 5px;
    border-radius: 5px;
    margin-top: 10px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
}

.reply-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 12px;
    margin-top: 5px;
}
#reply-preview {
    display: none; /* Hidden by default */
    background-color: #f9f9f9;
    border-left: 4px solid #007bff;
    padding: 5px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
    border-radius: 5px;
    align-items: center;
    justify-content: space-between;
}

#reply-to {
    flex-grow: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 14px;
}

#cancel-reply {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 16px;
    margin-left: 10px;
}

#cancel-reply:hover {
    color: #0056b3;
}
#reply-to {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.highlight {
    background-color: #f0f8ff; /* Light blue background */
    transition: background-color 0.5s ease;
}
#back-to-bottom {
    position: absolute;
    bottom: 100px;
    right: 40px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    z-index: 1000;
    transition: opacity 0.3s ease;
    font: 1.5em sans-serif;
}

#back-to-bottom:hover {
    background-color: #0056b3;
}

#back-to-bottom:active {
    transform: scale(0.9);
}
@media screen and (max-width: 600px){
    #back-to-bottom {
    bottom: 350px;}
}
#new-message-count {
    background-color: #dc3545;
    color: white;
    font-size: 14px;
    font-weight: bold;
    border-radius: 50%;
    padding: 4px 8px;
    margin-left: 10px;
    vertical-align: middle;
    display: inline-block;
    min-width: 24px;
    text-align: center;
    position: absolute;
    top: -10px;
    left: -15px;
}
.dropdown-container {
    position: relative;
    display: inline-block;
}

.options-btn {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
}

.dropdown-menu {
    position: absolute;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    width: 120px;
    display: none;
    flex-direction: column;
}

.dropdown-item {
    padding: 5px;
    font-size: 14px;
    color: #333;
    cursor: pointer;
    background: none;
    border: none;
    text-align: left;
    width: 100%;
}

.dropdown-item:hover {
    background: #f1f1f1;
}
/* Chat Container */
#messages {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 15px;
    max-height: 380px;
    overflow-y: auto;
    scrollbar-width: thin;
    border-radius: 10px;
}

/* Message Bubbles */


/* Sender's Messages */
.sender {
    align-self: flex-end;
    background-color: #d1e7dd;
    color: #fff;
    border-bottom-right-radius: 5px;
}

/* Receiver's Messages */
.receiver {
    align-self: flex-start;
    background: #ffffff;
    color: #333;
    border-bottom-left-radius: 5px;
}

/* Timestamp */
.time {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.7);
    align-self: flex-end;
    margin-top: 5px;
}

.receiver .time {
    color: #888;
}



/* Reply Section */
.reply {
    font-size: 12px;
    color: #ff9800;
    background: rgba(255, 152, 0, 0.1);
    padding: 5px;
    border-left: 4px solid #ff9800;
    border-radius: 5px;
    font-weight: 500;
}

/* Dropdown Button */
.options-btn {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: rgba(0, 0, 0, 0.5);
}

.receiver .options-btn {
    color: rgba(0, 0, 0, 0.5);
}

/* Dropdown Menu */
.dropdown-container {
    position: relative;
    align-self: flex-end;
    z-index: 1000;
}

.dropdown-menu {
    position: absolute;
    right: 0;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: none;
    width: 10y0px;
    overflow: hidden;
}

.dropdown-item {
    padding: 10px 15px;
    font-size: 14px;
    color: #333;
    cursor: pointer;
    transition: background 0.2s;
    float: right;
    border-radius: 20%;
}

.dropdown-item:hover {
    background: #f5f5f5;
}

/* Highlight Replied Message */
.highlight {
    background-color: #fdf2c5 !important;
    transition: background-color 0.5s ease;
}
#search-container {
    width: 30%;
   
}

#search-input {
    width: 90%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    outline: none;
    font-size: 14px;
    transition: border-color 0.3s;
}

#search-input:focus {
    border-color: #6a11cb;
}

/* Highlight search matches */
.highlight {
    background-color: yellow;
    padding: 2px;
    border-radius: 3px;
}
.user-profile{
    display: flex;
    align-items: center;
    justify-content: space-between;
}
/* Typing Bubble - Styled like an incoming message */
.typing-bubble {
    max-width: 60px;
    background-color: #f8d7da;
    padding: 10px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

/* Bouncing dots animation */
.typing-dots {
    display: flex;
    gap: 5px;
}

.typing-dots span {
    width: 8px;
    height: 8px;
    background-color: purple;
    border-radius: 50%;
    animation: typing 1.5s infinite ease-in-out;
}

.typing-dots span:nth-child(1) {
    animation-delay: 0s;
}
.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}
.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

/* Keyframes for bouncing dots */
@keyframes typing {
    0% { transform: translateY(0); opacity: 0.3; }
    50% { transform: translateY(-4px); opacity: 1; }
    100% { transform: translateY(0); opacity: 0.3; }
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>" style="height: 100vh;">
   <!-- Sidebar -->
   <?php if ($_SESSION['role'] === 'User'): ?>
    <aside style="height: 100%" class="sidebar">
            <img src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2><?php echo $_SESSION['name']; ?></h2>
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
            <?php endif; ?>
            
        <main class="content">
       <!-- <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
                <li class=" icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class=" icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class=" icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class=" icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class=" icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class=" icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class=" icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" style="color: white" class="count-badge">0</span></a></li>
                <li class=" icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
                <li  class=" icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                <li  class=" icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
                <li  class=" icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                <li class=" icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                <li  class=" icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                <li class=" icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
              <li class=" icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class=" icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class=" icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class=" icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class=" icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class=" icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class=" icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class=" icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class=" icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
              <br></ul> -->
            <!-- Modal for Image Viewer -->
<div id="image-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.8); z-index: 1000; justify-content: center; align-items: center;">
    <span id="close-modal" style="position: absolute; top: 20px; right: 30px; color: white; font-size: 30px; font-weight: bold; cursor: pointer;">&times;</span>
    <img id="modal-image" style="max-width: 90%; max-height: 90%; border-radius: 8px;">
</div>
    <div class="container">
        <div id="chat-container">
        <div id="receiver-details" style="display: flex; align-items: center; margin-bottom: 1px;"></div>
        
        <div  class="user-profile">
                <span id="user-status" class="offline">Offline</span>
                <div id="search-container">
            <input type="text" id="search-input" placeholder="Search messages..." oninput="searchMessages()">
        </div>
            </div>
        
        <div id="messages"></div>
        <div id="typing-indicator" class="message receiver typing-bubble" style="display: none; font-style: italic; font-size: 14px;margin-left: 1px;"></div>
        <button id="back-to-bottom" style="display: none;">
            <i class="fas fa-arrow-alt-circle-down"></i> <span id="new-message-count" style="display: none;">0</span>
        </button>
    <div id="reply-preview" style="display: none;">
            <span id="reply-to"></span>
            <button type="button" id="cancel-reply" aria-label="Cancel Reply">✖</button>
        </div>
        <form id="chat-form" enctype="multipart/form-data">
         <!-- Reply Preview -->
        
    <input type="hidden" id="reply-to-id" name="reply_to" value="">
            <button type="button" id="emoji-btn">😀</button>
            <label for="media-input" class="upload-icon" >
            <i class="fas fa-paperclip"></i>
            </label>
            <input type="text" name="message" required id="message-input" placeholder="Type a message...">
            <input type="file" name="media[]" style="display: none;" id="media-input" accept="image/*,video/*,.pdf,.doc,.docx,.txt" multiple>
            <button type="submit" id="send-button"><i class="fas fa-paper-plane"></i></button>
            <div id="emoji-picker" class="emoji-picker">
                <!-- Emoji List (Static for Simplicity) -->
                <span class="emoji">😂</span>
                <span class="emoji">🥰</span>
                <span class="emoji">😀</span>
                <span class="emoji">😘</span>
                <span class="emoji">😍</span>
                <span class="emoji">🤔</span>
                <span class="emoji">😎</span>
                <span class="emoji">😢</span>
                <span class="emoji">😊</span>
                <span class="emoji">😡</span>
                <span class="emoji">😭</span>
                <span class="emoji">🙏</span>
                <span class="emoji">🔥</span>
                <span class="emoji">👍</span>
                <span class="emoji">👎</span>
                <span class="emoji">🎉</span>
                <span class="emoji">🎂</span>
                <span class="emoji">❤️</span>
                <span class="emoji">💔</span>
                <span class="emoji">🙌</span>
                <span class="emoji">⭐</span> 

            </div>
        </form>
        <!-- Progress Bar -->
        <div id="upload-progress" style="display: none;">
            <div id="progress-bar" style="width: 0%; height: 10px; background: #4caf50; border-radius: 5px;"></div>
        </div>
    </div>
    <audio id="reaction-sound" src="assets/sounds/reaction.mp3" preload="auto"></audio>
<script>
const senderId = <?php echo $_SESSION['user_id']; ?> // Replace with logged-in user's ID
const receiverId = new URLSearchParams(window.location.search).get('user_id');

let isUserScrolling = false;

const messagesDiv = document.getElementById('messages');

// Detect if the user is scrolling
messagesDiv.addEventListener('scroll', () => {
    const atBottom = messagesDiv.scrollHeight - messagesDiv.scrollTop === messagesDiv.clientHeight;
    isUserScrolling = !atBottom;
});

let newMessageCount = 0; // Track the number of new messages
let lastMessageCount = 0; // Track the total number of messages from the last fetch

function fetchMessages() {
    const messagesDiv = document.getElementById('messages');
    const backToBottomButton = document.getElementById('back-to-bottom');
    const newMessageCountSpan = document.getElementById('new-message-count');
    const wasAtBottom = messagesDiv.scrollHeight - messagesDiv.scrollTop === messagesDiv.clientHeight;
    const searchQuery = document.getElementById('search-input').value.toLowerCase(); // Store search query

    // Store the currently open dropdown (if any)
    const openDropdownMessageId = document.querySelector('.dropdown-menu[style="display: block;"]')
        ?.parentElement.querySelector('.options-btn')?.dataset.messageId;

    fetch(`fetch_messages.php?sender_id=${senderId}&receiver_id=${receiverId}`)
        .then(response => response.json())
        .then(messages => {
            const currentMessageCount = messages.length; // Total messages fetched

            messagesDiv.innerHTML = ''; // Clear existing messages

            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.sender_id == senderId ? 'sender' : 'receiver'}`;
                messageDiv.id = `message-${msg.id}`; // Assign a unique ID to each message

                const contentDiv = document.createElement('div');
                contentDiv.className = 'content';
                contentDiv.textContent = msg.message

                const readDiv = document.createElement('span');
                readDiv.className = 'read-status';
                readDiv.textContent = `${msg.sender_id == senderId ? (msg.is_read == 1 ? '✔✔' : '✔') : ''}`;

                const timeDiv = document.createElement('div');
                timeDiv.className = 'time';
                const messageTime = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                timeDiv.textContent = messageTime;

               // Add media
               if (msg.media) {
                    const fileUrls = msg.media.split(','); // Split media URLs
                    fileUrls.forEach(url => {
                        if (url.match(/\.(jpg|jpeg|png|gif)$/i)) {
                            // Display images directly
                            const mediaElement = document.createElement('img');
                            mediaElement.src = url;
                            mediaElement.style.maxWidth = '200px';
                            mediaElement.style.borderRadius = '5px';
                            mediaElement.style.marginTop = '10px';
                            messageDiv.appendChild(mediaElement);
                        } else if (url.match(/\.(mp4|mov|avi|mkv)$/i)) {
                            // Video link
                            const videoLink = document.createElement('a');
                            videoLink.href = url;
                            videoLink.target = '_blank';
                            videoLink.textContent = '🎥 View Video';
                            videoLink.className = 'video-link';
                            messageDiv.appendChild(videoLink);
                        } else if (url.match(/\.(pdf|doc|docx|txt)$/i)) {
                            // Document link
                            const docLink = document.createElement('a');
                            docLink.href = url;
                            docLink.target = '_blank';
                            docLink.textContent = '📄 View Document';
                            docLink.className = 'document-link';
                            messageDiv.appendChild(docLink);
                        }
                    });
                }

                 // Dropdown container
                 const dropdownContainer = document.createElement('div');
                dropdownContainer.className = 'dropdown-container';

                // Options button (⋮)
                const optionsButton = document.createElement('button');
                optionsButton.className = 'options-btn';
                optionsButton.innerHTML = '⋮'; // Three-dot menu
                optionsButton.dataset.messageId = msg.id;

                // Dropdown menu
                const dropdownMenu = document.createElement('div');
                dropdownMenu.className = 'dropdown-menu';
                dropdownMenu.style.display = 'none';

                // Reply Button
                const replyButton = document.createElement('button');
                replyButton.className = 'dropdown-item reply-btn';
                replyButton.innerHTML = '<i class="fas fa-reply"></i>';
                replyButton.dataset.messageId = msg.id;
                replyButton.dataset.messageText = msg.message;


                // Add Edit and Delete buttons for sender's messages
                if (msg.sender_id == senderId) {
                    const editButton = document.createElement('button');
                    editButton.className = 'dropdown-item edit-btn';
                    editButton.innerHTML = '<i class="fas fa-pen"></i>';
                    editButton.dataset.messageId = msg.id;

                    const deleteButton = document.createElement('button');
                    deleteButton.className = 'dropdown-item delete-btn';
                    deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteButton.dataset.messageId = msg.id;

                    dropdownMenu.appendChild(editButton);
                    dropdownMenu.appendChild(deleteButton);
                }

                // Reaction button
                const reactionBtn = document.createElement('button');
                reactionBtn.className = 'dropdown-item reaction-btn';
                reactionBtn.innerHTML = '<i class="fas fa-surprise"></i>';
                reactionBtn.dataset.messageId = msg.id;

                dropdownMenu.appendChild(reactionBtn);
                dropdownMenu.appendChild(replyButton);

                // Toggle dropdown menu visibility on click
                optionsButton.addEventListener('click', function (e) {
                    e.stopPropagation(); // Prevent closing immediately
                    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
                    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function () {
                    dropdownMenu.style.display = 'none';
                });

                // Restore open dropdown (if it was open before reload)
            if (openDropdownMessageId) {
                const newOptionsButton = document.querySelector(`.options-btn[data-message-id="${openDropdownMessageId}"]`);
                if (newOptionsButton) {
                    newOptionsButton.nextElementSibling.style.display = 'block';
                }
            }

                // Add the reply section if the message is a reply
                if (msg.reply_to_message) {
                    const replyDiv = document.createElement('div');
                    replyDiv.className = 'reply';
                    replyDiv.textContent = `Replying to: "${msg.reply_to_message}"`;
                    replyDiv.dataset.replyToId = msg.reply_to; // Add the ID of the replied message

                    // Add click event to scroll to the replied message
                    replyDiv.addEventListener('click', function () {
                        const targetMessage = document.getElementById(`message-${this.dataset.replyToId}`);
                        if (targetMessage) {
                            targetMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            // Highlight the replied message briefly
                            targetMessage.classList.add('highlight');
                            setTimeout(() => targetMessage.classList.remove('highlight'), 1000); // Remove highlight after 1 second
                        }
                    });

                    messageDiv.appendChild(replyDiv);
                }



                // Reaction display
                const reactionDisplay = document.createElement('div');
                reactionDisplay.className = 'reaction-display';
                reactionDisplay.dataset.messageId = msg.id;

                // Parse reactions and display them
                if (msg.reactions) {
                    const reactions = msg.reactions.split(',');
                    reactions.forEach(r => {
                        const [userId, reaction] = r.split(':');

                        // Check if reaction already exists to prevent duplicate animations
                        if (!reactionDisplay.querySelector(`[data-user-id="${userId}"]`)) {
                            const reactionSpan = document.createElement('span');
                            reactionSpan.textContent = reaction;
                            reactionSpan.dataset.userId = userId;
                            reactionSpan.classList.add('reaction-added'); // Apply animation class
                            reactionDisplay.appendChild(reactionSpan);

                            // Remove the animation class after it plays
                            setTimeout(() => {
                                reactionSpan.classList.remove('reaction-added');
                            }, 500); // Match animation duration
                        }
                    });
                }

                
                contentDiv.appendChild(optionsButton);
                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(timeDiv);
                contentDiv.appendChild(dropdownMenu);
                messageDiv.appendChild(dropdownContainer);
                timeDiv.appendChild(readDiv);
                messageDiv.appendChild(reactionDisplay);
                messagesDiv.appendChild(messageDiv);
            });

            // Handle auto-scrolling and new message counting
            if (wasAtBottom) {
                // If the user is at the bottom, scroll automatically and reset new message count
                messagesDiv.scrollTo({ top: messagesDiv.scrollHeight, behavior: 'smooth' });
                newMessageCount = 0; // Reset count if user is at the bottom
                newMessageCountSpan.style.display = 'none'; // Hide the count
            } else {
                // Only count messages sent to the current user (receiver)
                const receiverMessages = messages.filter(msg => msg.receiver_id == senderId);

                if (receiverMessages.length > lastMessageCount) {
                    newMessageCount += receiverMessages.length - lastMessageCount; // Increment by the difference
                }

                newMessageCountSpan.textContent = newMessageCount;
                newMessageCountSpan.style.display = newMessageCount > 0 ? 'inline' : 'none'; // Show count if > 0
            }

            // Restore open dropdown (if it was open before reload)
            if (openDropdownMessageId) {
                const newOptionsButton = document.querySelector(`.options-btn[data-message-id="${openDropdownMessageId}"]`);
                if (newOptionsButton) {
                    newOptionsButton.nextElementSibling.style.display = 'block';
                }
            }

            // Apply search again after reload
            if (searchQuery !== "") {
                searchMessages(); // Reapply search filter
            }

            // Update the last message count
            lastMessageCount = messages.filter(msg => msg.receiver_id == senderId).length; // Only count receiver messages

            updateReadStatus(receiverId, senderId);
        })
        .catch(error => console.error('Error fetching messages:', error));
}

function searchMessages() {
    const searchQuery = document.getElementById('search-input').value.toLowerCase();
    const messages = document.querySelectorAll('.message .content');

    messages.forEach(msg => {
        const text = msg.textContent.toLowerCase();
        const messageDiv = msg.closest('.message');

        if (text.includes(searchQuery) && searchQuery !== "") {
            messageDiv.style.display = "block";
            msg.innerHTML = msg.textContent.replace(
                new RegExp(searchQuery, "gi"),
                match => `<span class="highlight">${match}</span>`
            );
        } else {
            messageDiv.style.display = searchQuery ? "none" : "block";
            msg.innerHTML = msg.textContent; // Remove highlight when search is cleared
        }
    });
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('reply-btn')) {
        const replyToId = e.target.dataset.messageId; // ID of the message being replied to
        const replyToText = e.target.dataset.messageText; // Text of the message being replied to

        // Show the reply preview in the form
        const replyPreview = document.getElementById('reply-preview');
        replyPreview.style.display = 'flex'; // Show preview
        replyPreview.style.alignItems = 'center';

        // Set the reply text and hidden input
        document.getElementById('reply-to').textContent = `Replying to: "${replyToText}"`;
        document.getElementById('reply-to-id').value = replyToId;
    }
});

// Cancel Reply Logic
document.getElementById('cancel-reply').addEventListener('click', function () {
    const replyPreview = document.getElementById('reply-preview');
    replyPreview.style.display = 'none'; // Hide preview
    document.getElementById('reply-to').textContent = ''; // Clear reply text
    document.getElementById('reply-to-id').value = ''; // Clear hidden input
});

document.addEventListener('DOMContentLoaded', function () {
    const messagesDiv = document.getElementById('messages');
    const backToBottomButton = document.getElementById('back-to-bottom');
    const newMessageCountSpan = document.getElementById('new-message-count');

    // Show the "Back to Bottom" button when user scrolls up
    messagesDiv.addEventListener('scroll', function () {
        const isAtBottom = messagesDiv.scrollHeight - messagesDiv.scrollTop === messagesDiv.clientHeight;

        if (isAtBottom) {
            backToBottomButton.style.display = 'none'; // Hide button when at the bottom
        } else {
            backToBottomButton.style.display = 'block'; // Show button when scrolling up
        }
    });

    // Scroll to the bottom when the button is clicked
    backToBottomButton.addEventListener('click', function () {
        messagesDiv.scrollTo({ top: messagesDiv.scrollHeight, behavior: 'smooth' });

        // Reset new message count
    newMessageCount = 0;
    newMessageCountSpan.textContent = '0';
    newMessageCountSpan.style.display = 'none'; // Hide the count

    });
});

function scrollToBottom() {
    const messagesDiv = document.getElementById('messages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value;

    if (message.trim() === '') return;

    const formData = new FormData();
    formData.append('message', message);
    formData.append('sender_id', senderId);
    formData.append('receiver_id', receiverId);

    fetch('send_message.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear the input
                messageInput.value = '';

                // Append the new message
                const messagesDiv = document.getElementById('messages');
                const newMessageDiv = document.createElement('div');
                newMessageDiv.className = 'message sender';
                newMessageDiv.innerHTML = `
                    <div class="content">${message}</div>
                    <div class="time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                `;
                messagesDiv.appendChild(newMessageDiv);

                // Scroll to the bottom after appending the new message
                scrollToBottom();
            } else {
                console.error('Error sending message:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}

function scrollToBottom() {
    const messagesDiv = document.getElementById('messages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Fetch messages on page load
fetchMessages();

document.getElementById('chat-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('receiver_id', receiverId);

    fetch('upload_media.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('message-input').value = '';
                document.getElementById('media-input').value = '';
                fetchMessages(); // Reload messages
            } else {
                alert(data.message || 'Failed to send message.');
            }
        })
        .catch(error => console.error('Error uploading media:', error));
});

// Refresh messages every 2 seconds
setInterval(fetchMessages, 2000);

// Initial fetch
fetchMessages();

document.addEventListener("DOMContentLoaded", function () {
    const receiverId = new URLSearchParams(window.location.search).get("user_id");
    const receiverDetailsDiv = document.getElementById("receiver-details");

    // Fetch receiver's information
    fetch(`fetch_receiver.php?receiver_id=${receiverId}`)
        .then(response => response.json())
        .then(data => {
            // Create elements for receiver's name and profile picture
            const profileImg = document.createElement("img");
            profileImg.src = data.profile_picture || "assets/default-profile.jpg"; // Default profile picture if none is uploaded
            profileImg.alt = "Profile Picture";
            profileImg.style.width = "80px";
            profileImg.style.height = "80px";
            profileImg.style.borderRadius = "50%";
            profileImg.style.marginRight = "10px";

            const receiverName = document.createElement("span");
            receiverName.textContent = data.name;

            // Append to receiver details container
            receiverDetailsDiv.appendChild(profileImg);
            receiverDetailsDiv.appendChild(receiverName);
        })
        .catch(error => console.error("Error fetching receiver details:", error));
});

const messageInput = document.getElementById("message-input");

let typingTimeout;

messageInput.addEventListener("input", () => {
    clearTimeout(typingTimeout);

    // Notify the server that the user is typing
    fetch('update_typing_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: senderId, typing_status: 1 })
    });

    // Reset typing status after a delay
    typingTimeout = setTimeout(() => {
        fetch('update_typing_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: senderId, typing_status: 0 }),
        });
    }, 1000); // Typing status will reset after 2 seconds of inactivity
});

function checkTypingStatus() {
    fetch(`fetch_typing_status.php?receiver_id=${receiverId}`)
        .then(response => response.json())
        .then(data => {
            const typingIndicator = document.getElementById("typing-indicator");
            if (data.typing == 1) {
                typingIndicator.innerHTML = `<div class='typing-dots'>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </div>`;
                typingIndicator.style.display = "block";
            } else {
                typingIndicator.style.display = "none";
            }
        });
}

// Check typing status every second
setInterval(checkTypingStatus, 1000);
function updateReadStatus(senderId, receiverId) {
   fetch('update_read_status.php', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ sender_id: senderId, receiver_id: receiverId })
   })
   .then(response => response.json())
      .then(data => {
       if (data.status == "success") {
           console.log("Messages marked as read");
       }
   });
}
function fetchUserStatus(userId) {
    fetch(`get_user_status.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById("user-status");
            if (data.status === "online") {
                statusElement.textContent = "Online";
                statusElement.classList.add("online");
                statusElement.classList.remove("offline");
            } else if (data.status === "offline" && data.last_online) {
                const lastOnlineTime = new Date(data.last_online).toLocaleString();
                statusElement.textContent = `Last online: ${lastOnlineTime}`;
                statusElement.classList.add("offline");
                statusElement.classList.remove("online");
            } else {
                statusElement.textContent = "Offline";
                statusElement.classList.add("offline");
                statusElement.classList.remove("online");
            }
        });
}

// Call the function periodically to update the status
const userId = new URLSearchParams(window.location.search).get('user_id');
setInterval(() => fetchUserStatus(userId), 1000);
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


document.addEventListener('click', function (e) {
    if (e.target.classList.contains('reaction-btn')) {
        const messageId = e.target.dataset.messageId;

        // Remove existing pickers
        document.querySelectorAll('.reaction-picker').forEach(picker => picker.remove());

        // Create the reaction picker
        const reactionPicker = document.createElement('div');
        reactionPicker.className = 'reaction-picker';
        reactionPicker.innerHTML = `
            <span class="reaction" data-reaction="👍">👍</span>
            <span class="reaction" data-reaction="❤️">❤️</span>
            <span class="reaction" data-reaction="😂">😂</span>
            <span class="reaction" data-reaction="😢">😢</span>
            <span class="reaction" data-reaction="🔥">🔥</span>
        `;
        document.body.appendChild(reactionPicker);

        // Position the picker near the button
        const rect = e.target.getBoundingClientRect();
        reactionPicker.style.position = 'absolute';
        reactionPicker.style.top = `${rect.bottom + window.scrollY}px`;
        reactionPicker.style.left = `${rect.left}px`;

        // Handle reaction click
        reactionPicker.addEventListener('click', function (event) {
    if (event.target.classList.contains('reaction')) {
        const reaction = event.target.dataset.reaction;

        // Send reaction to the server
        fetch('add_reaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message_id: messageId, reaction: reaction })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const reactionDisplay = document.querySelector(`.reaction-display[data-message-id="${messageId}"]`);

                    // Add the reaction to the display
                    const reactionSpan = document.createElement('span');
                    reactionSpan.textContent = reaction;
                    reactionSpan.classList.add('reaction-added'); // Apply animation class
                    reactionDisplay.appendChild(reactionSpan);

                    // Play the reaction sound
                    const reactionSound = document.getElementById('reaction-sound');
                    reactionSound.currentTime = 0; // Reset the sound to the beginning
                    reactionSound.play();

                    // Remove the animation class after it plays
                    setTimeout(() => {
                        reactionSpan.classList.remove('reaction-added');
                    }, 500); // Match the animation duration
                } else {
                    alert(data.message || 'Failed to add reaction.');
                }
            })
            .catch(error => console.error('Error adding reaction:', error));

        reactionPicker.remove(); // Remove the picker
    }
});

        // Close picker if clicked outside
        document.addEventListener('click', function closePicker(event) {
            if (!reactionPicker.contains(event.target) && event.target !== e.target) {
                reactionPicker.remove();
                document.removeEventListener('click', closePicker);
            }
        });
    }
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('delete-btn')) {
        const messageId = e.target.dataset.messageId;

        // Confirm delete
        if (confirm('Are you sure you want to delete this message?')) {
            fetch('delete_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: messageId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchMessages(); // Reload messages
                } else {
                    alert('Failed to delete message.');
                }
            });
        }
    }
});


document.addEventListener('click', function (e) {
    if (e.target.classList.contains('edit-btn')) {
        const messageId = e.target.dataset.messageId;
        const messageDiv = document.getElementById(`message-${messageId}`);
        const messageContentDiv = messageDiv.querySelector('.content');

        if (!messageContentDiv) {
            alert("Message content not found!");
            return;
        }

        const currentMessage = messageContentDiv.textContent;

        // Prompt user to enter a new message
        const newMessage = prompt('Edit your message:', currentMessage);
        if (newMessage !== null && newMessage.trim() !== '') {
            fetch('edit_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: messageId, new_message: newMessage })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageContentDiv.textContent = newMessage; // Update the UI immediately
                } else {
                    alert('Failed to edit message.');
                }
            })
            .catch(error => console.error('Error editing message:', error));
        }
    }
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
    const emojiButton = document.getElementById('emoji-btn');
    const emojiPicker = document.getElementById('emoji-picker');
    const messageInput = document.getElementById('message-input');

    // Show/Hide Emoji Picker
    emojiButton.addEventListener('click', () => {
        emojiPicker.style.display = emojiPicker.style.display === 'none' || emojiPicker.style.display === '' 
            ? 'block' 
            : 'none';
    });

    // Add emoji to the input field
    emojiPicker.addEventListener('click', (event) => {
        if (event.target.classList.contains('emoji')) {
            messageInput.value += event.target.textContent;
            messageInput.focus(); // Focus back on the input field
            emojiPicker.style.display = 'none'; // Optionally hide picker after selection
        }
    });

    // Close the picker if clicked outside
    document.addEventListener('click', (event) => {
        if (!emojiPicker.contains(event.target) && event.target !== emojiButton) {
            emojiPicker.style.display = 'none';
        }
    });
});
document.addEventListener("DOMContentLoaded", function () {
        const messagesDiv = document.getElementById("messages");
        const chatForm = document.getElementById("chat-form"); // Your chat form ID
        const originalHeight = window.innerHeight;

        // Listen for window resize (detects keyboard appearance)
        window.addEventListener("resize", () => {
            const currentHeight = window.innerHeight;
            
            if (currentHeight < originalHeight) {
                // Keyboard is active
                const keyboardHeight = originalHeight - currentHeight;
                messagesDiv.style.height = `calc(100vh - ${chatForm.offsetHeight + keyboardHeight}px)`;
            } else {
                // Keyboard is hidden
                messagesDiv.style.height = `calc(100vh - ${chatForm.offsetHeight}px)`;
            }
        });
    });
    document.addEventListener('DOMContentLoaded', () => {
    const imageModal = document.getElementById('image-modal');
    const modalImage = document.getElementById('modal-image');
    const closeModal = document.getElementById('close-modal');

    // Variables for zoom functionality
    let scale = 1;

    // Open modal on image click
    document.getElementById('messages').addEventListener('click', (e) => {
        if (e.target.tagName === 'IMG') {
            modalImage.src = e.target.src; // Set modal image source
            imageModal.style.display = 'flex'; // Show the modal
            scale = 1; // Reset zoom scale
            modalImage.style.transform = `scale(${scale})`;
        }
    });

    // Close modal on click of the close button
    closeModal.addEventListener('click', () => {
        imageModal.style.display = 'none';
    });

    // Close modal when clicking outside the image
    imageModal.addEventListener('click', (e) => {
        if (e.target === imageModal) {
            imageModal.style.display = 'none';
        }
    });

    // Zoom functionality with mouse wheel
    modalImage.addEventListener('wheel', (e) => {
        e.preventDefault();
        scale += e.deltaY * -0.01; // Zoom in/out based on scroll direction
        scale = Math.min(Math.max(scale, 1), 3); // Limit zoom scale between 1x and 3x
        modalImage.style.transform = `scale(${scale})`;
    });
});
let startX;

imageModal.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
});

imageModal.addEventListener('touchend', (e) => {
    const endX = e.changedTouches[0].clientX;
    if (startX - endX > 50) {
        // Swipe left
        navigateToNextImage();
    } else if (endX - startX > 50) {
        // Swipe right
        navigateToPreviousImage();
    }
});

function navigateToNextImage() {
    const images = document.querySelectorAll('#messages img');
    const currentIndex = [...images].findIndex(img => img.src === modalImage.src);
    const nextIndex = (currentIndex + 1) % images.length;
    modalImage.src = images[nextIndex].src;
}

function navigateToPreviousImage() {
    const images = document.querySelectorAll('#messages img');
    const currentIndex = [...images].findIndex(img => img.src === modalImage.src);
    const previousIndex = (currentIndex - 1 + images.length) % images.length;
    modalImage.src = images[previousIndex].src;
}
document.getElementById('chat-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('receiver_id', receiverId);

    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.getElementById('upload-progress');

    // Show the progress bar
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload_media.php', true);

    // Update the progress bar
    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBar.style.width = percentComplete + '%';
        }
    });

    // Hide the progress bar on completion
    xhr.addEventListener('load', function () {
        progressBar.style.width = '100%';
        setTimeout(() => {
            progressContainer.style.display = 'none'; // Hide progress bar
        }, 1000);

        // Handle response
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
            document.getElementById('message-input').value = '';
            document.getElementById('media-input').value = '';
            document.getElementById('reply-to-id').value = ''; // Clear reply-to value
            document.getElementById('reply-to-section').style.display = 'none'; // Hide reply-to section
            fetchMessages(); // Reload messages
        } else {
            alert(response.message || 'Failed to send message.');
        }
    });

    // Handle errors
    xhr.addEventListener('error', function () {
        alert('An error occurred during the file upload.');
    });

    // Send the form data
    xhr.send(formData);
});

    </script>
</body>
</html>

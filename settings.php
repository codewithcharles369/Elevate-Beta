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

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Settings</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Slider Toggle */
.theme-toggle {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.theme-toggle input {
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
    background-color: #6a11cb;
    transition: 0.4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: #2575fc;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #2196f3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}


#settings-container {
    max-width: 600px;
    margin: 20px auto;
    font-family: Arial, sans-serif;
    background: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    color: #333;
}

#settings-container h1 {
    font-size: 24px;
    color: #007BFF;
    text-align: center;
    margin-bottom: 20px;
}

#profile-update-form{
    width: 100%;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

        /* Form Container */
        form {
    width: 100%;
    background: rgba(0, 0, 0, 0.3);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
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
form textarea {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 15px;
}

button {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    font-weight: bold;
    background: #007BFF;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}
.profile-picture-preview {
    display: block;
    width: 150px;
    height: 150px;
    margin-bottom: 10px;
    border-radius: 50%;
    object-fit: cover;
    margin-left: auto;
    margin-right: auto;
}
.danger-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    border-radius
}
/* Widget Settings Container */
.widget-settings {
    background: linear-gradient(135deg, #4B0082, #8A2BE2);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    margin: 20px 0;
    transition: transform 0.3s ease;
}

.widget-settings h2 {
    text-align: center;
    margin-bottom: 15px;
    font-size: 24px;
}

/* Toggle Switch Styles */
.toggle-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding: 10px;
}

/* Toggle Switch */
.toggle-switch {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 18px;
    cursor: pointer;
    position: relative;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transition: background 0.3s ease;
}

.toggle-switch:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Hide the default checkbox */
.toggle-switch input[type="checkbox"] {
    display: none;
}

/* Slider Design */
.widget-settings .slider {
    position: relative;
    width: 50px;
    height: 24px;
    background-color: #ccc;
    border-radius: 34px;
    transition: background-color 0.3s;
}

.widget-settings .slider::before {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    background-color: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s ease;
}

/* Toggle Active State */
.widget-settings input[type="checkbox"]:checked + .slider {
    background-color: #FFD700;
}

.widget-settings input[type="checkbox"]:checked + .slider::before {
    transform: translateX(26px);
}


    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
   <!-- Sidebar -->
   <?php if ($user['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
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
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i> Bookmarks</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php" class="active"><i class="fas fa-cog"></i>Settings</a></li>
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
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i>Bookmarked Posts</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php" class="active"><i class="fas fa-cog"></i>Settings</a></li>
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
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <a href="#"><li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
        <div class="settings-section">

        <h4 class="animate-on-scroll" style="text-align: center;">Theme Settings</h4>
    <label class="animate-on-scroll theme-toggle">
        <input class="animate-on-scroll" type="checkbox" id="theme-toggle">
        <span class="animate-on-scroll slider"></span>
    </label>
    <p class="animate-on-scroll" style="text-align: center;" id="theme-status">Light Mode</p><br><br>
 
        
    <form id="profile-update-form" enctype="multipart/form-data">
    <h2 style="text-align: center;">Update Your Profile</h2>
        <!-- Profile Picture -->
        

        <!-- Name -->
        <div class="animate-on-scroll form-group">
            <label class="animate-on-scroll" for="name">Name</label>
            <input class="animate-on-scroll" type="text" id="name" name="name" placeholder="Enter your name">
        </div>

        <!-- Bio -->
        <div class="animate-on-scroll form-group">
            <label class="animate-on-scroll" for="bio">Bio</label>
            <textarea class="animate-on-scroll" id="bio" name="bio" rows="4" placeholder="Tell us about yourself"></textarea>
        </div>

        <!-- Social Links -->
        <div class="animate-on-scroll form-group">
            <label class="animate-on-scroll" for="social-links">Social Links</label>
            <input class="animate-on-scroll" type="url" id="social-links" name="social_links" placeholder="Enter your social media link">
        </div>

        <!-- Email -->
        <div class="animate-on-scroll form-group">
            <label class="animate-on-scroll" for="email">Email</label>
            <input class="animate-on-scroll" type="email" id="email" name="email" placeholder="Enter your email">
        </div>

        <!-- Password -->
        <div class="animate-on-scroll form-group">
            <label class="animate-on-scroll" for="password">Password</label>
            <input class="animate-on-scroll" type="password" id="password" name="password" placeholder="Enter your new password">
        </div><br>

        <div class="form-group">
            <img id="profile-picture-preview" src="default-profile.png" alt="Profile Picture" class="animate-on-scroll profile-picture-preview">
            <label class="animate-on-scroll" for="profile-picture">Profile- Picture</label>
            <input class="animate-on-scroll" type="file" accept="image/*" id="profile-picture" name="profile_picture">
        </div>

        <!-- Submit Button -->
        <button class="animate-on-scroll" type="submit">Update Profile</button>
    </form>
        <br>
        <br>
        <br>

        <section class="widget-settings">
    <h2>⚙️ Dashboard Widget Settings</h2>
    <div class="toggle-container">
        

        <label class="toggle-switch">
            <input type="checkbox" class="widget-toggle" data-widget="analytics-widget" checked>
            <span class="slider"></span>
            Advanced Analytics
        </label>

        <label class="toggle-switch">
            <input type="checkbox" class="widget-toggle" data-widget="recommendations-widget" checked>
            <span class="slider"></span>
            People You May Know
        </label>

        <label class="toggle-switch">
            <input type="checkbox" class="widget-toggle" data-widget="suggested-posts-widget" checked>
            <span class="slider"></span>
            Suggested Posts
        </label>

        <label class="toggle-switch">
            <input type="checkbox" class="widget-toggle" data-widget="posts-activity-widget" checked>
            <span class="slider"></span>
            Your Posting Activity
        </label>

        <label class="toggle-switch">
            <input type="checkbox" class="widget-toggle" data-widget="engagement-overview-widget" checked>
            <span class="slider"></span>
            Engagement Overview
        </label>

        <label class="toggle-switch">
            <input type="checkbox" class="widget-toggle" data-widget="task-manager-widget" checked>
            <span class="slider"></span>
            Task Manager
        </label>
    </div>
</section>

    <div id="settings-container">
    <h1 class="animate-on-scroll">Account Settings</h1>
    <!-- Other settings form -->
    <button id="deactivate-account-btn" class="animate-on-scroll danger-btn">Deactivate Account</button>
</div>

<ul class="nav">
              <?php if ($user['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <?php elseif ($user['role'] === 'Admin'): ?>
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
            </ul><br>
</div>
<br>
<br>
<script>
    
    document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");
    const themeStatus = document.getElementById("theme-status");

    // Check the current theme from the body class
    const currentTheme = document.body.classList.contains("dark") ? "dark" : "light";
    themeToggle.checked = currentTheme === "dark";
    themeStatus.textContent = currentTheme === "dark" ? "Purple Theme" : "Blue Theme";

    // Update styles dynamically on toggle
    themeToggle.addEventListener("change", function () {
        const selectedTheme = themeToggle.checked ? "dark" : "light";

        // Apply theme to body
        document.body.className = selectedTheme;
        themeStatus.textContent = selectedTheme === "dark" ? "Purple Theme" : "Blue Theme";

        // Send the theme preference to the server
        fetch("update_theme.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `theme=${selectedTheme}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("Failed to update theme. Please try again.");
            }
        })
        .catch(error => console.error("Error:", error));
    });
});
function submitProfileForm() {
    const form = document.getElementById('profile-update-form');
    form.submit();
}
document.getElementById('profile-update-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('update_profile.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Reload the page to reflect changes
            } else {
                alert(data.message || 'Failed to update profile.');
            }
        })
        .catch(error => console.error('Error:', error));
});
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('name');
    const bioInput = document.getElementById('bio');
    const socialLinksInput = document.getElementById('social-links');
    const emailInput = document.getElementById('email');
    const profilePicturePreview = document.getElementById('profile-picture-preview');

    // Fetch user profile data
    fetch('fetch_user_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                nameInput.value = user.name || '';
                bioInput.value = user.bio || '';
                socialLinksInput.value = user.social_links || '';
                emailInput.value = user.email || '';
                if (user.profile_picture) {
                    profilePicturePreview.src = user.profile_picture; // Show existing profile picture
                }
            } else {
                alert(data.message || 'Failed to fetch user data.');
            }
        })
        .catch(error => console.error('Error fetching profile data:', error));
});
document.getElementById('deactivate-account-btn').addEventListener('click', function () {
    if (confirm('Are you sure you want to deactivate your account? If you do not log in within 7 days, your account will be permanently deleted.')) {
        fetch('deactivate_account.php', {
            method: 'POST',
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.href = 'index.html'; // Redirect to homepage after deactivation
                }
            })
            .catch(error => console.error('Error:', error));
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

document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".widget-toggle");

    // Fetch saved widget settings when the settings page loads
    fetch('get_widget_settings.php')
        .then(response => response.json())
        .then(settings => {
            toggles.forEach(toggle => {
                const widgetId = toggle.dataset.widget;
                toggle.checked = settings[widgetId] || false; // Set toggle based on saved settings
            });
        })
        .catch(error => console.error('Error fetching widget settings:', error));

    // Update widget visibility when toggles are changed
    toggles.forEach(toggle => {
        toggle.addEventListener("change", function () {
            const widgetId = this.dataset.widget;
            const isVisible = this.checked;

            // Send visibility status to the server
            fetch('update_widget_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `widget_id=${widgetId}&visible=${isVisible ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optionally, update the dashboard in real-time if needed
                    const widget = document.querySelector(`[data-id="${widgetId}"]`);
                    if (widget) {
                        widget.style.display = isVisible ? "block" : "none";
                    }
                }
            })
            .catch(error => console.error('Error updating widget settings:', error));
        });
    });
});
</script>
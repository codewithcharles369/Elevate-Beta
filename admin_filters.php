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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage filters</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>/* Base styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    
    .container3 {
      width: 80%;
      margin: 50px auto;
      background-color: #ffffff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .container2 {
      width: 80%;
      margin: 50px auto;
      background-color: #6a0dad;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    h2 {
      text-align: center;
      color: #6a0dad; /* Purple */
    }


    h3.tag {
      text-align: center;
      color: white; /* Purple */
    }

    h2.tag {
      text-align: center;
      color: #ffffff; /* Purple */
    }
    
    .category-form, .tag-form {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    
    #category-name {
      padding: 10px;
      font-size: 16px;
      margin-right: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    #tag-name {
      padding: 10px;
      font-size: 16px;
      margin-right: 10px;
      border: 1px solid #ddd;
      background-color: inherit;
      border-radius: 5px;
      color: white;
    }
    
    button {
      padding: 10px 20px;
      font-size: 16px;
      background-color: #6a0dad;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    button.tag {
      padding: 10px 20px;
      font-size: 16px;
      background-color: white;
      color: #6a0dad;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    
    button:hover {
      background-color: #520e8c;
    }

    button.tag:hover {
      background-color: white;
    }
    
    /* Category list */
    .category-list,
    .tag-list {
      text-align: center;
      color: black;
    }
    
    #category-list,
    #tag-list {
      list-style-type: none;
      padding: 0;
    }
    
    #category-list li {
      background-color: #f9f9f9;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
      text-align: center;
    }

    #tag-list li {
      background-color: #520e8c;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
      text-align: center;
    }
    
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
<!-- Sidebar -->
<aside class="sidebar" style="overflow-y: scroll;">
        <img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;">
        <h2 class="animate-on-scroll" style="color: white"><?php echo $_SESSION['name']; ?></h2>
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
                <li><a href="admin_filters.php" class="active"><i class="fas fa-folder-open"></i>Manage Filters</a></li>
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
        <ul class="nav">
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_groups.php"><i class="fas fa-users"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
              <a href=""><li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
    <div class="container3">
        <h2 class="animate-on-scroll">Manage Categories</h2>
        <div class="animate-on-scroll category-form">
            
            <input class="animate-on-scroll" type="text" id="category-name" placeholder="Enter category name">
            <button  id="add-category" class="animate-on-scroll btn">Add Category</button>
           
        </div>

        <div class="animate-on-scroll category-list">
      <h3 class="animate-on-scroll">Existing Categories</h3>
      <ul class="animate-on-scroll" id="category-list"></ul>
    </div>
    </div>
    <div class="animate-on-scroll container2">
    <h2 class="animate-on-scroll tag">Manage Tags</h2>

    <div class="animate-on-scroll tag-form">
        <input class="animate-on-scroll" type="text" id="tag-name" placeholder="Enter tag name">
        <button id="add-tag" class="animate-on-scroll btn">Add Tag</button>
    </div>
    <div class="animate-on-scroll tag-list">
      <h3 class="animate-on-scroll tag">Existing Tags</h3>
        <ul style="color: white;" class="animate-on-scroll" id="tag-list"></ul>
        </div>
  </div>
<br>
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
              <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li  class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br><br><br>
    <script>
       document.addEventListener("DOMContentLoaded", function () {
    const categoryInput = document.getElementById("category-name");
    const tagInput = document.getElementById("tag-name");
    const categoryList = document.getElementById("category-list");
    const tagList = document.getElementById("tag-list");

    // Fetch and display categories
    function fetchCategories() {
        fetch("fetch_categories.php")
            .then(response => response.json())
            .then(data => {
                categoryList.innerHTML = "";
                data.forEach(category => {
                    const li = document.createElement("li");
                    li.textContent = category.name;

                    // Add delete button
                    const deleteBtn = document.createElement("button");
                    deleteBtn.textContent = "Delete";
                    deleteBtn.style.marginLeft = "10px";
                    deleteBtn.onclick = () => deleteCategory(category.id);

                    li.appendChild(deleteBtn);
                    categoryList.appendChild(li);
                });
            });
    }

    // Fetch and display tags
    function fetchTags() {
        fetch("fetch_tags.php")
            .then(response => response.json())
            .then(data => {
                tagList.innerHTML = "";
                data.forEach(tag => {
                    const li = document.createElement("li");
                    li.textContent = tag.name;

                    // Add delete button
                    const deleteBtn = document.createElement("button");
                    deleteBtn.textContent = "Delete";
                    deleteBtn.style.marginLeft = "10px";
                    deleteBtn.onclick = () => deleteTag(tag.id);

                    li.appendChild(deleteBtn);
                    tagList.appendChild(li);
                });
            });
    }

    // Add category
    document.getElementById("add-category").addEventListener("click", function () {
        const name = categoryInput.value.trim();
        if (!name) return alert("Please enter a category name");

        fetch("add_category.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === "success") {
                    categoryInput.value = "";
                    fetchCategories();
                }
            });
    });

    // Add tag
    document.getElementById("add-tag").addEventListener("click", function () {
        const name = tagInput.value.trim();
        if (!name) return alert("Please enter a tag name");

        fetch("add_tag.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === "success") {
                    tagInput.value = "";
                    fetchTags();
                }
            });
    });
    // Delete category
    function deleteCategory(id) {
        if (!confirm("Are you sure you want to delete this category?")) return;

        fetch("delete_category.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === "success") {
                    fetchCategories();
                }
            });
    }

    // Delete tag
    function deleteTag(id) {
        if (!confirm("Are you sure you want to delete this tag?")) return;

        fetch("delete_tag.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === "success") {
                    fetchTags();
                }
            });
    }

    // Initial fetch
    fetchCategories();
    fetchTags();
});
</script>
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
            </script>
</body>
</html>
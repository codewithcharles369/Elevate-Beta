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
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;

    $user_id = $_GET['id'] ?? $_SESSION['user_id'];

}


if (!isset($_GET['id'])) {
    die('Invalid post ID');
}

$postId = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM group_posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die('Post not found');
}

// Ensure only the author can access this page
if ($post['user_id'] != $_SESSION['user_id']) {
    die('Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $pdo->prepare("SELECT media, group_id FROM group_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    $existingMedia = json_decode($post['media'], true) ?? [];

    $keptMedia = $_POST['existing_media'] ?? [];
    $uploadedFiles = [];

    if (!empty($_FILES['media']['name'][0])) {
        foreach ($_FILES['media']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['media']['error'][$index] === UPLOAD_ERR_OK) {
                $filename = time() . '_' . $_FILES['media']['name'][$index];
                move_uploaded_file($tmpName, 'uploads/' . $filename);
                $uploadedFiles[] = 'uploads/' . $filename;
            }
        }
    }

    $finalMedia = array_merge($keptMedia, $uploadedFiles);
    $mediaJson = json_encode($finalMedia);

    $stmt = $pdo->prepare("UPDATE group_posts SET title = ?, content = ?, media = ? WHERE id = ?");
    $stmt->execute([$title, $content, $mediaJson, $postId]);

    header("Location: group_posts.php?id=" . $post['group_id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Create Post</title>
    <style>
        /* Container */
.group-post-form-container {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    margin: 40px auto;
    max-width: 700px;
    animation: fadeIn 0.5s ease-out;
    transition: background-color 0.3s ease;
}

.group-post-form-container h1 {
    font-size: 2.2rem;
    color: #6a0dad;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.group-post-form-container h1 i {
    color: #9b59b6;
}

/* Form Elements */
.group-post-form .form-group {
    margin-bottom: 20px;
}

.group-post-form label {
    display: block;
    font-weight: bold;
    color: #4a0072;
    margin-bottom: 8px;
    font-size: 1rem;
}

.group-post-form input[type="text"],
.group-post-form textarea,
.group-post-form input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #fafafa;
    transition: border-color 0.3s ease, background-color 0.3s ease;
}

.group-post-form input[type="text"]:focus,
.group-post-form textarea:focus {
    border-color: #6a0dad;
    background-color: #fff;
    outline: none;
}

.group-post-form textarea {
    height: 150px;
    resize: vertical;
}

/* Helper Text */
.input-helper-text {
    font-size: 0.9rem;
    color: #777;
    margin-top: 4px;
}

/* Submit Button */
.submit-btn {
    background-color: #6a0dad;
    color: white;
    font-size: 1.1rem;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.submit-btn i {
    font-size: 1.2rem;
}

.submit-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

.submit-btn:active {
    transform: scale(0.98);
}

/* Error Message */
.error-message {
    color: red;
    background-color: #ffecec;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
}

/* Fade-in Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.group-post-form-container {
    opacity: 0;
    transform: translateY(10px);
    animation: slideInForm 0.6s ease forwards;
}

@keyframes slideInForm {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Media Placeholder */
.media-placeholder {
    background-color: #f9f9f9;
    padding: 16px;
    border: 2px dashed #6a0dad;
    text-align: center;
    border-radius: 12px;
    cursor: pointer;
    font-size: 0.95rem;
    transition: background-color 0.3s ease;
}

.media-placeholder:hover {
    background-color: #f3e5ff;
}

/* Preview Container */
.media-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 10px;
}

/* Media Preview Items */
.media-preview-item {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 120px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background-color: #f3f3f3;
}

.preview-media {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

/* Remove Button */
.remove-media-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    background-color: #ff4d4d;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 14px;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: background-color 0.3s ease;
}

.remove-media-btn:hover {
    background-color: #cc0000;
}
/* Draft Controls Wrapper */
.draft-controls {
    margin-top: 10px;
    text-align: right;
}

/* Clear Draft Button */
.clear-draft-btn {
    background-color: transparent;
    color: #6a0dad;
    border: 2px solid #6a0dad;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.clear-draft-btn:hover {
    background-color: #6a0dad;
    color: white;
    transform: translateY(-2px);
}

.clear-draft-btn:active {
    transform: translateY(1px);
}
#quill-editor {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.ql-toolbar {
    border-radius: 8px 8px 0 0;
    background-color: #fafafa;
}

.ql-container {
    border-radius: 0 0 8px 8px;
    font-family: 'Segoe UI', sans-serif;
    font-size: 1rem;
    color: initial;
}

.post-preview-container {
    background-color: #fafafa;
    border: 1px solid #e0e0e0;
    padding: 20px;
    border-radius: 12px;
    margin-top: 30px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.post-preview-container h2 {
    color: #6a0dad;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.preview-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 12px;
    word-wrap: break-word;
}

.preview-content {
    line-height: 1.7;
    font-size: 1rem;
    color: #555;
    word-wrap: break-word;
    min-height: 150px;
    border-left: 3px solid #6a0dad;
    padding-left: 12px;
}

body.dark .post-preview-container {
    background-color: #1e1e1e;
    border-color: #333;
}

body.dark .preview-title {
    color: #ffffff;
}

body.dark .preview-content {
    color: #cccccc;
    border-left-color: #9b59b6;
}

.media-preview-container {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.media-preview-item {
    position: relative;
    border: 1px solid #ddd;
    padding: 5px;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.media-preview-item img, .media-preview-item video {
    max-width: 100px;
    height: auto;
    display: block;
    border-radius: 5px;
}

.remove-media-btn {
    margin-top: 5px;
    background-color: #ff4d4d;
    color: white;
    border: none;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 12px;
    border-radius: 5px;
}

.remove-media-btn:hover {
    background-color: #ff1a1a;
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
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i>  All Posts</a></li>
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
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i> All Posts</a></li>
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
              <a href="#"><li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>


            <div class="group-post-form-container animate-on-scroll">
    <h1><i class="fas fa-plus-circle"></i> Edit Group Post</h1>
    <?php if (!empty($error)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="edit_group_post.php?id=<?= $postId ?>" method="POST" enctype="multipart/form-data" class="group-post-form">
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" placeholder="Enter your post title..." required>
        </div>

        <div class="form-group">
            <label for="quill-editor"><i class="fas fa-align-left"></i> Content</label>
            <div id="quill-editor" style="height: 200px;"><?= htmlspecialchars_decode($post['content']) ?></div>
            <input type="hidden" value="<?= htmlspecialchars($post['content']) ?>" name="content" id="hidden-content">
        </div>

        <div id="existing-media-imputs"></div>


        <div class="form-group media-preview-group">
            <label for="media"><i class="fas fa-images"></i> Upload Media (Optional)</label>
            <div class="media-placeholder" onclick="document.getElementById('media').click();">
                <span id="media-placeholder-text">Click to upload media</span>
            </div>
            <input type="file" id="media" name="media[]" accept="image/*,video/*" multiple style="display: none;">
            <div id="media-preview" class="media-preview-container" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
        </div>


        <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Post</button>
    </form>

    <div class="post-preview-container animate-on-scroll">
    <h2>Post Preview</h2>
    <div class="preview-title" id="preview-title">Your Title Will Appear Here</div>
    <div class="preview-content" id="preview-content">Your Post Content Will Appear Here</div>
</div>
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
const titleInput = document.getElementById('title');
const quillEditor = document.getElementById('quill-editor');

document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('title');
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write your post content here...',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image', 'video'],
                ['blockquote', 'code-block'],
                ['clean']
            ],
        },
    });

    // Preview Elements
    const previewTitle = document.getElementById('preview-title');
    const previewContent = document.getElementById('preview-content');

    // Sync Content with Hidden Input on Submit
    document.querySelector('.group-post-form').addEventListener('submit', function () {
        document.getElementById('hidden-content').value = quill.root.innerHTML;
    });

    // Real-Time Preview Update
    titleInput.addEventListener('input', function () {
        previewTitle.textContent = titleInput.value || 'Your Title Will Appear Here';
    });

    quill.on('text-change', function () {
        const contentHtml = quill.root.innerHTML;
        previewContent.innerHTML = contentHtml.trim() !== '<p><br></p>' ? contentHtml : 'Your Post Content Will Appear Here';
    });


    // Sync Quill content with hidden input on submit
    document.querySelector('.group-post-form').addEventListener('submit', function () {
        document.getElementById('hidden-content').value = quill.root.innerHTML;

        // Clear draft from storage on successful submit
        localStorage.removeItem('groupPostDraftTitle');
        localStorage.removeItem('groupPostDraftContent');
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const existingMedia = <?= $post['media'] ? json_encode(json_decode($post['media'])) : '[]' ?>;
    const previewContainer = document.getElementById('media-preview');
    const placeholderText = document.getElementById('media-placeholder-text');
    const mediaInput = document.getElementById('media');
    const existingMediaInputsContainer = document.getElementById('existing-media-inputs');

    // Display existing media
    function displayExistingMedia() {
        if (existingMedia.length > 0) {
            existingMedia.forEach((filePath) => {
                appendMediaPreview(filePath, false);
            });
        }
        updatePlaceholderText();
    }

    // Show newly uploaded media previews
    mediaInput.addEventListener('change', function (event) {
        const files = Array.from(event.target.files);
        files.forEach((file) => {
            const fileURL = URL.createObjectURL(file);
            appendMediaPreview(fileURL, true, file);
        });
        updatePlaceholderText();
    });

    // Reusable - Create and Append Media Preview
    function appendMediaPreview(src, isNew, file = null) {
        const wrapper = document.createElement('div');
        wrapper.classList.add('media-preview-item');

        if (src.match(/\.(jpeg|jpg|png|gif)$/i) || (isNew && file.type.startsWith('image'))) {
            const img = document.createElement('img');
            img.src = src;
            img.classList.add('preview-media');
            wrapper.appendChild(img);
        } else if (src.match(/\.(mp4|webm|mov|avi)$/i) || (isNew && file.type.startsWith('video'))) {
            const video = document.createElement('video');
            video.src = src;
            video.controls = true;
            video.classList.add('preview-media');
            wrapper.appendChild(video);
        } else {
            const unknown = document.createElement('p');
            unknown.textContent = 'Unsupported File';
            wrapper.appendChild(unknown);
        }

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.textContent = 'Remove';
        removeBtn.classList.add('remove-media-btn');
        removeBtn.addEventListener('click', function () {
            wrapper.remove();
            if (!isNew) {
                const inputToRemove = existingMediaInputsContainer.querySelector(`input[value="${src}"]`);
                if (inputToRemove) inputToRemove.remove();
            }
            updatePlaceholderText();
        });

        wrapper.appendChild(removeBtn);

        // If it's existing media, track it for form submission
        if (!isNew) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'existing_media[]';
            hiddenInput.value = src;
            existingMediaInputsContainer.appendChild(hiddenInput);
        }

        previewContainer.appendChild(wrapper);
    }

    // Update placeholder text
    function updatePlaceholderText() {
        const mediaCount = previewContainer.children.length;
        placeholderText.textContent = mediaCount > 0 ? `${mediaCount} file(s) selected` : 'Click to upload media';
    }

    displayExistingMedia();
});
</script>
</body>
</html>
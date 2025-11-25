<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/db.php';

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

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = intval($_POST['category_id']);
    $tags = $_POST['tags']; // Assume tags are an array
    $mediaPaths = [];
    $scheduled_at = trim($_POST['scheduled_at']) ?? null ;
    $hashtags = $_POST['hashtags'] ?? null; // Can be empty
    $audience = $_POST['audience']; // 'public', 'followers', 'private'


    // Handle multiple file uploads
    if (!empty($_FILES['media']['name'][0])) {
        foreach ($_FILES['media']['name'] as $key => $fileName) {
            $fileTmpName = $_FILES['media']['tmp_name'][$key];
            $fileSize = $_FILES['media']['size'][$key];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif'];
            $allowedVideoExt = ['mp4', 'mov', 'avi', 'mkv'];

            if (in_array($fileExt, array_merge($allowedImageExt, $allowedVideoExt)) && $fileSize <= 1024 * 1024 * 1024) { // 10MB limit
                $uniqueName = uniqid('media_', true) . '.' . $fileExt;
                $targetPath = 'uploads/' . $uniqueName;

                if (move_uploaded_file($fileTmpName, $targetPath)) {
                    $mediaPaths[] = $uniqueName;
                }
            }
        }
    }

    // Convert media paths to JSON
    $mediaPathsJson = json_encode($mediaPaths);

    // Insert post into the database
    $stmt = $pdo->prepare("INSERT INTO posts (title, content, media, user_id, category_id, hashtags, scheduled_at, audience) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $mediaPathsJson, $_SESSION['user_id'], $category_id, $hashtags, $scheduled_at, $audience]);
    $post_id = $pdo->lastInsertId();

    // Insert tags into the post_tags table
    if (!empty($tags)) {
        $tagStmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
        foreach ($tags as $tag_id) {
            $tagStmt->execute([$post_id, $tag_id]);
        }
    }

        // 🔔 Notify Followers
    $stmt = $pdo->prepare("SELECT follower_id FROM follows WHERE following_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $followers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($followers)) {
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message, is_read) VALUES (?, ?, ?, ?, 0)");
        foreach ($followers as $follower_id) {
            $message = "New post from " . $_SESSION['name'] . ": $title";
            $link = "view_post2.php?id=$post_id";
            $notificationStmt->execute([$follower_id, $_SESSION['user_id'], $link, $message]);
        }
    }

    header("Location: my_posts.php");
    exit;
}
?>
<?php
// Fetch categories and tags from the database
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$tags = $pdo->query("SELECT * FROM tags")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>


/* ==== FORM WRAPPER ==== */
form {
    width: 100%;
    max-width: 750px;
    margin: auto;
    background: rgba(255, 255, 255, 0.15);
    padding: 35px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.25);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: fadeIn 0.8s ease;
}

/* Header */
h2 {
    text-align: center;
    font-size: 28px;
    margin-bottom: 25px;
    font-weight: 800;
    color: #fff;
}

/* Labels */
form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 15px;
    color: #f9f9f9;
}

/* Inputs / selects */
form input,
form select,
form textarea {
    width: 100%;
    padding: 13px 15px;
    margin-bottom: 18px;
    background: rgba(255,255,255,0.85);
    border: none;
    border-radius: 10px;
    font-size: 15px;
    transition: 0.25s ease;
}

form input:focus,
form select:focus,
form textarea:focus {
    outline: none;
    background: white;
    box-shadow: 0 0 10px #7E5FF2;
}

/* File Input */
input[type="file"] {
    background: rgba(255,255,255,0.9);
    padding: 12px;
    border-radius: 10px;
}

/* Submit Button */
form button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #6A11CB, #2575FC);
    color: white;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 15px;
    transition: 0.3s ease;
}

form button:hover {
    transform: translateY(-2px);
    background: linear-gradient(135deg, #5b0fba, #1e63d4);
}

/* Suggestions box */
.title-suggestions-container,
.hashtag-suggestions-container {
    background: rgba(255,255,255,0.2);
    padding: 12px;
    border-radius: 12px;
    margin-top: -10px;
}

/* Live Preview */
.post-preview {
    margin-top: 35px;
    padding: 20px;
    border-radius: 15px;
    background: rgba(0,0,0,0.45);
    color: white;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.post-preview h3 {
    font-size: 22px;
    margin-bottom: 15px;
}

.preview-card {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 12px;
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    form {
        padding: 20px;
        border-radius: 15px;
    }

    .post-preview {
        padding: 15px;
    }
}

/* ==========================
   Modern Post Preview Styling
   ========================== */

/* Wrapper */
.post-preview-container {
    margin-top: 35px;
    padding: 25px;
    border-radius: 20px;
    background: linear-gradient(145deg, rgba(255,255,255,0.75), rgba(255,255,255,0.6));
    box-shadow: 
        0 10px 25px rgba(0,0,0,0.15),
        inset 0 0 12px rgba(255,255,255,0.4);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: 0.35s ease;
    max-width: 850px;
    margin-left: auto;
    margin-right: auto;
    animation: fadeIn 0.7s ease;
}

/* Dark Mode Support */
body.dark .post-preview-container {
    background: rgba(25, 25, 35, 0.55);
    box-shadow: 
        0 10px 30px rgba(0,0,0,0.6),
        inset 0 0 20px rgba(255,255,255,0.07);
}

/* Section Title */
.post-preview-container h2 {
    font-size: 1.8rem;
    color: #6a11cb;
    font-weight: 800;
    margin-bottom: 18px;
    text-align: center;
}

/* Dark mode title */
body.dark .post-preview-container h2 {
    color: #b88fff;
}

/* Preview Card */
.preview-card {
    background: rgba(255,255,255,0.8);
    border-radius: 14px;
    padding: 20px;
    border-left: 5px solid #6a11cb;
    overflow-wrap: break-word;
    transition: 0.3s ease;
}

/* Dark mode preview card */
body.dark .preview-card {
    background: rgba(0,0,0,0.35);
    border-left-color: #9b59b6;
}

/* Title */
.preview-title {
    font-size: 1.9rem;
    font-weight: 700;
    color: #222;
    margin-bottom: 12px;
}

body.dark .preview-title {
    color: #fff;
}

/* Content */
.preview-content {
    font-size: 1.05rem;
    color: #444;
    line-height: 1.7;
    padding-top: 5px;
    min-height: 120px;
}

body.dark .preview-content {
    color: #dcdcdc;
}

/* Media Preview Area */
.preview-media-gallery {
    margin-top: 15px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.preview-media-gallery img,
.preview-media-gallery video {
    width: 160px;
    height: 160px;
    border-radius: 12px;
    object-fit: cover;
    box-shadow: 0 5px 12px rgba(0,0,0,0.15);
    transition: 0.3s ease;
}

.preview-media-gallery img:hover,
.preview-media-gallery video:hover {
    transform: scale(1.03);
}

/* Fade-in animation */
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to   {opacity: 1; transform: translateY(0);}
}

.media-preview-container {
    position: relative;
    margin-top: 10px;
    overflow: hidden;
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.2);
    padding: 10px;
    max-width: 100%;
    height: 300px;
}

.media-slider {
    display: flex;
    transition: transform 0.5s ease;
    height: 100%;
}

.media-slide {
    min-width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.media-slide img, .media-slide video {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 8px;
}
.ql-toolbar {
    background-color: #f3f3f3;
    border-radius: 8px 8px 0 0;
}

.ql-container {
    border-radius: 0 0 0 0;
    background-color: #fff;
    color: black;
}

.ql-editor {
    min-height: 150px;
    font-size: 16px;
    line-height: 1.6;
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
                    <li><a href="create_post.php" class="active"><i class="fas fa-pen"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
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
                    <li><a href="create_post.php" class="active"><i class="fas fa-pen"></i>Create Post</a></li>
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
        <main class="content">
   
        <ul class="nav">
              <?php if ($user['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
                <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <?php elseif ($user['role'] === 'Admin'): ?>
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
              <a href="#"><li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
            <h2><i class="fas fa-pen"></i> Create a New Post</h2>
            
<form id="createPostForm" action="create_post.php" method="POST" enctype="multipart/form-data">

    <label for="title">Post Title</label>
    <input type="text" id="title" name="title" placeholder="Enter post title..." required>

    <div class="title-suggestions-container">
        <h4>🔍 Smart Title Suggestions</h4>
        <ul id="titleSuggestionsList"></ul>
    </div>

    <label for="content">Content</label>
    <div id="quillEditor"></div>
    <textarea id="content" name="content" style="display:none"></textarea>

    <button type="button" id="voiceToTextBtn">🎙️ Voice Input</button>
    <span id="voiceStatus" style="display:none;">Listening...</span>

    <label for="media">Media Upload</label>
    <input type="file" id="media" name="media[]" accept="image/*,video/*" multiple>

    <label for="category">Category</label>
    <select name="category_id" id="category" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="tags">Tags</label>
    <select name="tags[]" id="tags" multiple>
        <?php foreach ($tags as $tag): ?>
            <option value="<?php echo $tag['id']; ?>"><?php echo $tag['name']; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="hashtags">Hashtags (optional)</label>
    <input type="text" id="hashtags" name="hashtags" placeholder="#motivation #success">
    <div id="hashtagSuggestions" class="hashtag-suggestions-container"></div>

    <label for="audience">Audience</label>
    <select id="audience" name="audience">
        <option value="public">🌍 Public</option>
        <option value="followers">👥 Followers Only</option>
        <option value="private">🔒 Private</option>
    </select>

    <label>
        <input type="checkbox" id="scheduleToggle"> Schedule post?
    </label>

    <div id="scheduleTimeInput" style="display:none;">
        <label for="scheduled_at">Date & Time</label>
        <input type="datetime-local" id="scheduled_at" name="scheduled_at">
    </div>

    <button type="submit">Publish Post</button>
</form>



        <div class="post-preview">
        <h3>🔎 Live Post Preview</h3>
        <div class="preview-card">
            <div class="media-preview-container" id="mediaPreviewContainer" style="display: none;">
                <div class="media-slider" id="mediaSlider"></div>
            </div>
            <h2 id="previewTitle">Post Title Preview</h2>
            <p id="previewContent">Your post content will appear here as you type...</p>

        </div>
        
        
    </div>
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
    </div>
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
document.addEventListener("DOMContentLoaded", function () {
    const micButtons = document.querySelectorAll(".mic-btn");

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        micButtons.forEach(button => {
            const recognition = new SpeechRecognition();
            recognition.lang = "en-US";
            recognition.interimResults = true;
            recognition.continuous = true; // Continuous listening for smoother flow

            let isRecording = false;
            let finalTranscript = ""; // To store confirmed speech

            button.addEventListener("click", () => {
                const targetId = button.dataset.target;
                const targetElement = document.getElementById(targetId);

                if (!isRecording) {
                    recognition.start();
                    button.classList.add("active");
                    button.textContent = "🛑 Stop";
                    isRecording = true;
                } else {
                    recognition.stop();
                    button.classList.remove("active");
                    button.textContent = "🎤";
                    isRecording = false;
                }

                recognition.onresult = (event) => {
                    let interimTranscript = "";

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        let transcript = event.results[i][0].transcript.trim().toLowerCase();

                        // Handle commands
                        transcript = handleVoiceCommands(transcript, targetElement);

                        if (event.results[i].isFinal) {
                            // Prevent duplication
                            if (!finalTranscript.endsWith(transcript)) {
                                finalTranscript += transcript + " ";
                            }
                        } else {
                            interimTranscript = transcript;
                        }
                    }

                    // Update the text field with both final and interim text
                    targetElement.value = finalTranscript + interimTranscript;
                };

                recognition.onerror = (event) => {
                    console.error("Speech recognition error:", event.error);
                    recognition.stop();
                    button.classList.remove("active");
                    button.textContent = "🎤";
                    isRecording = false;
                };

                recognition.onend = () => {
                    if (isRecording) {
                        recognition.start(); // Restart if still in recording mode
                    } else {
                        button.classList.remove("active");
                        button.textContent = "🎤";
                    }
                };
            });
        });

        // Handle voice commands for punctuation and editing
        function handleVoiceCommands(transcript, targetElement) {
            const commands = {
                "new line": "\n",
                "comma": ",",
                "period": ".",
                "full stop": ".",
                "question mark": "?",
                "exclamation mark": "!",
                "colon": ":",
                "semicolon": ";",
                "open bracket": "(",
                "close bracket": ")",
                "open quote": '"',
                "close quote": '"',
                "apostrophe": "'",
                "dash": "-",
                "hyphen": "-",
                "delete": "DELETE" // Special marker for delete action
            };

            if (commands[transcript] !== undefined) {
                if (transcript === "delete") {
                    // Delete the last character
                    targetElement.value = targetElement.value.slice(0, -1);
                    return ""; // Prevent adding 'delete' to the text
                } else {
                    return commands[transcript];
                }
            }

            return transcript; // Return the regular transcript if no command matches
        }
    } else {
        micButtons.forEach(button => {
            button.disabled = true;
            button.textContent = "🎙️ Not Supported";
        });
        console.warn("SpeechRecognition is not supported in this browser.");
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const titleInput = document.getElementById("title");
    const contentInput = document.getElementById("content");
    const previewTitle = document.getElementById("previewTitle");
    const previewContent = document.getElementById("previewContent");

    titleInput.addEventListener("input", function () {
        previewTitle.textContent = titleInput.value.trim() || "Post Title Preview";
    });

    quill.on('text-change', function () {
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = quill.root.innerHTML;
});
});
document.addEventListener("DOMContentLoaded", function () {
    const mediaInput = document.getElementById("media");
    const mediaPreviewContainer = document.getElementById("mediaPreviewContainer");
    const mediaSlider = document.getElementById("mediaSlider");

    let slideIndex = 0;
    let slideInterval;

    mediaInput.addEventListener("change", function () {
        const files = mediaInput.files;
        mediaSlider.innerHTML = ""; // Clear previous slides

        if (files.length > 0) {
            mediaPreviewContainer.style.display = "block";

            Array.from(files).forEach(file => {
                const slide = document.createElement("div");
                slide.classList.add("media-slide");

                if (file.type.startsWith("image/")) {
                    const img = document.createElement("img");
                    img.src = URL.createObjectURL(file);
                    slide.appendChild(img);
                } else if (file.type.startsWith("video/")) {
                    const video = document.createElement("video");
                    video.src = URL.createObjectURL(file);
                    video.controls = true;
                    slide.appendChild(video);
                }

                mediaSlider.appendChild(slide);
            });

            // Reset and start slider
            slideIndex = 0;
            updateSlider();
            startSlider();
        } else {
            mediaPreviewContainer.style.display = "none";
            stopSlider();
        }
    });

    function updateSlider() {
        const offset = -slideIndex * 100;
        mediaSlider.style.transform = `translateX(${offset}%)`;
    }

    function startSlider() {
        stopSlider(); // Clear previous interval if any

        slideInterval = setInterval(() => {
            slideIndex++;
            if (slideIndex >= mediaSlider.children.length) {
                slideIndex = 0;
            }
            updateSlider();
        }, 3000); // Auto slide every 3 seconds
    }

    function stopSlider() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    

    const hiddenContent = document.getElementById('content');

    // Sync Quill content to hidden input before form submission
    document.getElementById('createPostForm').addEventListener('submit', function () {
        hiddenContent.value = quill.root.innerHTML;
    });

    // Optional: Live Preview
    const previewContent = document.getElementById('previewContent');
    quill.on('text-change', function () {
        previewContent.innerHTML = quill.root.innerHTML;
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const titleInput = document.getElementById('title');
    const contentEditor = document.querySelector('#quillEditor'); // Assuming Quill.js
    const quill = new Quill(contentEditor, {
    });

    const hiddenContent = document.getElementById('content');

    // Load saved draft on page load
    const savedTitle = localStorage.getItem('draft_title');
    const savedContent = localStorage.getItem('draft_content');

    if (savedTitle) titleInput.value = savedTitle;
    if (savedContent) quill.root.innerHTML = savedContent;

    // Auto-save to localStorage on input change
    titleInput.addEventListener('input', () => {
        localStorage.setItem('draft_title', titleInput.value);
    });

    quill.on('text-change', () => {
        localStorage.setItem('draft_content', quill.root.innerHTML);
        hiddenContent.value = quill.root.innerHTML; // Sync content for submission
    });

    // Clear draft on successful form submission (optional)
    const postForm = document.getElementById('createPostForm');
    postForm.addEventListener('submit', () => {
        localStorage.removeItem('draft_title');
        localStorage.removeItem('draft_content');
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Write something amazing...',
        modules: {
            toolbar: [
                [{ header: [1, 2, false] }],
                ['bold', 'italic', 'underline'],
                ['link', 'blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean'] // Remove formatting
            ]
        }
    });

    const hiddenContent = document.getElementById('content');
    const titleInput = document.getElementById('title');
    const previewContent = document.getElementById('previewContent');
    const clearDraftBtn = document.getElementById('clearDraft');

    // Load Draft
    const savedTitle = localStorage.getItem('draft_title');
    const savedContent = localStorage.getItem('draft_content');
    if (savedTitle) titleInput.value = savedTitle;
    if (savedContent) quill.root.innerHTML = savedContent;
    hiddenContent.value = savedContent || '';

    // Auto-Save Draft
    titleInput.addEventListener('input', () => {
        localStorage.setItem('draft_title', titleInput.value);
    });

    quill.on('text-change', () => {
        const content = quill.root.innerHTML;
        localStorage.setItem('draft_content', content);
        hiddenContent.value = content; // Update hidden input for submission

        // Live Preview Update
        previewContent.innerHTML = content || 'Your post content will appear here as you type...';
    });

    // Clear Draft
    clearDraftBtn.addEventListener('click', () => {
        localStorage.removeItem('draft_title');
        localStorage.removeItem('draft_content');
        titleInput.value = '';
        quill.setText('');
        hiddenContent.value = '';
        previewContent.innerHTML = 'Your post content will appear here as you type...';
    });

    // Final submission sync (for safety)
    document.getElementById('createPostForm').addEventListener('submit', function () {
        hiddenContent.value = quill.root.innerHTML;
        localStorage.removeItem('draft_title');
        localStorage.removeItem('draft_content');
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const scheduleToggle = document.getElementById('scheduleToggle');
    const scheduleTimeInput = document.getElementById('scheduleTimeInput');
    const scheduleInput = document.getElementById('scheduled_at');

    scheduleToggle.addEventListener('change', function () {
        if (scheduleToggle.checked) {
            scheduleTimeInput.style.display = 'block';
            scheduleInput.required = true;
        } else {
            scheduleTimeInput.style.display = 'none';
            scheduleInput.value = '';
            scheduleInput.required = false;
        }
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const contentEditor = document.querySelector('#quillEditor');
    const quill = Quill.find(contentEditor);
    const titleInput = document.getElementById('title');
    const suggestionsList = document.getElementById('titleSuggestionsList');

    // Common Patterns Based on Keywords
    const titlePatterns = {
    "tips": [
        "Top 10 Tips for [Topic]",
        "Practical Tips to Improve [Topic]",
        "Essential [Topic] Tips You Need to Know"
    ],
    "ideas": [
        "Creative Ideas to Boost [Topic]",
        "Best [Topic] Ideas for Beginners",
        "Unique [Topic] Ideas You Should Try"
    ],
    "how to": [
        "How to [Topic] Effectively",
        "Mastering [Topic] in 5 Easy Steps",
        "How to Get Started with [Topic]"
    ],
    "guide": [
        "The Ultimate Guide to [Topic]",
        "Beginner’s Guide to [Topic]",
        "Advanced [Topic] Guide for Pros"
    ],
    "steps": [
        "5 Easy Steps to [Topic]",
        "Step-by-Step Process to Master [Topic]",
        "Simple Steps to Improve [Topic]"
    ],
    "success": [
        "Secrets to Success in [Topic]",
        "How to Achieve Success in [Topic]",
        "Success Stories: Lessons from [Topic] Experts"
    ],
    "grow": [
        "How to Grow in [Topic]",
        "Proven Ways to Grow [Topic]",
        "Strategies to Expand Your [Topic] Skills"
    ],
    "beginner": [
        "Beginner’s Guide to [Topic]",
        "Getting Started with [Topic]",
        "Essential [Topic] Tips for Beginners"
    ],
    "mistakes": [
        "Common Mistakes in [Topic] and How to Avoid Them",
        "5 Costly [Topic] Mistakes You’re Making",
        "The Do’s and Don’ts of [Topic]"
    ],
    "trends": [
        "Latest Trends in [Topic] You Need to Know",
        "Emerging [Topic] Trends in 2025",
        "Future of [Topic]: What’s Next?"
    ],
    "challenge": [
        "Take the [Topic] Challenge!",
        "30-Day [Topic] Challenge to Transform Your Life",
        "How I Overcame [Topic] Challenges"
    ],
    "reasons": [
        "5 Reasons Why [Topic] Matters",
        "Why You Should Focus on [Topic]",
        "Reasons You’re Failing at [Topic] (and How to Fix It)"
    ],
    "best": [
        "Best Practices for [Topic]",
        "Top 5 Best Tools for [Topic]",
        "Best [Topic] Resources You Need"
    ],
    "secrets": [
        "Hidden Secrets of [Topic] Experts",
        "Insider Secrets to Mastering [Topic]",
        "Little-Known Secrets About [Topic]"
    ],
    "improve": [
        "How to Improve Your [Topic] Skills",
        "Simple Ways to Level Up Your [Topic]",
        "Practical Strategies to Enhance [Topic]"
    ],
    "lessons": [
        "Lessons I Learned from [Topic]",
        "Key Takeaways from [Topic] Experts",
        "What [Topic] Taught Me About Success"
    ]
};

    // Extract content & generate suggestions
    quill.on('text-change', function () {
        const contentText = quill.getText().toLowerCase();

        const foundKeywords = Object.keys(titlePatterns).filter(keyword => contentText.includes(keyword));

        suggestionsList.innerHTML = '';

        if (foundKeywords.length === 0) {
            suggestionsList.innerHTML = '<li>No suggestions yet. Start writing more content!</li>';
            return;
        }

        const uniqueSuggestions = new Set();

        foundKeywords.forEach(keyword => {
            titlePatterns[keyword].forEach(pattern => {
                const suggestedTitle = pattern.replace("[Topic]", "your topic");
                uniqueSuggestions.add(suggestedTitle);
            });
        });

        uniqueSuggestions.forEach(title => {
            const listItem = document.createElement('li');
            listItem.textContent = title;
            listItem.addEventListener('click', function () {
                titleInput.value = this.textContent;
            });
            suggestionsList.appendChild(listItem);
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const voiceBtn = document.getElementById("voiceToTextBtn");
    const voiceStatus = document.getElementById("voiceStatus");
    const contentEditor = document.querySelector("#quillEditor");
    const quill = Quill.find(contentEditor); // Find existing Quill instance

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        voiceBtn.textContent = "🎙️ Voice Input Not Supported";
        voiceBtn.disabled = true;
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = "en-US";
    recognition.interimResults = true;
    recognition.continuous = true;

    let isRecording = false;

    // Start / Stop Toggle
    voiceBtn.addEventListener("click", function () {
        if (!isRecording) {
            recognition.start();
            isRecording = true;
            voiceBtn.classList.add("active");
            voiceBtn.textContent = "🛑 Stop Voice Input";
            voiceStatus.style.display = "inline";
        } else {
            recognition.stop();
            isRecording = false;
            voiceBtn.classList.remove("active");
            voiceBtn.textContent = "🎙️ Voice Input";
            voiceStatus.style.display = "none";
        }
    });

    // Process voice input and insert into Quill editor
    recognition.addEventListener("result", function (event) {
        let finalTranscript = "";

        for (let i = event.resultIndex; i < event.results.length; i++) {
            let transcript = event.results[i][0].transcript.toLowerCase().trim();

            // Punctuation replacements
            transcript = transcript.replace(/\bcomma\b/g, ",");
            transcript = transcript.replace(/\bperiod\b|\bfull stop\b/g, ".");
            transcript = transcript.replace(/\bquestion mark\b/g, "?");
            transcript = transcript.replace(/\bexclamation mark\b/g, "!");
            transcript = transcript.replace(/\bcolon\b/g, ":");
            transcript = transcript.replace(/\bsemicolon\b/g, ";");
            transcript = transcript.replace(/\bopen quote\b/g, ' "');
            transcript = transcript.replace(/\bclose quote\b/g, '" ');
            transcript = transcript.replace(/\bopen bracket\b/g, "(");
            transcript = transcript.replace(/\bclose bracket\b/g, ")");
            transcript = transcript.replace(/\bdash\b|\bhyphen\b/g, "-");
            transcript = transcript.replace(/\bnew line\b/g, "\n");

            // Special Functional Commands
            if (transcript.includes("delete")) {
                deleteLastWord();
                continue; // Skip appending "delete"
            }

            if (transcript.includes("clear all")) {
                quill.setText("");
                continue;
            }

            if (transcript.includes("undo")) {
                quill.history.undo();
                continue;
            }

            if (transcript.includes("bold this")) {
                makeLastWordBold();
                continue;
            }

            if (transcript.includes("italic this")) {
                makeLastWordItalic();
                continue;
            }

            // Only append final transcripts into the editor
            if (event.results[i].isFinal) {
                finalTranscript += transcript + " ";
            }
        }

        if (finalTranscript) {
            const currentPosition = quill.getSelection(true)?.index || quill.getLength();
            quill.insertText(currentPosition, finalTranscript);
            quill.setSelection(currentPosition + finalTranscript.length);
        }
    });

    recognition.addEventListener("end", function () {
        if (isRecording) {
            recognition.start(); // Restart if user is still recording
        } else {
            voiceBtn.classList.remove("active");
            voiceBtn.textContent = "🎙️ Voice Input";
            voiceStatus.style.display = "none";
        }
    });

    recognition.addEventListener("error", function (event) {
        console.error("Speech recognition error:", event.error);
        recognition.stop();
        isRecording = false;
        voiceBtn.classList.remove("active");
        voiceBtn.textContent = "🎙️ Voice Input";
        voiceStatus.style.display = "none";
    });

    // Helper Functions
    function deleteLastWord() {
        const currentText = quill.getText();
        const trimmedText = currentText.trimEnd();
        const lastSpaceIndex = trimmedText.lastIndexOf(" ");
        const newText = lastSpaceIndex === -1 ? "" : trimmedText.substring(0, lastSpaceIndex);

        quill.setText(newText + " ");
        quill.setSelection(newText.length + 1); // Move cursor to end
    }

    function makeLastWordBold() {
        const range = quill.getSelection(true);
        const index = range.index - 1; // Go back by one character to catch the last word
        quill.formatText(index, 1, "bold", true);
    }

    function makeLastWordItalic() {
        const range = quill.getSelection(true);
        const index = range.index - 1;
        quill.formatText(index, 1, "italic", true);
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const listenBtn = document.getElementById("listenPreviewBtn");
    const pauseBtn = document.getElementById("pausePreviewBtn");
    const stopBtn = document.getElementById("stopPreviewBtn");

    const previewTitle = document.getElementById("previewTitle");
    const previewContent = document.getElementById("previewContent");

    let speechUtterance;
    let isPaused = false;

    // Start Reading
    listenBtn.addEventListener("click", function () {
        // Stop any ongoing speech before starting new
        speechSynthesis.cancel();

        const titleText = previewTitle.innerText.trim();
        const contentText = previewContent.innerText.trim();

        const fullText = `Title: ${titleText}. Content: ${contentText}`;

        if (!titleText && !contentText) {
            alert("Nothing to read. Please enter a title or content.");
            return;
        }

        speechUtterance = new SpeechSynthesisUtterance(fullText);
        speechUtterance.lang = "en-US";
        speechUtterance.rate = 1;
        speechUtterance.pitch = 1;

        speechSynthesis.speak(speechUtterance);

        // Show Pause/Stop buttons when speech starts
        pauseBtn.style.display = "inline-block";
        stopBtn.style.display = "inline-block";

        // Reset pause state
        isPaused = false;
    });

    // Pause Reading
    pauseBtn.addEventListener("click", function () {
        if (!isPaused) {
            speechSynthesis.pause();
            pauseBtn.textContent = "▶️ Resume";
            isPaused = true;
        } else {
            speechSynthesis.resume();
            pauseBtn.textContent = "⏸️ Pause";
            isPaused = false;
        }
    });

    // Stop Reading
    stopBtn.addEventListener("click", function () {
        speechSynthesis.cancel();

        // Reset button visibility
        pauseBtn.style.display = "none";
        stopBtn.style.display = "none";
        pauseBtn.textContent = "⏸️ Pause";
        isPaused = false;
    });

    // Hide pause/stop when speech ends naturally
    speechUtterance?.addEventListener("end", () => {
        pauseBtn.style.display = "none";
        stopBtn.style.display = "none";
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const quill = Quill.find(document.querySelector("#quillEditor")); // Your existing Quill instance
    const hashtagSuggestionsContainer = document.getElementById('hashtagSuggestions');
    const hashtagsInput = document.getElementById('hashtags');

    // Hashtag Dictionary (Expand later)
    const hashtagDictionary = {
        "success": ["#Success", "#Winning", "#Achieve"],
        "growth": ["#GrowthMindset", "#PersonalDevelopment", "#LevelUp"],
        "motivation": ["#Motivation", "#StayInspired", "#KeepPushing"],
        "ideas": ["#CreativeIdeas", "#Innovation", "#ThinkBig"],
        "learning": ["#KeepLearning", "#LifelongLearning", "#SkillUp"],
        "business": ["#BusinessTips", "#Entrepreneurship", "#StartupLife"],
        "career": ["#CareerGrowth", "#WorkSmart", "#ProfessionalDevelopment"],
        "mindset": ["#PositiveMindset", "#MindsetMatters", "#WinningMindset"],
        "success story": ["#SuccessStory", "#Inspiration", "#DreamBig"],
        "finance": ["#MoneyMatters", "#FinanceTips", "#WealthBuilding"]
    };

    // Generate suggestions based on content
    quill.on('text-change', function () {
        const contentText = quill.getText().toLowerCase();

        const matchingHashtags = new Set();

        for (const keyword in hashtagDictionary) {
            if (contentText.includes(keyword)) {
                hashtagDictionary[keyword].forEach(tag => matchingHashtags.add(tag));
            }
        }

        // Display the suggestions
        hashtagSuggestionsContainer.innerHTML = '';

        if (matchingHashtags.size === 0) {
            hashtagSuggestionsContainer.innerHTML = '<p style="color: gray">No hashtag suggestions yet.</p>';
            return;
        }

        matchingHashtags.forEach(tag => {
            const tagButton = document.createElement('span');
            tagButton.classList.add('hashtag-suggestion');
            tagButton.textContent = tag;

            tagButton.addEventListener('click', function () {
                // Append tag to hashtags input
                const currentTags = hashtagsInput.value.trim();
                if (!currentTags.includes(tag)) {
                    hashtagsInput.value = currentTags ? `${currentTags} ${tag}` : tag;
                }
            });

            hashtagSuggestionsContainer.appendChild(tagButton);
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const readingTimeDisplay = document.getElementById('readingTimeDisplay');
    const quill = Quill.find(document.querySelector("#quillEditor"));

    quill.on('text-change', function () {
        const contentText = quill.getText().trim();
        const wordCount = contentText.split(/\s+/).filter(word => word.length > 0).length;
        const readingTime = Math.ceil(wordCount / 200); // 200 WPM

        readingTimeDisplay.innerHTML = `📖 Estimated Reading Time: <strong>${readingTime || 1} min</strong>`;
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const seoTipsList = document.getElementById('seoTipsList');
    const quill = Quill.find(document.querySelector("#quillEditor"));
    const titleInput = document.getElementById("title");

    quill.on('text-change', updateSeoTips);
    titleInput.addEventListener('input', updateSeoTips);

    function updateSeoTips() {
        const contentText = quill.getText().trim();
        const titleText = titleInput.value.trim();

        const wordCount = contentText.split(/\s+/).filter(word => word.length > 0).length;
        const titleLength = titleText.length;
        const contentKeywords = contentText.toLowerCase().split(/\s+/);
        const titleKeywords = titleText.toLowerCase().split(/\s+/);

        const tips = [];

        // 1. Title Length
        if (titleLength === 0) {
            tips.push({ message: "Your post needs a title.", type: "bad" });
        } else if (titleLength < 40) {
            tips.push({ message: "Title is too short (recommended: 50-60 characters).", type: "warning" });
        } else if (titleLength > 70) {
            tips.push({ message: "Title is too long (recommended: 50-60 characters).", type: "warning" });
        } else {
            tips.push({ message: "Good title length!", type: "good" });
        }

        // 2. Title Contains Content Keyword
        const hasKeywordInTitle = contentKeywords.some(word => titleKeywords.includes(word));
        if (!hasKeywordInTitle) {
            tips.push({ message: "Consider including a key topic from your content in the title.", type: "warning" });
        } else {
            tips.push({ message: "Your title seems to reflect your content.", type: "good" });
        }

        // 3. Content Length
        if (wordCount < 300) {
            tips.push({ message: `Post is short (${wordCount} words). Consider adding more for better SEO.`, type: "warning" });
        } else {
            tips.push({ message: `Great post length! (${wordCount} words).`, type: "good" });
        }

        // 4. Headings (Optional)
        const contentHtml = quill.root.innerHTML;
        if (!contentHtml.includes("<h2") && !contentHtml.includes("<h3")) {
            tips.push({ message: "Consider using headings (e.g., <h2>) to structure your post.", type: "warning" });
        } else {
            tips.push({ message: "Good use of headings!", type: "good" });
        }

        // 5. Links Presence (Optional)
        if (!contentHtml.includes("<a href")) {
            tips.push({ message: "Consider adding useful internal or external links.", type: "warning" });
        } else {
            tips.push({ message: "Nice! You’ve added links.", type: "good" });
        }

        // Display Tips
        seoTipsList.innerHTML = "";
        tips.forEach(tip => {
            const li = document.createElement("li");
            li.textContent = tip.message;
            li.classList.add(`seo-${tip.type}`);
            seoTipsList.appendChild(li);
        });
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const counterDisplay = document.getElementById('contentCounter');
    const quill = Quill.find(document.querySelector("#quillEditor"));

    quill.on('text-change', function () {
        const contentText = quill.getText().trim();

        const characterCount = contentText.length;
        const wordCount = contentText.split(/\s+/).filter(word => word.length > 0).length;
        const paragraphCount = quill.root.innerHTML.split(/<\/p>|<br>/).filter(p => p.trim() !== "").length;

        counterDisplay.innerHTML = `📝 Characters: ${characterCount} | Words: ${wordCount} | Paragraphs: ${paragraphCount}`;
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const analysisList = document.getElementById('contentAnalysisList');
    const quill = Quill.find(document.querySelector("#quillEditor"));

    quill.on('text-change', analyzeContent);

    function analyzeContent() {
        const contentText = quill.getText().trim();

        if (contentText === "") {
            analysisList.innerHTML = "<li>No content to analyze yet.</li>";
            return;
        }

        const sentences = contentText.split(/[.!?]/).filter(s => s.trim().length > 0);
        const words = contentText.split(/\s+/).filter(word => word.length > 0);
        const syllables = words.reduce((total, word) => total + countSyllables(word), 0);

        const sentenceCount = sentences.length;
        const wordCount = words.length;

        // Avoid division by zero if no sentences
        const avgWordsPerSentence = sentenceCount > 0 ? wordCount / sentenceCount : 0;

        // Flesch Reading Ease Formula
        const fleschScore = 206.835 - (1.015 * avgWordsPerSentence) - (84.6 * (syllables / wordCount));
        let readabilityFeedback = getReadabilityLevel(fleschScore);

        // Sentence Length Feedback
        const longSentenceCount = sentences.filter(s => s.split(/\s+/).length > 25).length;

        // Passive Voice Detector (Basic - looks for 'was', 'were', 'is being')
        const passivePattern = /\b(was|were|is being|has been|have been|had been)\b/i;
        const passiveVoiceCount = sentences.filter(s => passivePattern.test(s)).length;

        // Feedback collection
        const feedback = [];

        feedback.push({ message: `Readability: ${readabilityFeedback} (Flesch Score: ${fleschScore.toFixed(1)})`, type: readabilityFeedback === "Easy" ? "good" : "warning" });

        if (longSentenceCount > 0) {
            feedback.push({ message: `${longSentenceCount} long sentence(s). Consider breaking them up.`, type: "warning" });
        } else {
            feedback.push({ message: "Sentence length looks good!", type: "good" });
        }

        if (passiveVoiceCount > 0) {
            feedback.push({ message: `Detected passive voice in ${passiveVoiceCount} sentence(s). Consider using active voice.`, type: "warning" });
        } else {
            feedback.push({ message: "Good use of active voice!", type: "good" });
        }

        // Display Feedback
        analysisList.innerHTML = "";
        feedback.forEach(tip => {
            const li = document.createElement("li");
            li.textContent = tip.message;
            li.classList.add(`analysis-${tip.type}`);
            analysisList.appendChild(li);
        });
    }

    // Helper Functions
    function countSyllables(word) {
        word = word.toLowerCase();
        if (word.length <= 3) return 1;
        const syllableMatches = word.match(/[aeiouy]{1,2}/g);
        const syllableCount = syllableMatches ? syllableMatches.length : 0;
        return syllableCount - (word.endsWith('e') ? 1 : 0);
    }

    function getReadabilityLevel(score) {
        if (score >= 70) return "Easy";
        if (score >= 50) return "Medium";
        return "Difficult";
    }
});
    </script>
</body>
</html>

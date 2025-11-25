<?php

session_start();
require 'includes/db.php';
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



require 'includes/db.php';


if (isset($_GET['id'])) {
    $post_id = $_GET['id'];

    // Fetch the post details
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            die("Post not found or you do not have permission to edit it.");
        }
    } catch (PDOException $e) {
        die("Error fetching post: " . $e->getMessage());
    }
}

// Get the post author's followers
$stmt = $pdo->prepare("SELECT follower_id FROM follows WHERE following_id = ?");
$stmt->execute([$user_id]);
$followers = $stmt->fetchAll(PDO::FETCH_COLUMN);


// Fetch categories
$categoryStmt = $pdo->query("SELECT * FROM categories");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all tags
$tagStmt = $pdo->query("SELECT * FROM tags");
$tags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch post's existing tags (for multi-select)
$postTagStmt = $pdo->prepare("SELECT tag_id FROM post_tags WHERE post_id = ?");
$postTagStmt->execute([$post['id']]);
$postTags = $postTagStmt->fetchAll(PDO::FETCH_COLUMN); // Fetches an array of tag IDs

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category_id'];
    $hashtags = trim($_POST['hashtags']);
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];    
    $audience = $_POST['audience'];
    $currentMedia = !empty($post['media']) ? json_decode($post['media'], true) : [];

    $newMedia = [];
    if (!empty($_FILES['media']['name'][0])) {
        foreach ($_FILES['media']['name'] as $key => $fileName) {
            $fileTmpName = $_FILES['media']['tmp_name'][$key];
            $fileSize = $_FILES['media']['size'][$key];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif'];
            $allowedVideoExt = ['mp4', 'mov', 'avi', 'mkv'];

            if (in_array($fileExt, array_merge($allowedImageExt, $allowedVideoExt)) && $fileSize <= 1024 * 1024 * 1024) { // 20MB limit
                $uniqueName = uniqid('media_', true) . '.' . $fileExt;
                $targetPath = 'uploads/' . $uniqueName;

                if (move_uploaded_file($fileTmpName, $targetPath)) {
                    $newMedia[] = $uniqueName;
                }
            }
        }
    }

   

    // Combine existing and new media
    $allMedia = array_merge($currentMedia, $newMedia);
    $mediaJson = json_encode($allMedia);

    if (!empty($title) && !empty($content)) {
        try {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, media = ?, audience = ?, last_edited_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $mediaJson, $audience, $post_id, $_SESSION['user_id']]);

            // After updating the `posts` table...
            $stmt = $pdo->prepare("INSERT INTO post_versions (post_id, title, content, category_id, hashtags, tags) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $post_id,
                $title,
                $content,
                $category_id,
                $hashtags,
                json_encode($tags)
            ]);
            // Update post's category and hashtags
            $stmt = $pdo->prepare("UPDATE posts SET category_id = ?, hashtags = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$category_id, $hashtags, $post_id, $_SESSION['user_id']]);

            // Clear existing tags and reinsert selected ones
            $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
            $stmt->execute([$post_id]);

            if (!empty($tags)) {
                foreach ($tags as $tag_id) {
                    $stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$post_id, $tag_id]);
                }
            }

            if (!empty($followers)) {
                $notification_message = "updated their post: <strong>$title</strong>";
                $link = "view_post2.php?id=$post_id";
            
                foreach ($followers as $follower_id) {
                    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
                    $notifStmt->execute([$follower_id, $user_id, $notification_message, $link]);
                }
            }

            $_SESSION['success'] = "Post updated successfully!";
            header("Location: my_posts.php#$post_id");
            exit;
        } catch (PDOException $e) {
            die("Error updating post: " . $e->getMessage());
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
    

/* Form Container */
form {
    width: 100%;
    background: rgba(0, 0, 0, 0.3);
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

 
form .button {
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
.media-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
}

.media-item {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 200px;
    max-height: 200px;
    object-fit: cover;
}

.media-item img, .media-item video {
    width: 100%;
    height: auto;
}
.media-item-wrapper{
    position: relative;
}
.bottom-right {
    position: absolute;
    bottom: 8px;
    right: 2px;
}
.ql-toolbar {
    background-color: #f3f3f3;
    border-radius: 8px 8px 0 0;
}

.ql-container {
    border-radius: 0 0 8px 8px;
    background-color: #fff;
    color: black;
}

.ql-editor {
    min-height: 150px;
    font-size: 16px;
    line-height: 1.6;
}
.audience-section {
    margin-top: 10px;
}

#audience {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    background-color: #fff;
}
.post-preview {
    margin-top: 20px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    color: black;
}

.preview-card h2 {
    color: #4B0082;
}

.slideshow-container {
    position: relative;
    max-width: 100%;
    margin-top: 10px;
    border-radius: 8px;
    overflow: hidden;
    background-color: #f3f3f3;
}

.slides-wrapper img,
.slides-wrapper video {
    width: 100%;
    display: none;
    border-radius: 8px;
}

.slides-wrapper .active {
    display: block;
}

.slideshow-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    border: none;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 50%;
    font-size: 18px;
    z-index: 10;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.slideshow-nav:hover {
    opacity: 1;
}

.slideshow-nav.left {
    left: 10px;
}

.slideshow-nav.right {
    right: 10px;
}
.version-history-container {
    margin-top: 15px;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 8px;
    color: black;
}

.version-history-container ul {
    list-style-type: none;
    padding-left: 0;
}

.version-history-container li {
    padding: 8px;
    background-color: #fff;
    margin-bottom: 5px;
    border-radius: 6px;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: background-color 0.3s;
    color: gray;

}

.version-history-container li:hover {
    background-color: #f0f0f0;
}
.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tags-container label {
    background-color: #f3f3f3;
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    color: blue;
}

.tags-container input[type="checkbox"] {
    margin-right: 8px;
}
.hashtag-suggestions-container {
    margin-top: 8px;
    background-color: #f9f9f9;
    padding: 10px;
    border-radius: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    border: 1px solid #ddd;
}

.hashtag-suggestion {
    background-color: #4B0082;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.hashtag-suggestion:hover {
    background-color: #6A0DAD;
}
#listenPreviewBtn,
#pausePreviewBtn,
#stopPreviewBtn {
    background-color: #FFD700;
    color: #4B0082;
    border: none;
    padding: 8px 16px;
    margin-top: 10px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease;
}

#listenPreviewBtn:hover,
#pausePreviewBtn:hover,
#stopPreviewBtn:hover {
    background-color: #FFB800;
}
</style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">
       <!-- Sidebar -->
       <?php if ($_SESSION['role'] === 'User'): ?>
    <aside style="height: 100%;  overflow-y: scroll" class="sidebar">
            <img class="animate-on-scroll" src="<?php echo $_SESSION['image']; ?>" width="100px" height="100px" style="border-radius: 50%;">
            <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pen"></i>Create Post</a></li>
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
            <?php endif; ?><!-- Main Content -->
        <main class="content">
    
        <ul class="nav">
              <?php if ($_SESSION['role'] === 'User'): ?>
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
            <?php elseif ($_SESSION['role'] === 'Admin'): ?>
              <li class="animate-on-scroll icon"><a href="admin_dashboard.php"><i class="fas fa-home"></i></a></li>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <?php if ($report_count > 0): ?><span class="count-badge"><?= $report_count ?></span><?php endif; ?></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
            <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
        <h2>Edit Post</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="edit_post.php?id=<?php echo $post_id; ?>" method="POST" enctype="multipart/form-data">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

            <label for="content">Content</label>
            <div id="quillEditor" style="height: 200px;"></div>
            <textarea style="display: none" id="content" name="content" rows="10"><?php echo htmlspecialchars($post['content']); ?></textarea>

            <label for="category">Category</label>
            <select id="category" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($post['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Tags</label>
            <div class="tags-container">
                <?php foreach ($tags as $tag): ?>
                    <label>
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" 
                            <?php echo in_array($tag['id'], $postTags) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="hashtag-section">
            <label for="hashtags">Hashtags</label>
            <input type="text" id="hashtags" name="hashtags" value="<?php echo htmlspecialchars($post['hashtags']); ?>" placeholder="#Motivation #Success">
            <div id="hashtagSuggestions" class="hashtag-suggestions-container"></div>
            </div>

            <input type="hidden" id="post-id" name="post_id" value="<?= htmlspecialchars($post['id']); ?>">


            <label for="media">Upload New Media (Images or Videos):</label>
<input type="file" id="media" name="media[]" accept="image/*,video/*" multiple>

<?php if (!empty($post['media'])): ?>
    <p>Current Media:</p>
    <div class="media-container">
        <?php 
        $mediaFiles = json_decode($post['media'], true);
        foreach ($mediaFiles as $media): 
            $extension = strtolower(pathinfo($media, PATHINFO_EXTENSION));
        ?>
            <div class="media-item-wrapper">
                <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <img src="uploads/<?= htmlspecialchars($media); ?>" alt="Current Post Image" class="media-item">
                <?php elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])): ?>
                    <video controls class="media-item">
                        <source src="uploads/<?= htmlspecialchars($media); ?>" type="video/<?= $extension; ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
                <button class="bottom-right delete-media-btn delete-btn btn" data-media-name="<?= htmlspecialchars($media); ?>"><i class="fas fa-trash"></i></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

            <div class="audience-section">
                <label for="audience">Who can view this post?</label>
                <select id="audience" name="audience" required>
                    <option value="public" <?php if($post['audience'] === 'public') echo 'selected'; ?>>🌍 Public - Everyone</option>
                    <option value="followers" <?php if($post['audience'] === 'followers') echo 'selected'; ?>>👥 Followers Only</option>
                    <option value="private" <?php if($post['audience'] === 'private') echo 'selected'; ?>>🔒 Private - Only Me</option>
                </select>
            </div><br>

            <p id="draftStatus" style="color: green; font-size: 12px; display: none;">Draft saved!</p>
            <button class="button" type="submit">Update Post</button>
        </form>

        <div class="post-preview">
            <h4>🔎 Live Post Preview</h4>
            <div class="preview-card">
            <div id="previewMediaContainer" class="slideshow-container" style="display: none;">
                    <button type="button" class="slideshow-nav left" id="prevSlideBtn">❮</button>
                    <div id="previewSlidesWrapper" class="slides-wrapper"></div>
                    <button type="button" class="slideshow-nav right" id="nextSlideBtn">❯</button>
                </div>
                <h2 id="previewTitle">Post Title Preview</h2>
                <div style="color: gray; text-align: center" id="previewContent">Post content will appear here as you edit...</div>
                <button type="button" id="listenPreviewBtn">🔊 Listen to Preview</button>
                <button type="button" id="pausePreviewBtn" style="display: none;">⏸️ Pause</button>
                <button type="button" id="stopPreviewBtn" style="display: none;">⏹️ Stop</button>
            </div>
        </div>

        <div class="version-history-container">
            <h4>📜 Version History</h4>
            <ul id="versionHistoryList">
                <li>Loading versions...</li>
            </ul>
        </div>

        <ul class="nav">
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <?php if ($_SESSION['role'] === 'User'): ?>
                <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
            <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                <li class="animate-on-scroll icon"><a href="admin_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_users.php"><i class="fas fa-user-cog"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_posts.php"><i class="fas fa-file-alt"></i></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_comments.php"><i class="fas fa-comments"></i></a></li>
                    <li  class="animate-on-scroll icon"><a href="admin_reports.php"><i class="fas fa-chart-line"></i></a></li>
                    <li class="animate-on-scroll icon"><a href="admin_filters.php"><i class="fas fa-folder-open"></i></a></li>
            <?php endif; ?>
            <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#notifications-container"><i class="fas fa-bell"></i></a></li>
              <li class="animate-on-scroll icon"><a href="dashboard.php#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
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
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
    const deleteButtons = document.querySelectorAll('.delete-media-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const mediaName = this.dataset.mediaName;
            const postId = document.getElementById('post-id').value; // Hidden input with post ID

            if (confirm('Are you sure you want to delete this media?')) {
                fetch('delete_media.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `post_id=${postId}&media_name=${encodeURIComponent(mediaName)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            // Remove the media item from the DOM
                            this.parentElement.remove();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });
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
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Edit your post...',
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

    // Load existing content from PHP into the editor
    const postContent = `<?php echo addslashes($post['content']); ?>`; // Handles quotes properly
    quill.root.innerHTML = postContent;

    // Sync content to hidden input on change
    quill.on('text-change', () => {
        hiddenContent.value = quill.root.innerHTML;
    });

    // Sync on form submit (safety)
    document.getElementById('editPostForm').addEventListener('submit', function () {
        hiddenContent.value = quill.root.innerHTML;
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const titleInput = document.getElementById('title'); // Assuming your title input is still named this
    const previewTitle = document.getElementById('previewTitle');
    const previewContent = document.getElementById('previewContent');
    const mediaInput = document.getElementById('media'); // Assuming input type file for media
    const quill = Quill.find(document.querySelector("#quillEditor"));

    // Title Live Update
    titleInput.addEventListener('input', () => {
        previewTitle.textContent = titleInput.value || 'Post Title Preview';
    });

    // Content Live Update
    quill.on('text-change', () => {
        previewContent.innerHTML = quill.root.innerHTML || 'Post content will appear here as you edit...';
    });

});
document.addEventListener("DOMContentLoaded", function () {
    const mediaInput = document.getElementById('media');
    const previewMediaContainer = document.getElementById('previewMediaContainer');
    const slidesWrapper = document.getElementById('previewSlidesWrapper');
    const prevSlideBtn = document.getElementById('prevSlideBtn');
    const nextSlideBtn = document.getElementById('nextSlideBtn');
    const deleteMediaButtons = document.querySelectorAll('.delete-media-btn');

    let mediaFiles = [];
    let slideIndex = 0;

    // Load Existing Media from the server into the preview
    const existingMedia = <?php echo json_encode(json_decode($post['media'], true) ?: []); ?>;
    existingMedia.forEach(url => {
        mediaFiles.push({ type: detectMediaType(url), url, isNew: false });
    });

    updateSlideshow();

    // When new files are selected
    mediaInput.addEventListener('change', function () {
        Array.from(mediaInput.files).forEach(file => {
            const url = URL.createObjectURL(file);
            const type = file.type.startsWith('image') ? 'image' : (file.type.startsWith('video') ? 'video' : 'file');
            mediaFiles.push({ type, url, isNew: true });
        });

        updateSlideshow();
    });

    // Delete Existing Media - Update Slideshow in Real-Time
    deleteMediaButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const mediaName = this.dataset.mediaName;

            // Remove from mediaFiles array
            mediaFiles = mediaFiles.filter(media => !(media.url.includes(mediaName) && !media.isNew));

            // Update slideshow
            updateSlideshow();
        });
    });

    function detectMediaType(url) {
        const extension = url.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
            return 'image';
        } else if (['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'].includes(extension)) {
            return 'video';
        }
        return 'file';
    }

    function updateSlideshow() {
        slidesWrapper.innerHTML = '';

        if (mediaFiles.length === 0) {
            previewMediaContainer.style.display = 'none';
            return;
        }

        previewMediaContainer.style.display = 'block';

        mediaFiles.forEach((media, index) => {
            if (media.type === 'image') {
                const img = document.createElement('img');
                img.src = media.isNew ? media.url : 'uploads/' + media.url;
                img.classList.add('slide');
                if (index === 0) img.classList.add('active');
                slidesWrapper.appendChild(img);
            } else if (media.type === 'video') {
                const video = document.createElement('video');
                video.src = media.isNew ? media.url : 'uploads/' + media.url;
                video.controls = true;
                video.classList.add('slide');
                if (index === 0) video.classList.add('active');
                slidesWrapper.appendChild(video);
            }
        });

        slideIndex = 0;
        showSlide(slideIndex);
    }

    function showSlide(index) {
        const slides = slidesWrapper.getElementsByClassName('slide');
        if (slides.length === 0) return;

        Array.from(slides).forEach(slide => slide.classList.remove('active'));

        // Handle wrap-around navigation
        if (index >= slides.length) slideIndex = 0;
        if (index < 0) slideIndex = slides.length - 1;

        slides[slideIndex].classList.add('active');
    }

    // Next/Previous Controls
    nextSlideBtn.addEventListener('click', function () {
        slideIndex++;
        showSlide(slideIndex);
    });

    prevSlideBtn.addEventListener('click', function () {
        slideIndex--;
        showSlide(slideIndex);
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const titleInput = document.getElementById("title");
    const mediaInput = document.getElementById("media");
    const categorySelect = document.getElementById("category");
    const hashtagsInput = document.getElementById("hashtags");
    const tagsCheckboxes = document.querySelectorAll("input[name='tags[]']");
    const quill = Quill.find(document.querySelector("#quillEditor"));
    const clearDraftBtn = document.createElement("button");

    const postId = "<?php echo $post['id']; ?>";
    const draftKey = `edit_draft_${postId}`;

    loadDraft();

    setInterval(saveDraft, 5000);

    clearDraftBtn.textContent = "🗑️ Clear Draft";
    clearDraftBtn.style = "margin-top: 10px; background-color: #FF6347; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer;";
    document.querySelector(".post-preview").appendChild(clearDraftBtn);

    clearDraftBtn.addEventListener("click", function () {
        localStorage.removeItem(draftKey);
        titleInput.value = "";
        quill.root.innerHTML = "";
        categorySelect.value = "<?php echo $post['category_id']; ?>";
        hashtagsInput.value = "<?php echo htmlspecialchars($post['hashtags']); ?>";
        tagsCheckboxes.forEach(checkbox => checkbox.checked = <?php echo json_encode($postTags); ?>.includes(checkbox.value));
        alert("Draft cleared!");
    });

    function saveDraft() {
        const selectedTags = Array.from(tagsCheckboxes)
            .filter(tag => tag.checked)
            .map(tag => tag.value);

        const draftData = {
            title: titleInput.value,
            content: quill.root.innerHTML,
            category: categorySelect.value,
            hashtags: hashtagsInput.value,
            tags: selectedTags
        };

        localStorage.setItem(draftKey, JSON.stringify(draftData));
    }

    function loadDraft() {
        const savedDraft = localStorage.getItem(draftKey);
        if (savedDraft) {
            const draftData = JSON.parse(savedDraft);
            titleInput.value = draftData.title;
            quill.root.innerHTML = draftData.content;
            categorySelect.value = draftData.category;
            hashtagsInput.value = draftData.hashtags;

            tagsCheckboxes.forEach(checkbox => {
                checkbox.checked = draftData.tags.includes(checkbox.value);
            });
        }
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const versionHistoryList = document.getElementById('versionHistoryList');
    const quill = Quill.find(document.querySelector("#quillEditor"));
    const titleInput = document.getElementById("title");
    const categorySelect = document.getElementById("category");
    const hashtagsInput = document.getElementById("hashtags");
    const tagsCheckboxes = document.querySelectorAll("input[name='tags[]']");

    const postId = "<?php echo $post['id']; ?>";

    function loadVersionHistory() {
        fetch(`fetch_versions.php?post_id=${postId}`)
            .then(response => response.json())
            .then(data => {
                versionHistoryList.innerHTML = '';

                if (data.length === 0) {
                    versionHistoryList.innerHTML = '<li>No versions available.</li>';
                    return;
                }

                data.forEach(version => {
                    const li = document.createElement('li');
                    li.textContent = `Version from ${new Date(version.created_at).toLocaleString()}`;
                    li.addEventListener('click', function () {
                        if (confirm('Restore this version? Unsaved changes will be lost.')) {
                            titleInput.value = version.title;
                            quill.root.innerHTML = version.content;
                            categorySelect.value = version.category_id;
                            hashtagsInput.value = version.hashtags;

                            const versionTags = version.tags ? JSON.parse(version.tags) : [];
                            tagsCheckboxes.forEach(checkbox => {
                                checkbox.checked = versionTags.includes(parseInt(checkbox.value));
                            });
                        }
                    });

                    versionHistoryList.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Error loading versions:', error);
                versionHistoryList.innerHTML = '<li>Failed to load version history.</li>';
            });
    }

    loadVersionHistory();
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
    </script>
</body>
</html>
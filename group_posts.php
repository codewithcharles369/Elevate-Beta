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

// Validate group ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Group ID is required.");
}

$groupId = intval($_GET['id']);


// Fetch group details
$stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

// Check if the user is a member
$membershipStmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$membershipStmt->execute([$groupId, $userId]);
$membership = $membershipStmt->fetch();
$isMember = $membership ? true : false;
$userRole = $membership['role'] ?? null;

$postsPerPage = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $postsPerPage;

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$searchCondition = '';
$params = [$groupId];

if ($search) {
    $searchCondition = "AND (gp.title LIKE ? OR gp.content LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$queryBase = "
    SELECT gp.*, u.name AS author_name,
        (SELECT COUNT(*) FROM group_post_likes WHERE post_id = gp.id) AS like_count,
        (SELECT COUNT(*) FROM group_post_bookmarks WHERE post_id = gp.id) AS bookmark_count,
        (SELECT COUNT(*) FROM group_post_likes WHERE post_id = gp.id AND user_id = $userId) AS user_liked,
        (SELECT COUNT(*) FROM group_post_bookmarks WHERE post_id = gp.id AND user_id = $userId) AS user_bookmarked,
        (SELECT COUNT(*) FROM group_post_comments WHERE post_id = gp.id) AS comment_count
    FROM group_posts gp
    JOIN users u ON gp.user_id = u.id
    WHERE gp.group_id = ?
    $searchCondition
";

if ($sort === 'popular') {
    $queryBase .= " ORDER BY like_count DESC, gp.created_at DESC";
} else { // latest
    $queryBase .= " ORDER BY gp.created_at DESC";
}

$queryBase .= " LIMIT $postsPerPage OFFSET $offset"; // Fixed: Directly in the query string

$stmt = $pdo->prepare($queryBase);
$stmt->execute($params);
$groupPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalQuery = "
    SELECT COUNT(*) FROM group_posts gp
    JOIN users u ON gp.user_id = u.id
    WHERE gp.group_id = ?
    $searchCondition
";

$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute(array_slice($params, 0, count($params) - 0)); // Removing LIMIT & OFFSET params
$totalPosts = $totalStmt->fetchColumn();

$totalPages = ceil($totalPosts / $postsPerPage);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title><?= htmlspecialchars($group['name']) ?> - Posts</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
        }
        .group-posts-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 16px;
}

.group-post-card {
    background-color: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: calc(33.333% - 14px);
    display: flex;
    flex-direction: column;
    position: relative;
    cursor: pointer;
}

.group-post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.group-post-media-container {
    height: 160px;
    background-color: #f3f3f3;
    position: relative;
    overflow: hidden;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.group-post-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 0.8s ease;
}

.group-post-slide.active {
    opacity: 1;
}

.group-post-media {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.group-post-placeholder {
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f0f0f0;
    color: #999;
    font-style: italic;
}

.group-post-content {
    padding: 16px;
    flex-grow: 1;
}

.group-post-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #6a0dad;
    margin-bottom: 8px;
}

.group-post-excerpt {
    font-size: 1rem;
    color: #444;
    margin-bottom: 12px;
}

.group-post-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #777;
}

.group-post-meta a {
    color: #6a0dad;
    text-decoration: none;
    font-weight: bold;
}

.group-post-engagement {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    font-size: 0.9rem;
    color: #555;
}

.group-post-engagement i {
    margin-right: 5px;
    color: #6a0dad;
}

.group-post-actions {
    background-color: #f9f9f9;
    padding: 12px;
    text-align: center;
    border-bottom-left-radius: 14px;
    border-bottom-right-radius: 14px;
}

.group-post-btn {
    background-color: #6a0dad;
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.group-post-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .group-post-card {
        width: 100%;
    }
}

.group-post-sorting {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.group-post-sorting label {
    font-size: 1rem;
    color: #333;
}

.group-post-sorting select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.group-post-sorting select:focus {
    outline: none;
    border-color: #6a0dad;
}

.pagination {
    margin-top: 20px;
    text-align: center;
}

.pagination a {
    padding: 8px 12px;
    margin: 0 4px 4px 0;
    background-color: #f3f3f3;
    color: #6a0dad;
    text-decoration: none;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.pagination a:hover {
    background-color: #e4e4e4;
}

.pagination a.active {
    background-color: #6a0dad;
    color: white;
    font-weight: bold;
}

.group-post-search {
    margin-bottom: 15px;
    display: flex;
    gap: 8px;
    align-items: center;
}

.group-post-search input[type="text"] {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    width: 100%;
    max-width: 300px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.group-post-search input:focus {
    outline: none;
    border-color: #6a0dad;
}

.group-post-search button {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s ease;
}

.group-post-search button:hover {
    background-color: #4a0072;
}

.group-post-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 12px;
}

.fab-create-post {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background-color: #6a0dad;
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 28px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    text-decoration: none;
    transition: transform 0.3s ease, background-color 0.3s ease;
    z-index: 999;
}

.fab-create-post:hover {
    background-color: #4a0072;
    transform: translateY(-4px);
}

.fab-create-post i {
    pointer-events: none; /* Makes sure the icon itself doesn't interfere with hover */
}

.fab-create-post {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInFAB 0.5s ease-out forwards;
}

@keyframes fadeInFAB {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}
.group-post-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding: 12px;
    background-color: #f9f9f9;
    border-bottom-left-radius: 14px;
    border-bottom-right-radius: 14px;
}

.group-post-btn {
    background-color: #6a0dad;
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.group-post-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

.edit-group-post-btn {
    background-color: #4CAF50;
}

.edit-group-post-btn:hover {
    background-color: #388E3C;
}

.post-actions button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    transition: transform 0.2s ease;
    color: #333;
}

.post-actions button:hover {
    transform: scale(1.1);
}

.liked {
    color: #4caf50;
    animation: likeBurst 0.4s ease;
}

.bookmarked {
    color: #ffa500;
    animation: bookmarkFlip 0.4s ease;
}

@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.5); }
    100% { transform: scale(1); }
}

@keyframes bookmarkFlip {
    0% { transform: rotateY(0); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(360deg); }
}

#copy-success {
    background: #4caf50;
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    position: fixed;
    top: 10px;
    right: 10px;
    display: none;
}

/* Delete Button */
.delete-group-post-btn {
    background: #e74c3c;
}

.delete-group-post-btn i {
    font-size: 16px;
}

.delete-group-post-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.delete-group-post-btn:active {
    transform: translateY(1px);
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #6a0dad;
    color: #ffffff;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    opacity: 0;
    transition: opacity 0.5s ease, transform 0.5s ease;
    z-index: 1000;
}

.toast-notification.show {
    display: block;
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.toast-notification.hide {
    opacity: 0;
    transform: translateX(-50%) translateY(10px);
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

            <?php if (isset($_SESSION['toast'])): ?>
    <script>
        showToast("<?= $_SESSION['toast'] ?>");
    </script>
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

    
        <h2 class="animate-on-scroll" >Posts in <?= htmlspecialchars($group['name']) ?></h2>

        <div class="group-post-controls">
    <div class="group-post-search animate-on-scroll">
        <input type="text" id="search-query" name="search" placeholder="Search posts..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <button type="button" onclick="submitGroupPostSearch()">Search</button>
    </div>

    <div class="group-post-sorting animate-on-scroll">
        <label for="sort-posts">Sort By:</label>
        <select id="sort-posts" onchange="sortGroupPosts()">
            <option value="latest" <?= isset($_GET['sort']) && $_GET['sort'] == 'popular' ? '' : 'selected'; ?>>Latest</option>
            <option value="popular" <?= isset($_GET['sort']) && $_GET['sort'] == 'popular' ? 'selected' : ''; ?>>Popular</option>
        </select>
    </div>
</div>
            
        <section class="group-posts-container">
    <?php foreach ($groupPosts as $post): ?>
        <div class="group-post-card animate-on-scroll">
    <div class="group-post-media-container slideshow-container" data-post-id="<?= $post['id']; ?>">
        <?php
        $media = json_decode($post['media'], true);
        if (is_string($media)) {
            $media = json_decode($media, true);
        }

        if (!empty($media)) :
            foreach ($media as $index => $file):
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                ?>
                <div class="group-post-slide <?= $index === 0 ? 'active' : ''; ?>">
                    <?php if (in_array($ext, ['mp4', 'webm', 'mov'])) : ?>
                        <video muted autoplay loop playsinline class="group-post-media">
                            <source src="<?= htmlspecialchars($file); ?>" type="video/<?= $ext; ?>">
                        </video>
                    <?php else : ?>
                        <img src="<?= htmlspecialchars($file); ?>" alt="Post Media" class="group-post-media">
                    <?php endif; ?>
                </div>
        <?php endforeach;
        else: ?>
            <div class="group-post-placeholder">No Media</div>
        <?php endif; ?>
    </div>

    <div class="group-post-content">
        <h3 class="group-post-title"><?= htmlspecialchars($post['title']); ?></h3>
        <p class="group-post-excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 100, '...')); ?></p>

        <div class="group-post-meta">
            <span>By <a href="user_profile.php?id=<?= $post['user_id']; ?>"><?= htmlspecialchars($post['author_name']); ?></a></span>
            <span><?= date('F j, Y', strtotime($post['created_at'])); ?></span>
        </div>

        <div class="group-post-engagement">
            <span><i class="fas fa-thumbs-up"></i><span id="like-count-<?= $post['id']; ?>"><?= $post['like_count']; ?></span> Likes</span>
            <span><i class="fas fa-comment"></i> <?= $post['comment_count'] ?> Comments</span>
            <span><i class="fas fa-bookmark"></i> <span id="bookmark-count-<?= $post['id']; ?>"><?= $post['bookmark_count']; ?></span> Bookmarks</span> <!-- Placeholder -->
        </div>

        <div class="post-actions">
            <button class="like-btn" data-post-id="<?= $post['id']; ?>">
                <i id="like-icon-<?= $post['id']; ?>" class="<?= $post['user_liked'] ? 'fas fa-thumbs-up liked' : 'far fa-thumbs-up'; ?>"></i>
                
            </button>

            <button class="bookmark-btn" data-post-id="<?= $post['id']; ?>">
                <i id="bookmark-icon-<?= $post['id']; ?>" class="<?= $post['user_bookmarked'] ? 'fas fa-bookmark bookmarked' : 'far fa-bookmark'; ?>"></i>
            </button>

            <button onclick="openShareModal(<?= $post['id']; ?>)">
                <i class="fas fa-share"></i>
            </button>
        </div>
        
    </div>


    <div class="group-post-actions">
        <a href="view_group_post.php?group_id=<?= $post['group_id']; ?>&post_id=<?= $post['id']; ?>" class="group-post-btn">View Post</a>
        <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
            <a href="edit_group_post.php?id=<?= $post['id']; ?>" class="group-post-btn edit-group-post-btn"><i class="fa fa-pen"></i> Edit Post</a>
        <?php endif; ?>
        <!-- Delete Button (Visible only to post author, admin, or moderator) -->
        <?php if ($_SESSION['user_id'] == $post['user_id'] || $userRole === 'admin' || $userRole === 'moderator'): ?>
            <form action="delete_group_post.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?')">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="hidden" name="group_id" value="<?= $group['id'] ?>"> <!-- Add this line -->
                <button type="submit" class="group-post-btn delete-group-post-btn"><i class="fa fa-trash"></i> Delete</button>
            </form>
        <?php endif; ?>
    </div>
</div>
    <?php endforeach; ?>
</section>
<?php
if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?id=<?= $groupId ?>&page=<?= $i ?>&sort=<?= htmlspecialchars($sort) ?>" class="<?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<a href="create_group_post.php?group_id=<?= $groupId ?>" class="fab-create-post" title="Create Group Post" onclick="playFabSound()">
    <i class="fas fa-pen"></i>
</a>

<audio id="fab-sound" src="assets/sounds/comment_click.mp3" preload="auto"></audio>

<script>
    function playFabSound() {
        document.getElementById('fab-sound').play();
    }
</script>
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
document.addEventListener('DOMContentLoaded', function () {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = 1;
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    animatedElements.forEach(element => {
        element.style.opacity = 0;
        element.style.transform = 'translateY(20px)';
        observer.observe(element);
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const slideshowContainers = document.querySelectorAll('.slideshow-container');

    slideshowContainers.forEach(container => {
        const slides = container.querySelectorAll('.group-post-slide');
        let currentSlide = 0;

        if (slides.length > 1) {
            setInterval(() => {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }, 3000); // Change slide every 3s
        }
    });
});
function sortGroupPosts() {
    const selectedSort = document.getElementById('sort-posts').value;
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', selectedSort);
    window.location.href = currentUrl.toString();
}

function submitGroupPostSearch() {
    const searchQuery = document.getElementById('search-query').value.trim();
    const sortValue = document.getElementById('sort-posts').value;
    const currentUrl = new URL(window.location.href);

    if (searchQuery) {
        currentUrl.searchParams.set('search', searchQuery);
    } else {
        currentUrl.searchParams.delete('search');
    }

    currentUrl.searchParams.set('sort', sortValue);
    currentUrl.searchParams.set('page', 1); // Reset to first page on search
    window.location.href = currentUrl.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    const likeSound = 'assets/sounds/like_click.mp3';
    const bookmarkSound = 'assets/sounds/bookmark_click.mp3';

    function playSound(sound) {
        const audio = new Audio(sound);
        audio.play();
    }

    // Like Button Click
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function () {
            playSound(likeSound);
            const postId = this.dataset.postId;
            const likeIcon = document.getElementById(`like-icon-${postId}`);
            const likeCount = document.getElementById(`like-count-${postId}`);

            fetch('like_group_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    likeIcon.classList.toggle('fas', data.liked);
                    likeIcon.classList.toggle('far', !data.liked);
                    likeIcon.classList.toggle('liked', data.liked);
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
            const bookmarkIcon = document.getElementById(`bookmark-icon-${postId}`);
            const bookmarkCount = document.getElementById(`bookmark-count-${postId}`);

            fetch('bookmark_group_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    bookmarkIcon.classList.toggle('fas', data.bookmarked);
                    bookmarkIcon.classList.toggle('far', !data.bookmarked);
                    bookmarkIcon.classList.toggle('bookmarked', data.bookmarked);
                    bookmarkCount.textContent = data.bookmark_count;
                }
            });
        });
    });
});

// Share Modal Functions
function openShareModal(postId) {
    const modal = document.getElementById('share-modal');
    const postUrl = window.location.origin + '/view_group_post.php?group_id=<?= $groupId; ?>&post_id=' + postId;

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
    document.execCommand('copy');

    playSound('sounds/success.mp3');

    const successMessage = document.getElementById('copy-success');
    successMessage.style.display = 'block';
    setTimeout(() => { successMessage.style.display = 'none'; }, 2000);
}

window.onclick = function(event) {
    const modal = document.getElementById('share-modal');
    if (event.target === modal) {
        closeShareModal();
    }
};

function showToast(message) {
    const toast = document.getElementById('toast-notification');
    toast.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.add('hide');
    }, 2500);

    setTimeout(() => {
        toast.style.display = 'none';
        toast.classList.remove('show', 'hide');
    }, 3000);
}
</script>
</body>
</html>
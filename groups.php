<?php
include 'includes/db.php'; // Database connection
session_start();

$userId = $_SESSION['user_id']; // Assume the user is logged in

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

$categories = $pdo->query("SELECT * FROM group_categories")->fetchAll(PDO::FETCH_ASSOC);


$searchQuery = $_GET['search'] ?? '';

// Highlight Function
function highlight($text, $query) {
    if (!$query) return htmlspecialchars($text);
    return preg_replace('/(' . preg_quote($query, '/') . ')/i', '<span class="highlight">$1</span>', htmlspecialchars($text));
}


// Fetch My Groups
$myGroupsStmt = $pdo->prepare("
    SELECT g.id, g.name, g.description, g.image, g.category_id,
           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count 
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ? 
    AND (g.name LIKE ? OR g.description LIKE ?)
");
$myGroupsStmt->execute([$userId, "%$searchQuery%", "%$searchQuery%"]);
$myGroups = $myGroupsStmt->fetchAll();

// Fetch Other Groups
$otherGroupsStmt = $pdo->prepare("
    SELECT g.id, g.name, g.description, g.image, g.category_id,
           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count 
    FROM groups g
    WHERE g.id NOT IN (
        SELECT group_id FROM group_members WHERE user_id = ?
    )
    AND (g.name LIKE ? OR g.description LIKE ?)
");
$otherGroupsStmt->execute([$userId, "%$searchQuery%", "%$searchQuery%"]);
$otherGroups = $otherGroupsStmt->fetchAll();

// Fetch the user's level
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT level FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userLevel = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Groups</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }

        h1, h2 {
            text-align: center;
        }

       /* 🌟 Page Layout */
.groups-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* 🔍 Search Bar */
.search-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}

.search-container input {
    padding: 12px;
    width: 60%;
    border-radius: 25px 0 0 25px;
    border: 1px solid #ddd;
    font-size: 1rem;
    transition: 0.3s ease-in-out;
}

.search-container input:focus {
    border-color: #6c63ff;
    box-shadow: 0 4px 8px rgba(108, 99, 255, 0.2);
    outline: none;
}

.search-container button {
    padding: 12px 20px;
    background-color: #6c63ff;
    color: white;
    border: none;
    border-radius: 0 25px 25px 0;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.3s ease-in-out;
}

.search-container button:hover {
    background-color: #4a4aad;
}

/* 📜 Groups Section */
.groups-section {
    margin-top: 20px;
}

/* 🎭 Group Cards */
.groups-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

/* Stylish Group Card */
.group-card {
    background-color: #ffffff;
    border-radius: 15px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-align: center;
    position: relative;
}

.group-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
}

.group-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.group-card:hover img {
    transform: scale(1.05);
}

.group-card h3 {
    font-size: 1.5rem;
    color: #6a0dad;
    margin-top: 12px;
}

.group-card p {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 12px;
    line-height: 1.5;
    padding: 0 10px;
}

.group-card .member-count {
    font-size: 0.85rem;
    color: #777;
}

.group-card .btn {
    background-color: #6a0dad;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-bottom: 12px;
    display: inline-block;
}

.group-card .btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

/* 🎨 Create Group Button */
.create-group-link {
    float: right;
    padding: 10px 15px;
    background: #6c63ff;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s ease-in-out;
}

.create-group-link:hover {
    background: #4a4aad;
    transform: scale(1.05);
}

/* 🔥 Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-on-scroll {
    opacity: 0;
    transform: translateY(10px);
    transition: 0.5s ease-in-out;
}

.animate-on-scroll.visible {
    opacity: 1;
    transform: translateY(0);
}
body.dark-mode .group-card {
    background-color: #1e1e1e;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

body.dark-mode .group-card h3 {
    color: #bb86fc;
}

body.dark-mode .group-card p {
    color: #d1d1d1;
}

body.dark-mode .group-card .member-count {
    color: #a0a0a0;
}

body.dark-mode .group-card .btn {
    background-color: #bb86fc;
    color: #1e1e1e;
}

body.dark-mode .group-card .btn:hover {
    background-color: #d8b4fe;
    color: #1e1e1e;
}
.group-card {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInGroupCard 0.8s ease-out forwards;
}

@keyframes fadeInGroupCard {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

.group-hover-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: none;
}

.group-card:hover .group-hover-actions {
    display: block;
}

.group-hover-actions button {
    background-color: #6a0dad;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: background-color 0.3s ease;
}

.group-hover-actions button:hover {
    background-color: #4a0072;
}

.sort-container {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    font-family: 'Poppins', sans-serif;
}

.sort-container label {
    font-size: 1rem;
    color: #333;
}

.sort-container select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #ffffff;
    color: #333;
    transition: border-color 0.3s ease;
}

.sort-container select:focus {
    outline: none;
    border-color: #6c63ff;
    box-shadow: 0 4px 8px rgba(108, 99, 255, 0.2);
}

body.dark-mode .sort-container label {
    color: #f5f5f5;
}

body.dark-mode .sort-container select {
    background-color: #1e1e1e;
    color: #f5f5f5;
    border-color: #444;
}

.filter-container {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-container label {
    font-size: 1rem;
    color: #333;
}

.filter-container select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #ffffff;
    color: #333;
    transition: border-color 0.3s ease;
}

.filter-container select:focus {
    outline: none;
    border-color: #6a0dad;
    box-shadow: 0 4px 8px rgba(106, 13, 173, 0.2);
}

/* Dark Mode Support */
body.dark-mode .filter-container label {
    color: #f5f5f5;
}

body.dark-mode .filter-container select {
    background-color: #1e1e1e;
    color: #f5f5f5;
    border-color: #444;
}

/* Floating Action Button (FAB) */
.fab-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.fab-main-btn {
    background-color: #6a0dad;
    color: white;
    border: none;
    border-radius: 50%;
    width: 56px;
    height: 56px;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, background-color 0.3s ease;
}

.fab-main-btn:hover {
    background-color: #4a0072;
    transform: rotate(45deg);
}

/* Hidden by default */
.fab-options {
    display: none;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
    margin-bottom: 8px;
}

.fab-option-btn {
    background-color: #ffffff;
    color: #6a0dad;
    border: none;
    border-radius: 50px;
    padding: 12px 18px;
    font-size: 14px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.fab-option-btn i {
    font-size: 16px;
    color: #6a0dad;
}

.fab-option-btn:hover {
    background-color: #f3e5f5;
    transform: translateY(-2px);
}

/* Dark Mode Styles */
body.dark-mode .fab-main-btn {
    background-color: #bb86fc;
    color: #121212;
}

body.dark-mode .fab-main-btn:hover {
    background-color: #9c6eff;
}

body.dark-mode .fab-option-btn {
    background-color: #2a2a2a;
    color: #bb86fc;
}

body.dark-mode .fab-option-btn i {
    color: #bb86fc;
}

body.dark-mode .fab-option-btn:hover {
    background-color: #3a3a3a;
}

.groups-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 12px;
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
                    <li><a href="groups.php"  class="active"><i class="fas fa-users"></i>Groups</a></li>
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
                    <li><a href="groups.php"  class="active"><i class="fas fa-users"></i>Groups</a></li>
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

          <!--  <?php if ($userLevel >= 3): ?>
                    <a href="create_group.php" class="animate-on-scroll create-group-link"><i class="fas fa-plus"></i> Create Group</a>
                <?php else: ?>
                    <strong class="animate-on-scroll" style="float: right">You need Level 3 to create groups</strong><br>
                <?php endif; ?><br> -->

            <div class="groups-page">
                <h1 class="animate-on-scroll"><i class="fas fa-users"></i>Groups</h1>

                

                <div class="groups-controls">
                    <div class="search-container">
                        <form action="" method="get">
                            <input type="text" name="search" placeholder="Search groups..." value="<?= htmlspecialchars($searchQuery) ?>">
                            <button class="animate-on-scroll" type="submit">Search</button>
                        </form>
                    </div>
                

                    <div class="filter-container animate-on-scroll">
                    <!-- <label for="category-filter">Filter by Category:</label> -->
                        <select id="category-filter" onchange="filterGroups()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

       <!-- <div class="sort-container animate-on-scroll">
            <label for="sort-my-groups">Sort by:</label>
            <select id="sort-my-groups" onchange="sortMyGroups()">
                <option value="default">Default</option>
                <option value="name">Name (A-Z)</option>
                <option value="members">Most Members</option>
            </select> 
        </div>-->
        <!-- My Groups Section -->
        <div class="groups-section">
            <h2 class="animate-on-scroll">My Groups</h2>
            <div class="groups-container">
                <?php if (count($myGroups) > 0): ?>
                    <?php foreach ($myGroups as $group): ?>
                        <div class="group-card animate-on-scroll" data-category-id="<?php echo $group['category_id']; ?>">
                            <img src="<?= htmlspecialchars($group['image']) ?: 'assets/default-group.jpg' ?>" alt="Group Image">
                            <h3><?= htmlspecialchars($group['name']) ?></h3>
                            <p><?= htmlspecialchars($group['description']) ?></p>
                            <p class="member-count"><strong><?= $group['member_count'] ?> Members</strong></p>
                            <a href="group.php?id=<?= $group['id'] ?>" class="btn">View Group</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results animate-on-scroll">You haven't joined any groups yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
                    
        <!--<div class="sort-container animate-on-scroll">
            <label for="sort-other-groups">Sort by:</label>
            <select id="sort-other-groups" onchange="sortOtherGroups()">
                <option value="default">Default</option>
                <option value="name">Name (A-Z)</option>
                <option value="members">Most Members</option>
            </select>
        </div>-->
        <!-- Other Groups Section -->
        <div class="groups-section">
            <h2 class="animate-on-scroll">Other Groups</h2>
            <div class="groups-container">
                <?php if (count($otherGroups) > 0): ?>
                    <?php foreach ($otherGroups as $group): ?>
                        <div class="group-card animate-on-scroll" data-category-id="<?php echo $group['category_id']; ?>">
                            <img src="<?= htmlspecialchars($group['image']) ?: 'assets/default-group.jpg' ?>" alt="Group Image">
                            <h3><?= htmlspecialchars($group['name']) ?></h3>
                            <p><?= htmlspecialchars($group['description']) ?></p>
                            <p class="member-count"><strong><?= $group['member_count'] ?> Members</strong></p>
                            <a href="group.php?id=<?= $group['id'] ?>" class="btn">View Group</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results animate-on-scroll">No groups available to join.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="fab-container">
    <button class="fab-main-btn" onclick="toggleFabOptions()">
        <i class="fas fa-plus"></i>
    </button>

    <div class="fab-options">
        <a href="create_group.php" class="fab-option-btn" onclick="playButtonSound()">
            <i class="fas fa-pen"></i> Create Group
        </a>
        <a href="group_bookmarks.php" class="fab-option-btn" onclick="playButtonSound()">
            <i class="fas fa-bookmark"></i> Group Bookmarks
        </a>
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

function toggleFabOptions() {
    const options = document.querySelector('.fab-options');
    options.style.display = (options.style.display === 'flex') ? 'none' : 'flex';
}

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


function sortMyGroups() {
    const sortBy = document.getElementById('sort-my-groups').value;
    const myGroupsContainer = document.querySelector('.groups-section:nth-of-type(1) .groups-container');
    if (myGroupsContainer) sortGroupCards(myGroupsContainer, sortBy);
}

function sortOtherGroups() {
    const sortBy = document.getElementById('sort-other-groups').value;
    const otherGroupsContainer = document.querySelector('.groups-section:nth-of-type(2) .groups-container');
    if (otherGroupsContainer) sortGroupCards(otherGroupsContainer, sortBy);
}

function sortGroupCards(container, sortBy) {
    const cards = Array.from(container.querySelectorAll('.group-card'));

    const sortedCards = cards.sort((a, b) => {
        const nameA = a.querySelector('h3')?.textContent.trim().toLowerCase() || '';
        const nameB = b.querySelector('h3')?.textContent.trim().toLowerCase() || '';

        const membersA = parseInt(a.querySelector('.member-count')?.textContent.match(/\d+/)?.[0] || '0');
        const membersB = parseInt(b.querySelector('.member-count')?.textContent.match(/\d+/)?.[0] || '0');

        switch (sortBy) {
            case 'name':
                return nameA.localeCompare(nameB);
            case 'members':
                return membersB - membersA;
            default:
                return 0;
        }
    });

    // Empty the container and append sorted cards
    container.innerHTML = '';
    sortedCards.forEach(card => container.appendChild(card));
}

function filterGroups() {
    const selectedCategory = document.getElementById('category-filter').value;
    const groupCards = document.querySelectorAll('.group-card');

    groupCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category-id');
        if (!selectedCategory || cardCategory === selectedCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
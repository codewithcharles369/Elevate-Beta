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



$postsPerPage = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $postsPerPage;

// Fetch Bookmarked Group Posts
$stmt = $pdo->prepare("
    SELECT gp.*, u.name AS author_name, u.profile_picture,
        (SELECT COUNT(*) FROM group_post_likes WHERE post_id = gp.id) AS like_count,
        (SELECT COUNT(*) FROM group_post_comments WHERE post_id = gp.id) AS comment_count,
        (SELECT COUNT(*) FROM group_post_bookmarks WHERE post_id = gp.id AND user_id = ?) AS user_bookmarked
    FROM group_post_bookmarks gb
    JOIN group_posts gp ON gb.post_id = gp.id
    JOIN users u ON gp.user_id = u.id
    WHERE gb.user_id = ?
    ORDER BY gb.created_at DESC
    LIMIT $postsPerPage OFFSET $offset
");
$stmt->execute([$userId, $userId]);
$bookmarkedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total bookmarks for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM group_post_bookmarks WHERE user_id = ?");
$totalStmt->execute([$userId]);
$totalBookmarks = $totalStmt->fetchColumn();
$totalPages = ceil($totalBookmarks / $postsPerPage);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Group Post Bookmarks | Elevate</title>
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

/* Post Card Container */
.post-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(106, 13, 173, 0.12);
}

/* Media Slideshow */
.media-slideshow {
    position: relative;
    width: 100%;
    height: 200px;
    border-radius: 12px;
    overflow: hidden;
}

.slideshow-item {
    position: absolute;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.8s ease-in-out;
}

.slideshow-item.active {
    opacity: 1;
}

.media-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f4f4f4;
    height: 200px;
    color: #777;
    font-size: 1rem;
    font-weight: 500;
}

/* Post Content */
.post-card h3 {
    font-size: 1.4rem;
    font-weight: bold;
    color: #6a0dad;
    margin: 12px 0;
    line-height: 1.4;
    transition: color 0.3s ease;
}

.post-card p {
    font-size: 1rem;
    color: #555;
    line-height: 1.6;
    margin-bottom: 12px;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.9rem;
    color: #777;
}

.author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #6a0dad;
}

.post-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Post Action Buttons */
.post-actions {
    display: flex;
    gap: 12px;
    margin-top: 10px;
}

.post-actions button {
    background: #f5f5f5;
    border: none;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    gap: 6px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-size: 0.9rem;
    color: #333;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.post-actions button:hover {
    background: #e9e9e9;
    transform: translateY(-2px);
}

.post-actions i {
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.like-icon.liked {
    color: #4caf50;
}

.bookmark-icon.bookmarked {
    color: #ffa500;
}

.share-icon {
    color: #6a0dad;
}

/* Animation for Like Button */
@keyframes likeBurst {
    0% { transform: scale(1); }
    50% { transform: scale(1.5); }
    100% { transform: scale(1); }
}

.like-icon.liked {
    animation: likeBurst 0.4s ease;
}

/* Dark Mode Compatibility */
.dark-mode .post-card {
    background: #1e1e2f;
    color: #e4e4e4;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dark-mode .post-card h3 {
    color: #d4bfff;
}

.dark-mode .post-meta,
.dark-mode .post-actions button {
    background: #2a2a3f;
    color: #e4e4e4;
}

.dark-mode .post-actions button:hover {
    background: #373751;
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


    
        <h2  >Your Bookmarked Group Posts</h2>

        <div class="bookmarked-posts-grid">
        <?php if (!empty($bookmarkedPosts)): ?>
            <?php foreach ($bookmarkedPosts as $post): ?>
                <div class="post-card">
                    <div class="media-slideshow">
                        <?php
                        $media = json_decode($post['media'], true);
                        if (!empty($media)):
                            foreach ($media as $index => $file):
                        ?>
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)): ?>
                                <img src="<?= htmlspecialchars($file); ?>" class="slideshow-item <?= $index === 0 ? 'active' : ''; ?>" alt="Post Image">
                            <?php elseif (preg_match('/\.(mp4|webm|ogg)$/i', $file)): ?>
                                <video src="<?= htmlspecialchars($file); ?>" class="slideshow-item <?= $index === 0 ? 'active' : ''; ?>" controls></video>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div class="media-placeholder">No Media Available</div>
                        <?php endif; ?>
                    </div>

                    <h3><?= htmlspecialchars($post['title']); ?></h3>
                    <p><?= nl2br(htmlspecialchars(substr($post['content'], 0, 100))); ?>...</p>

                    <div class="post-meta">
                        <img src="<?= htmlspecialchars($post['profile_picture']); ?>" alt="Author Avatar" class="author-avatar">
                        <span>By <?= htmlspecialchars($post['author_name']); ?></span>
                        <span>Likes: <?= $post['like_count']; ?></span>
                        <span>Comments: <?= $post['comment_count']; ?></span>
                    </div>

                    <div class="post-actions">
                        <button class="like-btn" onclick="toggleLike(<?= $post['id']; ?>)">
                            <i class="<?= $post['user_bookmarked'] ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                        </button>
                        <button class="bookmark-btn" onclick="toggleBookmark(<?= $post['id']; ?>)">
                            <i class="fas fa-bookmark bookmarked"></i>
                        </button>
                        <button onclick="openShareModal(<?= $post['id']; ?>)">
                            <i class="fas fa-share"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven’t bookmarked any group posts yet.</p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1; ?>">Previous</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1; ?>">Next</a>
        <?php endif; ?>
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
    const likeSound = 'sounds/like.mp3';
    const bookmarkSound = 'sounds/bookmark.mp3';

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
</script>
</body>
</html>
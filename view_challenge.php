<?php
require 'includes/db.php';
session_start();

$userId = $_SESSION['user_id']; // Assume the user is logged in


$challenge_id = $_GET['id'];
$group_id = $_GET['group_id'];

$stmtChallenge = $pdo->prepare("SELECT * FROM group_challenges WHERE id = ?");
$stmtChallenge->execute([$challenge_id]);
$challenge = $stmtChallenge->fetch(PDO::FETCH_ASSOC);

// Check if the user is a member
$membershipStmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$membershipStmt->execute([$group_id, $userId]);
$membership = $membershipStmt->fetch();
$isMember = $membership ? true : false;
$userRole = $membership['role'] ?? null;

$stmtSubmissions = $pdo->prepare("SELECT s.*, u.name, u.profile_picture FROM group_challenge_submissions s JOIN users u ON s.user_id = u.id WHERE s.challenge_id = ? AND s.status = 'approved'");
$stmtSubmissions->execute([$challenge_id]);
$submissions = $stmtSubmissions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Challenge</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
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
    </script>
    <style>
        /* Container for the Challenge View Page */
.view-challenge-container {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    margin-bottom: 24px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Challenge Header */
.challenge-header h1 {
    font-size: 2rem;
    color: #6a0dad;
    margin-bottom: 8px;
}

.challenge-description {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 12px;
}

.challenge-deadline {
    font-size: 0.9rem;
    color: #e74c3c;
    margin-bottom: 20px;
}

/* Submission Form */
.submit-challenge-form h3 {
    margin-bottom: 12px;
    color: #6a0dad;
}

 textarea {
    width: 100%;
    height: 100px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background-color: #f9f9f9;
    font-size: 1rem;
    resize: none;
    margin-bottom: 12px;
}

.submit-challenge-form input[type="file"] {
    margin-bottom: 12px;
}

.submit-challenge-btn {
    background-color: #6a0dad;
    color: white;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.submit-challenge-btn:hover {
    background-color: #4a0072;
}

/* Submission Cards */
.challenge-submissions h3 {
    margin-top: 24px;
    margin-bottom: 12px;
    color: #6a0dad;
}

.submission-card {
    background-color: #f9f9f9;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 16px;
    transition: transform 0.3s ease;
}

.submission-card:hover {
    transform: translateY(-4px);
}

.submission-user {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.submission-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.submission-content {
    font-size: 1rem;
    color: #333;
    margin-bottom: 8px;
}

.submission-media img, .submission-media video {
    max-width: 100%;
    border-radius: 8px;
    margin-top: 8px;
}

.submission-likes {
    margin-top: 6px;
    font-size: 0.9rem;
    color: #e74c3c;
}

.submission-likes i {
    margin-right: 4px;
}

/* Dark Mode Support */
body.dark-mode .view-challenge-container,
body.dark-mode .submission-card {
    background-color: #1e1e1e;
    color: white;
}

body.dark-mode .challenge-description,
body.dark-mode .submission-content {
    color: #ddd;
}

body.dark-mode .submission-likes {
    color: #ff6b6b;
}

.approve-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 14px;
    margin-right: 5px;
    cursor: pointer;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.approve-btn:hover {
    background-color: #45a049;
}

.reject-btn {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 8px 14px;
    cursor: pointer;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.reject-btn:hover {
    background-color: #c0392b;
}

.submission-status {
    font-size: 0.9rem;
    margin-top: 6px;
}
.pending-submissions h3, .approved-submissions h3 {
    color: #6a0dad;
    margin-bottom: 12px;
}

.submission-card {
    background-color: #ffffff;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 16px;
    transition: transform 0.3s ease;
}

.submission-card:hover {
    transform: translateY(-3px);
}

.submission-card.pending {
    border-left: 5px solid #f39c12;
}

.submission-card.approved {
    border-left: 5px solid #4caf50;
}

.submission-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
}

.approve-btn {
    background-color: #4caf50;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
}

.approve-btn:hover {
    background-color: #45a049;
}

.reject-btn {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
}

.reject-btn:hover {
    background-color: #c0392b;
}

/* Dark Mode Support */
body.dark-mode .submission-card {
    background-color: #1e1e1e;
    color: white;
}

body.dark-mode .submission-card.pending {
    border-left-color: #f39c12;
}

body.dark-mode .submission-card.approved {
    border-left-color: #4caf50;
}
.vote-submission-btn {
    background-color: #6a0dad;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.vote-submission-btn:hover {
    background-color: #4a0072;
    transform: translateY(-2px);
}

.submission-likes i {
    color: #999;
    margin-right: 6px;
    transition: color 0.3s ease;
}

.submission-likes i.liked {
    color: #e74c3c;
}

body.dark-mode .vote-submission-btn {
    background-color: #9b59b6;
}

body.dark-mode .vote-submission-btn:hover {
    background-color: #6a0dad;
}

body.dark-mode .submission-likes i {
    color: #bbb;
}

body.dark-mode .submission-likes i.liked {
    color: #ff6b6b;
}
.challenge-page-container {
    padding: 24px;
}

.challenge-header-card, .submission-form-card, .pending-submissions-card, .approved-submissions-card {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    transition: background-color 0.3s ease, color 0.3s ease;
}

.challenge-title {
    color: #6a0dad;
    font-size: 2rem;
    margin-bottom: 8px;
}

.challenge-description {
    font-size: 1rem;
    color: #333;
}

.challenge-deadline {
    color: #e74c3c;
    font-size: 0.9rem;
}

.submission-card {
    background-color: #fafafa;
    padding: 16px;
    margin-bottom: 12px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.submission-card:hover {
    transform: translateY(-3px);
}

.submission-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.submission-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.submission-content {
    margin-top: 8px;
    font-size: 1rem;
    color: #333;
}

.submission-media img, .submission-media video {
    width: 100%;
    max-height: 250px;
    object-fit: cover;
    border-radius: 8px;
    margin-top: 10px;
}

.submission-likes {
    margin-top: 8px;
    font-size: 0.9rem;
    color: #e74c3c;
}

.approve-btn, .reject-btn {
    margin-top: 8px;
    padding: 6px 12px;
    font-size: 0.9rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.approve-btn { background-color: #4caf50; color: white; }
.reject-btn { background-color: #e74c3c; color: white; }

.approve-btn:hover, .reject-btn:hover { transform: translateY(-2px); }

/* Dark Mode */
body.dark-mode .challenge-header-card,
body.dark-mode .submission-form-card,
body.dark-mode .pending-submissions-card,
body.dark-mode .approved-submissions-card {
    background-color: #1e1e1e;
    color: #f5f5f5;
}

body.dark-mode .submission-card {
    background-color: #2a2a2a;
    color: white;
}
    </style>
<head>
<body>

<div class="challenge-page-container animate-on-scroll">
    <!-- Challenge Header Section -->
    <div class="challenge-header-card">
        <h1 class="challenge-title"><?php echo htmlspecialchars($challenge['title']); ?></h1>
        <p class="challenge-description"><?php echo nl2br(htmlspecialchars($challenge['description'])); ?></p>
        <?php if (!empty($challenge['deadline'])): ?>
            <p class="challenge-deadline"><i class="fas fa-clock"></i> Deadline: <?php echo date('F j, Y g:i A', strtotime($challenge['deadline'])); ?></p>
        <?php endif; ?>
    </div>

    <!-- Submission Form -->
    <div class="submission-form-card">
        <h3>Submit Your Entry</h3>
        <form action="submit_challenge.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
            <textarea name="content" placeholder="Write your submission..." required></textarea>
            <input type="file" name="media" accept="image/*,video/*">
            <button type="submit" class="submit-challenge-btn">Submit Entry</button>
        </form>
    </div>

    <?php
// Fetch PENDING submissions
$stmtPendingSubmissions = $pdo->prepare("SELECT s.*, u.name, u.profile_picture FROM group_challenge_submissions s JOIN users u ON s.user_id = u.id WHERE s.challenge_id = ? AND s.status = 'pending'");
$stmtPendingSubmissions->execute([$challenge_id]);
$pendingSubmissions = $stmtPendingSubmissions->fetchAll(PDO::FETCH_ASSOC);

// Fetch APPROVED submissions
$stmtApprovedSubmissions = $pdo->prepare("SELECT s.*, u.name, u.profile_picture FROM group_challenge_submissions s JOIN users u ON s.user_id = u.id WHERE s.challenge_id = ? AND s.status = 'approved'");
$stmtApprovedSubmissions->execute([$challenge_id]);
$approvedSubmissions = $stmtApprovedSubmissions->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- PENDING SUBMISSIONS (Visible to Admin/Moderator) -->
<?php if ($userRole === 'admin' || $userRole === 'moderator' && !empty($pendingSubmissions)): ?>
    <div class="challenge-submissions pending-submissions">
        <h3>Pending Submissions</h3>
        <?php foreach ($pendingSubmissions as $submission): ?>
            <div class="submission-card pending">
                <div class="submission-user">
                    <img src="<?php echo htmlspecialchars($submission['profile_picture']); ?>" alt="User Avatar" class="submission-avatar">
                    <strong><?php echo htmlspecialchars($submission['name']); ?></strong>
                </div>
                <p class="submission-content"><?php echo nl2br(htmlspecialchars($submission['content'])); ?></p>

                <?php if (!empty($submission['media'])): ?>
                    <div class="submission-media">
                        <?php if (strpos($submission['media'], '.mp4') !== false): ?>
                            <video controls>
                                <source src="<?php echo htmlspecialchars($submission['media']); ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($submission['media']); ?>" alt="Submission Media">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Approval Buttons -->
                <form action="approve_submission.php" method="POST" style="display:inline;">
                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                    <input type="hidden" name="challenge_id" value="<?php echo $challenge_id; ?>">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <button type="submit" class="approve-btn">Approve</button>
                </form>

                <form action="reject_submission.php" method="POST" style="display:inline;">
                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                    <input type="hidden" name="challenge_id" value="<?php echo $challenge_id; ?>">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <button type="submit" class="reject-btn">Reject</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- APPROVED SUBMISSIONS (Visible to Everyone) -->
<div class="challenge-submissions approved-submissions">
    <h3>Approved Submissions</h3>
    <?php if (empty($approvedSubmissions)): ?>
        <p>No approved submissions yet.</p>
    <?php else: ?>
        <?php foreach ($approvedSubmissions as $submission): ?>
            <?php
                // Check if the user already liked the submission
                $stmtUserVote = $pdo->prepare("SELECT id FROM group_challenge_votes WHERE submission_id = ? AND user_id = ?");
                $stmtUserVote->execute([$submission['id'], $_SESSION['user_id']]);
                $userHasVoted = $stmtUserVote->fetch();
                ?>
            <div class="submission-card approved">
                <div class="submission-user">
                    <img src="<?php echo htmlspecialchars($submission['profile_picture']); ?>" alt="User Avatar" class="submission-avatar">
                    <strong><?php echo htmlspecialchars($submission['name']); ?></strong>
                </div>
                <p class="submission-content"><?php echo nl2br(htmlspecialchars($submission['content'])); ?></p>

                <?php if (!empty($submission['media'])): ?>
                    <div class="submission-media">
                        <?php if (strpos($submission['media'], '.mp4') !== false): ?>
                            <video controls>
                                <source src="<?php echo htmlspecialchars($submission['media']); ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($submission['media']); ?>" alt="Submission Media">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Like Count -->
                <p class="submission-likes" id="likes-<?php echo $submission['id']; ?>">
                    <i class="fas fa-heart <?php echo $userHasVoted ? 'liked' : ''; ?>"></i> 
                    <span><?php echo $submission['likes']; ?></span> Likes
                </p>

                <!-- Like (Vote) Button -->
                <button class="vote-submission-btn" data-submission-id="<?php echo $submission['id']; ?>" data-liked="<?php echo $userHasVoted ? '1' : '0'; ?>">
                    <?php echo $userHasVoted ? 'Unlike' : 'Vote'; ?>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.vote-submission-btn').forEach(button => {
    button.addEventListener('click', function() {
        const submissionId = this.getAttribute('data-submission-id');
        const liked = this.getAttribute('data-liked') === '1';

        fetch('vote_submission.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `submission_id=${submissionId}&liked=${liked ? 1 : 0}`
        })
        .then(response => response.json())
        .then(data => {
            const likeDisplay = document.getElementById(`likes-${submissionId}`);
            likeDisplay.querySelector('span').textContent = data.likes;

            // Toggle button state and text
            if (liked) {
                this.textContent = 'Vote';
                this.setAttribute('data-liked', '0');
                likeDisplay.querySelector('i').classList.remove('liked');
            } else {
                this.textContent = 'Unlike';
                this.setAttribute('data-liked', '1');
                likeDisplay.querySelector('i').classList.add('liked');
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>
</body>
<html>
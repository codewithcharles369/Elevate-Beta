<?php
require 'includes/db.php';
session_start();

if (!isset($_GET['post_id']) || !isset($_SESSION['user_id'])) {
    die("Invalid request");
}

$post_id = $_GET['post_id'];
$user_id = $_SESSION['user_id'];

// Fetch user's theme preference
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$theme = $user['theme'] ?? 'light';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'];
    $stmt = $pdo->prepare("INSERT INTO reports (post_id, user_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $reason]);
    echo "Post reported successfully!";
    
    header("Location: view_post2.php?id= $post_id;");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Report Post</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <style>
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to bottom right, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        form {
    width: 100%;
    background: rgba(255, 255, 255, 0.1);
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
    font-size: 16px;
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
        
            </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>" style="height: 100vh;">

    <h2>Report Post</h2>
    <form method="POST">
        <label for="reason">Reason for reporting:</label><br>
        <textarea id="reason" name="reason" rows="4" cols="50" required></textarea><br><br>
        <button type="submit">Submit Report</button>
    </form>
</div>

</body>
</html>
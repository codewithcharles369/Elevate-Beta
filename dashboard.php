<?php
include "includes/db.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
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

$stmt = $pdo->prepare("SELECT COUNT(*) AS posts_count FROM posts WHERE  user_id = ?");
$stmt->execute([$user_id]);
$posts = $stmt->fetch();
$posts_count = $posts['posts_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS likes_count FROM likes WHERE  user_id = ?");
$stmt->execute([$user_id]);
$likes = $stmt->fetch();
$likes_count = $likes['likes_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS comments_count FROM comments WHERE  user_id = ?");
$stmt->execute([$user_id]);
$comments = $stmt->fetch();
$comments_count = $comments['comments_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS views_count FROM post_views WHERE  user_id = ?");
$stmt->execute([$user_id]);
$views = $stmt->fetch();
$views_count = $views['views_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS followers_count FROM follows WHERE  following_id = ?");
$stmt->execute([$user_id]);
$followers = $stmt->fetch();
$followers_count = $followers['followers_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS following_count FROM follows WHERE  follower_id = ?");
$stmt->execute([$user_id]);
$following = $stmt->fetch();
$following_count = $following['following_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

  body.dark header{
    background-color:rgba(0, 0, 0, 0.5);
  }


body.dark.header{
  background-color:rgba(0, 0, 0, 0.5);
  color: #fff;
}

body.light .stat-card{
  background-color: rgba(0, 0, 0, 0.4);
    color: #fff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  body.dark .stat-card{
    background-color: rgba(0, 0, 0, 0.4);
    color: #fff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);transition: transform 0.3s ease, box-shadow 0.3s ease;
}

  body.light .weather-widget {
    background-color: rgba(0, 0, 0, 0.4);
    color: #fff;
  }
  body.dark .weather-widget {
    background: rgba(0, 0, 0, 0.4);
  color: #fff;
  }



body.light #calendar .header {
  background-color: #395886;
  color: #ffffff;

}
body.light #unread-messages-list,
body.dark #unread-messages-list{
  color: black;
}

body.dark #notifications-list,
body.light #notifications-list
{
  color: black;
}
        /* Main Content */
main {
  flex-grow: 1;
  padding: 20px;
}

.header, .user {
  padding: 20px;
  border-radius: 5px;
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
}


.user {
  background-color:rgba(0, 0, 0, 0.6);
  color: #fff;
  margin-left: auto;
  margin-right: auto;
  display: none;
  text-align: center;
}

@media screen and (max-width: 600px){
  .user {
    display: block;
  }
}

.content {
  margin-top: 20px;
  color: black;
  padding: 20px;
  border-radius: 5px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Stats Section */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

/* Stat Card Icons */
.stat-card i {
  font-size: 40px;
  margin-bottom: 10px;
  color: rgba(255, 255, 255, 0.8);
  transition: color 0.3s ease;
}

/* Icon hover effect */
.stat-card:hover i {
  color: #fff;
}


.stat-card {
  padding: 20px;
  border-radius: 10px;
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card h3 {
  margin: 0;
  font-size: 18px;
  font-weight: bold;
}

.stat-card p {
  margin: 10px 0 0;
  font-size: 24px;
  font-weight: bold;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}
/* Animation for stat cards */
.stat-card {
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.8s ease-out forwards;
}

/* Delay for each card */
.stat-card:nth-child(1) { animation-delay: 2.1s; }
.stat-card:nth-child(2) { animation-delay: 2.2s; }
.stat-card:nth-child(3) { animation-delay: 2.4s; }
.stat-card:nth-child(4) { animation-delay: 2.6s; }
.stat-card:nth-child(5) { animation-delay: 2.8s; }
.stat-card:nth-child(6) { animation-delay: 3.0s; }
.stat-card:nth-child(7) { animation-delay: 3.2s; }
.stat-card:nth-child(8) { animation-delay: 3.4s; }
.stat-card:nth-child(9) { animation-delay: 3.6s; }

@keyframes fadeInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.recent-activities {
  color: black;
  margin-top: 20px;
  padding: 20px;
  background-color: #ffffff;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.recent-activities h2 {
  font-size: 18px;
  margin-bottom: 10px;
}

.recent-activities ul {
  list-style: none;
  padding: 0;
}

.recent-activities li {
  margin-bottom: 10px;
  font-size: 14px;
  display: flex;
  align-items: center;
}

.recent-activities li span {
  margin-right: 10px;
}
.user-stats {
    background: rgba(0, 0, 0, 0.4);
  color: #fff;
  padding: 20px;
  border-radius: 10px;
  margin-top: 20px;
}

.user-stats h2 {
  font-size: 18px;
  margin-bottom: 10px;
}

.user-stats ul {
  list-style: none;
  padding: 0;
}

.user-stats li {
  margin-bottom: 8px;
}
.task-manager {
  color: black;
  background-color: #ffffff;
  border-radius: 10px;
  padding: 20px;
  margin-top: 20px;
}



.task-manager ul {
  list-style: none;
  padding: 0;
}

.task-manager li {
  margin-bottom: 10px;
}

.task-manager input {
  padding: 8px;
  width: calc(100% - 100px);
  margin-right: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

.task-manager button {
  text-decoration: none;
    color: #fff;
    background-color: #007bff;
    padding: 14px;
    border-radius: 5px;
    font-size: 14px;
    margin-right: 5px;
    transition: background 0.3s ease, transform 0.3s ease;
}

.task-manager button:hover {
  background-color: #5548d1;
}
.weather-widget {
  padding: 20px;
  border-radius: 10px;
  margin-top: 20px;
  text-align: center;
}

.weather-widget h2 {
  font-size: 18px;
}

.weather-widget p {
  font-size: 16px;
}
.calendar-widget {
  background-color: #ffffff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 20px;
}

.calendar-widget h2 {
  font-size: 18px;
  margin-bottom: 10px;
}

#calendar {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 5px;
  text-align: center;
}

#calendar div {
  padding: 10px;
  color: black;
  background-color: #f4f4f4;
  border-radius: 5px;
  font-size: 14px;
}

#calendar .header {
  background-color: #4a3fb8;
  color: #ffffff;
  font-weight: bold;
}

#calendar .today {
  background-color: #ff6f61;
  color: white;
  font-weight: bold;
}
#todo-container {
    color: black;
    margin: 20px 0;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #ffffff;
}

#todo-form {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
#todo-form button, #todo-list button{
  text-decoration: none;
    color: #fff;
    background-color: #007bff;
    padding: 14px;
    border-radius: 5px;
    font-size: 14px;
    margin-right: 5px;
    transition: background 0.3s ease, transform 0.3s ease;
}
#todo-list button{
  background-color: #dc3545;
}


#todo-input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

#todo-list {
    list-style: none;
    padding: 0;
}

#todo-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px;
    border-bottom: 1px solid #eee;
}

#todo-list li:last-child {
    border-bottom: none;
}

.todo-task.completed {
    text-decoration: line-through;
    color: gray;
}



#loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #ffffff; /* Adjust background color */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Ensure it appears above other elements */
}

#loading-screen img {
    width: 100%; /* Adjust size */
    animation: fadeIn 2s ease-in-out infinite;
}

@keyframes fadeIn {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}
#loading-screen.fade-out {
    animation: fadeOut 1s ease-in-out forwards;
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
        visibility: hidden;
    }
}
/* Notifications Container */
#notifications-container, #unread-messages-container {
  width: 100%;
  background: rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  transition: transform 0.3s;
}

#notifications-container:hover, #unread-messages-container:hover {
  transform: translateY(-5px);
}

/* Header */
#notifications-container h3, #unread-messages-container h3 {
  font-size: 22px;
  margin-bottom: 15px;
  color: #fff;
  text-align: center;
  letter-spacing: 1px;
}

/* Mark All as Read Button */
.mark-btn {
  background-color: #8a2be2;
  color: #fff;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  width: 100%;
  font-weight: bold;
  transition: background 0.4s, transform 0.3s;
  margin-bottom: 15px;
}

.mark-btn:hover {
  background: #6a0dad;
  transform: scale(1.03);
}

/* Notification List */
#notifications-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

/* Notification Item */
.notification-item, .unread-message-item {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.1);
  padding: 15px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: background 0.3s, transform 0.3s;
  position: relative;
  overflow: hidden;
}

.notification-item.unread, .unread-message-item.unread {
  border-left: 4px solid #ffcc00;
  background: ;
}

.notification-item:hover, .unread-message-item:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.02);
}

/* Sender Picture */
.sender-pic {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 15px;
  border: 3px solid #fff;
  transition: transform 0.3s;
}

.sender-pic:hover {
  transform: scale(1.1);
}

/* Notification Content */
.notification-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.message {
  font-size: 15px;
  font-weight: bold;
  color: #fff;
  line-height: 1.4;
}

.time {
  font-size: 12px;
  color: #e0e0e0;
}

/* Actions */
.notification-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mark-as-read-btn,
.link {
  background-color: #8a2be2;
  color: #fff;
  border: 1px solid #8a2be2;
  padding: 6px 10px;
  border-radius: 6px;
  text-decoration: none;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.3s ease;
  text-align: center;
  font-weight: bold;
}

.mark-as-read-btn:hover,
.link:hover {
  background-color: #8a2be2;
  color: #fff;
  transform: scale(1.05);
}

/* Notification Badge (Optional) */
.notification-item.unread::before, .unread-message-item.unread::before {
  content: '';
  position: absolute;
  top: 10px;
  right: 10px;
  width: 10px;
  height: 10px;
  background: #ffcc00;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(255, 204, 0, 0.8);
  animation: pulse 1.5s infinite ease-in-out;
}

/* Pulse Animation */
@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.3);
    opacity: 0.7;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
/* Gradient Background for Stat Cards 
.stat-card {
    background: linear-gradient(135deg, #8A2BE2, #4B0082); /* Purple gradient 
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
*/
.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.4);
}

/* Gradient Background for Notifications & Messages */
#notifications-container, #unread-messages-container {
    background: linear-gradient(145deg, #6A5ACD, #483D8B); 
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#notifications-container:hover, #unread-messages-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* Gradient for Task Manager */
#todo-container {
    background: linear-gradient(135deg, #7B68EE, #9370DB);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#todo-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
}
/* Motivation Widget Styles */
.motivation-widget {
  background: linear-gradient(to bottom right, #6a11cb, #2575fc); /* Purple gradient */
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-top: 20px;
    animation: fadeIn 1s ease-in-out;
}

.motivation-widget:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
}

.motivation-widget h2 {
    font-size: 24px;
    margin-bottom: 10px;
    letter-spacing: 1px;
}

#quote-text {
    font-size: 18px;
    font-style: italic;
    margin: 10px 0;
    line-height: 1.5;
}

#quote-author {
    font-size: 16px;
    font-weight: bold;
    color: #FFD700; /* Golden touch for the author's name */
}
/* Blinking Cursor Effect */
#quote-text::after {
    content: "|";
    animation: blink 0.7s infinite;
    color: #FFD700; /* Golden color for the cursor */
    font-weight: bold;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
/* Quick Post Button */
#quick-post-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #8A2BE2, #4B0082);
    color: #fff;
    border: none;
    padding: 15px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    transition: transform 0.3s, background 0.3s;
    z-index: 1000;
}

#quick-post-btn:hover {
    transform: scale(1.1);
    background: linear-gradient(135deg, #6A0DAD, #4B0082);
}

/* Quick Post Modal */
.modal {
    display: none; 
    position: fixed; 
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: linear-gradient(135deg, #7B68EE, #9370DB);
    padding: 20px;
    border-radius: 15px;
    width: 90%;
    max-width: 400px;
    color: #fff;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
    animation: fadeIn 0.5s ease;
}

/* Modal Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Close Button */
.close-btn {
    float: right;
    font-size: 24px;
    cursor: pointer;
    color: #FFD700;
}

.close-btn:hover {
    color: #FF4500;
}

/* Form Styling */
#quick-post-form input,
#quick-post-form textarea {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: none;
    border-radius: 8px;
    background: #fff;
    color: #333;
    font-size: 16px;
    resize: none;
}

#quick-post-form button {
    background: #6A0DAD;
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

#quick-post-form button:hover {
    background: #4B0082;
}
/* Chart Container */
.chart-container {
    background: linear-gradient(135deg, #4B0082, #8A2BE2);
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    margin-top: 20px;
    color: #fff;
    transition: transform 0.3s;
}

.chart-container:hover {
    transform: translateY(-5px);
}

.chart-container h2 {
    text-align: center;
    margin-bottom: 10px;
    font-size: 22px;
    letter-spacing: 1px;
}
/* Analytics Panel Styles */
.analytics-panel {
    background: linear-gradient(135deg, #4B0082, #8A2BE2);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
    transition: transform 0.3s;
}

.analytics-panel:hover {
    transform: translateY(-5px);
}

.analytics-panel h2, .chart-box h3, .top-posts h3 {
    text-align: center;
    margin-bottom: 10px;
}

.chart-box {
    margin-bottom: 20px;
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 10px;
}

.top-posts ul {
    list-style: none;
    padding: 0;
}

.top-posts li {
    background: rgba(255, 255, 255, 0.1);
    margin: 10px 0;
    padding: 10px;
    border-radius: 8px;
    transition: transform 0.3s;
}

.top-posts li:hover {
    transform: scale(1.03);
    background: rgba(255, 255, 255, 0.2);
}
/* Recommended Users Panel */
.recommendations-panel {
    background: linear-gradient(135deg, #4B0082, #8A2BE2);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
    transition: transform 0.3s;
}

.recommendations-panel:hover {
    transform: translateY(-5px);
}

.recommendations-panel h2 {
    text-align: center;
    margin-bottom: 10px;
    font-size: 24px;
}

#recommendedUsersList {
    list-style: none;
    padding: 0;
}

#recommendedUsersList li {
    background: rgba(255, 255, 255, 0.1);
    margin: 10px 0;
    padding: 15px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    transition: transform 0.3s;
}

#recommendedUsersList li:hover {
    transform: scale(1.03);
}

/* Profile Picture */
.recommend-profile-pic {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #FFD700;
}

/* User Info */
.recommend-user-info {
    flex: 1;
}

/* Buttons */
.recommend-user-btn, .view-profile-link {
    background: #FFD700;
    color: #4B0082;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
    text-decoration: none;
    font-weight: bold;
}

.recommend-user-btn:hover, .view-profile-link:hover {
    background: #FFB800;
    color: #fff;
}
.suggested-posts-panel {
    background: linear-gradient(135deg, #6A0DAD, #9370DB);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
    transition: transform 0.3s;
}

.suggested-posts-panel:hover {
    transform: translateY(-5px);
}

#suggestedPostsList li {
    background: rgba(255, 255, 255, 0.1);
    margin: 10px 0;
    padding: 15px;
    border-radius: 10px;
    transition: transform 0.3s;
}

#suggestedPostsList li:hover {
    transform: scale(1.03);
}
/* Common Styles for Post Cards */
.post-card {
    background: linear-gradient(135deg, #6A0DAD, #9370DB);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    margin: 15px 0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.post-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4);
}

/* Gradient Overlay Effect */
.post-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: skewX(-30deg);
    transition: all 0.5s ease;
}

.post-card:hover::before {
    left: 100%;
}

/* Post Title */
.post-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
    transition: color 0.3s ease;
}

.post-card:hover .post-title {
    color: #FFD700;
}

/* Engagement Metrics */
.engagement-metrics {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
    font-size: 14px;
    opacity: 0.9;
}

.engagement-metrics span {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Read More Button */
.read-more-btn {
    background: #FFD700;
    color: #4B0082;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s, transform 0.3s;
    text-decoration: none;
    display: inline-block;
}

.read-more-btn:hover {
    background: #FFB800;
    transform: scale(1.05);
}
/* Dashboard Widgets Container */
.dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 10px;
}

/* Widget Card Style */
.widget {
    background: linear-gradient(135deg, #4B0082, #8A2BE2);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: move;
    position: relative;
}

/* Drag Hover Effect */
.widget.dragging {
    opacity: 0.5;
    border: 2px dashed #FFD700;
}

/* Toggle Button for Widgets */
.toggle-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #FFD700;
    color: #4B0082;
    border: none;
    padding: 5px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s ease;
}

.toggle-btn:hover {
    background: #FFB800;
}
/* Voice-to-Text Post Section */
.voice-to-text-post {
    background: linear-gradient(135deg, #4B0082, #8A2BE2);
    color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    margin: 20px 0;
}

/* Buttons */
#startRecording {
    background: #FFD700;
    color: #4B0082;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-top: 10px;
}

#startRecording:hover {
    background: #FFB800;
}

/* Recording Status */
#recordingStatus {
    font-style: italic;
    color: #FFD700;
    margin-top: 10px;
}
#readAloudBtn {
    background: #FFD700;
    color: #4B0082;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.3s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

#readAloudBtn:hover {
    background: #FFB800;
    transform: scale(1.05);
}
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme); ?>">

<!-- Loading Screen -->
<div id="loading-screen">
    <img src="assets/Elevate -Your Chance to be more-.jpg" alt="Elevate Logo" />
</div>
    
        <!-- Sidebar -->
        <aside style="height: 100%; overflow-y: auto" class="sidebar">
        <a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;"></a>
            <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
            <nav>
                <ul>
                  <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="search_page.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-search"></i>  Search User</a></li>
                    <li><a href="public_posts.php"><i class="fas fa-file-alt"></i>  All Posts</a></li>
                    <li><a href="create_post.php"><i class="fas fa-pencil"></i>Create Post</a></li>
                    <li><a href="groups.php"><i class="fas fa-users"></i>Groups</a></li>
                    <li><a href="my_posts.php"><i class="fas fa-file"></i>My Posts</a></li>
                    <li><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i> Bookmarks</a></li>
                    <li><a href="leaderboards.php"><i class="fas fa-trophy"></i> Leaderboards</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                </ul>
            </nav>
        </aside>
        

        <!-- Main Content -->
        <main  class="content">
        <header class="animate-on-scroll user">
        <a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><img class="animate-on-scroll" src="<?php echo $user['profile_picture']; ?>" width="100px" height="100px" style="border-radius: 50%;"></a>
            <h2 class="animate-on-scroll"><?php echo $_SESSION['name']; ?></h2>
            <p class="animate-on-scroll"><?php echo $user['bio']; ?></p>
            </header><br>
           <header class="animate-on-scroll header">
           <h1 class="animate-on-scroll"><i class="fas fa-home"></i> Dashboard</h1>
            <p id="welcomeMessage" class="animate-on-scroll">Welcome to Elevate! Let's inspire and grow together.</p>
            </header>
            
            <section class="motivation-widget animate-on-scroll">
                <h2>✨ Daily Motivation ✨</h2>
                <p id="quote-text">Loading your inspiration...</p>
                <small id="quote-author"></small>
            </section>

            <br>
            <ul class="nav">
              <a><li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="#notifications-container"><i class="fas fa-bell"></i><span id="notification-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="#unread-messages-container"><i class="fas fa-envelope"></i><span id="unread-message-count" class="count-badge">0</span></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>

            <!--<button id="readAloudBtn">🔊 Read Aloud</button>-->
      <section class="stats">
        <div class="stat-card">
          <i class="fas fa-pencil-alt"></i>
          <h3 class="animate-on-scroll">Total Posts</h3>
          <p class="animate-on-scroll count" data-count=" <?php if ($posts_count > 0): ?><?= $posts_count ?><?php endif; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-heart"></i>
          <h3 class="animate-on-scroll">Total Likes</h3>
          <p class="animate-on-scroll count"  data-count="<?php if ($likes_count > 0): ?><?= $likes_count ?><?php endif; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-comments"></i>
          <h3 class="animate-on-scroll">Total Comments</h3>
          <p class="animate-on-scroll count" data-count="<?php if ($comments_count > 0): ?><?= $comments_count ?><?php endif; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-eye"></i>
          <h3 class="animate-on-scroll">Total Views</h3>
          <p class="animate-on-scroll count" data-count="<?php if ($views_count > 0): ?><?= $views_count ?><?php endif; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-heart"></i>
          <h3 class="animate-on-scroll">Post Likes</h3>
          <p class="animate-on-scroll count" id="total-likes">0</p>
        </div>
       <!-- <div class="stat-card">
          <i class="fas fa-comments"></i>
          <h3 class="animate-on-scroll">Posts Comments</h3>
          <p class="animate-on-scroll count" id="total-comments">0</p>
        </div> -->
        <div class="stat-card">
          <i class="fas fa-eye"></i>
          <h3 class="animate-on-scroll">Posts Views</h3>
          <p class="animate-on-scroll count" id="total-views">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-users"></i>
          <h3 class="animate-on-scroll">Total Followers</h3>
          <p class="animate-on-scroll count" data-count="<?php if ($followers_count > 0): ?><?= $followers_count ?><?php endif; ?>">0</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-user-plus"></i>
          <h3 class="animate-on-scroll">Total Following</h3>
          <p class="animate-on-scroll count" data-count="<?php if ($following_count > 0): ?><?= $following_count ?><?php endif; ?>">0</p>
        </div>
      </section>
      
      <br><br>

      
      <section class="dashboard-widgets" id="dashboardWidgets">

    <section data-id="analytics-widget" class="widget analytics-panel animate-on-scroll">
        <h2>📊 Advanced Analytics</h2>
        
        <!-- Heatmap -->
        <div class="chart-box">
            <h3>🔥 Engagement Heatmap</h3>
            <canvas id="heatmapChart"></canvas>
        </div>

        <!-- Follower Growth -->
        <div class="chart-box">
            <h3>🚀 Follower Growth Over Time</h3>
            <canvas id="followerGrowthChart"></canvas>
        </div>

        <!-- Top Posts -->
        <div class="top-posts">
            <h3>🏆 Top-Performing Posts</h3>
            <ul id="topPostsList"></ul>
        </div>
    </section>

<section data-id="recommendations-widget" class="widget recommendations-panel animate-on-scroll">
    <h2>🤝 People You May Know</h2>
    <ul id="recommendedUsersList"></ul>
</section>

<section data-id="suggested-posts-widget" class="widget suggested-posts-panel animate-on-scroll">
    <h2>✨ Suggested Posts for You</h2>
    <ul id="suggestedPostsList"></ul>
</section>

<section data-id="posts-activity-widget" class="widget chart-container animate-on-scroll">
        <h2>📈 Your Posting Activity</h2>
        <canvas id="postsChart"></canvas>
    </section>

    <section data-id="engagement-overview-widget" class="widget chart-container animate-on-scroll">
        <h2>📊 Engagement Overview</h2>
        <canvas id="engagementChart"></canvas>
    </section>

    <div data-id="task-manager-widget" class="widget animate-on-scroll" id="todo-container">
        <h2 style=" color: #333;" class="animate-on-scroll">Task Manager</h2><br>
        <form class="animate-on-scroll" id="todo-form">
          <input class="animate-on-scroll" type="text" id="todo-input" placeholder="Add a new task..." required>
          <button class="animate-on-scroll" type="submit">Add Task</button>
        </form>
      <ul class="animate-on-scroll" id="todo-list"></ul>
      </div>
</section>

<div class="animate-on-scroll" id="notifications-container"><br>
          <h3 class="animate-on-scroll" >Notifications <i class="fas fa-bell"></i></h3>
          <ul class="animate-on-scroll" id="notifications-list"></ul></div>
      <br><br>
      <div class="animate-on-scroll" id="unread-messages-container"><br>
        <h3 class="animate-on-scroll">Unread Messages <i class="fas fa-envelope"></i></h3>
        <ul class="animate-on-scroll" id="unread-messages-list"></ul>
    </div>

      

   

      
      
     <!-- <section class="animate-on-scroll calendar-widget">
            <h2 class="animate-on-scroll" style="color: initial">Calendar</h2>
            <div class="animate-on-scroll" id="calendar"></div>
          </section>-->
      <div class="animate-on-scroll weather-widget">
        <h2 class="animate-on-scroll">Weather</h2>
        <p class="animate-on-scroll">🌤️ Sunny, 25°C</p>
      </div>

      <ul class="nav">
              <li class="animate-on-scroll icon"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i></a></li>
              <li class="animate-on-scroll icon"><a href="search_page.php"><i class="fas fa-search"></i></a></li>
              <li class="animate-on-scroll icon"><a href="#notifications-container"><i class="fas fa-bell"></i></a></li>
              <li class="animate-on-scroll icon"><a href="#unread-messages-container"><i class="fas fa-envelope"></i></a></li>
              <li class="animate-on-scroll icon"><a href="public_posts.php"><i class="fas fa-file-alt"></i></a></li>
              <li class="animate-on-scroll icon"><a href="create_post.php"><i class="fas fa-pen"></i></a></li>
              <li class="animate-on-scroll icon"><a href="groups.php"><i class="fas fa-users"></i></a></li>
              <li class="animate-on-scroll icon"><a href="my_posts.php"><i class="fas fa-file"></i></a></li>
              <li class="animate-on-scroll icon"><a href="bookmarked_posts.php"><i class="fas fa-bookmark"></i></a></li>
              <li class="animate-on-scroll icon"><a href="leaderboards.php"><i class="fas fa-trophy"></i></a></li>
              <li class="animate-on-scroll icon"><a href="settings.php"><i class="fas fa-cog"></i></a></li>
              <li class="animate-on-scroll icon"><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul><br>
            <br>
            <br>
            <br>
                      <!-- Quick Post Button -->
          <button id="quick-post-btn" title="Quick Post">
              <i class="fas fa-pen"></i>
          </button>

          <!-- Quick Post Modal -->
          <div id="quick-post-modal" class="modal">
              <div class="modal-content">
                  <span class="close-btn">&times;</span>
                  <h2>Create a Quick Post ✍️</h2>
                  <form id="quick-post-form">
                      <input type="text" name="title" placeholder="Post Title" required>
                      <textarea name="content" placeholder="Write your post here..." required></textarea>
                      <button type="submit">Publish</button>
                  </form>
                  <p id="post-status" style="display: none; color: #FFD700; text-align: center;"></p>
              </div>
          </div>
        </main>
    </div>
    <script>

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute("href")).scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    });
}); 

window.addEventListener("load", () => {
    const loadingScreen = document.getElementById("loading-screen");

    // Ensure loading screen stays for at least 3 seconds
    setTimeout(() => {
        loadingScreen.classList.add("fade-out");
    }, 2000); // 2 seconds
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


        
        document.addEventListener('DOMContentLoaded', () => {
  const menuItems = document.querySelectorAll('.menu-item');

  menuItems.forEach(item => {
    item.addEventListener('click', () => {
      // Remove active class from all
      menuItems.forEach(menu => menu.classList.remove('active'));

      // Add active class to the clicked menu item
      item.classList.add('active');
    });
  });
});
document.addEventListener('DOMContentLoaded', () => {
  const stats = {
    posts: 150,
    categories: 12,
    comments: 45,
    views: 10543
  };

  const statCards = document.querySelectorAll('.stat-card');
  
  // Update each stat card dynamically
  statCards.forEach(card => {
    const title = card.querySelector('h3').textContent.toLowerCase();
    const value = stats[title.replace(' ', '')];
    if (value) {
      card.querySelector('p').textContent = value.toLocaleString();
    }
  });
});
document.addEventListener('DOMContentLoaded', () => {
  const counters = document.querySelectorAll('.count');

  counters.forEach(counter => {
    const updateCount = () => {
      const target = +counter.getAttribute('data-count');
      const current = +counter.textContent;
      const increment = Math.ceil(target / 100); // Speed of the count-up

      if (current < target) {
        counter.textContent = current + increment;
        setTimeout(updateCount, 70); // Adjust speed here
      } else {
        counter.textContent = target.toLocaleString(); // Add commas for large numbers
      }
    };

    updateCount();
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const calendar = document.getElementById('calendar');
  const today = new Date();
  const currentMonth = today.getMonth();
  const currentYear = today.getFullYear();
  const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  function renderCalendar(month, year) {
    // Clear the calendar
    calendar.innerHTML = '';

    // Add day headers
    daysOfWeek.forEach(day => {
      const header = document.createElement('div');
      header.textContent = day;
      header.className = 'header';
      calendar.appendChild(header);
    });

    // Get the first day of the month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Add blank spaces for days before the first day
    for (let i = 0; i < firstDay; i++) {
      const empty = document.createElement('div');
      calendar.appendChild(empty);
    }

    // Add the days of the month
    for (let day = 1; day <= daysInMonth; day++) {
      const dayElement = document.createElement('div');
      dayElement.textContent = day;

      // Highlight today
      if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
        dayElement.className = 'today';
      }

      calendar.appendChild(dayElement);
    }
  }

  // Render the current month's calendar
  renderCalendar(currentMonth, currentYear);
});
document.addEventListener("DOMContentLoaded", function () {
    const todoForm = document.getElementById("todo-form");
    const todoInput = document.getElementById("todo-input");
    const todoList = document.getElementById("todo-list");

    // Fetch tasks
    function fetchTodos() {
        fetch("fetch_todos.php")
            .then(response => response.json())
            .then(todos => {
                todoList.innerHTML = "";
                todos.forEach(todo => {
                    const listItem = document.createElement("li");
                    listItem.innerHTML = `
                        <input type="checkbox" class="todo-checkbox" data-id="${todo.id}" ${todo.is_completed == 1 ? "checked" : ""}>
                        <span class="todo-task ${todo.is_completed == 1 ? "completed" : ""}">${todo.task}</span>
                        <button class="delete-todo-btn" data-id="${todo.id}">Delete</button>
                    `;
                    todoList.appendChild(listItem);
                });
            })
            .catch(error => console.error("Error fetching todos:", error));
    }

    // Add task
    todoForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const task = todoInput.value.trim();
        if (task) {
            fetch("add_todo.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task=${encodeURIComponent(task)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTodos();
                        todoInput.value = "";
                    }
                })
                .catch(error => console.error("Error adding todo:", error));
        }
    });

    // Update task
    todoList.addEventListener("change", function (e) {
        if (e.target.classList.contains("todo-checkbox")) {
            const taskId = e.target.dataset.id;
            const isCompleted = e.target.checked ? 1 : 0;

            fetch("update_todo.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}&is_completed=${isCompleted}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTodos();
                    }
                })
                .catch(error => console.error("Error updating todo:", error));
        }
    });

    // Delete task
    todoList.addEventListener("click", function (e) {
        if (e.target.classList.contains("delete-todo-btn")) {
            const taskId = e.target.dataset.id;

            fetch("delete_todo.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTodos();
                    }
                })
                .catch(error => console.error("Error deleting todo:", error));
        }
    });

    // Initial fetch
    fetchTodos();
});
document.addEventListener('DOMContentLoaded', function () {
    const totalLikesElement = document.getElementById('total-likes');

    // Fetch total likes
    function fetchTotalLikes() {
        fetch('fetch_user_likes.php')
            .then(response => response.json())
            .then(data => {
                totalLikesElement.textContent = data.total_likes;
            })
            .catch(error => console.error('Error fetching total likes:', error));
    }

    // Initial fetch
    fetchTotalLikes();
});
document.addEventListener('DOMContentLoaded', function () {
    const totalCommentsElement = document.getElementById('total-comments');

    // Fetch total comments
    function fetchTotalComments() {
        fetch('fetch_user_comments.php')
            .then(response => response.json())
            .then(data => {
                totalCommentsElement.textContent = data.total_comments;
            })
            .catch(error => console.error('Error fetching total comments:', error));
    }

    // Initial fetch
    fetchTotalComments();
});
document.addEventListener('DOMContentLoaded', function () {
    const totalViewsElement = document.getElementById('total-views');

    // Fetch total views
    function fetchTotalViews() {
        fetch('fetch_user_views.php')
            .then(response => response.json())
            .then(data => {
                totalViewsElement.textContent = data.total_views;
            })
            .catch(error => console.error('Error fetching total views:', error));
    }

    // Initial fetch
    fetchTotalViews();
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

function timeAgo(timestamp) {
    const now = new Date();
    const postTime = new Date(timestamp);
    const diffInMinutes = Math.floor((now - postTime) / (1000 * 60));

    if (diffInMinutes < 1) {
        return 'Just now';
    } else if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes !== 1 ? 's' : ''} ago`;
    } else if (diffInMinutes < 1440) {
        const hours = Math.floor(diffInMinutes / 60);
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    } else {
        const days = Math.floor(diffInMinutes / 1440);
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    }
}

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

                const markBtn = document.createElement('button');
                markBtn.textContent = '✔Mark all read';
                markBtn.className = 'mark-all-as-read mark-btn';
                notificationsList.appendChild(markBtn);

                

                if (notifications.length > 0) {
                    notifications.forEach(notification => {
                      const time = timeAgo(notification.created_at);
                        const listItem = document.createElement('li');
                        listItem.className = 'notification-item unread';
                        listItem.innerHTML = `
                            <img src="${notification.profile_picture || 'default-profile.png'}" alt="${notification.sender_name}" class="sender-pic">
                            <div class="notification-content">
                              <strong class="message"><a class="message" href="user_profile.php?id=${notification.sender_id}">${notification.sender_name}</a> - ${notification.message}</strong>
                              <small class="time">${time}</small>
                            </div>
                            <div class="notification-actions">
                              <button class="mark-as-read-btn" data-notification-id="${notification.id}">Mark as Read</button>
                              <a class="link" href=${notification.link}>View</a>
                            </div>
                        `;
                        
                        
                        notificationsList.appendChild(listItem);
                        
                    });
                } else {
                    notificationsList.innerHTML = '<li>No new notifications</li>';
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

      // Mark notification as read
      notificationsList.addEventListener('click', function (event) {
        if (event.target.classList.contains('mark-as-read-btn')) {
            const notificationId = event.target.dataset.notificationId;

            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications(); // Refresh the notifications list
                } else {
                    alert(data.message || 'Failed to mark notification as read');
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        
    });

    // Mark All notification as read
    notificationsList.addEventListener('click', function (event) {
        if (event.target.classList.contains('mark-all-as-read')) {
            const notificationId = event.target.dataset.notificationId;

            fetch('mark_all_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications(); // Refresh the notifications list
                } else {
                    alert(data.message || 'Failed to mark notification as read');
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        
    });

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
                      const time = timeAgo(msg.latest_message_time);
                        const listItem = document.createElement('li');
                        listItem.className = 'unread-message-item unread';
                        listItem.innerHTML = `
                            <img src="${msg.profile_picture || 'assets/default-photo.png'}" alt="${msg.sender_name}" class="sender-pic">
                            <div class="notification-content">
                              <strong class="message"><a class="sender-name" href="user_profile.php?id=${msg.sender_id}">${msg.sender_name}</a>(${msg.unread_count} unread)</strong>
                              <small class="time">${time}</small>
                            </div>
                            <div class="notification-actions">
                            <a class="link" href="chat.php?user_id=${msg.sender_id}" class="view-chat-link">View chat</a>
                            </div>
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
    const quotes = [
        { text: "Success is not final, failure is not fatal: It is the courage to continue that counts.", author: "Winston Churchill" },
        { text: "The only limit to our realization of tomorrow is our doubts of today.", author: "Franklin D. Roosevelt" },
        { text: "Believe you can and you're halfway there.", author: "Theodore Roosevelt" },
        { text: "Your time is limited, so don’t waste it living someone else’s life.", author: "Steve Jobs" },
        { text: "Do what you can, with what you have, where you are.", author: "Theodore Roosevelt" },
        { text: "Hustle in silence and let your success make the noise.", author: "Unknown" },
        { text: "Don’t watch the clock; do what it does. Keep going.", author: "Sam Levenson" },
        { text: "Great things never come from comfort zones.", author: "Unknown" }
    ];

    const quoteElement = document.getElementById("quote-text");
    const authorElement = document.getElementById("quote-author");

    const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];

    // Typewriter effect function
    function typeWriterEffect(text, element, delay = 50) {
        let index = 0;
        const interval = setInterval(() => {
            element.textContent += text.charAt(index);
            index++;
            if (index === text.length) {
                clearInterval(interval);
                // Display the author after the quote finishes
                setTimeout(() => {
                    authorElement.textContent = `— ${randomQuote.author}`;
                }, 500);
            }
        }, delay);
    }

    // Start the typewriter effect after a 2-second delay
    setTimeout(() => {
        quoteElement.textContent = ""; // Clear the placeholder
        typeWriterEffect(`"${randomQuote.text}"`, quoteElement);
    }, 2000);
});
document.addEventListener("DOMContentLoaded", function () {
    const quickPostBtn = document.getElementById("quick-post-btn");
    const quickPostModal = document.getElementById("quick-post-modal");
    const closeBtn = document.querySelector(".close-btn");

    // Open the modal
    quickPostBtn.addEventListener("click", () => {
        quickPostModal.style.display = "flex";
    });

    // Close the modal
    closeBtn.addEventListener("click", () => {
        quickPostModal.style.display = "none";
    });

    // Close modal when clicking outside the content
    window.addEventListener("click", (event) => {
        if (event.target === quickPostModal) {
            quickPostModal.style.display = "none";
        }
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const quickPostForm = document.getElementById("quick-post-form");
    const postStatus = document.getElementById("post-status");
    const quickPostModal = document.getElementById("quick-post-modal");

    quickPostForm.addEventListener("submit", function (e) {
        e.preventDefault(); // Prevent form from submitting the traditional way

        const formData = new FormData(quickPostForm);

        fetch("quick_post.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                postStatus.style.display = "block";
                postStatus.textContent = "✅ Post published successfully!";
                postStatus.style.color = "#32CD32"; // Green color for success

                // Clear form fields
                quickPostForm.reset();

                // Close modal after 2 seconds
                setTimeout(() => {
                    postStatus.style.display = "none";
                    quickPostModal.style.display = "none";
                }, 2000);
            } else {
                postStatus.style.display = "block";
                postStatus.textContent = "❌ Error: " + data.message;
                postStatus.style.color = "#FF4500"; // Red color for error
            }
        })
        .catch(error => {
            console.error("Error:", error);
            postStatus.style.display = "block";
            postStatus.textContent = "❌ An error occurred. Please try again.";
            postStatus.style.color = "#FF4500";
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('postsChart').getContext('2d');

    // Fetch post data from PHP
    fetch('fetch_post_data.php')
        .then(response => response.json())
        .then(data => {
            const postDates = data.map(item => item.date);
            const postCounts = data.map(item => item.count);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: postDates,
                    datasets: [{
                        label: 'Posts Over Time',
                        data: postCounts,
                        backgroundColor: 'rgba(138, 43, 226, 0.2)',
                        borderColor: '#8A2BE2',
                        borderWidth: 3,
                        pointBackgroundColor: '#FFD700',
                        tension: 0.4 // Smooth curves
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.2)' },
                            ticks: { color: '#fff' }
                        },
                        x: {
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            ticks: { color: '#fff' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { color: '#fff' }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching post data:', error));
});
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('engagementChart').getContext('2d');

    // Fetch engagement data
    fetch('fetch_engagement_data.php')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Likes', 'Comments', 'Views'],
                    datasets: [{
                        label: 'Engagement',
                        data: [data.likes, data.comments, data.views],
                        backgroundColor: ['#8A2BE2', '#FF4500', '#32CD32'],
                        borderColor: ['#6A0DAD', '#B22222', '#228B22'],
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#fff' },
                            grid: { color: 'rgba(255, 255, 255, 0.2)' }
                        },
                        x: {
                            ticks: { color: '#fff' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { color: '#fff' }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching engagement data:', error));
});
document.addEventListener("DOMContentLoaded", function () {
    fetchHeatmapData();
    fetchFollowerGrowthData();
    fetchTopPosts();
    
    // 1. Engagement Heatmap
    function fetchHeatmapData() {
        fetch('fetch_heatmap_data.php')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('heatmapChart').getContext('2d');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.days,
                        datasets: data.hours.map((hourData, index) => ({
                            label: `${index}:00`,
                            data: hourData,
                            backgroundColor: `rgba(138, 43, 226, ${(index + 1) / 10})`
                        }))
                    },
                    options: {
                        scales: {
                            x: { stacked: true, ticks: { color: '#fff' } },
                            y: { stacked: true, ticks: { color: '#fff' } }
                        },
                        plugins: {
                            legend: { labels: { color: '#fff' } }
                        }
                    }
                });
            });
    }

    // 2. Follower Growth Chart
    function fetchFollowerGrowthData() {
        fetch('fetch_follower_growth.php')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('followerGrowthChart').getContext('2d');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.dates,
                        datasets: [{
                            label: 'Followers',
                            data: data.counts,
                            borderColor: '#FFD700',
                            backgroundColor: 'rgba(255, 215, 0, 0.2)',
                            borderWidth: 2,
                            tension: 0.3
                        }]
                    },
                    options: {
                        scales: {
                            y: { beginAtZero: true, ticks: { color: '#fff' } },
                            x: { ticks: { color: '#fff' } }
                        },
                        plugins: {
                            legend: { labels: { color: '#fff' } }
                        }
                    }
                });
            });
    }

    // 3. Top-Performing Posts
    function fetchTopPosts() {
        fetch('fetch_top_posts.php')
            .then(response => response.json())
            .then(posts => {
                const postList = document.getElementById('topPostsList');
                postList.innerHTML = '';

                if (posts.length === 0) {
                    postList.innerHTML = '<li>No top posts available.</li>';
                    return;
                }

                posts.forEach(post => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('post-card');  // Apply the card style

                    listItem.innerHTML = `
                        <div class="post-title">${post.title}</div>
                        <div class="engagement-metrics">
                            <span>❤️ ${post.likes}</span>
                            <span>💬 ${post.comments}</span>
                            <span>👀 ${post.views}</span>
                        </div>
                        <a href="view_post2.php?id=${post.id}" class="read-more-btn">Read More</a>
                    `;
                    postList.appendChild(listItem);
                });
            })
            .catch(error => console.error('Error fetching top posts:', error));
    }
});
document.addEventListener("DOMContentLoaded", function () {
    fetchRecommendedUsers();

    function fetchRecommendedUsers() {
        fetch('fetch_recommended_users.php')
            .then(response => response.json())
            .then(users => {
                const userList = document.getElementById('recommendedUsersList');
                userList.innerHTML = '';

                if (users.length === 0) {
                    userList.innerHTML = '<li>No recommendations at the moment.</li>';
                    return;
                }

                users.forEach(user => {
                    const listItem = document.createElement('li');
                    listItem.innerHTML = `
                    <a href="user_profile.php?id=${user.id}"><img src="${user.profile_picture || 'assets/default-photo.png'}" alt="${user.name}" class="recommend-profile-pic"></a>
                        <div class="recommend-user-info">
                            <strong>${user.name}</strong><br>
                            <small>${user.mutual_groups} mutual groups</small>
                        </div>
                        <button class="recommend-user-btn" data-user-id="${user.id}">Follow</button>
                    `;
                    userList.appendChild(listItem);
                });
            })
            .catch(error => console.error('Error fetching recommended users:', error));
    }

    // Follow button functionality
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('recommend-user-btn')) {
            const userId = event.target.dataset.userId;

            fetch('follow_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    event.target.textContent = 'Following';
                    event.target.disabled = true;
                }
            });
        }
    });
});
document.addEventListener("DOMContentLoaded", function () {
    fetchSuggestedPosts();

    function fetchSuggestedPosts() {
        fetch('fetch_suggested_posts.php')
            .then(response => response.json())
            .then(posts => {
                const postList = document.getElementById('suggestedPostsList');
                postList.innerHTML = '';

                if (posts.length === 0) {
                    postList.innerHTML = '<li>No suggested posts right now.</li>';
                    return;
                }

                posts.forEach(post => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('post-card');  // Apply the card style

                    listItem.innerHTML = `
                        <div class="post-title">${post.title}</div>
                        <div class="engagement-metrics">
                            <span>❤️ ${post.likes}</span>
                            <span>💬 ${post.comments}</span>
                            <span>👀 ${post.views}</span>
                        </div>
                        <a href="view_post2.php?id=${post.id}" class="read-more-btn">Read More</a>
                    `;
                    postList.appendChild(listItem);
                });
            })
            .catch(error => console.error('Error fetching suggested posts:', error));
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const widgetsContainer = document.getElementById("dashboardWidgets");
    const widgets = document.querySelectorAll(".widget");

    // Load saved layout from localStorage
    loadWidgetLayout();

    widgets.forEach(widget => {
        widget.draggable = true;

        widget.addEventListener("dragstart", (e) => {
            widget.classList.add("dragging");
            e.dataTransfer.setData("text/plain", widget.dataset.id);
        });

        widget.addEventListener("dragend", () => {
            widget.classList.remove("dragging");
            saveWidgetLayout(); // Save layout after drag-and-drop
        });

        
    });

    // Handle dropping
    widgetsContainer.addEventListener("dragover", (e) => e.preventDefault());
    widgetsContainer.addEventListener("drop", (e) => {
        e.preventDefault();
        const draggedId = e.dataTransfer.getData("text/plain");
        const draggedWidget = document.querySelector(`[data-id="${draggedId}"]`);
        const afterElement = getDragAfterElement(widgetsContainer, e.clientY);
        if (afterElement == null) {
            widgetsContainer.appendChild(draggedWidget);
        } else {
            widgetsContainer.insertBefore(draggedWidget, afterElement);
        }
    });

    // Determine widget placement while dragging
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll(".widget:not(.dragging)")];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // Save layout to localStorage
    function saveWidgetLayout() {
        const layout = [];
        document.querySelectorAll(".widget").forEach(widget => {
            layout.push({
                id: widget.dataset.id,
                visible: widget.style.display !== "none"
            });
        });
        localStorage.setItem("widgetLayout", JSON.stringify(layout));
    }

    // Load layout from localStorage
    function loadWidgetLayout() {
        const savedLayout = JSON.parse(localStorage.getItem("widgetLayout"));
        if (savedLayout) {
            savedLayout.forEach(layout => {
                const widget = document.querySelector(`[data-id="${layout.id}"]`);
                if (widget) {
                    widget.style.display = layout.visible ? "block" : "none";
                    document.getElementById("dashboardWidgets").appendChild(widget);
                }
            });
        }
    }
});
document.addEventListener("DOMContentLoaded", function () {
    // Fetch widget settings when the dashboard loads
    fetch('get_widget_settings.php')
        .then(response => response.json())
        .then(settings => {
            const widgets = document.querySelectorAll('.widget');

            widgets.forEach(widget => {
                const widgetId = widget.dataset.id;
                const isVisible = settings[widgetId] || false; // Hidden by default
                widget.style.display = isVisible ? 'block' : 'none';
            });
        })
        .catch(error => console.error('Error fetching widget settings:', error));
});
document.addEventListener("DOMContentLoaded", function () {
    const startRecordingBtn = document.getElementById("startRecording");
    const postContent = document.getElementById("postContent");
    const recordingStatus = document.getElementById("recordingStatus");

    // Check if SpeechRecognition is supported
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.lang = "en-US";
        recognition.interimResults = true; // Enable real-time transcription
        recognition.continuous = true;     // Keep listening until manually stopped

        let isRecording = false;
        let finalTranscript = ""; // Store the final confirmed transcript

        // Toggle voice input on button click
        startRecordingBtn.addEventListener("click", () => {
            if (!isRecording) {
                recognition.start();
                recordingStatus.style.display = "block";
                startRecordingBtn.textContent = "🛑 Stop Voice Input";
                isRecording = true;
            } else {
                recognition.stop();
                recordingStatus.style.display = "none";
                startRecordingBtn.textContent = "🎙️ Start Voice Input";
                isRecording = false;
            }
        });

        // Handle the speech recognition result
        recognition.addEventListener("result", (event) => {
            let interimTranscript = ""; // Temporary transcript for real-time results

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript + " "; // Append final results
                } else {
                    interimTranscript += transcript; // Show interim results while speaking
                }
            }

            // Combine final and interim transcripts in the textarea
            postContent.value = finalTranscript + interimTranscript;
        });

        // Ensure recognition auto-restarts if interrupted
        recognition.addEventListener("end", () => {
            if (isRecording) {
                recognition.start(); // Restart if still recording
            }
        });
    } else {
        startRecordingBtn.disabled = true;
        startRecordingBtn.textContent = "🎙️ Voice Input Not Supported";
        console.warn("SpeechRecognition is not supported in this browser.");
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const readAloudBtn = document.getElementById("readAloudBtn");

    readAloudBtn.addEventListener("click", () => {
        const selectedText = window.getSelection().toString().trim();

        if (selectedText) {
            const utterance = new SpeechSynthesisUtterance(selectedText);
            utterance.lang = "en-US"; // Language setting
            utterance.rate = 1;       // Speed of speech (1 is normal)
            utterance.pitch = 1;      // Pitch of voice

            speechSynthesis.speak(utterance);
        } else {
            alert("Please select some text to read aloud.");
        }
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const welcomeMessage = "<?php echo isset($_SESSION['welcome_message']) ? $_SESSION['welcome_message'] : ''; ?>";
    const welcomeBackMessage = "<?php echo isset($_SESSION['welcome_back_message']) ? $_SESSION['welcome_back_message'] : ''; ?>";

    let messageToRead = welcomeMessage || welcomeBackMessage;

    if (messageToRead) {
        const utterance = new SpeechSynthesisUtterance(messageToRead);
        utterance.lang = "en-US";
        utterance.rate = 1;
        utterance.pitch = 1.1;

        speechSynthesis.speak(utterance);

        // Clear the session message
        fetch('clear_welcome_message.php');
    }
});
    </script>
</body>
</html>
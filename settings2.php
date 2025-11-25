<?php
include 'includes/db.php';
session_start();

// Check if the user is logged in
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


$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settingd</title>
</head>
<style>
    .content {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.sidebar {
    flex: 1;
    max-width: 250px;
}

.main {
    flex: 3;
}
       /* Slider Toggle */
       .theme-toggle {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.theme-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #2196f3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}
/* Base Styles for Light and Dark Modes */
body.light {
    background-color: #f9f9f9;
    color: #333;
}

body.dark {
    background-color: #181818;
    color: #fff;
}

/* Main Section */
.main {
    padding: 20px;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

body.dark .main {
    background-color: #282828;
    box-shadow: 0 2px 5px rgba(255, 255, 255, 0.1);
}

/* Sidebar */
.sidebar {
    width: 250px;
    padding: 15px;
    background-color: #eeeeee;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

body.dark .sidebar {
    background-color: #202020;
    box-shadow: 0 2px 5px rgba(255, 255, 255, 0.1);
}

/* Buttons */
button {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    background-color: #007bff;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #0056b3;
}

body.dark button {
    background-color: #1e90ff;
}

body.dark button:hover {
    background-color: #104e8b;
}

/* Headings and Links */
h1, h2, h3, h4, h5, h6 {
    color: inherit;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

body.dark a {
    color: #1e90ff;
}
</style>
<body>
    <div class="settings-page">
        <h1>User Settings</h1>
        <div class="settings-section">
            <h3>Theme Settings</h3>
            <label class="theme-toggle">
                <input type="checkbox" id="theme-toggle">
                <span class="slider"></span>
            </label>
            <p id="theme-status">Light Mode</p>
        </div>
    
        <div class="content">
            <div class="sidebar">
                <h2>Sidebar</h2>
                <p>This is the sidebar content.</p>
                <button>Sidebar Button</button>
            </div>
            <div class="main">
                <h2>Main Section</h2>
                <p>This is the main content area.</p>
                <button>Main Section Button</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");
    const themeStatus = document.getElementById("theme-status");

    // Check the current theme from the body class
    const currentTheme = document.body.classList.contains("dark") ? "dark" : "light";
    themeToggle.checked = currentTheme === "dark";
    themeStatus.textContent = currentTheme === "dark" ? "Dark Mode" : "Light Mode";

    // Update styles dynamically on toggle
    themeToggle.addEventListener("change", function () {
        const selectedTheme = themeToggle.checked ? "dark" : "light";

        // Apply theme to body
        document.body.className = selectedTheme;
        themeStatus.textContent = selectedTheme === "dark" ? "Dark Mode" : "Light Mode";

        // Send the theme preference to the server
        fetch("update_theme.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `theme=${selectedTheme}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("Failed to update theme. Please try again.");
            }
        })
        .catch(error => console.error("Error:", error));
    });
});
    </script>
</body>
</html>
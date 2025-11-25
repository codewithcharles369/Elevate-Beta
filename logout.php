<?php
session_start();

// Set the goodbye message
if (isset($_SESSION['name'])) {
    $_SESSION['goodbye_message'] = "Goodbye, " . htmlspecialchars($_SESSION['name']) . "! See you again soon on Elevate!";
}

// Destroy the session after redirecting to the goodbye page
header("Location: goodbye.php");
exit();
?>
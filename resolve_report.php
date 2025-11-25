<?php
include 'includes/db.php'; // Include database connection
session_start();

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin'){
    header("Location: login.php");
    exit;
}

// Check if report ID is provided
if (isset($_GET['id'])) {
    $report_id = intval($_GET['id']); // Sanitize the input

    // Mark the report as resolved in the database
    $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
    if ($stmt->execute([$report_id])) {
        header("Location: admin_reports.php?message=Report resolved successfully");
        exit;
    } else {
        header("Location: admin_reports.php?error=Could not resolve the report");
        exit;
    }
} else {
    header("Location: admin_reports.php?error=Invalid request");
    exit;
}
?>
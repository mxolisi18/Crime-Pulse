<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_register.php");
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: manage_reports.php");
    exit();
}

// Validate required fields
$report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

if (!$report_id || !$message) {
    $_SESSION['error'] = "Missing required fields.";
    header("Location: view_report.php?id=" . $report_id);
    exit();
}

try {
    // Insert the note
    $stmt = $pdo->prepare("
        INSERT INTO messages (report_id, user_id, message_text, is_from_reporter, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$report_id, $_SESSION['user_id'], $message]);

    $_SESSION['success'] = "Note added successfully.";
} catch (PDOException $e) {
    error_log('Error adding note: ' . $e->getMessage());
    $_SESSION['error'] = "Failed to add note.";
}

// Redirect back to the report view
header("Location: view_report.php?id=" . $report_id);
exit();
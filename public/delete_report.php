<?php
session_start();
require_once __DIR__ . '/../config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: track_page.php');
    exit;
}

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login_register.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$csrf = $_POST['csrf_token'] ?? '';

if (empty($id) || empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    $_SESSION['report_error'] = 'Invalid request.';
    header('Location: track_page.php');
    exit;
}

try {
    // Verify ownership
    $stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user_id]);
    $report = $stmt->fetch();

    if (!$report) {
        $_SESSION['report_error'] = 'Report not found or access denied.';
        header('Location: track_page.php');
        exit;
    }

    // If there's any media rows, delete files and rows
    $stmt = $pdo->prepare('SELECT * FROM media WHERE report_id = ?');
    $stmt->execute([$id]);
    $medias = $stmt->fetchAll();
    foreach ($medias as $m) {
        $path = __DIR__ . '/../' . $m['file_path'];
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    $stmt = $pdo->prepare('DELETE FROM media WHERE report_id = ?');
    $stmt->execute([$id]);

    // Delete the report
    $stmt = $pdo->prepare('DELETE FROM reports WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);

    $_SESSION['report_success'] = 'Report deleted successfully.';
    header('Location: track_page.php');
    exit;

} catch (PDOException $e) {
    error_log('Delete report error: ' . $e->getMessage());
    $_SESSION['report_error'] = 'Failed to delete report. Please try again later.';
    header('Location: track_page.php');
    exit;
}

?>

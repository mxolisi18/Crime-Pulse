<?php
session_start();
require_once __DIR__ . '/../config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login_register.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id <= 0) {
    header('Location: track_page.php');
    exit;
}

try {
    // Load report and verify ownership
    $stmt = $pdo->prepare("SELECT r.*, c.name as crime_type_name FROM reports r INNER JOIN crime_types c ON r.crime_type_id = c.id WHERE r.id = ? AND r.user_id = ? LIMIT 1");
    $stmt->execute([$id, $user_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        $_SESSION['report_error'] = 'Report not found or access denied.';
        header('Location: track_page.php');
        exit;
    }

    // Handle POST update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $crime_type = $_POST['crime_type'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $report_date = $_POST['incident_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $csrf = $_POST['csrf_token'] ?? '';

        if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            $_SESSION['report_error'] = 'Invalid request.';
            header('Location: edit_report.php?id=' . $id);
            exit;
        }

        if (empty($crime_type) || empty($location) || empty($report_date) || empty($description)) {
            $_SESSION['report_error'] = 'All fields are required.';
            header('Location: edit_report.php?id=' . $id);
            exit;
        }

        // Get or create crime type id
        $stmt = $pdo->prepare('SELECT id FROM crime_types WHERE name = ? LIMIT 1');
        $stmt->execute([$crime_type]);
        $ct = $stmt->fetch();
        if (!$ct) {
            $stmt = $pdo->prepare('INSERT INTO crime_types (name) VALUES (?)');
            $stmt->execute([$crime_type]);
            $crime_type_id = $pdo->lastInsertId();
        } else {
            $crime_type_id = $ct['id'];
        }

        // Handle optional new evidence upload
        $filePath = $report['encrypted_data'];
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName   = time() . '_' . basename($_FILES['evidence']['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $targetFile)) {
                // delete old file if exists
                if (!empty($filePath)) {
                    $old = __DIR__ . '/../' . $filePath;
                    if (file_exists($old)) @unlink($old);
                }
                $filePath = 'uploads/' . $fileName;
            }
        }

        // Update report
        $stmt = $pdo->prepare('UPDATE reports SET crime_type_id = ?, location = ?, report_date = ?, description = ?, encrypted_data = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$crime_type_id, $location, $report_date, $description, $filePath, $id, $user_id]);

        $_SESSION['report_success'] = 'Report updated successfully.';
        header('Location: track_page.php');
        exit;
    }

} catch (PDOException $e) {
    error_log('Edit report error: ' . $e->getMessage());
    $_SESSION['report_error'] = 'An error occurred. Please try again later.';
    header('Location: track_page.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report</title>
    <link rel="stylesheet" href="user-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-box { max-width: 700px; margin: 40px auto; }
    </style>
</head>
<body class="auth-page">
    <div class="container">
        <div class="form-box">
            <h2>Edit Report #<?= htmlspecialchars($report['id']); ?></h2>
            <?php if(isset($_SESSION['report_error'])) { echo "<p class='error-message'>" . $_SESSION['report_error'] . "</p>"; unset($_SESSION['report_error']); } ?>
            <form method="POST" action="edit_report.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= htmlspecialchars($report['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                <label>Crime Type</label>
                <input type="text" name="crime_type" value="<?= htmlspecialchars($report['crime_type_name']); ?>" required>

                <label>Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($report['location']); ?>" required>

                <label>Date of Incident</label>
                <input type="date" name="incident_date" value="<?= date('Y-m-d', strtotime($report['report_date'])); ?>" required>

                <label>Description</label>
                <textarea name="description" rows="6" required><?= htmlspecialchars($report['description']); ?></textarea>

                <label>Replace Evidence (optional)</label>
                <input type="file" name="evidence" accept="image/*,video/*">

                <div style="margin-top:15px">
                    <button type="submit">Update Report</button>
                    <a href="track_page.php" class="secondary-button">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

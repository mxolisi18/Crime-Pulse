<?php
session_start();
require_once __DIR__ . '/../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Only process POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect form data
    $crime_type_name = trim($_POST['crime_type'] ?? '');
    $location        = trim($_POST['location'] ?? '');
    $report_date     = $_POST['incident_date'] ?? '';
    $description     = trim($_POST['description'] ?? '');

    // Validation
    if (empty($crime_type_name) || empty($location) || empty($report_date) || empty($description)) {
        $_SESSION['report_error'] = 'All fields are required.';
        header('Location: user_page.php');
        exit;
    }

    // Optional file upload
    $fileName = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileName   = time() . '_' . basename($_FILES['evidence']['name']);
        $targetFile = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['evidence']['tmp_name'], $targetFile)) {
            $_SESSION['report_error'] = 'Failed to upload file.';
            header('Location: user_page.php');
            exit;
        }
    }

    try {
        // Get or create crime type ID
        $stmt = $pdo->prepare("SELECT id FROM crime_types WHERE name = ? LIMIT 1");
        $stmt->execute([$crime_type_name]);
        $crime_type = $stmt->fetch();

        if (!$crime_type) {
            $stmt = $pdo->prepare("INSERT INTO crime_types (name) VALUES (?)");
            $stmt->execute([$crime_type_name]);
            $crime_type_id = $pdo->lastInsertId();
        } else {
            $crime_type_id = $crime_type['id'];
        }

        // Generate passphrase
        $passphrase = bin2hex(random_bytes(16));
        $passphrase_hash = password_hash($passphrase, PASSWORD_DEFAULT);

        // Insert report
        $stmt = $pdo->prepare("
            INSERT INTO reports 
                (user_id, crime_type_id, location, report_date, description, status, passphrase_hash) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $user_id,
            $crime_type_id,
            $location,
            $report_date,
            $description,
            $passphrase_hash
        ]);

        $report_id = $pdo->lastInsertId();

        // If file was uploaded, insert into media table
if ($fileName) {
            // determine file type
            $fileType = 'document';
            $mime = $_FILES['evidence']['type'] ?? '';
            if (str_starts_with($mime, 'image/')) $fileType = 'image';
            elseif (str_starts_with($mime, 'video/')) $fileType = 'video';


    // insert into media table
    $stmt = $pdo->prepare("
        INSERT INTO media (report_id, file_path, file_type, file_size, uploaded_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $report_id,
        $file_path = 'uploads/' . $fileName,
        $file_type,
        $_FILES['evidence']['size'] ?? 0
    ]);
}

        $_SESSION['report_success'] = 'Your report has been submitted successfully!';
        header('Location: user_page.php');
        exit;

    } catch (PDOException $e) {
        error_log('Report submission error: ' . $e->getMessage());
        $_SESSION['report_error'] = 'Failed to submit report. Please try again later.';
        header('Location: user_page.php');
        exit;
    }

} else {
    header('Location: user_page.php');
    exit;
}
?>
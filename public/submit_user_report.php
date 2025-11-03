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
    $crime_type_id = $_POST['crime_type'] ?? '';
    $location      = trim($_POST['location'] ?? '');
    $report_date   = $_POST['incident_date'] ?? '';
    $description   = trim($_POST['description'] ?? '');

    // Validation
    if (empty($crime_type_id) || empty($location) || empty($report_date) || empty($description)) {
        $_SESSION['report_error'] = 'All fields are required.';
        header('Location: user_page.php');
        exit;
    }

    // Optional file upload
    $filePath = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileName   = time() . '_' . basename($_FILES['evidence']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['evidence']['tmp_name'], $targetFile)) {
            $filePath = 'uploads/' . $fileName;
        }
    }

    try {
        // Get or create crime type ID
        $stmt = $pdo->prepare("SELECT id FROM crime_types WHERE name = ? LIMIT 1");
        $stmt->execute([$crime_type_id]);
        $crime_type = $stmt->fetch();
        
        if (!$crime_type) {
            $stmt = $pdo->prepare("INSERT INTO crime_types (name) VALUES (?)");
            $stmt->execute([$crime_type_id]);
            $crime_type_id = $pdo->lastInsertId();
        } else {
            $crime_type_id = $crime_type['id'];
        }

        // Generate a random passphrase for the report
        $passphrase = bin2hex(random_bytes(16));
        $passphrase_hash = password_hash($passphrase, PASSWORD_DEFAULT);

        // Insert report with user_id
        $stmt = $pdo->prepare("
            INSERT INTO reports 
                (user_id, crime_type_id, location, report_date, description, status, encrypted_data, passphrase_hash) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([
            $user_id,          // Logged in user's ID
            $crime_type_id,
            $location,
            $report_date,
            $description,
            $filePath,
            $passphrase_hash
        ]);

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
    // Redirect if form not submitted
    header('Location: user_page.php');
    exit;
}
<?php
session_start();
require_once __DIR__ . '/../config.php';

// If user is logged in, they shouldn't use anonymous reporting
if (isset($_SESSION['user_id'])) {
    // Clear the session and redirect to anonymous report page
    session_unset();
    session_destroy();
    session_start();
}

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
        header('Location: anonymous_report.php');
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
            $filePath = 'uploads/' . $fileName; // relative path to store in DB
        }
    }

    try {
        // First, get or create the crime type ID
        $stmt = $pdo->prepare("SELECT id FROM crime_types WHERE name = ? LIMIT 1");
        $stmt->execute([$crime_type_id]);
        $crime_type = $stmt->fetch();
        
        if (!$crime_type) {
            // Create new crime type if it doesn't exist
            $stmt = $pdo->prepare("INSERT INTO crime_types (name) VALUES (?)");
            $stmt->execute([$crime_type_id]);
            $crime_type_id = $pdo->lastInsertId();
        } else {
            $crime_type_id = $crime_type['id'];
        }

        // Generate a random passphrase for the report
        $passphrase = bin2hex(random_bytes(16));
        $passphrase_hash = password_hash($passphrase, PASSWORD_DEFAULT);

        // Insert report
        $stmt = $pdo->prepare("
            INSERT INTO reports 
                (user_id, crime_type_id, location, report_date, description, status, encrypted_data, passphrase_hash) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([
            null,              // Always null for anonymous reports
            $crime_type_id,    // Now we have the correct ID
            $location,
            $report_date,
            $description,
            $filePath,         // optional file path
            $passphrase_hash   // required passphrase hash
        ]);
        

        $report_id = $pdo->lastInsertId();

        // Generate tracking info for anonymous submission
        $tracking_number = 'CR' . date('Y') . str_pad($report_id, 6, '0', STR_PAD_LEFT);
        
        // Store tracking info in session temporarily
        $_SESSION['anonymous_report_info'] = [
            'tracking_number' => $tracking_number,
            'access_code' => $passphrase // This is the random hex we generated earlier
        ];
        
        // Always redirect to success page for anonymous reports
        header('Location: anonymous_success.php');
        exit;

    } catch (PDOException $e) {
        error_log('Report submission error: ' . $e->getMessage());
        $_SESSION['report_error'] = 'Failed to submit report. Please try again later.';
        header('Location: anonymous_report.php');
        exit;
    }

} else {
    // Redirect if form not submitted
    header('Location: ' . ($user_id ? 'user_page.php' : 'anonymous_report.php'));
    exit;
}
?>

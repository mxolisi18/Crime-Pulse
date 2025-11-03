<?php
session_start();
require_once __DIR__ . '/../config.php';

//Check login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login_register.php");
    exit();
}

// Verify user exists in DB and is active
try {
    $stmt = $pdo->prepare('SELECT id, is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $userRow = $stmt->fetch();
    if (!$userRow || !((int)$userRow['is_active'])) {
        // Invalid session — clear and redirect
        session_unset();
        session_destroy();
        header("Location: login_register.php");
        exit();
    }
} catch (PDOException $e) {
    error_log('User validation error: ' . $e->getMessage());
    // On DB error, redirect to login to be safe
    session_unset();
    session_destroy();
    header("Location: login_register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Reporting Website - User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="user-style.css">
</head>
<body class="auth-page">
    <div class="top-header">
        <ul>
            <div class="left-links">
                <li><a href="user_page.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="track_page.php"><i class="fas fa-file-alt"></i> View Reports</a></li>
                <!--<li><a href="cancellation_page.php"><i class="fas fa-ban"></i> Cancel Your Report</a></li>-->
            </div>
            <div class="right-links">
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </div>
        </ul>
    </div>
    <div class="container">
        <div class="form-box active">
            <form action="submit_user_report.php" method="post">
                <h2>🔒 Report a Crime</h2>
                <p style="color: #666; margin-bottom: 20px; text-align: center;">Your identity will remain completely confidential.</p>
                
                <?php 
                if(isset($_SESSION['report_success'])) {
                    echo "<p class='success-message'>" . $_SESSION['report_success'] . "</p>";
                    unset($_SESSION['report_success']);
                }
                if(isset($_SESSION['report_error'])) {
                    echo "<p class='error-message'>" . $_SESSION['report_error'] . "</p>";
                    unset($_SESSION['report_error']);
                }
                ?>

                <select name="crime_type" required>
                    <option value="">--Select Crime Type--</option>
                    <option value="theft">Theft</option>
                    <option value="assault">Assault</option>
                    <option value="burglary">Burglary</option>
                    <option value="vandalism">Vandalism</option>
                    <option value="fraud">Fraud</option>
                    <option value="drug_related">Drug Related</option>
                    <option value="other">Other</option>
                </select>

                <input type="text" name="location" placeholder="Location of incident" required>
                <input type="date" name="incident_date" placeholder="Date of incident" required>
                <input type="file" name="evidence" accept="image/*,video/*" placeholder="Upload evidence (optional)">
                <textarea name="description" rows="6" placeholder="Describe the incident in detail..." required style="width: 100%; padding: 15px; border: none; background: #f5fff7; border-radius: 6px; font-family: 'Poppins', serif; font-size: 16px; margin-bottom: 20px; resize: vertical;"></textarea>
                <button type="submit" name="submit_anonymous">Submit Report</button>
            </form>
        </div>
    </div>
</body>
</html>

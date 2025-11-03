<?php
session_start();

// If there's no tracking info, redirect to the home page
if (!isset($_SESSION['anonymous_report_info'])) {
    header('Location: index.php');
    exit;
}

$reportInfo = $_SESSION['anonymous_report_info'];
// Clear the session data after displaying
unset($_SESSION['anonymous_report_info']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Submitted Successfully - Anonymous Crime Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="user-style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="form-box">
            <h2><i class="fas fa-check-circle" style="color: #4CAF50;"></i> Report Submitted Successfully</h2>
            <div class="success-info">
                <p>Your report has been submitted successfully. Please save the following information to track your report:</p>
                
                <div class="tracking-info">
                    <h3>Report Tracking Number:</h3>
                    <p class="tracking-number"><?php echo htmlspecialchars($reportInfo['tracking_number']); ?></p>
                    
                    <h3>Report Access Code:</h3>
                    <p class="access-code"><?php echo htmlspecialchars($reportInfo['access_code']); ?></p>
                </div>

                <div class="warning-box">
                    <p><i class="fas fa-exclamation-triangle"></i> Important:</p>
                    <ul>
                        <li>Please save this information immediately. You will need it to check your report status.</li>
                        <li>This information will not be shown again.</li>
                        <li>Keep this information confidential and secure.</li>
                    </ul>
                </div>
            </div>
            <div class="button-group">
                <button onclick="window.print()" class="secondary-button">
                    <i class="fas fa-print"></i> Print This Page
                </button>
                <a href="index.php" class="primary-button">
                    <i class="fas fa-home"></i> Return to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
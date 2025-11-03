<?php
session_start();

// If user is logged in, they shouldn't use anonymous reporting
if (isset($_SESSION['user_id'])) {
    // Clear any existing session first
    session_unset();
    session_destroy();
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Anonymous Report - Crime Report System</title>
</head>
<body class="auth-page">
    <div class="container wide">
        <!-- Why Anonymous Section -->
        <div class="anonymous-banner">
            <div class="anonymous-content">
                <h3>🔒 Anonymous Crime Reporting</h3>
                <p>Your identity will be completely protected. No registration or personal information required.</p>
            </div>
        </div>

        <div class="form-box active">
            <form action="submit_anonymous_report.php" method="post">
                <h2>🔒 Anonymous Crime Report</h2>
                <p style="color: #666; margin-bottom: 20px;">Your identity will remain completely confidential.</p>
                
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
                
                <textarea name="description" rows="6" placeholder="Describe the incident in detail..." required style="width: 100%; padding: 15px; border: none; background: #eee; border-radius: 6px; font-family: 'Poppins', serif; font-size: 16px; margin-bottom: 20px; resize: vertical;"></textarea>

                <button type="submit" name="submit_anonymous">Submit Anonymous Report</button>
                <div class="form-links">
                    <p><a href="check_anonymous_report.php"><i class="fas fa-search"></i> Check Report Status</a></p>
                    <p><a href="login_register.php">← Back to Login</a></p>
                </div>
            </form>
        </div>

        <!-- Divider -->
        <div class="divider">
            <span>Important Information</span>
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <h3>What You Should Know</h3>
            <ul class="benefits-list">
                <li>✓ Your report is completely anonymous</li>
                <li>✓ No personal information is collected</li>
                <li>✓ Keep your tracking number safe</li>
                <li>✓ Check status using tracking number</li>
                <li>✓ Include as much detail as possible</li>
            </ul>
        </div>
    </div>
</body>
</html>
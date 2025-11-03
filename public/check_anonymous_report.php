<?php
session_start();
require_once __DIR__ . '/../config.php';

$report = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking_number = $_POST['tracking_number'] ?? '';
    $access_code = $_POST['access_code'] ?? '';

    if (!empty($tracking_number) && !empty($access_code)) {
        // Extract the numeric part of tracking number (remove 'CR' and year)
        $report_id = intval(substr($tracking_number, 6));

        try {
            // First verify the report exists and the access code matches
            $stmt = $pdo->prepare("
                SELECT r.*, c.name as crime_type
                FROM reports r
                INNER JOIN crime_types c ON r.crime_type_id = c.id
                WHERE r.id = ? AND r.user_id IS NULL
            ");
            $stmt->execute([$report_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($report && password_verify($access_code, $report['passphrase_hash'])) {
                // Valid report found
            } else {
                $error = "Invalid tracking number or access code. Please try again.";
                $report = null;
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
            error_log("Error checking anonymous report: " . $e->getMessage());
        }
    } else {
        $error = "Please provide both tracking number and access code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Report Status - Anonymous Crime Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="user-style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="form-box">
            <h2><i class="fas fa-search"></i> Check Report Status</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!$report): ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" 
                               name="tracking_number" 
                               placeholder="Tracking Number (e.g., CR2025000123)" 
                               required 
                               pattern="CR\d{10}"
                               title="Please enter a valid tracking number (e.g., CR2025000123)">
                    </div>

                    <div class="input-group">
                        <input type="text" 
                               name="access_code" 
                               placeholder="Access Code" 
                               required>
                    </div>

                    <button type="submit">Check Status</button>
                    <p><a href="index.php">← Back to Home</a></p>
                </form>
            <?php else: ?>
                <div class="report-details">
                    <h3>Report Details</h3>
                    <table class="report-info">
                        <tr>
                            <th>Tracking Number:</th>
                            <td><?php echo htmlspecialchars($tracking_number); ?></td>
                        </tr>
                        <tr>
                            <th>Crime Type:</th>
                            <td><?php echo htmlspecialchars($report['crime_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td><?php echo htmlspecialchars($report['location']); ?></td>
                        </tr>
                        <tr>
                            <th>Date Reported:</th>
                            <td><?php echo date("d M Y", strtotime($report['report_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td class="status <?php echo strtolower($report['status']); ?>">
                                <?php echo htmlspecialchars($report['status']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo nl2br(htmlspecialchars($report['description'])); ?></td>
                        </tr>
                    </table>

                    <div class="button-group">
                        <a href="check_anonymous_report.php" class="secondary-button">
                            <i class="fas fa-search"></i> Check Another Report
                        </a>
                        <a href="index.php" class="primary-button">
                            <i class="fas fa-home"></i> Return to Home
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
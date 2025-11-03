<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: user_page.php");
    exit();
}

// Fetch quick statistics
try {
    // Total reports count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
    $totalReports = $stmt->fetch()['total'];

    // Pending reports count
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM reports WHERE status = 'pending'");
    $stmt->execute();
    $pendingReports = $stmt->fetch()['pending'];

    // Total users count
    $stmt = $pdo->query("SELECT COUNT(*) as users FROM users WHERE role = 'user'");
    $totalUsers = $stmt->fetch()['users'];

    // Recent reports (last 5)
    $stmt = $pdo->prepare("
        SELECT r.*, c.name as crime_type, 
               CASE WHEN r.user_id IS NULL THEN 'Anonymous' ELSE u.username END as reporter
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN crime_types c ON r.crime_type_id = c.id
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $stmt->execute();
    $recentReports = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    $error = "An error occurred loading the dashboard.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Crime Report System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="user-style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <ul>
            <div class="left-links">
                <li><a href="admin_page.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_reports.php"><i class="fas fa-file-alt"></i> Manage Reports</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_crime_types.php"><i class="fas fa-tags"></i> Crime Types</a></li>
            </div>
            <div class="right-links">
                <li><span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </div>
        </ul>
    </div>

    <div class="container wide">
        <h1>Admin Dashboard</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <h3><?php echo number_format($totalReports); ?></h3>
                <p>Total Reports</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?php echo number_format($pendingReports); ?></h3>
                <p>Pending Reports</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo number_format($totalUsers); ?></h3>
                <p>Registered Users</p>
            </div>
        </div>

        <!-- Recent Reports Section -->
        <div class="admin-section">
            <h2>Recent Reports</h2>
            <table class="recent-reports">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReports as $report): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                        <td><?php echo htmlspecialchars($report['crime_type']); ?></td>
                        <td><?php echo htmlspecialchars($report['reporter']); ?></td>
                        <td><?php echo htmlspecialchars($report['location']); ?></td>
                        <td><span class="status <?php echo strtolower($report['status']); ?>"><?php echo htmlspecialchars($report['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Actions -->
        <div class="action-buttons">
            <a href="manage_reports.php" class="action-btn btn-reports">
                <i class="fas fa-file-alt"></i> Manage All Reports
            </a>
            <a href="manage_users.php" class="action-btn btn-users">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="manage_crime_types.php" class="action-btn btn-types">
                <i class="fas fa-tags"></i> Manage Crime Types
            </a>
        </div>
    </div>
</body>
</html>
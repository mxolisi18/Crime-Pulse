<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_register.php");
    exit();
}

// Generate or validate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Process status update
    if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['report_id']) && isset($_POST['status'])) {
        $report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $admin_note = filter_input(INPUT_POST, 'admin_note', FILTER_SANITIZE_STRING);
        
        try {
            $pdo->beginTransaction();
            
            // Update status
            $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
            $stmt->execute([$status, $report_id]);

            // Add admin note if provided
            if (!empty($admin_note)) {
                $stmt = $pdo->prepare("INSERT INTO report_notes (report_id, admin_id, note, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$report_id, $_SESSION['user_id'], $admin_note]);
            }

            $pdo->commit();
            $success = "Report status updated successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Error updating report status: ' . $e->getMessage());
            $error = "Failed to update report status.";
        }
    }

    // Process delete action
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['report_id'])) {
        $report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
        
        try {
            $pdo->beginTransaction();

            // Delete associated files first
            $stmt = $pdo->prepare("SELECT file_path FROM report_files WHERE report_id = ?");
            $stmt->execute([$report_id]);
            $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Delete report files records
            $stmt = $pdo->prepare("DELETE FROM report_files WHERE report_id = ?");
            $stmt->execute([$report_id]);

            // Delete report notes
            $stmt = $pdo->prepare("DELETE FROM report_notes WHERE report_id = ?");
            $stmt->execute([$report_id]);

            // Delete the report
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);

            $pdo->commit();
            $success = "Report deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Error deleting report: ' . $e->getMessage());
            $error = "Failed to delete report.";
        }
    }
}

// Build query conditions based on filters
$conditions = [];
$params = [];

// Status filter
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
if ($status_filter) {
    $conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

// Date range filter
$date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING);
$date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING);
if ($date_from) {
    $conditions[] = "r.report_date >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $conditions[] = "r.report_date <= ?";
    $params[] = $date_to;
}

// Crime type filter
$crime_type = filter_input(INPUT_GET, 'crime_type', FILTER_SANITIZE_NUMBER_INT);
if ($crime_type) {
    $conditions[] = "r.crime_type_id = ?";
    $params[] = $crime_type;
}

// Search filter
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
if ($search) {
    $conditions[] = "(r.location LIKE ? OR r.description LIKE ? OR u.username LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

// Sorting
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?: 'created_at';
$sort_dir = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_STRING) ?: 'DESC';
$allowed_sort_fields = ['created_at', 'report_date', 'status', 'location'];
$sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'created_at';
$sort_dir = $sort_dir === 'ASC' ? 'ASC' : 'DESC';

// Initialize variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$reports = [];
$crimeTypes = [];
$totalReports = 0;
$totalPages = 1;

try {
    // Fetch crime types for filter
    $stmt = $pdo->query("SELECT id, name FROM crime_types ORDER BY name");
    $crimeTypes = $stmt->fetchAll();

    // Build the base query
    $baseQuery = "
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN crime_types c ON r.crime_type_id = c.id
    ";
    
    if (!empty($conditions)) {
        $baseQuery .= " WHERE " . implode(" AND ", $conditions);
    }

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) " . $baseQuery;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalReports = $stmt->fetchColumn();
    $totalPages = ceil($totalReports / $perPage);

    // Fetch reports for current page
    $query = "
        SELECT r.id, r.report_date, r.location, r.description, r.status, r.created_at,
               COALESCE(c.name, 'Unknown') as crime_type, 
               CASE WHEN r.user_id IS NULL THEN 'Anonymous' ELSE COALESCE(u.username, 'Unknown') END as reporter
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN crime_types c ON r.crime_type_id = c.id
    ";
    
    // Add WHERE clause if there are conditions
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add ORDER BY and LIMIT
    $query .= " ORDER BY r." . $sort_by . " " . $sort_dir . " LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    
    // Debug the query and parameters
    error_log('Final Query: ' . $query);
    error_log('Parameters: ' . print_r(array_merge($params, [$perPage, $offset]), true));
    
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $reports = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching reports: ' . $e->getMessage());
    // Show detailed error in development environment
    $error = "Database error: " . $e->getMessage();
    
    // Log the query for debugging
    error_log('Query: ' . $query);
    error_log('Parameters: ' . print_r($params, true));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Crime Report System</title>
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
                <li><a href="manage_reports.php" class="active"><i class="fas fa-file-alt"></i> Manage Reports</a></li>
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
        <h1>Manage Reports</h1>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="get" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <?php foreach (['pending', 'investigating', 'resolved', 'closed'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="crime_type">Crime Type:</label>
                        <select name="crime_type" id="crime_type">
                            <option value="">All Types</option>
                            <?php foreach ($crimeTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $crime_type == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date_from">Date From:</label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                    </div>

                    <div class="filter-group">
                        <label for="date_to">Date To:</label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                    </div>

                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" placeholder="Search in reports..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="manage_reports.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <table class="reports-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>
                        <a href="?sort=report_date&dir=<?php echo $sort_by === 'report_date' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                            Date <?php if ($sort_by === 'report_date') echo $sort_dir === 'ASC' ? '↑' : '↓'; ?>
                        </a>
                    </th>
                    <th>Type</th>
                    <th>Reporter</th>
                    <th>
                        <a href="?sort=location&dir=<?php echo $sort_by === 'location' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                            Location <?php if ($sort_by === 'location') echo $sort_dir === 'ASC' ? '↑' : '↓'; ?>
                        </a>
                    </th>
                    <th>Details</th>
                    <th>
                        <a href="?sort=status&dir=<?php echo $sort_by === 'status' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                            Status <?php if ($sort_by === 'status') echo $sort_dir === 'ASC' ? '↑' : '↓'; ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($report['id']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                    <td><?php echo htmlspecialchars($report['crime_type']); ?></td>
                    <td><?php echo htmlspecialchars($report['reporter']); ?></td>
                    <td><?php echo htmlspecialchars($report['location']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($report['description'])); ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <?php
                                $statuses = ['pending', 'in_review', 'closed'];
                                foreach ($statuses as $status):
                                    $selected = $status === $report['status'] ? 'selected' : '';
                                ?>
                                <option value="<?php echo $status; ?>" <?php echo $selected; ?>>
                                    <?php echo $status === 'in_review' ? 'In Review' : ucfirst($status); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-small">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_register.php");
    exit();
}

// Process user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

        try {
            switch ($action) {
                case 'activate':
                case 'deactivate':
                    $is_active = $action === 'activate' ? 1 : 0;
                    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$is_active, $user_id]);
                    $success = "User " . ($is_active ? "activated" : "deactivated") . " successfully.";
                    break;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$user_id]);
                    $success = "User deleted successfully.";
                    break;

                // New Changes //
                case 'make_admin':
                    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success = "User promoted to admin successfully.";
                    break;

                case 'remove_admin':
                    $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ? AND id != ?");
                    $stmt->execute([$user_id, $_SESSION['user_id']]); 
                    $success = "Admin role removed successfully.";
                    break;

            }
        } catch (PDOException $e) {
            error_log('Error managing user: ' . $e->getMessage());
            $error = "Failed to process user action.";
        }
    }
}

// Initialize variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$users = [];
$totalUsers = 0;
$totalPages = 1;

try {
    // Get total count for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);

    // Fetch users for current page
    $stmt = $pdo->prepare("
        SELECT id, username, created_at, is_active, role,
               (SELECT COUNT(*) FROM reports WHERE user_id = users.id) as report_count
        FROM users 
        WHERE id != ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $perPage, $offset]);
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching users: ' . $e->getMessage());
    $error = "An error occurred while fetching users.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Crime Report System</title>
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
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_crime_types.php"><i class="fas fa-tags"></i> Crime Types</a></li>
            </div>
            <div class="right-links">
                <li><span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </div>
        </ul>
    </div>

    <div class="container wide">
        <h1>Manage Users</h1>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Joined</th>
                    <th>Reports</th>
                    <th>Status</th>

                    <!-- New Changes -->
                    <th>Role</th>

                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td><?php echo $user['report_count']; ?></td>
                    <td>
                        <span class="user-status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>

                    <!-- New Changes -->
                    <td><?php echo htmlspecialchars($user['role']); ?></td>

                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                            
                            <!-- New Changes -->
                            <?php if ($user['role'] === 'user'): ?>
                            <button type="submit" name="action" value="make_admin"
                                    class="action-btn btn-activate"
                                    onclick="return confirm('Make this user an admin?')">
                                <i class="fas fa-user-shield"></i> Make Admin
                            </button>
                            <?php elseif ($user['role'] === 'admin'): ?>
                            <button type="submit" name="action" value="remove_admin"
                                    class="action-btn btn-deactivate"
                                    onclick="return confirm('Remove admin rights from this user?')">
                                <i class="fas fa-user"></i> Remove Admin
                            </button>
                            <?php endif; ?>

                            <?php if ($user['is_active']): ?>
                                <button type="submit" name="action" value="deactivate" 
                                        class="action-btn btn-deactivate" 
                                        onclick="return confirm('Are you sure you want to deactivate this user?')">
                                    <i class="fas fa-user-slash"></i> Deactivate
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="activate" 
                                        class="action-btn btn-activate">
                                    <i class="fas fa-user-check"></i> Activate
                                </button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete" 
                                    class="action-btn btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
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
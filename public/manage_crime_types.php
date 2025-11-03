<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_register.php");
    exit();
}

// Process crime type actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
                    
                    if ($name && $description) {
                        $stmt = $pdo->prepare("INSERT INTO crime_types (name, description) VALUES (?, ?)");
                        $stmt->execute([$name, $description]);
                        $success = "Crime type added successfully.";
                    }
                    break;

                case 'update':
                    $id = filter_input(INPUT_POST, 'type_id', FILTER_SANITIZE_NUMBER_INT);
                    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
                    
                    if ($id && $name && $description) {
                        $stmt = $pdo->prepare("UPDATE crime_types SET name = ?, description = ? WHERE id = ?");
                        $stmt->execute([$name, $description, $id]);
                        $success = "Crime type updated successfully.";
                    }
                    break;

                case 'delete':
                    $id = filter_input(INPUT_POST, 'type_id', FILTER_SANITIZE_NUMBER_INT);
                    
                    if ($id) {
                        // Check if the crime type is in use
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE crime_type_id = ?");
                        $stmt->execute([$id]);
                        $count = $stmt->fetchColumn();

                        if ($count > 0) {
                            $error = "Cannot delete: This crime type is used in {$count} reports.";
                        } else {
                            $stmt = $pdo->prepare("DELETE FROM crime_types WHERE id = ?");
                            $stmt->execute([$id]);
                            $success = "Crime type deleted successfully.";
                        }
                    }
                    break;
            }
        } catch (PDOException $e) {
            error_log('Error managing crime types: ' . $e->getMessage());
            $error = "Failed to process the request.";
        }
    }
}

// Fetch all crime types with usage count
try {
    $stmt = $pdo->query("
        SELECT ct.*, COUNT(r.id) as usage_count 
        FROM crime_types ct
        LEFT JOIN reports r ON ct.id = r.crime_type_id
        GROUP BY ct.id
        ORDER BY ct.name
    ");
    $crimeTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching crime types: ' . $e->getMessage());
    $error = "An error occurred while fetching crime types.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Crime Types - Crime Report System</title>
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
                <li><a href="manage_crime_types.php" class="active"><i class="fas fa-tags"></i> Crime Types</a></li>
            </div>
            <div class="right-links">
                <li><span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </div>
        </ul>
    </div>

    <div class="container wide">
        <h1>Manage Crime Types</h1>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add New Crime Type Form -->
        <div class="add-form">
            <h2>Add New Crime Type</h2>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-row">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn">Add Crime Type</button>
            </form>
        </div>

        <!-- Crime Types Grid -->
        <div class="crime-types-grid">
            <?php foreach ($crimeTypes as $type): ?>
            <div class="crime-type-card">
                <h3><?php echo htmlspecialchars($type['name']); ?></h3>
                <p><?php echo htmlspecialchars($type['description']); ?></p>
                <div class="usage-count">
                    <i class="fas fa-chart-bar"></i> Used in <?php echo $type['usage_count']; ?> reports
                </div>
                <div class="action-buttons">
                    <button class="btn-edit" onclick="editCrimeType(<?php 
                        echo htmlspecialchars(json_encode([
                            'id' => $type['id'],
                            'name' => $type['name'],
                            'description' => $type['description']
                        ])); 
                    ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="type_id" value="<?php echo $type['id']; ?>">
                        <button type="submit" class="btn-delete" 
                                <?php echo $type['usage_count'] > 0 ? 'disabled' : ''; ?>
                                onclick="return confirm('Are you sure you want to delete this crime type?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Edit Crime Type</h2>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="type_id" id="edit_type_id">
                <div class="form-row">
                    <label for="edit_name">Name:</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-row">
                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="description" rows="3" required></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCrimeType(type) {
            document.getElementById('edit_type_id').value = type.id;
            document.getElementById('edit_name').value = type.name;
            document.getElementById('edit_description').value = type.description;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
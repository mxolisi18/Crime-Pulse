<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_register.php");
    exit();
}

// Validate report ID
$report_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$report_id) {
    header("Location: manage_reports.php");
    exit();
}

try {
    // Fetch report details with joins
    $stmt = $pdo->prepare("
        SELECT r.*,
               c.name as crime_type,
               c.description as crime_type_description,
               CASE WHEN r.user_id IS NULL THEN 'Anonymous' ELSE u.username END as reporter
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN crime_types c ON r.crime_type_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        $_SESSION['error'] = "Report not found.";
        header("Location: manage_reports.php");
        exit();
    }

    // Fetch media files
    $stmt = $pdo->prepare("
        SELECT id, file_path, file_type, file_size, uploaded_at
        FROM media
        WHERE report_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$report_id]);
    $media_files = $stmt->fetchAll();

    // Fetch messages/notes
    $stmt = $pdo->prepare("
        SELECT m.*, u.username
        FROM messages m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.report_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$report_id]);
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error viewing report: ' . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving the report details.";
    header("Location: manage_reports.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report - Crime Report System</title>
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
        <div class="page-header">
            <h1>View Report #<?php echo htmlspecialchars($report['id']); ?></h1>
            <a href="manage_reports.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Report Details -->
        <div class="report-details">
            <h2>Report Details</h2>
            
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="status-badge <?php echo $report['status']; ?>">
                        <?php echo ucfirst($report['status']); ?>
                    </span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Report Date</div>
                <div class="detail-value">
                    <?php echo date('F j, Y g:i A', strtotime($report['report_date'])); ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Crime Type</div>
                <div class="detail-value">
                    <strong><?php echo htmlspecialchars($report['crime_type']); ?></strong><br>
                    <?php if ($report['crime_type_description']): ?>
                        <small><?php echo htmlspecialchars($report['crime_type_description']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Reporter</div>
                <div class="detail-value"><?php echo htmlspecialchars($report['reporter']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Location</div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($report['location']); ?>
                    <?php if ($report['location']): ?>
                        <div class="map-container" id="map"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Description</div>
                <div class="detail-value">
                    <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                </div>
            </div>

            <!-- Media Files -->
            <?php if ($media_files): ?>
                <h3>Attached Files</h3>
                <div class="media-grid">
                    <?php foreach ($media_files as $file): ?>
                        <div class="media-item">
                            <?php if ($file['file_type'] === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                     alt="Report Image">
                            <?php else: ?>
                                <i class="fas fa-file"></i>
                            <?php endif; ?>
                            <div>
                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                   target="_blank" class="btn btn-small">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="btn" onclick="openUpdateModal()">
                    <i class="fas fa-edit"></i> Update Status
                </button>
                <button type="button" class="btn" onclick="openMessageModal()">
                    <i class="fas fa-comment"></i> Add Note
                </button>
                <form method="post" style="display: inline;" 
                      onsubmit="return confirm('Are you sure you want to delete this report? This action cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Report
                    </button>
                </form>
            </div>
        </div>

        <!-- Messages/Notes Section -->
        <?php if ($messages): ?>
            <div class="messages-section">
                <h2>Messages & Notes</h2>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['is_from_reporter'] ? 'reporter' : 'admin'; ?>">
                        <div class="message-meta">
                            <?php if ($message['is_from_reporter']): ?>
                                <strong>Reporter</strong>
                            <?php else: ?>
                                <strong><?php echo htmlspecialchars($message['username']); ?></strong>
                            <?php endif; ?>
                            <span>on <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message_text'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Status Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <h2>Update Report Status</h2>
            <form method="post" action="manage_reports.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                
                <div class="form-group">
                    <label for="status">New Status:</label>
                    <select name="status" id="status" class="form-control" required>
                        <?php foreach (['pending', 'in_review', 'closed'] as $status): ?>
                            <option value="<?php echo $status; ?>" 
                                    <?php echo $status === $report['status'] ? 'selected' : ''; ?>>
                                <?php echo $status === 'in_review' ? 'In Review' : ucfirst($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note">Add Note (optional):</label>
                    <textarea name="admin_note" id="note" rows="3" class="form-control"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn">Update Status</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('updateModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h2>Add Admin Note</h2>
            <form method="post" action="add_note.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                
                <div class="form-group">
                    <label for="message">Note:</label>
                    <textarea name="message" id="message" rows="4" class="form-control" required></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn">Add Note</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('messageModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    

    <script>
        function openUpdateModal() {
            document.getElementById('updateModal').style.display = 'block';
        }

        function openMessageModal() {
            document.getElementById('messageModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        <?php if ($report['location']): ?>
        // Initialize Google Maps
        function initMap() {
            const geocoder = new google.maps.Geocoder();
            const mapDiv = document.getElementById('map');
            
            geocoder.geocode({
                address: '<?php echo addslashes($report['location']); ?>'
            }, function(results, status) {
                if (status === 'OK') {
                    const map = new google.maps.Map(mapDiv, {
                        zoom: 15,
                        center: results[0].geometry.location
                    });
                    
                    new google.maps.Marker({
                        map: map,
                        position: results[0].geometry.location
                    });
                }
            });
        }
        </script>
        <?php if ($report['location']): ?>
        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap">
        </script>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
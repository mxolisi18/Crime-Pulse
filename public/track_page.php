<?php
session_start();
require_once __DIR__ . '/../config.php';

//Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// CSRF token for actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch reports joined with crime_type
$sql = $sql = "
SELECT r.id, c.name AS crime_type, r.location, r.description, r.report_date, r.status,
       (SELECT m.file_path 
        FROM media m 
        WHERE m.report_id = r.id 
        ORDER BY m.uploaded_at ASC 
        LIMIT 1) AS file_path
FROM reports r
INNER JOIN crime_types c ON r.crime_type_id = c.id
WHERE r.user_id = ?
ORDER BY r.report_date DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Crime Reports</title>
<link rel="stylesheet" href="user-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Top Header -->
<div class="top-header">
    <ul>
        <div class="left-links">
            <li><a href="user_page.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="track_page.php"><i class="fas fa-file-alt"></i> View Reports</a></li>
            <!--<li><a href="cancellation_page.php"><i class="fas fa-ban"></i> Cancel Report</a></li>-->
        </div>
        <div class="right-links">
            <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </div>
    </ul>
</div>

<div class="container">
    <h2>My Reported Crimes</h2>

    <?php if (count($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Crime Type</th>
                <th>Location</th>
                <th>Description</th>
                <th>Media</th> <!-- in table header -->
                <th>Date Reported</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $count = 1; foreach ($result as $row): ?>
            <tr>
                <td><?= $count++; ?></td>
                <td><?= htmlspecialchars($row['crime_type']); ?></td>
                <td><?= htmlspecialchars($row['location']); ?></td>
                <td><?= htmlspecialchars($row['description']); ?></td>

            <!--For media display-->
                <td>
                    <?php if (!empty($row['file_path'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($row['file_path']); ?>" alt="Report Media" style="width:100px; height:auto;">
                    <?php else: ?>
                        No Evidence Found
                    <?php endif; ?>
                </td>

                <td><?= date("d M Y", strtotime($row['report_date'])); ?></td>
                <td class="status <?= strtolower(str_replace(' ', '_', $row['status'])); ?>">
                    <?= htmlspecialchars($row['status']); ?>
                </td>
                <td class="actions">
                    <a class="link-button edit" href="edit_report.php?id=<?= $row['id']; ?>"><i class="fas fa-edit"></i> Edit</a>
                    <form method="POST" action="delete_report.php" onsubmit="return confirm('Are you sure you want to delete this report?');" style="display:inline">
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="link-button delete"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="no-data">You haven’t reported any crimes yet.</p>
    <?php endif; ?>
</div>

</body>
</html>

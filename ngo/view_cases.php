<?php
session_start();
include('config.php');

// Check if NGO is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];
$ngo_name = $_SESSION["name"];

// Fetch all cases assigned to this NGO
$stmt = $conn->prepare("
    SELECT c.*, 
           u.name AS user_name, 
           u.email AS user_email, 
           s.full_name AS assigned_staff
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN team_members s ON c.assigned_staff_id = s.id
    WHERE c.assigned_ngo_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Dashboard | Case Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="">Recent Assigned Cases</h3>
        <span class="badge badge-primary">NGO: <?= htmlspecialchars($ngo_name) ?></span>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Assigned Staff</th>
                        <th>Created At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($case = $result->fetch_assoc()): ?>
                    <tr class="<?= $case['assigned_staff_id'] ? '' : 'table-warning' ?>">
                        <td><strong>#<?= $case['id'] ?></strong></td>
                        <td><?= htmlspecialchars($case['case_title']) ?></td>
                        <td><?= htmlspecialchars($case['user_name']) ?></td>
                        <td><?= htmlspecialchars($case['user_email']) ?></td>
                        <td>
                            <span class="badge badge-pill badge-<?= $case['status'] == 'closed' ? 'secondary' : 'success' ?>">
                                <?= ucfirst($case['status']) ?>
                            </span>
                        </td>
                        <td>
                            <i class="small"><?= $case['assigned_staff'] ?? 'Pending Assignment' ?></i>
                        </td>
                        <td><?= date('M d, Y', strtotime($case['created_at'])) ?></td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info">View</a>
                                <a href="counseling_sessions.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-warning">Sessions</a>
                                <a href="skills_progress.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-dark">Skills</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card p-5 text-center shadow-sm">
            <p class="lead mb-0 text-muted">No cases have been assigned to your NGO yet.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
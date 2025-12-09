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

// Handle Closure Request actions
if (isset($_GET['closure_id'], $_GET['action'])) {
    $closure_id = intval($_GET['closure_id']);
    $action = $_GET['action'];

    if (in_array($action, ['close','reject'])) {
        $status = $action === 'close' ? 'closed' : 'rejected';
        $stmt = $conn->prepare("UPDATE cases c 
            JOIN closure_requests cr ON c.id=cr.case_id 
            SET c.status=?, cr.status=?, cr.responded_at=NOW() 
            WHERE cr.id=? AND cr.ngo_id=?");
        $stmt->bind_param("ssii", $status, $status, $closure_id, $ngo_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Closure request has been ". ($status=='closed' ? 'closed' : 'rejected') ." successfully.";
    }
}

// Fetch all cases and closure requests for this NGO
$stmt = $conn->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email, s.full_name AS assigned_staff,
           cr.id AS closure_id, cr.status AS closure_status, cr.notes AS closure_notes
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngo_staff s ON c.assigned_staff_id = s.id
    LEFT JOIN closure_requests cr ON c.id=cr.case_id AND cr.ngo_id=?
    WHERE c.assigned_ngo_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("ii", $ngo_id, $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <h3 class="mb-4">Recent Cases & Closure Requests</h3>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>User</th>
                        <th>User Email</th>
                        <th>Status</th>
                        <th>Assigned Staff</th>
                        <th>Created At</th>
                        <th>Closure Request</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($case = $result->fetch_assoc()): ?>
                        <tr class="<?= $case['assigned_staff_id'] ? 'table-success' : 'table-warning' ?>">
                            <td><?= $case['id'] ?></td>
                            <td><?= htmlspecialchars($case['case_title']) ?></td>
                            <td><?= htmlspecialchars($case['user_name']) ?></td>
                            <td><?= htmlspecialchars($case['user_email']) ?></td>
                            <td><?= ucfirst($case['status']) ?></td>
                            <td><?= $case['assigned_staff'] ?? 'Not Assigned' ?></td>
                            <td><?= $case['created_at'] ?></td>
                            <td>
                                <?php if ($case['closure_id']): ?>
                                    <span class="badge badge-<?= $case['closure_status']=='pending' ? 'warning' : ($case['closure_status']=='closed' ? 'success' : 'danger') ?>">
                                        <?= ucfirst($case['closure_status']) ?>
                                    </span>
                                    <br>
                                    <?= htmlspecialchars($case['closure_notes']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_case_details.php?id=<?= $case['id'] ?>" class="m-1 btn btn-sm btn-info">View</a>
                                <a href="counseling_sessions.php?case_id=<?= $case['id'] ?>" class="m-1 btn btn-sm btn-warning">Sessions</a>
                                <a href="skills_progress.php?case_id=<?= $case['id'] ?>" class="m-1 btn btn-sm btn-dark">Skills & Progress</a>
                                <?php if($case['closure_status']=='pending'): ?>
                                    <a href="?closure_id=<?= $case['closure_id'] ?>&action=close" class="btn btn-sm btn-success mb-1" onclick="return confirm('Close this case?');">Close Now</a>
                                    <a href="?closure_id=<?= $case['closure_id'] ?>&action=reject" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Reject this closure request?');">Reject</a>
                                <?php endif; ?>
                                <?php if($case['status']=='pending'): ?>
                                    <a href="update_case_status.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-success mb-1">Update Status</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">No cases assigned yet.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Handle Closure Request actions by Admin
if (isset($_GET['closure_id'], $_GET['action'])) {
    $closure_id = intval($_GET['closure_id']);
    $action = $_GET['action'];

    if ($action === 'close') {
        // Approve closure request -> close case
        $stmt = $conn->prepare("
            UPDATE cases c
            JOIN closure_requests cr ON c.id = cr.case_id
            SET 
                c.status = 'closed',
                c.closed_at = NOW(),
                cr.status = 'approved',
                cr.responded_at = NOW()
            WHERE cr.id = ?
        ");
        $stmt->bind_param("i", $closure_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Closure request approved and case closed successfully.";

    } elseif ($action === 'reject') {
        // Reject closure request only
        $stmt = $conn->prepare("
            UPDATE closure_requests 
            SET status = 'rejected', responded_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $closure_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Closure request rejected successfully.";
    }
}

// Fetch all cases with user, NGO, and closure request info
$sql = "
    SELECT c.*, u.name AS user_name, u.email AS user_email, n.ngo_name,
           cr.id AS closure_id, cr.status AS closure_status, cr.notes AS closure_notes
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
    LEFT JOIN closure_requests cr ON c.id = cr.case_id
    ORDER BY c.created_at DESC
";
$cases = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - View Cases</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">All Cases</h2>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped table-responsive-sm">
        <thead class="thead-dark">
        <tr>
            <th>Case ID</th>
            <th>Title</th>
            <th>User</th>
            <th>Email</th>
            <th>NGO</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Closure Request</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($case = mysqli_fetch_assoc($cases)): ?>
            <tr class="<?= $case['status'] === 'closed' ? 'table-secondary' : ($case['assigned_ngo_id'] ? 'table-success' : 'table-warning') ?>">
                <td><?= $case['id'] ?></td>
                <td><?= htmlspecialchars($case['case_title']) ?></td>
                <td><?= htmlspecialchars($case['user_name']) ?></td>
                <td><?= htmlspecialchars($case['user_email']) ?></td>
                <td><?= htmlspecialchars($case['ngo_name'] ?? '-') ?></td>
                <td><?= ucfirst($case['status']) ?></td>
                <td><?= $case['created_at'] ?></td>
                <td>
                    <?php if ($case['closure_id']): ?>
                        <span class="badge badge-<?= $case['closure_status'] == 'pending' ? 'warning' : ($case['closure_status'] == 'approved' ? 'success' : 'danger') ?>">
                            <?= ucfirst($case['closure_status']) ?>
                        </span>
                        <br><?= htmlspecialchars($case['closure_notes']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info mb-1">View Details</a>

                    <?php if ($case['closure_status'] === 'pending'): ?>
                        <a href="?closure_id=<?= $case['closure_id'] ?>&action=close" 
                           class="btn btn-sm btn-success mb-1"
                           onclick="return confirm('Approve closure request and close this case?');">
                           Approve & Close
                        </a>
                        <a href="?closure_id=<?= $case['closure_id'] ?>&action=reject" 
                           class="btn btn-sm btn-danger mb-1"
                           onclick="return confirm('Reject this closure request?');">
                           Reject
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

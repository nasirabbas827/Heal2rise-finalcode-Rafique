<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// --- LOGIC: Handle Closure Request (Approve/Reject) ---
if (isset($_GET['closure_id'], $_GET['action'])) {
    $closure_id = intval($_GET['closure_id']);
    $action = $_GET['action'];

    if ($action === 'close') {
        $stmt = $conn->prepare("
            UPDATE cases c
            JOIN closure_requests cr ON c.id = cr.case_id
            SET c.status = 'closed', c.closed_at = NOW(), cr.status = 'approved', cr.responded_at = NOW()
            WHERE cr.id = ?
        ");
        $stmt->bind_param("i", $closure_id);
        $stmt->execute();
        $msg = "Case closed successfully via closure request.";
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE closure_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $closure_id);
        $stmt->execute();
        $msg = "Closure request rejected.";
    }
}

// --- LOGIC: Handle Assignment & Manual Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_case'])) {
    $case_id = intval($_POST['case_id']);
    $assigned_ngo_id = intval($_POST['assigned_ngo_id']);
    $assigned_staff_id = intval($_POST['assigned_staff_id']);
    $new_status = $_POST['status']; // New status field from modal

    // If status is being set to closed manually, set the closed_at timestamp
    $closed_at_query = ($new_status === 'closed') ? ", closed_at = NOW()" : ", closed_at = NULL";

    $stmt = $conn->prepare("UPDATE cases SET assigned_ngo_id=?, assigned_staff_id=?, status=? $closed_at_query WHERE id=?");
    $stmt->bind_param("iisi", $assigned_ngo_id, $assigned_staff_id, $new_status, $case_id);
    
    if($stmt->execute()) {
        $msg = "Case #$case_id updated successfully (Status: " . ucfirst($new_status) . ").";
    } else {
        $msg = "Error updating case details.";
    }
}

// --- DATA FETCHING ---
$sql = "SELECT c.*, u.name AS user_name, n.ngo_name, tm.full_name AS staff_name,
               cr.id AS closure_id, cr.status AS closure_status
        FROM cases c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
        LEFT JOIN team_members tm ON c.assigned_staff_id = tm.id
        LEFT JOIN closure_requests cr ON c.id = cr.case_id
        ORDER BY c.created_at DESC";
$cases = mysqli_query($conn, $sql);

$ngo_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, ngo_name FROM ngos WHERE status='verified'"), MYSQLI_ASSOC);
$staff_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, full_name FROM team_members WHERE status='active'"), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin - Case Management</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-v-middle td { vertical-align: middle !important; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body class="bg-light">

<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 text-primary font-weight-bold"><i class="fas fa-tasks mr-2"></i>Admin Case Control</h4>
        </div>
        <div class="card-body">
            
            <?php if(isset($msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $msg ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-v-middle border">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Title & Date</th>
                            <th>User</th>
                            <th>Assignment</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($case = mysqli_fetch_assoc($cases)): ?>
                        <tr>
                            <td><strong>#<?= $case['id'] ?></strong></td>
                            <td>
                                <?= htmlspecialchars($case['case_title']) ?><br>
                                <small class="text-muted"><i class="far fa-calendar-alt mr-1"></i><?= date('M d, Y', strtotime($case['created_at'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($case['user_name']) ?></td>
                            <td>
                                <div class="small">
                                    <span class="text-muted text-uppercase font-weight-bold" style="font-size: 0.7rem;">NGO:</span> <?= htmlspecialchars($case['ngo_name'] ?? 'Unassigned') ?><br>
                                    <span class="text-muted text-uppercase font-weight-bold" style="font-size: 0.7rem;">Staff:</span> <?= htmlspecialchars($case['staff_name'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $badge_class = 'warning';
                                    if($case['status'] == 'approved') $badge_class = 'success';
                                    if($case['status'] == 'rejected') $badge_class = 'danger';
                                    if($case['status'] == 'closed') $badge_class = 'secondary';
                                ?>
                                <span class="badge badge-<?= $badge_class ?> px-3 py-2 text-uppercase">
                                    <?= $case['status'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info" title="Full Details"><i class="fas fa-eye"></i></a>
                                    
                                    <button type="button" class="btn btn-sm btn-dark" data-toggle="modal" data-target="#editModal<?= $case['id'] ?>" title="Edit Status/Assignment">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <?php if ($case['closure_status'] === 'pending'): ?>
                                        <a href="?closure_id=<?= $case['closure_id'] ?>&action=close" class="btn btn-sm btn-success" onclick="return confirm('Approve Closure?');"><i class="fas fa-check-double"></i></a>
                                    <?php endif; ?>
                                </div>

                                <div class="modal fade text-left" id="editModal<?= $case['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="post" action="">
                                            <input type="hidden" name="case_id" value="<?= $case['id'] ?>">
                                            <div class="modal-content">
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title">Edit Case #<?= $case['id'] ?></h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold text-primary">Update Overall Status</label>
                                                        <select name="status" class="form-control border-primary" required>
                                                            <option value="pending" <?= ($case['status']=='pending'?'selected':'') ?>>Pending Review</option>
                                                            <option value="approved" <?= ($case['status']=='approved'?'selected':'') ?>>Approved / Ongoing</option>
                                                            <option value="rejected" <?= ($case['status']=='rejected'?'selected':'') ?>>Rejected</option>
                                                            <option value="closed" <?= ($case['status']=='closed'?'selected':'') ?>>Closed</option>
                                                        </select>
                                                        <small class="form-text text-muted">Changing status to 'Closed' will mark the case as finished.</small>
                                                    </div>

                                                    <hr>

                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Assign NGO</label>
                                                        <select name="assigned_ngo_id" class="form-control" required>
                                                            <option value="">-- Select NGO --</option>
                                                            <?php foreach ($ngo_list as $ngo): ?>
                                                                <option value="<?= $ngo['id'] ?>" <?= ($ngo['id']==$case['assigned_ngo_id']?'selected':'') ?>><?= htmlspecialchars($ngo['ngo_name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Assign Team Member</label>
                                                        <select name="assigned_staff_id" class="form-control" required>
                                                            <option value="">-- Select Staff --</option>
                                                            <?php foreach ($staff_list as $member): ?>
                                                                <option value="<?= $member['id'] ?>" <?= ($member['id']==$case['assigned_staff_id']?'selected':'') ?>><?= htmlspecialchars($member['full_name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="assign_case" class="btn btn-primary font-weight-bold">Update Case Record</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
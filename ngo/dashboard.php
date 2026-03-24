<?php
session_start();
include('../config.php');

// 1. NGO Authentication Check
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];
$ngo_name = $_SESSION["ngo_name"] ?? "NGO Admin"; // Adjusted to match 'ngos' table column

$msg = '';

// =======================================================================
// 2. Handle Case Closure Approvals/Rejections (Action Logic)
// =======================================================================
if (isset($_GET['closure_id'], $_GET['action'])) {
    $closure_id = intval($_GET['closure_id']);
    $action = $_GET['action'];

    if (in_array($action, ['approve', 'reject'])) {
        $case_status = ($action === 'approve') ? 'closed' : 'approved';
        $request_status = ($action === 'approve') ? 'approved' : 'rejected';

        // Update Case and Closure Request status simultaneously
        $conn->begin_transaction();
        try {
            // Update the closure request
            $stmt1 = $conn->prepare("UPDATE closure_requests SET status = ?, responded_at = NOW() WHERE id = ? AND ngo_id = ?");
            $stmt1->bind_param("sii", $request_status, $closure_id, $ngo_id);
            $stmt1->execute();

            // Update the main case status
            $stmt2 = $conn->prepare("UPDATE cases c JOIN closure_requests cr ON c.id = cr.case_id SET c.status = ?, c.closed_at = IF(?='closed', NOW(), NULL) WHERE cr.id = ?");
            $stmt2->bind_param("ssi", $case_status, $case_status, $closure_id);
            $stmt2->execute();

            $conn->commit();
            $msg = "Closure request successfully " . ($action === 'approve' ? "approved and case closed." : "rejected.");
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error processing request.";
        }
    }
}

// =======================================================================
// 3. Fetch Dashboard Statistics
// =======================================================================
// Using a single query or separate ones for clarity
$stats = [];
$stats['total_cases'] = $conn->query("SELECT COUNT(*) FROM cases WHERE assigned_ngo_id = $ngo_id")->fetch_row()[0] ?? 0;
$stats['active_cases'] = $conn->query("SELECT COUNT(*) FROM cases WHERE assigned_ngo_id = $ngo_id AND status = 'approved'")->fetch_row()[0] ?? 0;
$stats['pending_cases'] = $conn->query("SELECT COUNT(*) FROM cases WHERE assigned_ngo_id = $ngo_id AND status = 'pending'")->fetch_row()[0] ?? 0;
$stats['total_staff'] = $conn->query("SELECT COUNT(*) FROM team_members")->fetch_row()[0] ?? 0; // Note: team_members needs ngo_id in schema to filter per NGO

// =======================================================================
// 4. Fetch Case List (Including pending closure indicators)
// =======================================================================
$query = "
    SELECT c.*, u.name AS user_name, 
           tm.full_name AS staff_name,
           (SELECT id FROM closure_requests WHERE case_id = c.id AND status = 'pending' LIMIT 1) as pending_closure_id
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN team_members tm ON c.assigned_staff_id = tm.id
    WHERE c.assigned_ngo_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$cases_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Dashboard | <?= htmlspecialchars($ngo_name) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .stat-card { border-radius: 15px; border: none; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .bg-gradient-blue { background: linear-gradient(45deg, #2193b0, #6dd5ed); color: white; }
        .bg-gradient-green { background: linear-gradient(45deg, #11998e, #38ef7d); color: white; }
        .bg-gradient-orange { background: linear-gradient(45deg, #f12711, #f5af19); color: white; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container-fluid px-4 py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="font-weight-bold">Welcome, <?= htmlspecialchars($ngo_name) ?></h2>
            <p class="text-muted">Control panel for managing cases, staff, and closure requests.</p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-info alert-dismissible fade show"><?= $msg ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card stat-card bg-gradient-blue shadow-sm">
                <div class="card-body">
                    <h6>Total Cases</h6>
                    <h2 class="mb-0 font-weight-bold"><?= $stats['total_cases'] ?></h2>
                    <i class="fas fa-folder-open fa-2x opacity-50 position-absolute" style="right:20px; bottom:20px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-gradient-green shadow-sm">
                <div class="card-body">
                    <h6>Active Therapy</h6>
                    <h2 class="mb-0 font-weight-bold"><?= $stats['active_cases'] ?></h2>
                    <i class="fas fa-heartbeat fa-2x opacity-50 position-absolute" style="right:20px; bottom:20px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-gradient-orange shadow-sm">
                <div class="card-body">
                    <h6>Pending Review</h6>
                    <h2 class="mb-0 font-weight-bold"><?= $stats['pending_cases'] ?></h2>
                    <i class="fas fa-clock fa-2x opacity-50 position-absolute" style="right:20px; bottom:20px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-dark text-white shadow-sm">
                <div class="card-body">
                    <h6>Field Staff</h6>
                    <h2 class="mb-0 font-weight-bold"><?= $stats['total_staff'] ?></h2>
                    <i class="fas fa-users fa-2x opacity-50 position-absolute" style="right:20px; bottom:20px;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold">Recent Case Activity</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Case Title</th>
                            <th>Patient</th>
                            <th>Staff Assigned</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $cases_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td>
                                <span class="font-weight-bold d-block"><?= htmlspecialchars($row['case_title']) ?></span>
                                <small class="text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= $row['staff_name'] ?? '<span class="text-danger small">Unassigned</span>' ?></td>
                            <td>
                                <span class="badge badge-<?= ($row['status'] == 'pending') ? 'warning' : (($row['status'] == 'closed') ? 'secondary' : 'success') ?> p-2 px-3">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </td>
                             
                            <td class="text-center">
                                <a href="view_case_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info rounded-pill px-3">View</a>
                                <?php if($row['pending_closure_id']): ?>
                                    <div class="btn-group ml-1">
                                        <a href="?closure_id=<?= $row['pending_closure_id'] ?>&action=approve" class="btn btn-sm btn-success" title="Approve Closure"><i class="fas fa-check"></i></a>
                                        <a href="?closure_id=<?= $row['pending_closure_id'] ?>&action=reject" class="btn btn-sm btn-danger" title="Reject Closure"><i class="fas fa-times"></i></a>
                                    </div>
                                <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
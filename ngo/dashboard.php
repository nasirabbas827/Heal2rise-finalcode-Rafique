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

// =======================================================================
// 1. NGO Dashboard Statistics Fetch
// =======================================================================
try {
    // Total Cases Assigned to this NGO (Regardless of status)
    $total_cases = $conn->query("SELECT COUNT(*) FROM cases WHERE assigned_ngo_id = {$ngo_id}")->fetch_row()[0] ?? 0;
    
    // Active Cases (Approved and not closed)
    $active_cases = $conn->query("SELECT COUNT(*) FROM cases WHERE assigned_ngo_id = {$ngo_id} AND status = 'approved' AND closed_at IS NULL")->fetch_row()[0] ?? 0;
    
    // Pending Cases (Requiring initial review)
    $pending_cases = $conn->query("SELECT COUNT(*) FROM cases WHERE assigned_ngo_id = {$ngo_id} AND status = 'pending'")->fetch_row()[0] ?? 0;

    // Total NGO Staff
    $total_staff = $conn->query("SELECT COUNT(*) FROM ngo_staff WHERE ngo_id = {$ngo_id}")->fetch_row()[0] ?? 0;

    // Pending Closure Requests
    $pending_closure_requests = $conn->query("SELECT COUNT(*) FROM closure_requests WHERE ngo_id = {$ngo_id} AND status = 'pending'")->fetch_row()[0] ?? 0;
    
    // Total Skill Programs
    $total_programs = $conn->query("SELECT COUNT(*) FROM skill_programs WHERE ngo_id = {$ngo_id}")->fetch_row()[0] ?? 0;

    // Fetch Staff List for quick view
    $staff_result = $conn->query("SELECT id, full_name, designation FROM ngo_staff WHERE ngo_id = {$ngo_id} ORDER BY full_name ASC LIMIT 5");

} catch (Exception $e) {
    // Basic error handling/fallbacks
    $total_cases = 0; $active_cases = 0; $pending_cases = 0; $total_staff = 0; $pending_closure_requests = 0; $total_programs = 0;
    $staff_result = false;
}

// =======================================================================
// 2. Handle Closure Request actions (Existing Logic)
// =======================================================================
$msg = '';
if (isset($_GET['closure_id'], $_GET['action'])) {
    $closure_id = intval($_GET['closure_id']);
    $action = $_GET['action'];

    if (in_array($action, ['close','reject'])) {
        // Status for the 'cases' table. 'closed' is a terminal status. 'approved' means work can continue.
        $case_status = $action === 'close' ? 'closed' : 'approved'; 
        // Status for the 'closure_requests' table.
        $request_status = $action === 'close' ? 'approved' : 'rejected'; 

        // Important: When rejecting a closure request, the case status must be set back to 'approved' (or similar working status)
        // If closed, set closed_at date.
        
        $stmt_update = $conn->prepare("
            UPDATE cases c 
            JOIN closure_requests cr ON c.id = cr.case_id 
            SET 
                c.status = ?, 
                c.closed_at = CASE WHEN ? = 'closed' THEN NOW() ELSE NULL END,
                cr.status = ?, 
                cr.responded_at = NOW() 
            WHERE cr.id = ? AND cr.ngo_id = ?
        ");
        $stmt_update->bind_param("sssi", $case_status, $case_status, $request_status, $closure_id, $ngo_id);
        $stmt_update->execute();
        $stmt_update->close();
        
        $msg = "Closure request has been ". ($request_status=='approved' ? 'approved (Case Closed)' : 'rejected') ." successfully.";
    }
}

// =======================================================================
// 3. Fetch all cases and closure requests for this NGO (Existing Logic)
// =======================================================================
$stmt = $conn->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email, s.full_name AS assigned_staff,
           cr.id AS closure_id, cr.status AS closure_status, cr.notes AS closure_notes
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngo_staff s ON c.assigned_staff_id = s.id
    LEFT JOIN closure_requests cr ON c.assigned_ngo_id = cr.ngo_id AND c.id = cr.case_id AND cr.status = 'pending' 
    WHERE c.assigned_ngo_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Dashboard - <?= htmlspecialchars($ngo_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card-stats {
            transition: transform 0.3s;
            border-left: 5px solid;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .card-assigned { border-color: #28a745; } /* Success/Active */
        .card-pending { border-color: #ffc107; } /* Warning */
        .card-staff { border-color: #17a2b8; } /* Info */
        .card-programs { border-color: #6f42c1; } /* Purple/Programs */
        .card-closure { border-color: #dc3545; } /* Danger/Closure */
        
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-approved { background-color: #28a745; color: #ffffff; }
        .badge-rejected { background-color: #dc3545; color: #ffffff; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 font-weight-bold text-info">NGO Management Center </h1>
        <p class="lead">Hello, <strong><?= htmlspecialchars($ngo_name) ?></strong> — Here is your operational overview.</p>
    </div>

    <?php if(!empty($msg)) echo "<div class='alert alert-success text-center'>$msg</div>"; ?>
    
    <h3 class="mb-4 text-secondary"><i class="fas fa-chart-bar mr-2"></i> Operational Overview</h3>
    <div class="row mb-5">
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card card-stats card-assigned shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-success"><i class="fas fa-check-circle"></i> Active Cases</h5>
                    <h3 class="stats-value text-dark"><?= $active_cases ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card card-stats card-pending shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning"><i class="fas fa-hourglass-half"></i> Pending Review</h5>
                    <h3 class="stats-value text-dark"><?= $pending_cases ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card card-stats card-closure shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger"><i class="fas fa-gavel"></i> Pending Closures</h5>
                    <h3 class="stats-value text-dark"><?= $pending_closure_requests ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card card-stats card-staff shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-info"><i class="fas fa-user-friends"></i> Total Staff</h5>
                    <h3 class="stats-value text-dark"><?= $total_staff ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card card-stats card-programs shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-purple"><i class="fas fa-graduation-cap"></i> Skill Programs</h5>
                    <h3 class="stats-value text-dark"><?= $total_programs ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card card-stats shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary"><i class="fas fa-folder"></i> Total Cases</h5>
                    <h3 class="stats-value text-dark"><?= $total_cases ?></h3>
                </div>
            </div>
        </div>
        
    </div>
    
    <hr>
    
    <div class="row mb-5">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-users-cog mr-2"></i> Staff Management</h4>
                </div>
                <div class="card-body">
                    <p>Manage counselors and psychiatrists working for your NGO.</p>
                    <a href="view_staff.php" class="btn btn-block btn-info mb-3">View/Add/Edit Staff</a>
                    
                    <h6 class="mt-4">Quick Staff List:</h6>
                    <ul class="list-group list-group-flush">
                        <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                            <?php while($staff = $staff_result->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center small">
                                    <?= htmlspecialchars($staff['full_name']) ?>
                                    <span class="badge badge-secondary badge-pill"><?= htmlspecialchars($staff['designation']) ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted">No staff added yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

<div class="col-lg-6 mb-4">
    <div class="card shadow h-100">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0"><i class="fas fa-folder-open mr-2"></i> Case Workflow Management</h4>
        </div>
        <div class="card-body">
            <p>Directly manage new case submissions, staff assignments, and ongoing support activities.</p>
            
            <a href="view_cases.php" class="btn btn-block btn-primary mb-3">
                <i class="fas fa-user-plus mr-1"></i> Assign Staff to Cases
            </a>
            
            <a href="view_cases.php" class="btn btn-block btn-warning mb-3">
                <i class="fas fa-exclamation-triangle mr-1"></i> Review Pending Cases
            </a>
            
            
        </div>
    </div>
</div>
    </div>

    <hr>

    <h3 class="mb-4 text-primary"><i class="fas fa-table mr-2"></i> Cases Assigned to Your NGO</h3>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Assigned Staff</th>
                        <th>Created At</th>
                        <th>Closure Request</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($case = $result->fetch_assoc()): ?>
                        <tr class="<?= $case['status']=='pending' ? 'table-warning' : ($case['status']=='closed' ? 'table-secondary' : 'table-success') ?>">
                            <td><?= $case['id'] ?></td>
                            <td><?= htmlspecialchars($case['case_title']) ?></td>
                            <td><?= htmlspecialchars($case['user_name']) ?></td>
                            <td><span class="badge badge-pill badge-<?= $case['status']=='pending' ? 'warning' : ($case['status']=='closed' ? 'secondary' : 'success') ?>"><?= ucfirst($case['status']) ?></span></td>
                            <td><?= $case['assigned_staff'] ?? 'Not Assigned' ?></td>
                            <td><?= date('M d, Y', strtotime($case['created_at'])) ?></td>
                            <td>
                                <?php if ($case['closure_id']): ?>
                                    <span class="badge badge-pending">PENDING</span>
                                    <br><small><?= htmlspecialchars(substr($case['closure_notes'], 0, 30)).'...' ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_case_details.php?id=<?= $case['id'] ?>" class="m-1 btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>
                                <a href="counseling_sessions.php?case_id=<?= $case['id'] ?>" class="m-1 btn btn-sm btn-warning" title="Manage Sessions"><i class="fas fa-comments"></i></a>
                                <a href="skills_progress.php?case_id=<?= $case['id'] ?>" class="m-1 btn btn-sm btn-dark" title="Progress Notes & Skills"><i class="fas fa-tasks"></i></a>
                                
                                <?php if($case['closure_id']): ?>
                                    <div class="mt-2">
                                        <a href="?closure_id=<?= $case['closure_id'] ?>&action=close" class="btn btn-sm btn-success mb-1" onclick="return confirm('APPROVE and CLOSE this case?');">Approve & Close</a>
                                        <a href="?closure_id=<?= $case['closure_id'] ?>&action=reject" class="btn btn-sm btn-danger mb-1" onclick="return confirm('REJECT this closure request and keep case open?');">Reject</a>
                                    </div>
                                <?php endif; ?>
                                <?php if($case['status']=='pending'): ?>
                                    <a href="update_case_status.php?id=<?= $case['id'] ?>&action=approve" class="btn btn-sm btn-success mt-2">Approve Case</a>
                                    <a href="update_case_status.php?id=<?= $case['id'] ?>&action=assign" class="btn btn-sm btn-primary mt-2">Assign Staff</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">No cases currently assigned to your NGO.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
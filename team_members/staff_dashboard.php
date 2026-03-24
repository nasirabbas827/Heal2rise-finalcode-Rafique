<?php
session_start();
include('../config.php'); 

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];
$staff_name = $_SESSION["name"];

// 2. Fetch Operational Stats for this Staff Member
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM cases 
    WHERE assigned_staff_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Fetch all cases assigned to this staff with Counselor Info (if exists)
$query = "
    SELECT c.id, c.case_title, c.status, c.created_at, u.name AS user_name, u.email AS user_email,
           co.full_name AS assigned_counselor
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN case_counselor cc ON c.id = cc.case_id
    LEFT JOIN counselors co ON cc.counselor_id = co.id
    WHERE c.assigned_staff_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$cases_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard | Monitoring Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .card-counter { box-shadow: 2px 2px 10px #DADADA; margin: 5px; padding: 20px 10px; border-radius: 10px; color: #FFF; }
        .bg-active { background-color: #007bff; }
        .bg-pending { background-color: #ffc107; color: #212529; }
        .bg-closed { background-color: #6c757d; }
        .table-card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h1 class="font-weight-bold">Staff Console</h1>
            <p class="text-muted">Welcome back, <strong><?= htmlspecialchars($staff_name) ?></strong>. Managing your assigned recovery cases.</p>
        </div>
        <div class="col-md-4 text-right">
             <span class="text-muted small">Current Date: <?= date('M d, Y') ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card-counter bg-pending shadow-sm text-center">
                <i class="fas fa-hourglass-start fa-2x"></i>
                <h2 class="mt-2"><?= $stats['pending'] ?></h2>
                <span class="text-uppercase small">Pending Review</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-counter bg-active shadow-sm text-center">
                <i class="fas fa-tasks fa-2x"></i>
                <h2 class="mt-2"><?= $stats['active'] ?></h2>
                <span class="text-uppercase small">Active Monitoring</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-counter bg-closed shadow-sm text-center">
                <i class="fas fa-check-double fa-2x"></i>
                <h2 class="mt-2"><?= $stats['closed'] ?></h2>
                <span class="text-uppercase small">Successfully Recovered</span>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary font-weight-bold"><i class="fas fa-clipboard-list mr-2"></i>Assigned Case Tracker</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Case ID</th>
                            <th>Case Title</th>
                            <th>Patient / User</th>
                            <th>Status</th>
                            <th>Counselor Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cases_result->num_rows > 0): ?>
                            <?php while ($case = $cases_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="align-middle">#<?= $case['id'] ?></td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold"><?= htmlspecialchars($case['case_title']) ?></div>
                                        <small class="text-muted">Filed: <?= date('d M Y', strtotime($case['created_at'])) ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <?= htmlspecialchars($case['user_name']) ?><br>
                                        <small class="text-info"><?= htmlspecialchars($case['user_email']) ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-pill badge-<?= ($case['status'] == 'pending') ? 'warning' : (($case['status'] == 'closed') ? 'secondary' : 'success') ?> px-3">
                                            <?= strtoupper($case['status']) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if ($case['assigned_counselor']): ?>
                                            <span class="text-dark small"><i class="fas fa-user-md mr-1"></i> <?= htmlspecialchars($case['assigned_counselor']) ?></span>
                                        <?php else: ?>
                                            <a href="recommend_counselor.php?case_id=<?= $case['id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.7rem;">Recommend Now</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="btn-group">
                                            <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info" title="View Case"><i class="fas fa-eye"></i></a>
                                            <a href="closure_request.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-dark" title="Request Closure"><i class="fas fa-lock"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">You have not been assigned any cases yet.</td>
                            </tr>
                        <?php endif; ?>
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
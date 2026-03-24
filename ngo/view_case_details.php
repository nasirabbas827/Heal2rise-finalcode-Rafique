<?php
session_start();
include('../config.php');

// 1. NGO Authentication
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];

if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);

// 2. Fetch Detailed Case Info
$query = "
    SELECT c.*, 
           u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
           tm.full_name AS assigned_staff, tm.designation AS staff_role,
           co.full_name AS assigned_counselor, co.specialization AS counselor_spec
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN team_members tm ON c.assigned_staff_id = tm.id
    LEFT JOIN case_counselor cc ON c.id = cc.case_id
    LEFT JOIN counselors co ON cc.counselor_id = co.id
    WHERE c.id = ? AND c.assigned_ngo_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $case_id, $ngo_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows !== 1){
    echo "<script>alert('Case not found or access denied'); window.location='dashboard.php';</script>";
    exit;
}

$case = $result->fetch_assoc();
$stmt->close();

// 3. Fetch Session Summary
$session_stmt = $conn->prepare("SELECT COUNT(*) as total_sessions FROM counseling_sessions WHERE case_id = ?");
$session_stmt->bind_param("i", $case_id);
$session_stmt->execute();
$session_count = $session_stmt->get_result()->fetch_assoc()['total_sessions'];
$session_stmt->close();

// 4. NEW: Fetch Assigned Skill Programs (Rehab Modules)
$skills_stmt = $conn->prepare("
    SELECT sp.*, co.full_name as counselor_name 
    FROM skill_programs sp
    JOIN counselors co ON sp.counselor_id = co.id
    WHERE sp.case_id = ?
    ORDER BY sp.created_at DESC
");
$skills_stmt->bind_param("i", $case_id);
$skills_stmt->execute();
$skills_result = $skills_stmt->get_result();
$skills_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO | Case View #<?= $case_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card { border-radius: 10px; overflow: hidden; }
        .skill-badge { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        .rehab-list-item { border-left: 3px solid #6610f2; margin-bottom: 10px; background: #f8f9fa; transition: 0.2s; }
        .rehab-list-item:hover { background: #f1f1f1; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-bold mb-0 text-dark">Case Overview #<?= $case_id ?></h2>
            <p class="text-muted mb-0">NGO Management Console</p>
        </div>
        <span class="badge badge-<?= $case['status'] == 'closed' ? 'secondary' : 'primary' ?> p-2 px-4 shadow-sm">
            Status: <?= strtoupper($case['status']) ?>
        </span>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h4 class="font-weight-bold text-primary"><?= htmlspecialchars($case['case_title']) ?></h4>
                        <small class="text-muted"><i class="fas fa-calendar-alt mr-1"></i> <?= date('M d, Y', strtotime($case['created_at'])) ?></small>
                    </div>
                    <hr>
                    <h6 class="text-muted font-weight-bold small text-uppercase mb-2">Patient Complaint / Description</h6>
                    <p class="text-dark lead" style="font-size: 1.1rem;"><?= nl2br(htmlspecialchars($case['description'])) ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-graduation-cap mr-2 text-primary"></i>Assigned Rehab Programs</h5>
                </div>
                <div class="card-body pt-0">
                    <?php if($skills_result->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($sk = $skills_result->fetch_assoc()): ?>
                                <div class="list-group-item rehab-list-item border-top-0 rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($sk['program_name']) ?></h6>
                                        <span class="badge badge-info skill-badge"><?= $sk['mode'] ?></span>
                                    </div>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($sk['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-secondary font-weight-bold"><?= $sk['duration_weeks'] ?> Weeks Duration</small>
                                        <small class="text-primary">Counselor: <?= htmlspecialchars($sk['counselor_name']) ?></small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 border rounded bg-light">
                            <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">No specialized rehab modules assigned to this case yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 mb-4 text-center py-3 bg-white">
                        <h2 class="font-weight-bold text-info mb-0"><?= $session_count ?></h2>
                        <small class="text-uppercase font-weight-bold text-muted">Sessions Completed</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 mb-4 text-center py-3 bg-white">
                        <h2 class="font-weight-bold text-warning mb-0"><?= htmlspecialchars($case['severity'] ?? 'N/A') ?></h2>
                        <small class="text-uppercase font-weight-bold text-muted">Severity Level</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white font-weight-bold">Patient Profile</div>
                <div class="card-body">
                    <p class="mb-2"><strong><i class="fas fa-user mr-2"></i></strong> <?= htmlspecialchars($case['user_name']) ?></p>
                    <p class="mb-2"><strong><i class="fas fa-envelope mr-2"></i></strong> <a href="mailto:<?= $case['user_email'] ?>"><?= htmlspecialchars($case['user_email']) ?></a></p>
                    <p class="mb-0"><strong><i class="fas fa-phone mr-2"></i></strong> <?= htmlspecialchars($case['user_phone'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 bg-dark text-white">
                <div class="card-body">
                    <h6 class="font-weight-bold small text-uppercase text-info mb-3">Assigned Team Member</h6>
                    <?php if($case['assigned_staff']): ?>
                        <h6 class="mb-1 font-weight-bold"><?= $case['assigned_staff'] ?></h6>
                        <p class="small text-muted mb-0"><?= htmlspecialchars($case['staff_role']) ?></p>
                    <?php else: ?>
                        <p class="text-warning small mb-0 font-italic">No staff member assigned yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 border-left-info">
                <div class="card-body">
                    <h6 class="font-weight-bold small text-uppercase text-muted mb-3">Active Counselor</h6>
                    <?php if($case['assigned_counselor']): ?>
                        <h6 class="mb-1 text-primary font-weight-bold"><?= $case['assigned_counselor'] ?></h6>
                        <small class="text-secondary"><?= htmlspecialchars($case['counselor_spec']) ?></small>
                    <?php else: ?>
                        <p class="text-danger small mb-0">Referral Pending</p>
                    <?php endif; ?>
                </div>
            </div>

            <a href="dashboard.php" class="btn btn-outline-secondary btn-block shadow-sm">
                <i class="fas fa-chevron-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
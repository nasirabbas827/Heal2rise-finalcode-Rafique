<?php
session_start();
include('../config.php');

// 1. Security Check
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];

if (!isset($_GET['id'])) {
    header("location: staff_dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);

// 2. Fetch Full Case Context
$query = "
    SELECT 
        c.*,
        u.name AS user_name, u.email AS user_email, u.phone AS user_phone, u.age, u.gender,
        n.ngo_name, n.contact_person AS ngo_contact, n.email AS ngo_email, n.phone AS ngo_phone
    FROM cases c
    JOIN users u ON c.user_id = u.id
    JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE c.id = ? AND c.assigned_staff_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    die("<div class='container mt-5 alert alert-danger text-center'>Case not found or unauthorized access.</div>");
}

// 3. Fetch Assigned Counselor(s)
$counselor_query = "
    SELECT co.full_name, co.specialization, cc.recommended_at 
    FROM case_counselor cc
    JOIN counselors co ON cc.counselor_id = co.id
    WHERE cc.case_id = ?
";
$c_stmt = $conn->prepare($counselor_query);
$c_stmt->bind_param("i", $case_id);
$c_stmt->execute();
$assigned_counselors = $c_stmt->get_result();

// 4. Fetch Counseling Sessions
$session_query = "
    SELECT cs.*, co.full_name as counselor_name 
    FROM counseling_sessions cs
    JOIN counselors co ON cs.counselor_id = co.id
    WHERE cs.case_id = ? 
    ORDER BY cs.session_date DESC
";
$s_stmt = $conn->prepare($session_query);
$s_stmt->bind_param("i", $case_id);
$s_stmt->execute();
$sessions = $s_stmt->get_result();

// 5. NEW: Fetch Skill/Rehab Programs assigned to this case
$skills_query = "
    SELECT sp.*, co.full_name as counselor_name 
    FROM skill_programs sp
    JOIN counselors co ON sp.counselor_id = co.id
    WHERE sp.case_id = ? 
    ORDER BY sp.created_at DESC
";
$sk_stmt = $conn->prepare($skills_query);
$sk_stmt->bind_param("i", $case_id);
$sk_stmt->execute();
$skills = $sk_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Monitoring | #<?= $case_id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card { border-radius: 12px; border: none; }
        .skill-item { border-left: 4px solid #6f42c1; background: #fdfbff; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="font-weight-bold">Case Monitoring: <span class="text-primary">#<?= $case_id ?></span></h4>
        <span class="badge badge-<?= $case['status'] == 'closed' ? 'secondary' : 'success' ?> p-2 px-3">
            STATUS: <?= strtoupper($case['status']) ?>
        </span>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="font-weight-bold"><?= htmlspecialchars($case['case_title']) ?></h3>
                    <p class="text-muted small">Opened: <?= date('M d, Y', strtotime($case['created_at'])) ?></p>
                    <hr>
                    <h6 class="text-uppercase font-weight-bold small text-muted">Description</h6>
                    <p class="lead"><?= nl2br(htmlspecialchars($case['description'])) ?></p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white font-weight-bold">
                    <i class="fas fa-graduation-cap mr-2 text-primary"></i>Rehabilitation & Skill Programs
                </div>
                <div class="card-body">
                    <?php if ($skills->num_rows > 0): ?>
                        <div class="row">
                        <?php while($sk = $skills->fetch_assoc()): ?>
                            <div class="col-md-6 mb-3">
                                <div class="p-3 skill-item rounded shadow-sm">
                                    <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($sk['program_name']) ?></h6>
                                    <div class="small text-muted mb-2">
                                        <i class="fas fa-clock mr-1"></i> <?= $sk['duration_weeks'] ?> Weeks | 
                                        <i class="fas fa-laptop mr-1"></i> <?= $sk['mode'] ?>
                                    </div>
                                    <p class="x-small text-dark mb-2"><?= substr(htmlspecialchars($sk['description']), 0, 100) ?>...</p>
                                    <small class="text-primary">By: <?= htmlspecialchars($sk['counselor_name']) ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted py-3 small">No rehabilitation programs assigned yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white font-weight-bold">
                    <i class="fas fa-chart-line mr-2 text-info"></i>Counseling Progress & Logs
                </div>
                <div class="card-body">
                    <?php if ($sessions->num_rows > 0): ?>
                        <?php while($sess = $sessions->fetch_assoc()): ?>
                            <div class="border-bottom mb-3 pb-3">
                                <div class="d-flex justify-content-between">
                                    <h6 class="font-weight-bold mb-0 text-dark"><?= htmlspecialchars($sess['session_type']) ?></h6>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($sess['session_date'])) ?></small>
                                </div>
                                <p class="small text-secondary my-2"><?= nl2br(htmlspecialchars($sess['notes'])) ?></p>
                                <small class="text-info font-italic">Logged by: <?= htmlspecialchars($sess['counselor_name']) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted py-3 small">No counseling sessions recorded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white font-weight-bold">Patient Details</div>
                <div class="card-body">
                    <h5 class="mb-1 font-weight-bold"><?= htmlspecialchars($case['user_name']) ?></h5>
                    <p class="text-muted small"><?= $case['gender'] ?>, <?= $case['age'] ?> Yrs</p>
                    <hr>
                    <p class="small mb-1"><i class="fas fa-envelope mr-2"></i> <?= htmlspecialchars($case['user_email']) ?></p>
                    <p class="small mb-0"><i class="fas fa-phone mr-2"></i> <?= htmlspecialchars($case['user_phone']) ?></p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white font-weight-bold">Assigned Counselors</div>
                <div class="card-body">
                    <?php if ($assigned_counselors->num_rows > 0): ?>
                        <?php while($c = $assigned_counselors->fetch_assoc()): ?>
                            <div class="mb-3">
                                <h6 class="mb-0 font-weight-bold text-info"><?= htmlspecialchars($c['full_name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($c['specialization']) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="small text-danger">No Counselor recommended yet.</p>
                        <a href="recommend_counselor.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-outline-primary btn-block">Recommend Now</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="list-group shadow-sm">
                <?php if($case['status'] !== 'closed'): ?>
                    <a href="closure_request.php?id=<?= $case['id'] ?>" class="list-group-item list-group-item-action list-group-item-warning font-weight-bold text-center">
                        <i class="fas fa-check-circle mr-2"></i> Initiate Case Closure
                    </a>
                <?php endif; ?>
                <a href="staff_dashboard.php" class="list-group-item list-group-item-action text-center small text-muted">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
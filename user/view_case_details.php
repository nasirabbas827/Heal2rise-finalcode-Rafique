<?php
session_start();
include('../config.php');

// 1. Check user login
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];

// 2. Check if case ID is provided
if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);

// 3. Fetch case details with NGO and Staff
$stmt = $conn->prepare("
    SELECT 
        c.*, 
        n.ngo_name, n.contact_person, n.phone AS ngo_phone, n.email AS ngo_email,
        s.full_name AS staff_name, s.email AS staff_email, s.phone AS staff_phone, s.designation AS staff_designation
    FROM cases c
    JOIN ngos n ON c.assigned_ngo_id = n.id
    LEFT JOIN team_members s ON c.assigned_staff_id = s.id
    WHERE c.id=? AND c.user_id=?
");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    die("<p class='text-center mt-5'>Case not found or access denied.</p>");
}

// 4. Fetch Counseling Sessions for this user
$session_stmt = $conn->prepare("
    SELECT cs.*, co.full_name as counselor_name 
    FROM counseling_sessions cs
    JOIN counselors co ON cs.counselor_id = co.id
    WHERE cs.case_id = ?
    ORDER BY cs.session_date DESC
");
$session_stmt->bind_param("i", $case_id);
$session_stmt->execute();
$sessions = $session_stmt->get_result();

// 5. Fetch Recommended Skill Programs (Step 9 of workflow)
$skill_stmt = $conn->prepare("
    SELECT sp.* FROM skill_programs sp
    JOIN counselors co ON sp.counselor_id = co.id
    JOIN case_counselor cc ON cc.counselor_id = co.id
    WHERE cc.case_id = ?
");
$skill_stmt->bind_param("i", $case_id);
$skill_stmt->execute();
$skills = $skill_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Case Progress | #<?= $case_id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

    <style>
        .status-header { background: #f8f9fa; border-radius: 10px; padding: 20px; border-bottom: 4px solid #007bff; }
        .info-card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-radius: 12px; }
        .session-item { border-left: 4px solid #17a2b8; margin-bottom: 10px; background: #fff; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="font-weight-bold text-dark">Case Monitoring</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chevron-left"></i> Back to Dashboard</a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card info-card mb-4">
                <div class="card-body">
                    <div class=" justify-content-between">
                        <h4 class="text-primary font-weight-bold"><?= htmlspecialchars($case['case_title']) ?></h4>
                        <span class="badge badge-<?= $case['status']=='closed' ? 'secondary' : 'success' ?> px-3">
                            <?= strtoupper($case['status']) ?>
                        </span>
                    </div>
                    <p class="mt-3 text-secondary"><?= nl2br(htmlspecialchars($case['description'])) ?></p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block">Severity</small>
                            <span class="font-weight-bold"><?= $case['severity'] ?></span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Category</small>
                            <span class="font-weight-bold"><?= $case['problem_category'] ?></span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Risk</small>
                            <span class="font-weight-bold"><?= $case['risk_level'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="font-weight-bold mb-3 mt-4"><i class="fas fa-calendar-alt text-info mr-2"></i> Your Sessions</h5>
            <?php if($sessions->num_rows > 0): ?>
                <?php while($s = $sessions->fetch_assoc()): ?>
                    <div class="card session-item shadow-sm">
                        <div class="card-body py-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($s['session_type']) ?></h6>
                                    <p class="small text-muted mb-0">With: <?= htmlspecialchars($s['counselor_name']) ?> | <?= date('M d, h:i A', strtotime($s['session_date'])) ?></p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <?php if($s['meeting_link']): ?>
                                        <a href="<?= $s['meeting_link'] ?>" target="_blank" class="btn btn-sm btn-info">Join Meeting</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-light border text-center">No sessions scheduled yet. Your assigned counselor will update this soon.</div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <div class="card info-card mb-4">
                <div class="card-header bg-white font-weight-bold border-0">Assigned NGO Support</div>
                <div class="card-body pt-0">
                    <p class="mb-1 font-weight-bold"><?= htmlspecialchars($case['ngo_name']) ?></p>
                    <p class="small text-muted mb-1"><i class="fas fa-user mr-2"></i> <?= htmlspecialchars($case['contact_person']) ?></p>
                    <p class="small text-muted mb-1"><i class="fas fa-phone mr-2"></i> <?= htmlspecialchars($case['ngo_phone']) ?></p>
                    <p class="small text-muted mb-0"><i class="fas fa-envelope mr-2"></i> <?= htmlspecialchars($case['ngo_email']) ?></p>
                </div>
            </div>

            <?php if($case['staff_name']): ?>
            <div class="card info-card mb-4 bg-primary text-white">
                <div class="card-body">
                    <h6 class="small text-uppercase opacity-75">Monitoring Staff</h6>
                    <h5 class="mb-1 font-weight-bold"><?= htmlspecialchars($case['staff_name']) ?></h5>
                    <p class="small mb-0"><?= htmlspecialchars($case['staff_designation']) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card info-card">
                <div class="card-header bg-white font-weight-bold border-0">Rehabilitation & Skills</div>
                <div class="card-body pt-0">
                    <?php if($skills->num_rows > 0): ?>
                        <?php while($sk = $skills->fetch_assoc()): ?>
                            <div class="p-2 border rounded mb-2">
                                <h6 class="small font-weight-bold mb-1"><?= htmlspecialchars($sk['program_name']) ?></h6>
                                <p class="x-small text-muted mb-0" style="font-size: 0.75rem;"><?= htmlspecialchars($sk['duration_weeks']) ?> weeks • <?= $sk['mode'] ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted small">No active skill programs yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <?php if($case['status'] === 'pending'): ?>
            <a href="edit_case.php?id=<?= $case['id'] ?>" class="btn btn-success px-4">Edit My Request</a>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
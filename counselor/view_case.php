<?php
session_start();
include('../config.php');

// 1. Authentication & Role Validation
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'counselor') {
    header("location: ../login.php");
    exit;
}

$counselor_id = $_SESSION["id"];

// 2. Validate Case ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);

// 3. Fetch Case, Patient, and Referral Details
// Joins: case_counselor (assignment), cases (main info), users (patient), 
// team_members (staff who referred), and ngos (originating NGO)
$query = "
    SELECT c.*, 
           u.name AS patient_name, u.email AS patient_email, u.phone AS patient_phone, 
           u.gender, u.age, u.emergency_contact,
           tm.full_name AS referred_by_staff, tm.designation AS staff_role,
           n.ngo_name, n.phone AS ngo_contact
    FROM case_counselor cc
    JOIN cases c ON cc.case_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN team_members tm ON cc.recommended_by = tm.id
    LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE cc.case_id = ? AND cc.counselor_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $case_id, $counselor_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    die("<div class='container mt-5 alert alert-danger text-center'>Unauthorized Access: This case is not assigned to you or does not exist.</div>");
}

// 4. Fetch existing counseling sessions recorded BY THIS COUNSELOR for this case
$session_query = "SELECT * FROM counseling_sessions WHERE case_id = ? AND counselor_id = ? ORDER BY session_date DESC";
$stmt = $conn->prepare($session_query);
$stmt->bind_param("ii", $case_id, $counselor_id);
$stmt->execute();
$sessions = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Details | #<?= $case['id'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="dashboard.php" class="text-secondary"><i class="fas fa-chevron-left"></i> Back to Counselor Dashboard</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 font-weight-bold text-primary">Case #<?= $case['id'] ?>: <?= htmlspecialchars($case['case_title']) ?></h4>
                        <span class="badge badge-pill badge-<?= ($case['status'] == 'closed') ? 'secondary' : 'success' ?> px-3 py-2">
                            <?= strtoupper($case['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-uppercase text-muted font-weight-bold small">Patient's Narrative / Problem Description</h6>
                    <p class="mt-3" style="line-height: 1.6; font-size: 1.1rem; color: #444;">
                        <?= nl2br(htmlspecialchars($case['description'])) ?>
                    </p>
                    
                    <hr class="my-4">
                    
                    <div class="row text-center">
                        <div class="col-4 border-right">
                            <h6 class="text-muted small mb-1">Category</h6>
                            <p class="font-weight-bold mb-0"><?= htmlspecialchars($case['problem_category'] ?? 'General') ?></p>
                        </div>
                        <div class="col-4 border-right">
                            <h6 class="text-muted small mb-1">Severity</h6>
                            <p class="font-weight-bold mb-0 text-<?= ($case['severity'] == 'High') ? 'danger' : 'info' ?>">
                                <?= htmlspecialchars($case['severity'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="col-4">
                            <h6 class="text-muted small mb-1">Risk Level</h6>
                            <p class="font-weight-bold mb-0"><?= htmlspecialchars($case['risk_level'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-light"><i class="fas fa-history mr-2"></i>Treatment History</h5>
                    <a href="manage_sessions.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-plus"></i> Log New Session
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($sessions->num_rows > 0): ?>
                        <div class="timeline-wrapper">
                            <?php while($sess = $sessions->fetch_assoc()): ?>
                                <div class="pb-4 border-left pl-4 position-relative" style="border-width: 2px !important;">
                                    <i class="fas fa-check-circle text-success position-absolute" style="left: -11px; top: 0; background: white; padding: 2px;"></i>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($sess['session_type']) ?></h6>
                                        <span class="small text-muted"><?= date('M d, Y', strtotime($sess['session_date'])) ?></span>
                                    </div>
                                    <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($sess['notes'])) ?></p>
                                    <?php if($sess['meeting_link']): ?>
                                        <a href="<?= $sess['meeting_link'] ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 small">Join Link</a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No therapy sessions have been recorded for this case yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="bg-primary py-4 text-center text-white">
                    <i class="fas fa-user-circle fa-4x"></i>
                    <h5 class="mt-2 mb-0"><?= htmlspecialchars($case['patient_name']) ?></h5>
                    <small><?= htmlspecialchars($case['gender']) ?> | <?= $case['age'] ?> Years Old</small>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><i class="fas fa-envelope mr-2 text-muted"></i> <?= htmlspecialchars($case['patient_email']) ?></p>
                    <p class="small mb-2"><i class="fas fa-phone mr-2 text-muted"></i> <?= htmlspecialchars($case['patient_phone'] ?? 'N/A') ?></p>
                    <p class="small mb-0"><i class="fas fa-heartbeat mr-2 text-muted"></i> Emergency: <?= htmlspecialchars($case['emergency_contact'] ?? 'None') ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 bg-info text-white">
                <div class="card-body">
                    <h6 class="font-weight-bold text-uppercase small"><i class="fas fa-hand-holding-heart mr-2"></i>Referral Source</h6>
                    <hr class="border-light">
                    <p class="small mb-1"><strong>NGO:</strong> <?= htmlspecialchars($case['ngo_name']) ?></p>
                    <p class="small mb-1"><strong>Staff Member:</strong> <?= htmlspecialchars($case['referred_by_staff']) ?></p>
                    <p class="small mb-0 text-white-50 font-italic"><?= htmlspecialchars($case['staff_role']) ?></p>
                </div>
            </div>

            <div class="list-group shadow-sm border-0">
                <a href="manage_programs.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-book-reader mr-2 text-primary"></i> Recommended Skill Programs
                </a>
                
            </div>
        </div>
    </div>
</div>

<style>
    .btn-xs { padding: 0.125rem 0.25rem; font-size: 0.75rem; }
    .timeline-wrapper { border-left: 2px solid #e9ecef; margin-left: 10px; }
</style>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
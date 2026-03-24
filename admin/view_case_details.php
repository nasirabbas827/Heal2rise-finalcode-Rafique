<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: view_cases.php");
    exit;
}

$case_id = intval($_GET['id']);

// 2. Fetch Master Case Info (User, NGO, and Team Member)
$stmt = $conn->prepare("
    SELECT c.*, 
           u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
           n.ngo_name, n.email AS ngo_email,
           tm.full_name AS staff_name, tm.designation AS staff_role
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
    LEFT JOIN team_members tm ON c.assigned_staff_id = tm.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    die("Case not found.");
}

// 3. Fetch Counseling Sessions & Assigned Counselor Info
$stmt = $conn->prepare("
    SELECT s.*, co.full_name AS counselor_name 
    FROM counseling_sessions s
    LEFT JOIN counselors co ON s.counselor_id = co.id
    WHERE s.case_id = ?
    ORDER BY s.session_date DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$sessions = $stmt->get_result();
$stmt->close();

// 4. Fetch Skill/Rehab Programs (via Counselor specialization)
$stmt = $conn->prepare("
    SELECT sp.*, co.full_name AS instructor 
    FROM skill_programs sp
    JOIN counselors co ON sp.counselor_id = co.id
    JOIN case_counselor cc ON cc.counselor_id = co.id
    WHERE cc.case_id = ?
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$skills = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin View | Case #<?= $case_id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .detail-card { border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .section-header { border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; margin-bottom: 20px; color: #333; font-weight: bold; }
        .info-label { color: #888; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .status-badge { padding: 8px 15px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">

<?php include('admin_navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Case Management Profile</h2>
            <span class="text-muted">ID: #<?= $case_id ?> | Created: <?= date('M d, Y', strtotime($case['created_at'])) ?></span>
        </div>
        <div>
            <a href="view_cases.php" class="btn btn-outline-secondary btn-sm mr-2">Back</a>
            
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card detail-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h4 class="section-header w-100">Case Details</h4>
                        <span class="badge status-badge badge-<?= $case['status'] == 'closed' ? 'secondary' : 'success' ?>">
                            <?= strtoupper($case['status']) ?>
                        </span>
                    </div>
                    <h5 class="font-weight-bold text-dark"><?= htmlspecialchars($case['case_title']) ?></h5>
                    <p class="text-secondary"><?= nl2br(htmlspecialchars($case['description'])) ?></p>
                    
                    <div class="row mt-4">
                        <div class="col-md-4 border-right">
                            <span class="info-label">Category</span>
                            <p class="font-weight-bold"><?= htmlspecialchars($case['problem_category'] ?: 'General') ?></p>
                        </div>
                        <div class="col-md-4 border-right">
                            <span class="info-label">Severity</span>
                            <p class="text-danger font-weight-bold"><?= htmlspecialchars($case['severity'] ?: 'Normal') ?></p>
                        </div>
                        <div class="col-md-4">
                            <span class="info-label">Risk Level</span>
                            <p class="text-warning font-weight-bold"><?= htmlspecialchars($case['risk_level'] ?: 'Low') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card detail-card">
                <div class="card-body">
                    <h4 class="section-header">Therapy & Counseling Progress</h4>
                    <?php if($sessions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Counselor</th>
                                    <th>Type</th>
                                    <th>Notes</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($s = $sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($s['session_date'])) ?></td>
                                    <td class="font-weight-bold"><?= htmlspecialchars($s['counselor_name']) ?></td>
                                    <td><span class="badge badge-light border"><?= htmlspecialchars($s['session_type']) ?></span></td>
                                    <td><?= substr(htmlspecialchars($s['notes']), 0, 50) ?>...</td>
                                    <td><?= $s['duration_minutes'] ?> min</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comment-medical fa-3x text-light mb-2"></i>
                            <p class="text-muted">No counseling sessions recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card detail-card">
                <div class="card-body">
                    <h4 class="section-header">Rehabilitation & Skill Programs</h4>
                    <?php if($skills->num_rows > 0): ?>
                    <div class="row">
                        <?php while($sk = $skills->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="p-3 border rounded">
                                <h6 class="font-weight-bold mb-1 text-primary"><?= htmlspecialchars($sk['program_name']) ?></h6>
                                <p class="small text-muted mb-2"><?= htmlspecialchars($sk['description']) ?></p>
                                <div class="d-flex justify-content-between small">
                                    <span><i class="fas fa-calendar mr-1"></i> <?= $sk['duration_weeks'] ?> Weeks</span>
                                    <span><i class="fas fa-laptop mr-1"></i> <?= $sk['mode'] ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-muted text-center small">No rehabilitation programs assigned.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card detail-card border-top border-primary">
                <div class="card-body">
                    <h5 class="section-header small">Patient Profile</h5>
                    <p class="mb-1 font-weight-bold text-uppercase"><?= htmlspecialchars($case['user_name']) ?></p>
                    <p class="small text-muted mb-3"><i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($case['user_email']) ?></p>
                    <p class="small mb-0"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($case['user_phone'] ?: 'N/A') ?></p>
                </div>
            </div>

            <div class="card detail-card">
                <div class="card-body">
                    <h5 class="section-header small">Assigned NGO Provider</h5>
                    <p class="mb-1 font-weight-bold"><?= htmlspecialchars($case['ngo_name'] ?? 'Not Assigned') ?></p>
                    <p class="small text-info"><i class="fas fa-link mr-1"></i> <?= htmlspecialchars($case['ngo_email'] ?? '') ?></p>
                </div>
            </div>

            <div class="card detail-card bg-dark text-white">
                <div class="card-body">
                    <h5 class="section-header small text-white border-secondary">Monitoring Staff</h5>
                    <p class="mb-0 font-weight-bold text-warning"><?= htmlspecialchars($case['staff_name'] ?? 'Pending Assignment') ?></p>
                    <small class="opacity-50"><?= htmlspecialchars($case['staff_role'] ?? 'N/A') ?></small>
                </div>
            </div>

            <?php if($case['status'] == 'closed'): ?>
                <div class="alert alert-secondary text-center">
                    <i class="fas fa-archive mb-2"></i><br>
                    <strong>Case Closed</strong><br>
                    <small>On: <?= date('d M Y', strtotime($case['closed_at'])) ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
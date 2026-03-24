<?php
session_start();
include('../config.php');

// 1. Security Check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];

// 2. Get Case ID from URL (using 'id' to match your dashboard links)
if (!isset($_GET['id'])) {
    header("location: view_cases.php");
    exit;
}

$case_id = intval($_GET['id']);

// 3. Fetch case info to verify ownership
$stmt = $conn->prepare("SELECT case_title, status FROM cases WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    die("<div class='container mt-5 alert alert-danger text-center'>Unauthorized access to this case.</div>");
}

// 4. Fetch Skill Programs SPECIFICALLY linked to this Case ID
$stmt = $conn->prepare("
    SELECT sp.*, co.full_name AS counselor_name 
    FROM skill_programs sp
    JOIN counselors co ON sp.counselor_id = co.id
    WHERE sp.case_id = ? 
    ORDER BY sp.created_at DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$skills_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Learning Modules | <?= htmlspecialchars($case['case_title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

    <style>
        .skill-hero { background: linear-gradient(45deg, #6610f2, #6f42c1); color: white; border-radius: 15px; padding: 30px; }
        .program-card { border: none; border-radius: 15px; transition: 0.3s; }
        .program-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .mode-badge { position: absolute; top: 20px; right: 20px; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="skill-hero shadow-sm mb-5">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="font-weight-bold">Rehabilitation & Skills</h2>
                <p class="lead mb-0">Custom modules assigned by your counselor to aid your recovery journey.</p>
                <small class="opacity-75">Case: <?= htmlspecialchars($case['case_title']) ?></small>
            </div>
            <div class="col-md-4 text-right">
                <i class="fas fa-user-graduate fa-5x opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if($skills_result->num_rows > 0): ?>
            <?php while($s = $skills_result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card program-card shadow-sm h-100">
                        <div class="card-body p-4">
                            <span class="badge badge-pill badge-primary mode-badge px-3 py-2"><?= $s['mode'] ?></span>
                            
                            <h4 class="font-weight-bold text-dark pr-5"><?= htmlspecialchars($s['program_name']) ?></h4>
                            <p class="text-muted mt-3"><?= nl2br(htmlspecialchars($s['description'])) ?></p>
                            
                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-uppercase font-weight-bold text-secondary d-block">Assigned By</small>
                                    <span class="text-primary"><?= htmlspecialchars($s['counselor_name']) ?></span>
                                </div>
                                <div class="text-right">
                                    <small class="text-uppercase font-weight-bold text-secondary d-block">Duration</small>
                                    <span class="badge badge-light border"><?= $s['duration_weeks'] ?> Weeks</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="bg-white p-5 rounded shadow-sm border">
                    <i class="fas fa-tasks fa-4x text-light mb-3"></i>
                    <h5 class="text-muted">No specific modules assigned yet.</h5>
                    <p class="small text-secondary">Your counselor will list your rehabilitation programs here once they are finalized.</p>
                    <a href="dashboard.php" class="btn btn-primary mt-3">Return to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
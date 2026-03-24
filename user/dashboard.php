<?php
session_start();
include('config.php'); 

// 1. Check user login and role
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];
$name = $_SESSION["name"];

// =======================================================================
// 2. NGO Search and List Logic
// =======================================================================
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM ngos WHERE status='verified' AND (ngo_name LIKE ? OR service_areas LIKE ?) ORDER BY id DESC");
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT id, ngo_name, contact_person, phone, service_areas FROM ngos WHERE status='verified' ORDER BY id DESC");
}
$stmt->execute();
$ngo_result = $stmt->get_result();
$stmt->close();

// =======================================================================
// 3. User-Specific Case Data Fetch
// =======================================================================
// Fetch Active/Pending Cases for the current user
$case_stmt = $conn->prepare("
    SELECT 
        c.id, c.case_title, c.status, c.created_at, n.ngo_name 
    FROM cases c
    LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$case_stmt->bind_param("i", $user_id);
$case_stmt->execute();
$case_result = $case_stmt->get_result();
$case_stmt->close();

// =======================================================================
// 4. FIX: Fetch Upcoming Counseling Sessions (Joining with counselors table)
// =======================================================================
$session_stmt = $conn->prepare("
    SELECT 
        cs.session_date, 
        cs.session_type, 
        cs.duration_minutes, 
        cs.meeting_link, 
        c.case_title, 
        co.full_name as counselor_name
    FROM counseling_sessions cs
    JOIN cases c ON cs.case_id = c.id
    JOIN counselors co ON cs.counselor_id = co.id
    WHERE c.user_id = ? AND cs.session_date >= NOW()
    ORDER BY cs.session_date ASC
");
$session_stmt->bind_param("i", $user_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();
$session_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Heal2Rise</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body { background-color: #f8f9fa; }
        .card-case-status.pending { border-left: 5px solid #ffc107; }
        .card-case-status.approved { border-left: 5px solid #28a745; }
        .card-case-status.closed { border-left: 5px solid #6c757d; }
        .card-case-status.rejected { border-left: 5px solid #dc3545; }
        .card-session { border-left: 5px solid #17a2b8; background-color: #f0fbfc; }
        .card-ngo { transition: transform 0.2s; border: none; border-radius: 12px; }
        .card-ngo:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .welcome-section { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border-radius: 15px; padding: 40px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">

    <div class="welcome-section text-center mb-5 shadow">
        <h1 class="display-4 font-weight-bold text-white">Welcome, <?= htmlspecialchars($name) ?>!</h1>
        <p class="lead">Your journey to recovery and mental well-being starts here.</p>
        <div class="mt-4">
            <a href="submit_case.php" class="btn btn-light btn-lg font-weight-bold text-primary">
                <i class="fas fa-plus-circle mr-2"></i> Submit a New Case
            </a>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-7 mb-4">
            <h4 class="mb-3 font-weight-bold text-dark"><i class="fas fa-folder-open mr-2 text-primary"></i> Case Trackers</h4>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <?php if ($case_result->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($case = $case_result->fetch_assoc()): 
                                $status = strtolower($case['status']);
                            ?>
                                <div class="list-group-item card-case-status <?= $status ?> py-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($case['case_title']) ?></h6>
                                        <span class="badge badge-<?= ($status === 'approved' ? 'success' : ($status === 'closed' ? 'secondary' : ($status === 'rejected' ? 'danger' : 'warning'))) ?> px-3 py-2">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </div>
                                    <p class="mb-1 text-muted small">NGO: <?= htmlspecialchars($case['ngo_name'] ?? 'Assignment Pending') ?></p>
                                    <small class="text-muted"><i class="far fa-clock mr-1"></i> Submitted on <?= date('M d, Y', strtotime($case['created_at'])) ?></small>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard fa-3x text-light mb-3"></i>
                            <p class="text-muted">You haven't submitted any cases yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <h4 class="mb-3 font-weight-bold text-dark"><i class="fas fa-video mr-2 text-info"></i> Upcoming Sessions</h4>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <?php if ($session_result->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($session = $session_result->fetch_assoc()): ?>
                                <div class="list-group-item card-session py-3">
                                    <h6 class="font-weight-bold mb-1 text-info"><?= htmlspecialchars($session['session_type']) ?></h6>
                                    <p class="small mb-2 text-dark">
                                        <i class="fas fa-user-md mr-1"></i> Counselor: <strong><?= htmlspecialchars($session['counselor_name']) ?></strong><br>
                                        <i class="fas fa-calendar-alt mr-1"></i> <?= date('D, M d - h:i A', strtotime($session['session_date'])) ?> (<?= $session['duration_minutes'] ?> min)
                                    </p>
                                    <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-info btn-block">
                                        <i class="fas fa-external-link-alt mr-1"></i> Join Meeting
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-light mb-3"></i>
                            <p class="text-muted">No sessions scheduled at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-5 pt-4">
        <h3 class="text-center font-weight-bold mb-4">Verified Support Network</h3>
        <form method="get" class="form-inline mb-5 justify-content-center">
            <div class="input-group mb-3" style="width: 100%; max-width: 600px;">
                <input type="text" name="search" class="form-control form-control-lg" placeholder="Search by NGO Name or Service (e.g. Anxiety, Youth)" value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search mr-2"></i> Find NGO</button>
                </div>
            </div>
        </form>

        <div class="row">
            <?php if ($ngo_result->num_rows > 0): ?>
                <?php while($ngo = $ngo_result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100 card-ngo border-top border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary font-weight-bold mb-3"><?= htmlspecialchars($ngo['ngo_name']) ?></h5>
                                <div class="small text-muted mb-3">
                                    <p class="mb-1"><i class="fas fa-user-tie mr-2"></i> <?= htmlspecialchars($ngo['contact_person'] ?? 'Staff') ?></p>
                                    <p class="mb-0"><i class="fas fa-phone-alt mr-2"></i> <?= htmlspecialchars($ngo['phone'] ?? 'N/A') ?></p>
                                </div>
                                <h6 class="small font-weight-bold">Specializations:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php 
                                    $areas = explode(',', $ngo['service_areas']);
                                    foreach($areas as $area): ?>
                                        <span class="badge badge-light border text-secondary mr-1 mb-1"><?= trim(htmlspecialchars($area)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">We couldn't find any NGOs matching that search. Try another keyword!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
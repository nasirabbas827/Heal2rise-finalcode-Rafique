<?php
session_start();
include('config.php'); // Your DB connection: $conn = new mysqli(...)

// Check user login and role
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];
$name = $_SESSION["name"];

// =======================================================================
// 1. NGO Search and List Logic (Existing)
// =======================================================================

$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM ngos WHERE status='verified' AND ngo_name LIKE ? ORDER BY id DESC");
    $search_param = "%$search%";
    $stmt->bind_param("s", $search_param);
} else {
    // Also fetch the service area to display on the card
    $stmt = $conn->prepare("SELECT id, ngo_name, contact_person, phone, service_areas FROM ngos WHERE status='verified' ORDER BY id DESC");
}

$stmt->execute();
$ngo_result = $stmt->get_result();
$stmt->close();

// =======================================================================
// 2. User-Specific Case Data Fetch
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

// Fetch Upcoming Counseling Sessions for the current user's cases
$session_stmt = $conn->prepare("
    SELECT 
        cs.session_date, cs.session_type, cs.duration_minutes, cs.meeting_link, c.case_title, s.full_name as staff_name
    FROM counseling_sessions cs
    JOIN cases c ON cs.case_id = c.id
    JOIN ngo_staff s ON cs.staff_id = s.id
    WHERE c.user_id = ? AND cs.session_date >= NOW()
    ORDER BY cs.session_date ASC
");
$session_stmt->bind_param("i", $user_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();
$session_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Mental Health Support</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card-case-status.pending { border-left: 5px solid #ffc107; }
        .card-case-status.approved { border-left: 5px solid #28a745; }
        .card-case-status.rejected { border-left: 5px solid #dc3545; }
        .card-session { border-left: 5px solid #17a2b8; }
        .card-ngo { min-height: 280px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">

    <div class="text-center mb-5">
        <h1 class="display-4 text-primary font-weight-bold">Welcome back, <?= htmlspecialchars($name) ?>!</h1>
        <p class="lead">Check your case progress, upcoming sessions, or find a new support NGO.</p>
    </div>

    <h2 class="mb-4 text-info"><i class="fas fa-chart-line mr-2"></i> Your Current Status</h2>
    <div class="row mb-5">
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-folder-open mr-2"></i> My Cases</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($case_result->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($case = $case_result->fetch_assoc()): 
                                $status_class = strtolower($case['status']);
                            ?>
                                <li class="list-group-item card-case-status <?= $status_class ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($case['case_title']) ?></h6>
                                        <small class="text-muted">Filed: <?= date('M d, Y', strtotime($case['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-1 small">Assigned NGO: <?= htmlspecialchars($case['ngo_name'] ?? 'N/A') ?></p>
                                    <span class="badge badge-pill badge-<?= ($status_class === 'approved' ? 'success' : ($status_class === 'rejected' ? 'danger' : 'warning')) ?>">
                                        Status: <?= ucfirst($status_class) ?>
                                    </span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light text-center m-3">
                            You have no open or recent cases. Ready to <a href="#ngo-list"> submit one </a>?
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i> Upcoming Sessions</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($session_result->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($session = $session_result->fetch_assoc()): ?>
                                <li class="list-group-item card-session">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($session['session_type']) ?></h6>
                                        <small class="text-muted">Case: <?= htmlspecialchars($session['case_title']) ?></small>
                                    </div>
                                    <p class="mb-1 small">With:  <?= htmlspecialchars($session['staff_name']) ?>  (<?= $session['duration_minutes'] ?> min)</p>
                                    <p class="mb-1 small">Date:  <?= date('F d, Y h:i A', strtotime($session['session_date'])) ?> </p>
                                    <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info mt-1">Join Session <i class="fas fa-external-link-alt"></i></a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light text-center m-3">
                            No upcoming sessions scheduled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <h2 class="mb-4 text-primary" id="ngo-list"><i class="fas fa-handshake mr-2"></i> Find Support & Submit a New Case</h2>
    
    <form method="get" class="form-inline mb-4 justify-content-center">
        <input type="text" name="search" class="form-control mr-2" placeholder="Search NGOs by name or service area" style="width: 400px;" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
    </form>

    <div class="row">
        <?php if ($ngo_result->num_rows > 0): ?>
            <?php while($ngo = $ngo_result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 card-ngo">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary font-weight-bold"><?= htmlspecialchars($ngo['ngo_name']) ?></h5>
                            <hr>
                            <p class="card-text small"><strong>Contact Person:</strong> <?= htmlspecialchars($ngo['contact_person'] ?? 'N/A') ?></p>
                            <p class="card-text small"><strong>Phone:</strong> <?= htmlspecialchars($ngo['phone'] ?? 'N/A') ?></p>
                            <p class="card-text small flex-grow-1">
                                <strong>Service Areas:</strong> 
                                <span class="badge badge-info"><?= htmlspecialchars($ngo['service_areas']) ?></span>
                            </p>
                            
                            <form method="post" action="submit_case.php" class="mt-3">
                                <input type="hidden" name="ngo_id" value="<?= $ngo['id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                <button type="submit" class="btn btn-success btn-block">Submit Case to this NGO <i class="fas fa-arrow-right ml-1"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-center text-muted">No verified NGOs found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
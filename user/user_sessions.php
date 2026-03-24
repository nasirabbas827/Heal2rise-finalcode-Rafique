<?php
session_start();
include('../config.php');

// 1. Check user login and role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];

// 2. Check if case ID is provided (using 'id' to match your dashboard links)
if (!isset($_GET['id'])) {
    header("location: dashboard.php");
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
    die("<div class='container mt-5 alert alert-danger text-center'>Case not found or unauthorized access.</div>");
}

// 4. FIX: Fetch sessions joining with COUNSELORS table per your schema
$stmt = $conn->prepare("
    SELECT s.*, co.full_name AS counselor_name, co.specialization
    FROM counseling_sessions s
    JOIN counselors co ON s.counselor_id = co.id
    WHERE s.case_id = ?
    ORDER BY s.session_date DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$sessions_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Therapy Log | <?= htmlspecialchars($case['case_title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .session-card { border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .meeting-btn { border-radius: 20px; font-weight: 600; padding: 5px 20px; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb bg-transparent p-0 mb-2">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Sessions</li>
              </ol>
            </nav>
            <h2 class="font-weight-bold">Session History</h2>
            <p class="text-muted">Case: <span class="text-primary font-weight-bold"><?= htmlspecialchars($case['case_title']) ?></span></p>
        </div>
        
    </div>

            <?php if($sessions_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="pl-4">Date & Time</th>
                            <th>Counselor</th>
                            <th>Session Type</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s = $sessions_result->fetch_assoc()): 
                            $is_future = strtotime($s['session_date']) > time();
                        ?>
                        <tr>
                            <td class="pl-4">
                                <div class="font-weight-bold"><?= date('M d, Y', strtotime($s['session_date'])) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($s['session_date'])) ?> (<?= $s['duration_minutes'] ?? '0' ?> min)</small>
                            </td>
                            <td>
                                <div class="font-weight-bold text-dark"><?= htmlspecialchars($s['counselor_name']) ?></div>
                                <small class="badge badge-light border"><?= htmlspecialchars($s['specialization'] ?? 'Counselor') ?></small>
                            </td>
                            <td>
                                <span class="badge badge-pill badge-info px-3"><?= htmlspecialchars($s['session_type']) ?></span>
                            </td>
                            <td class="small text-muted" style="max-width: 300px;">
                                <?= $s['notes'] ? nl2br(htmlspecialchars($s['notes'])) : '<em>No summary notes yet.</em>' ?>
                                <?php if($s['next_session']): ?>
                                    <div class="mt-2 text-primary font-weight-bold">
                                        Next: <?= date('M d, h:i A', strtotime($s['next_session'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($s['meeting_link'] && $is_future): ?>
                                    <a href="<?= htmlspecialchars($s['meeting_link']) ?>" target="_blank" class="btn btn-primary btn-sm meeting-btn shadow-sm">
                                        <i class="fas fa-video mr-1"></i> Join Now
                                    </a>
                                <?php elseif($s['meeting_link']): ?>
                                    <span class="text-muted small italic">Session Completed</span>
                                <?php else: ?>
                                    <span class="text-muted small">In-Person</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-light mb-3"></i>
                    <h5 class="text-muted">No counseling sessions recorded yet.</h5>
                    <p class="small text-secondary">Sessions will appear here once your assigned counselor starts the therapy process.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
 
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
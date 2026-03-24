<?php
session_start();
include('../config.php'); 

// Check if Counselor is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'counselor') {
    header("location: ../login.php");
    exit;
}

$counselor_id = $_SESSION["id"];
$counselor_name = $_SESSION["name"];

// 1. Fetch Counselor Profile & Specialization
$stmt = $conn->prepare("SELECT specialization, qualification, experience_years, phone FROM counselors WHERE id = ?");
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Fetch Cases assigned to this counselor (via case_counselor link table)
$query = "
    SELECT c.id, c.case_title, c.status, c.created_at, 
           u.name AS user_name, u.email AS user_email,
           tm.full_name AS recommended_by_staff
    FROM case_counselor cc
    JOIN cases c ON cc.case_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN team_members tm ON cc.recommended_by = tm.id
    WHERE cc.counselor_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$cases_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Counselor Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container py-5">

    <div class="row mb-5 align-items-center">
        <div class="col-md-8">
            <h1 class="display-4 font-weight-bold text-primary">Hello, Dr. <?= htmlspecialchars($counselor_name) ?></h1>
            <p class="lead text-muted">Manage your specialized therapy cases and review user progress.</p>
        </div>
        <div class="col-md-4 text-right">
            <div class="card border-0 shadow-sm bg-white p-3">
                <h6 class="text-uppercase text-muted mb-1 small">Specialization</h6>
                <span class="badge badge-info p-2"><?= htmlspecialchars($profile['specialization'] ?? 'General') ?></span>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h5>Active Cases</h5>
                    <h2 class="mb-0"><?= $cases_result->num_rows ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Qualification:</strong> <?= htmlspecialchars($profile['qualification']) ?><br>
                        <strong>Experience:</strong> <?= htmlspecialchars($profile['experience_years']) ?> Years
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h4 class="mb-0"><i class="fas fa-user-md mr-2 text-primary"></i>Your Assigned Cases</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Case ID</th>
                            <th class="border-0">Title</th>
                            <th class="border-0">Patient Name</th>
                            <th class="border-0">Recommended By</th>
                            <th class="border-0">Status</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cases_result->num_rows > 0): ?>
                            <?php while ($case = $cases_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="align-middle"><strong>#<?= $case['id'] ?></strong></td>
                                    <td class="align-middle">
                                        <?= htmlspecialchars($case['case_title']) ?><br>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($case['created_at'])) ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <?= htmlspecialchars($case['user_name']) ?><br>
                                        <small class="text-primary"><?= htmlspecialchars($case['user_email']) ?></small>
                                    </td>
                                    <td class="align-middle text-muted">
                                        <i class="fas fa-user-tie mr-1 small"></i><?= htmlspecialchars($case['recommended_by_staff']) ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-pill badge-<?= $case['status'] == 'closed' ? 'secondary' : 'success' ?>">
                                            <?= ucfirst($case['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group">
                                            <a href="view_case.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info" title="View Case">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="manage_sessions.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-dark" title="Sessions">
                                                <i class="fas fa-calendar-check"></i> Sessions
                                            </a>
                                            <a href="manage_programs.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-success" title="Sessions">
                                                <i class="fas fa-calendar-check"></i> Skills
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3"></i><br>
                                    No specialized cases have been referred to you yet.
                                </td>
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
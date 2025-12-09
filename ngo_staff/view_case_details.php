<?php
session_start();
include('../config.php');

// Check if NGO staff is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo_staff') {
    header("location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];

// Check if case ID is provided
if (!isset($_GET['id'])) {
    header("location: staff_dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);

// Fetch case, assigned user, NGO, and assigned staff info
$stmt = $conn->prepare("
    SELECT 
        c.*,
        u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
        n.ngo_name, n.contact_person AS ngo_contact, n.email AS ngo_email, n.phone AS ngo_phone,
        s.full_name AS staff_name, s.email AS staff_email
    FROM cases c
    JOIN users u ON c.user_id = u.id
    JOIN ngos n ON c.assigned_ngo_id = n.id
    LEFT JOIN ngo_staff s ON c.assigned_staff_id = s.id
    WHERE c.id = ? AND c.assigned_staff_id = ?
");
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$case = $result->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "<p class='text-center mt-5'>Case not found or not assigned to you.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Details - Staff</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-center">Case Details</h2>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($case['case_title']) ?></h5>
            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($case['description'])) ?></p>
            <p><strong>Problem Category:</strong> <?= htmlspecialchars($case['problem_category']) ?></p>
            <p><strong>Severity:</strong> <?= htmlspecialchars($case['severity']) ?></p>
            <p><strong>Risk Level:</strong> <?= htmlspecialchars($case['risk_level']) ?></p>
            <p><strong>Status:</strong> <?= ucfirst($case['status']) ?></p>
            <p><strong>Created At:</strong> <?= $case['created_at'] ?></p>
            <p><strong>Closed At:</strong> <?= $case['closed_at'] ?? 'N/A' ?></p>
        </div>
    </div>

    <!-- Assigned User Info -->
    <h4>User Information</h4>
    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Name:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($case['user_email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($case['user_phone']) ?></p>
        </div>
    </div>

    <!-- Assigned NGO Info -->
    <h4>NGO Information</h4>
    <div class="card mb-4">
        <div class="card-body">
            <p><strong>NGO Name:</strong> <?= htmlspecialchars($case['ngo_name']) ?></p>
            <p><strong>Contact Person:</strong> <?= htmlspecialchars($case['ngo_contact']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($case['ngo_email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($case['ngo_phone']) ?></p>
        </div>
    </div>

    <!-- Assigned Staff Info -->
    <h4>Assigned Staff</h4>
    <div class="card mb-4">
        <div class="card-body">
            <?php if ($case['staff_name']): ?>
                <p><strong>Name:</strong> <?= htmlspecialchars($case['staff_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($case['staff_email']) ?></p>
            <?php else: ?>
                <p>No staff assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center">
        <a href="staff_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <?php if($case['status'] === 'pending'): ?>
            <a href="update_case_status.php?id=<?= $case['id'] ?>" class="btn btn-success">Update Status</a>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

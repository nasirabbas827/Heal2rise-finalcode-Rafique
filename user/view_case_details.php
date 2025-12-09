<?php
session_start();
include('config.php');

// Check user login
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Check if case ID is provided
if (!isset($_GET['id'])) {
    header("location: view_cases.php");
    exit;
}

$case_id = intval($_GET['id']);

// Fetch case details along with NGO and assigned staff
$stmt = $conn->prepare("
    SELECT 
        c.*, 
        n.ngo_name, n.contact_person, n.phone AS ngo_phone, n.email AS ngo_email,
        s.full_name AS staff_name, s.email AS staff_email, s.phone AS staff_phone, s.designation AS staff_designation
    FROM cases c
    JOIN ngos n ON c.assigned_ngo_id = n.id
    LEFT JOIN ngo_staff s ON c.assigned_staff_id = s.id
    WHERE c.id=? AND c.user_id=?
");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$case = $result->fetch_assoc();

if (!$case) {
    echo "<p class='text-center mt-5'>Case not found.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Details</title>
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

            <hr>
            <h5>Assigned NGO</h5>
            <p><strong>Name:</strong> <?= htmlspecialchars($case['ngo_name']) ?></p>
            <p><strong>Contact Person:</strong> <?= htmlspecialchars($case['contact_person']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($case['ngo_phone']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($case['ngo_email']) ?></p>

            <?php if ($case['staff_name']): ?>
                <hr>
                <h5>Assigned Staff</h5>
                <p><strong>Name:</strong> <?= htmlspecialchars($case['staff_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($case['staff_email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($case['staff_phone']) ?></p>
                <p><strong>Designation:</strong> <?= htmlspecialchars($case['staff_designation']) ?></p>
            <?php else: ?>
                <p><em>No staff assigned yet.</em></p>
            <?php endif; ?>

            <hr>
            <p><strong>Created At:</strong> <?= $case['created_at'] ?></p>
            <p><strong>Closed At:</strong> <?= $case['closed_at'] ?? 'N/A' ?></p>
        </div>
    </div>

    <div class="text-center">
        <a href="view_cases.php" class="btn btn-primary">Back to My Cases</a>
        <?php if($case['status'] === 'pending'): ?>
            <a href="edit_case.php?id=<?= $case['id'] ?>" class="btn btn-success">Edit Case</a>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];

if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);

// Fetch case details
$stmt = $conn->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email, s.full_name AS assigned_staff
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngo_staff s ON c.assigned_staff_id = s.id
    WHERE c.id = ? AND c.assigned_ngo_id = ?
");
$stmt->bind_param("ii", $case_id, $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows !== 1){
    echo "<script>alert('Case not found'); window.location='dashboard.php';</script>";
    exit;
}

$case = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container py-5">
    <h2 class="mb-4">Case Details</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h4 class="card-title"><?= htmlspecialchars($case['case_title']) ?></h4>
            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($case['description'])) ?></p>
            <p><strong>User:</strong> <?= htmlspecialchars($case['user_name']) ?> (<?= htmlspecialchars($case['user_email']) ?>)</p>
            <p><strong>Status:</strong> <?= ucfirst($case['status']) ?></p>
            <p><strong>Severity:</strong> <?= htmlspecialchars($case['severity']) ?></p>
            <p><strong>Risk Level:</strong> <?= htmlspecialchars($case['risk_level']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($case['problem_category']) ?></p>
            <p><strong>Assigned Staff:</strong> <?= $case['assigned_staff'] ?? 'Not Assigned' ?></p>
            <p><strong>Created At:</strong> <?= $case['created_at'] ?></p>
            <p><strong>Closed At:</strong> <?= $case['closed_at'] ?? 'N/A' ?></p>
        </div>
    </div>

    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

</body>
</html>

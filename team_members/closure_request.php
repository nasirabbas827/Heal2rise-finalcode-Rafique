<?php
session_start();
include('../config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];

if (!isset($_GET['case_id'])) {
    header("location: staff_dashboard.php");
    exit;
}

$case_id = intval($_GET['case_id']);

// Fetch case and NGO info
$stmt = $conn->prepare("
    SELECT c.case_title, c.assigned_ngo_id, n.ngo_name
    FROM cases c
    JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE c.id = ? AND c.assigned_staff_id = ?
");
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "<p class='text-center mt-5'>Case not found or not assigned to you.</p>";
    exit;
}

$ngo_id = $case['assigned_ngo_id'];
$msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = trim($_POST['notes']);

    $stmt = $conn->prepare("
        INSERT INTO closure_requests (case_id, staff_id, ngo_id, notes) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $case_id, $staff_id, $ngo_id, $notes);

    if ($stmt->execute()) {
        $msg = "Closure request submitted successfully!";
    } else {
        $msg = "Error submitting request. Please try again.";
    }
    $stmt->close();
}

// Fetch all closure requests for this case by this staff
$stmt = $conn->prepare("
    SELECT * FROM closure_requests 
    WHERE case_id = ? AND staff_id = ?
    ORDER BY request_date DESC
");
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$requests_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Closure Requests</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container py-5">
<h2 class="mb-4 text-center">Closure Requests for Case: <?= htmlspecialchars($case['case_title']) ?></h2>
<p><strong>NGO:</strong> <?= htmlspecialchars($case['ngo_name']) ?></p>

<?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>

<!-- Submit new request -->
<div class="card mb-4">
<div class="card-body">
<h5 class="card-title">Submit Closure Request</h5>
<form method="post" action="">
<div class="form-group">
<label>Notes (optional)</label>
<textarea name="notes" class="form-control" rows="3"></textarea>
</div>
<button type="submit" class="btn btn-dark">Submit Request</button>
</form>
</div>
</div>

<h4>Previous Requests</h4>
<?php if($requests_result->num_rows > 0): ?>
<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="thead-dark">
<tr>
<th>ID</th>
<th>Request Date</th>
<th>Status</th>
<th>Notes</th>
<th>Responded At</th>
</tr>
</thead>
<tbody>
<?php while($req = $requests_result->fetch_assoc()): ?>
<tr>
<td><?= $req['id'] ?></td>
<td><?= $req['request_date'] ?></td>
<td><?= ucfirst($req['status']) ?></td>
<td><?= nl2br(htmlspecialchars($req['notes'])) ?></td>
<td><?= $req['responded_at'] ?? '-' ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php else: ?>
<p class="text-center">No closure requests submitted yet.</p>
<?php endif; ?>

<div class="text-center mt-3">
<a href="staff_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];

if (!isset($_GET['case_id'])) {
    header("location: view_cases.php");
    exit;
}

$case_id = intval($_GET['case_id']);

// Fetch case info
$stmt = $conn->prepare("SELECT case_title FROM cases WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "Case not found.";
    exit;
}

// Fetch closure requests of this case
$stmt = $conn->prepare("SELECT * FROM closure_requests WHERE case_id=? ORDER BY request_date DESC");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$requests_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Closure Requests for <?= htmlspecialchars($case['case_title']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">
<h2>Closure Requests for Case: <?= htmlspecialchars($case['case_title']) ?></h2>

<?php if($requests_result->num_rows > 0): ?>
<table class="table table-bordered table-striped">
<thead>
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
<?php else: ?>
<p>No closure requests for this case.</p>
<?php endif; ?>

<a href="view_cases.php" class="btn btn-secondary mt-3">Back to Cases</a>
</div>
</body>
</html>

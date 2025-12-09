<?php
session_start();
include('config.php'); 

// Check if NGO staff is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo_staff') {
    header("location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];
$staff_name = $_SESSION["name"];

// Fetch NGO info for this staff
$stmt = $conn->prepare("
    SELECT n.id AS ngo_id, n.ngo_name, n.contact_person, n.email, n.phone, n.address
    FROM ngo_staff s
    JOIN ngos n ON s.ngo_id = n.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$ngo = $result->fetch_assoc();
$stmt->close();

// Fetch all cases assigned to this staff
$stmt = $conn->prepare("
    SELECT c.id, c.case_title, c.status, c.created_at, u.name AS user_name, u.email AS user_email
    FROM cases c
    JOIN users u ON c.user_id = u.id
    WHERE c.assigned_staff_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$cases_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Staff Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">

    <div class="text-center mb-5">
        <h1 class="display-4 font-weight-bold">Welcome, <?= htmlspecialchars($staff_name) ?></h1>
        <p class="lead">Manage your assigned cases and view your NGO information.</p>
    </div>

    <!-- NGO Info -->
    <h3 class="mb-3">NGO Information</h3>
    <div class="card mb-5">
        <div class="card-body">
            <p><strong>NGO Name:</strong> <?= htmlspecialchars($ngo['ngo_name']) ?></p>
            <p><strong>Contact Person:</strong> <?= htmlspecialchars($ngo['contact_person']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($ngo['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($ngo['phone']) ?></p>
            <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($ngo['address'])) ?></p>
        </div>
    </div>

    <!-- Assigned Cases -->
    <h3 class="mb-3">Assigned Cases</h3>

    <?php if ($cases_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>User</th>
                        <th>User Email</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($case = $cases_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $case['id'] ?></td>
                            <td><?= htmlspecialchars($case['case_title']) ?></td>
                            <td><?= htmlspecialchars($case['user_name']) ?></td>
                            <td><?= htmlspecialchars($case['user_email']) ?></td>
                            <td><?= ucfirst($case['status']) ?></td>
                            <td><?= $case['created_at'] ?></td>
                            <td>
    <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info">View Details</a>

    <?php if($case['status'] === 'pending'): ?>
        <a href="update_case_status.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-success">Update Status</a>
    <?php endif; ?>

    <!-- Counseling Sessions button -->
    <a href="sessions.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-warning">Sessions</a>

    <!-- Skills Tracking button -->
    <a href="skills.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-danger">Skills Tracking</a>

    <!-- Closure Request button -->
    <a href="closure_request.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-dark">Closure Request</a>
</td>


                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">No cases assigned to you yet.</p>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

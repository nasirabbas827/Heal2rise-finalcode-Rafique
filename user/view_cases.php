<?php
session_start();
include('config.php');

// Check if user is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];
$name = $_SESSION["name"];

// Handle deletion of case
if (isset($_GET['delete'])) {
    $case_id = intval($_GET['delete']);
    $delete_stmt = $conn->prepare("DELETE FROM cases WHERE id=? AND user_id=? AND status='pending'");
    $delete_stmt->bind_param("ii", $case_id, $user_id);
    $delete_stmt->execute();
    if ($delete_stmt->affected_rows > 0) {
        $delete_msg = '<div class="alert alert-success text-center">Case deleted successfully.</div>';
    } else {
        $delete_msg = '<div class="alert alert-danger text-center">Cannot delete case. Only pending cases can be deleted.</div>';
    }
}

// Fetch user's cases
$stmt = $conn->prepare("SELECT c.*, n.ngo_name 
                        FROM cases c 
                        JOIN ngos n ON c.assigned_ngo_id = n.id 
                        WHERE c.user_id=? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cases</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">

    <div class="text-center mb-4">
        <h2>My Cases</h2>
        <p class="lead">View all your submitted cases and manage pending cases.</p>
    </div>

    <?php if (!empty($delete_msg)) echo $delete_msg; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Case Title</th>
                    <th>NGO</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($case = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $case['id'] ?></td>
                        <td><?= htmlspecialchars($case['case_title']) ?></td>
                        <td><?= htmlspecialchars($case['ngo_name']) ?></td>
                        <td><?= ucfirst($case['status']) ?></td>
                        <td><?= $case['created_at'] ?></td>
                        <td>
    <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info mb-1">View</a>

    <?php if ($case['status'] === 'pending'): ?>
        <a href="edit_case.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-danger mb-1">Edit</a>
        <a href="?delete=<?= $case['id'] ?>" 
           class="btn btn-sm btn-danger mb-1"
           onclick="return confirm('Are you sure you want to delete this pending case?');">
           Delete
        </a>
    <?php endif; ?>

    <!-- Sessions -->
    <a href="user_sessions.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-warning mb-1">Sessions</a>

    <!-- Skills -->
    <a href="user_skills.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-danger mb-1">Skills</a>

    <!-- Closure Requests -->
    <a href="user_closure_requests.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-dark mb-1">Closure Requests</a>
</td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No cases found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

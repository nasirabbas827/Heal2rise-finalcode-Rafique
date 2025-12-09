<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Fetch all cases with user and NGO info
$sql = "
    SELECT c.*, u.name AS user_name, u.email AS user_email, n.ngo_name 
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
    ORDER BY c.created_at DESC
";
$cases = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - View Cases</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">All Cases</h2>
    <table class="table table-bordered table-striped table-responsive-sm">
        <thead class="thead-dark">
        <tr>
            <th>Case ID</th>
            <th>Title</th>
            <th>User</th>
            <th>User Email</th>
            <th>NGO</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($case = mysqli_fetch_assoc($cases)): ?>
            <tr>
                <td><?= $case['id'] ?></td>
                <td><?= htmlspecialchars($case['case_title']) ?></td>
                <td><?= htmlspecialchars($case['user_name']) ?></td>
                <td><?= htmlspecialchars($case['user_email']) ?></td>
                <td><?= htmlspecialchars($case['ngo_name'] ?? '-') ?></td>
                <td><?= ucfirst($case['status']) ?></td>
                <td><?= $case['created_at'] ?></td>
                <td>
                    <a href="view_case_details.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-info">View Details</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

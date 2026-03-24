<?php
session_start();
include('config.php');

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $staff_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM team_members WHERE id=?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Team member deleted successfully'); window.location='view_team.php';</script>";
    exit;
}

// Fetch all team members
$result = $conn->query("SELECT * FROM team_members ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Team Members - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(id) {
            if(confirm("Are you sure you want to delete this team member?")) {
                window.location.href = "?delete=" + id;
            }
        }
    </script>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container py-5">
    <h2 class="mb-4">Team Members</h2>
    <a href="add_team.php" class="btn btn-success mb-3">Add New Team Member</a>

    <?php if($result->num_rows > 0): ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Designation</th>
                <th>Experience</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($staff = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $staff['id'] ?></td>
                <td><?= htmlspecialchars($staff['full_name']) ?></td>
                <td><?= htmlspecialchars($staff['email']) ?></td>
                <td><?= htmlspecialchars($staff['phone']) ?></td>
                <td><?= htmlspecialchars($staff['designation']) ?></td>
                <td><?= $staff['experience_years'] ?></td>
                <td><?= ucfirst($staff['status']) ?></td>
                <td>
                    <a href="edit_team.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-primary m-1">Edit</a>
                    <button onclick="confirmDelete(<?= $staff['id'] ?>)" class="btn btn-sm btn-danger m-1">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
        <p class="text-center">No team members added yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];
$ngo_name = $_SESSION["name"];

// Handle deletion
if (isset($_GET['delete'])) {
    $staff_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM ngo_staff WHERE id=? AND ngo_id=?");
    $stmt->bind_param("ii", $staff_id, $ngo_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Staff deleted successfully'); window.location='view_staff.php';</script>";
    exit;
}

// Fetch staff
$stmt = $conn->prepare("SELECT * FROM ngo_staff WHERE ngo_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Staff - NGO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(id) {
            if(confirm("Are you sure you want to delete this staff?")) {
                window.location.href = "?delete=" + id;
            }
        }
    </script>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <h2 class="mb-4">Staff Members</h2>
    <a href="add_staff.php" class="btn btn-success mb-3">Add New Staff</a>

    <?php if($result->num_rows > 0): ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
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
                    <a href="edit_staff.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-primary m-1">Edit</a>
                    <button onclick="confirmDelete(<?= $staff['id'] ?>)" class="btn btn-sm btn-danger m-1">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
        <p class="text-center">No staff added yet.</p>
    <?php endif; ?>
</div>

</body>
</html>

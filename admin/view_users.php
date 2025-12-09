<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    echo "<script>alert('User deleted successfully.'); window.location='view_users.php';</script>";
    exit;
}

if (isset($_GET['delete_ngo'])) {
    $id = intval($_GET['delete_ngo']);
    mysqli_query($conn, "DELETE FROM ngos WHERE id = $id");
    echo "<script>alert('NGO deleted successfully.'); window.location='view_users.php';</script>";
    exit;
}

// Fetch users and NGOs
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$ngos = mysqli_query($conn, "SELECT * FROM ngos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Users & NGOs</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">

    <h2 class="mb-4">Users</h2>
    <table class="table table-bordered table-striped table-responsive-sm">
        <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($users)): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= $row['role'] ?></td>
                <td><?= $row['age'] ?></td>
                <td><?= $row['gender'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <a href="user_details.php?type=user&id=<?= $row['id'] ?>" class="btn btn-sm btn-info m-1">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h2 class="mb-4 mt-5">NGOs</h2>
    <table class="table table-bordered table-striped table-responsive-sm">
        <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>NGO Name</th>
            <th>Email</th>
            <th>Contact Person</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($ngos)): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['ngo_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['contact_person']) ?></td>
                <td><?= $row['phone'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <a href="user_details.php?type=ngo&id=<?= $row['id'] ?>" class="btn btn-sm btn-info m-1">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>
</body>
</html>

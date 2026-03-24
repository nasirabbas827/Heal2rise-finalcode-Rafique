<?php
session_start();
include('../config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("Location: ../login.php");
    exit;
}

// Delete counselor
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM counselors WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Counselor deleted'); window.location='view_counselors.php';</script>";
    exit;
}

$result = $conn->query("SELECT * FROM counselors ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Counselors</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/style.css">

    <script>
        function confirmDelete(id){
            if(confirm("Are you sure?")){
                window.location='view_counselors.php?delete='+id;
            }
        }
    </script>
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">
    <h2 class="mb-4">Counselors</h2>
    <a href="add_counselor.php" class="btn btn-success mb-3">Add Counselor</a>
    <?php if($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Qualification</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['qualification']) ?></td>
                    <td><?= htmlspecialchars($row['specialization']) ?></td>
                    <td><?= $row['experience_years'] ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td>
                        <a href="edit_counselor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-dark">Edit</a>
                        <button onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p class="text-center">No counselors added yet.</p>
    <?php endif; ?>
</div>
</body>
</html>
<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Get parameters
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['user', 'ngo'])) {
    echo "Invalid request";
    exit;
}

// Handle status update
if (isset($_POST['status'])) {
    $new_status = $_POST['status'];
    if ($type == 'user') {
        mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE ngos SET status='$new_status' WHERE id=$id");
    }
    echo "<script>alert('Status updated successfully'); window.location='user_details.php?type=$type&id=$id';</script>";
    exit;
}

// Fetch details
if ($type == 'user') {
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
    $data = mysqli_fetch_assoc($result);
} else {
    $result = mysqli_query($conn, "SELECT * FROM ngos WHERE id=$id");
    $data = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Details</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4"><?= ucfirst($type) ?> Details</h2>

    <table class="table table-bordered">
        <tbody>
        <?php foreach ($data as $key => $value): ?>
            <tr>
                <th><?= ucwords(str_replace('_', ' ', $key)) ?></th>
                <td><?= htmlspecialchars($value) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <form method="post" class="mt-3">
        <div class="form-group">
            <label>Update Status</label>
            <select name="status" class="form-control" required>
                <?php if ($type == 'user'): ?>
                    <option value="active" <?= $data['status']=='active'?'selected':'' ?>>Active</option>
                    <option value="pending" <?= $data['status']=='pending'?'selected':'' ?>>Pending</option>
                    <option value="blocked" <?= $data['status']=='blocked'?'selected':'' ?>>Blocked</option>
                <?php else: ?>
                    <option value="verified" <?= $data['status']=='verified'?'selected':'' ?>>Verified</option>
                    <option value="pending" <?= $data['status']=='pending'?'selected':'' ?>>Pending</option>
                    <option value="blocked" <?= $data['status']=='blocked'?'selected':'' ?>>Blocked</option>
                <?php endif; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Status</button>
        <a href="view_users.php" class="btn btn-secondary">Back</a>
    </form>

</div>

</body>
</html>

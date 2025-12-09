<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];

if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$case_id = intval($_GET['id']);
$msg = "";

// Fetch case
$stmt = $conn->prepare("SELECT * FROM cases WHERE id=? AND assigned_ngo_id=?");
$stmt->bind_param("ii", $case_id, $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows !== 1){
    echo "<script>alert('Case not found'); window.location='dashboard.php';</script>";
    exit;
}
$case = $result->fetch_assoc();

// Fetch NGO staff
$staff_result = $conn->query("SELECT id, full_name FROM ngo_staff WHERE ngo_id=$ngo_id AND status='active' ORDER BY full_name ASC");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $assigned_staff_id = !empty($_POST['assigned_staff']) ? intval($_POST['assigned_staff']) : NULL;

    $stmt = $conn->prepare("UPDATE cases SET status=?, assigned_staff_id=? WHERE id=? AND assigned_ngo_id=?");
    $stmt->bind_param("siii", $status, $assigned_staff_id, $case_id, $ngo_id);
    if($stmt->execute()){
        $msg = '<div class="alert alert-success">Case updated successfully!</div>';
        $case['status'] = $status;
        $case['assigned_staff_id'] = $assigned_staff_id;
    } else {
        $msg = '<div class="alert alert-danger">Error updating case.</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Case Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container py-5">
        <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
    <h2 class="mb-4">Update Case Status</h2>
    <?= $msg ?>

    <form method="post">
        <div class="form-group">
            <label>Case Title</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($case['case_title']) ?>" disabled>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="pending" <?= ($case['status']=='pending')?'selected':'' ?>>Pending</option>
                <option value="approved" <?= ($case['status']=='approved')?'selected':'' ?>>Approved</option>
                <option value="rejected" <?= ($case['status']=='rejected')?'selected':'' ?>>Rejected</option>
            </select>
        </div>

        <div class="form-group">
            <label>Assign Staff</label>
            <select name="assigned_staff" class="form-control">
                <option value="">-- Select Staff --</option>
                <?php while($staff = $staff_result->fetch_assoc()): ?>
                    <option value="<?= $staff['id'] ?>" <?= ($case['assigned_staff_id']==$staff['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($staff['full_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</div>
</div>

</body>
</html>

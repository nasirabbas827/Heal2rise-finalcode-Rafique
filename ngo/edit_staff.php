<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];
$ngo_name = $_SESSION["name"];
$staff_id = intval($_GET['id']);

// Fetch staff
$stmt = $conn->prepare("SELECT * FROM ngo_staff WHERE id=? AND ngo_id=?");
$stmt->bind_param("ii", $staff_id, $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows !== 1){
    echo "<script>alert('Staff not found'); window.location='view_staff.php';</script>";
    exit;
}
$staff = $result->fetch_assoc();

$full_name = $staff['full_name'];
$email = $staff['email'];
$phone = $staff['phone'];
$designation = $staff['designation'];
$qualification = $staff['qualification'];
$experience_years = $staff['experience_years'];
$status = $staff['status'];
$msg = "";

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $designation = trim($_POST['designation']);
    $qualification = trim($_POST['qualification']);
    $experience_years = intval($_POST['experience_years']);
    $status = $_POST['status'];

    $sql = "UPDATE ngo_staff SET full_name=?, email=?, phone=?, designation=?, qualification=?, experience_years=?, status=?";

    if(!empty($_POST['password'])){
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql .= ", password_hash=?";
    }
    $sql .= " WHERE id=? AND ngo_id=?";

    $stmt = $conn->prepare($sql);
    if(!empty($_POST['password'])){
        $stmt->bind_param("ssssssssss", $full_name, $email, $phone, $designation, $qualification, $experience_years, $status, $password_hash, $staff_id, $ngo_id);
    } else {
        $stmt->bind_param("sssssssss", $full_name, $email, $phone, $designation, $qualification, $experience_years, $status, $staff_id, $ngo_id);
    }

    if($stmt->execute()){
        $msg = '<div class="alert alert-success text-center">Staff updated successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger text-center">Error updating staff.</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Staff - NGO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
        <div class="card mx-auto" style="max-width: 650px;">
        <div class="card-body">
    <h2 class="mb-4">Edit Staff</h2>
    <?= $msg ?>
    <form method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Full Name</label>
                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($phone) ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Password (leave blank to keep current)</label>
                <input type="password" class="form-control" name="password">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Designation</label>
                <input type="text" class="form-control" name="designation" value="<?= htmlspecialchars($designation) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label>Experience (Years)</label>
                <input type="number" class="form-control" name="experience_years" value="<?= $experience_years ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Qualification</label>
                <input type="text" class="form-control" name="qualification" value="<?= htmlspecialchars($qualification) ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= ($status=='active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($status=='inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Update Staff</button>
            <a href="view_staff.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</div>
</div>

</body>
</html>

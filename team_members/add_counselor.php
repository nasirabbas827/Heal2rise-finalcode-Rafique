<?php
session_start();
include('../config.php');

// Only team members access
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("Location: ../login.php");
    exit;
}

$full_name = $email = $phone = $password = $qualification = $specialization = "";
$experience_years = 0;
$status = "active";
$msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $qualification = trim($_POST['qualification']);
    $experience_years = intval($_POST['experience_years']);
    $specialization = trim($_POST['specialization']);
    $status = $_POST['status'];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO counselors (full_name,email,phone,password_hash,qualification,experience_years,specialization,status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $full_name, $email, $phone, $password_hash, $qualification, $experience_years, $specialization, $status);

    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success text-center">Counselor added successfully!</div>';
        $full_name = $email = $phone = $password = $qualification = $specialization = "";
        $experience_years = 0;
        $status = "active";
    } else {
        $msg = '<div class="alert alert-danger text-center">Error adding counselor. Please try again.</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Counselor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">
    <div class="card mx-auto shadow-sm" style="max-width: 700px;">
        <div class="card-body">
            <h2 class="mb-4">Add Counselor</h2>
            <?= $msg ?>
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($full_name) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Qualification</label>
                        <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($qualification) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Specialization</label>
                        <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($specialization) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" value="<?= $experience_years ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($status=='active')?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= ($status=='inactive')?'selected':'' ?>>Inactive</option>
                    </select>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Add Counselor</button>
                    <a href="view_counselors.php" class="btn btn-secondary">View Counselors</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
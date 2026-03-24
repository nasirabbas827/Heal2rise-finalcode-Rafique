<?php
session_start();
include('config.php');

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$full_name = $email = $phone = $designation = $qualification = $password = "";
$experience_years = 0;
$status = "active";
$msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $designation = trim($_POST['designation']);
    $qualification = trim($_POST['qualification']);
    $password = trim($_POST['password']);
    $experience_years = intval($_POST['experience_years']);
    $status = $_POST['status'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO team_members (full_name, email, phone, password_hash, designation, qualification, experience_years, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $full_name, $email, $phone, $hashed_password, $designation, $qualification, $experience_years, $status);

    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success text-center">Team Member added successfully!</div>';
        // Reset form values
        $full_name = $email = $phone = $designation = $qualification = $password = "";
        $experience_years = 0;
        $status = "active";
    } else {
        $msg = '<div class="alert alert-danger text-center">Error adding team member. Please try again.</div>';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Team Member</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container py-5">
    <div class="card mx-auto" style="max-width: 650px;">
        <div class="card-body">
            <h2 class="mb-4">Add New Team Member</h2>
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
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
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
                    <button type="submit" class="btn btn-primary">Add Team Member</button>
                    <a href="view_team.php" class="btn btn-secondary">View Team Members</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
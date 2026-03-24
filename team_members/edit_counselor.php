<?php
session_start();
include('../config.php');

// Only team members access
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("Location: ../login.php");
    exit;
}

// Get counselor ID from URL
$counselor_id = intval($_GET['id']);

$full_name = $email = $phone = $qualification = $specialization = "";
$experience_years = 0;
$status = "active";
$msg = "";

// Fetch counselor info
$stmt = $conn->prepare("SELECT * FROM counselors WHERE id=?");
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    echo "<script>alert('Counselor not found'); window.location='view_counselors.php';</script>";
    exit;
}
$counselor = $result->fetch_assoc();
$stmt->close();

$full_name = $counselor['full_name'];
$email = $counselor['email'];
$phone = $counselor['phone'];
$qualification = $counselor['qualification'];
$experience_years = $counselor['experience_years'];
$specialization = $counselor['specialization'];
$status = $counselor['status'];

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $qualification = trim($_POST['qualification']);
    $experience_years = intval($_POST['experience_years']);
    $specialization = trim($_POST['specialization']);
    $status = $_POST['status'];

    $sql = "UPDATE counselors SET full_name=?, email=?, phone=?, qualification=?, experience_years=?, specialization=?, status=?";

    // Update password if provided
    if(!empty($_POST['password'])){
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql .= ", password_hash=?";
    }
    $sql .= " WHERE id=?";

    $stmt = $conn->prepare($sql);

    if(!empty($_POST['password'])){
        $stmt->bind_param("ssssssssi", $full_name, $email, $phone, $qualification, $experience_years, $specialization, $status, $password_hash, $counselor_id);
    } else {
        $stmt->bind_param("sssssssi", $full_name, $email, $phone, $qualification, $experience_years, $specialization, $status, $counselor_id);
    }

    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success text-center">Counselor updated successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger text-center">Error updating counselor. Please try again.</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Counselor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">
    <div class="card mx-auto shadow-sm" style="max-width: 700px;">
        <div class="card-body">
            <h2 class="mb-4">Edit Counselor</h2>
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
                        <label>Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
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
                    <button type="submit" class="btn btn-primary">Update Counselor</button>
                    <a href="view_counselors.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
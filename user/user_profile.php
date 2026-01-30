<?php
session_start();
include('config.php');

// ======================================
// AUTH CHECK
// ======================================
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];
$success = '';
$error = '';

// ======================================
// UPDATE PROFILE
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name              = trim($_POST['name']);
    $phone             = trim($_POST['phone']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $gender            = trim($_POST['gender']);
    $age               = !empty($_POST['age']) ? intval($_POST['age']) : null;

    if (empty($name)) {
        $error = "Name is required.";
    } else {
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, phone = ?, emergency_contact = ?, gender = ?, age = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssii",
            $name,
            $phone,
            $emergency_contact,
            $gender,
            $age,
            $user_id
        );

        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully.";
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// ======================================
// FETCH USER DATA
// ======================================
$stmt = $conn->prepare("
    SELECT name, email, phone, emergency_contact, gender, age
    FROM users WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Profile</h5>
                </div>

                <div class="card-body">

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="name" class="form-control"
                                           value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email (Read Only)</label>
                                    <input type="email" class="form-control"
                                           value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="<?= htmlspecialchars($user['phone']) ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Emergency Contact</label>
                                    <input type="text" name="emergency_contact" class="form-control"
                                           value="<?= htmlspecialchars($user['emergency_contact']) ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Male" <?= $user['gender']=='Male'?'selected':'' ?>>Male</option>
                                        <option value="Female" <?= $user['gender']=='Female'?'selected':'' ?>>Female</option>
                                        <option value="Other" <?= $user['gender']=='Other'?'selected':'' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Age</label>
                                    <input type="number" name="age" class="form-control"
                                           value="<?= htmlspecialchars($user['age']) ?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-block mt-3">
                                    Update Profile
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

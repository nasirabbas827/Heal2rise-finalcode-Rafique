<?php
session_start();
include('config.php');

// ======================================
// AUTH CHECK (NGO)
// ======================================
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION["id"];
$success = '';
$error = '';

// ======================================
// UPDATE NGO PROFILE
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ngo_name           = trim($_POST['ngo_name']);
    $registration_no    = trim($_POST['registration_number']);
    $address            = trim($_POST['address']);
    $contact_person     = trim($_POST['contact_person']);
    $phone              = trim($_POST['phone']);
    $service_areas      = trim($_POST['service_areas']);
    $capacity           = intval($_POST['capacity']);

    if (empty($ngo_name) || empty($contact_person)) {
        $error = "NGO name and contact person are required.";
    } else {

        $stmt = $conn->prepare("
            UPDATE ngos SET
                ngo_name = ?,
                registration_number = ?,
                address = ?,
                contact_person = ?,
                phone = ?,
                service_areas = ?,
                capacity = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssii",
            $ngo_name,
            $registration_no,
            $address,
            $contact_person,
            $phone,
            $service_areas,
            $capacity,
            $ngo_id
        );

        if ($stmt->execute()) {
            $_SESSION['name'] = $ngo_name;
            $success = "NGO profile updated successfully.";
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// ======================================
// FETCH NGO DATA
// ======================================
$stmt = $conn->prepare("
    SELECT ngo_name, registration_number, address, contact_person,
           phone, email, service_areas, capacity, status
    FROM ngos WHERE id = ?
");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$ngo = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Update NGO Profile</h5>
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
                                    <label>NGO Name</label>
                                    <input type="text" name="ngo_name" class="form-control"
                                           value="<?= htmlspecialchars($ngo['ngo_name']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Registration Number</label>
                                    <input type="text" name="registration_number" class="form-control"
                                           value="<?= htmlspecialchars($ngo['registration_number']) ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($ngo['address']) ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control"
                                           value="<?= htmlspecialchars($ngo['contact_person']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="<?= htmlspecialchars($ngo['phone']) ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email (Read Only)</label>
                                    <input type="email" class="form-control"
                                           value="<?= htmlspecialchars($ngo['email']) ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Capacity (Cases)</label>
                                    <input type="number" name="capacity" class="form-control"
                                           value="<?= htmlspecialchars($ngo['capacity']) ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Service Areas</label>
                                    <textarea name="service_areas" class="form-control" rows="2"><?= htmlspecialchars($ngo['service_areas']) ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <input type="text" class="form-control"
                                           value="<?= ucfirst($ngo['status']) ?>" readonly>
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

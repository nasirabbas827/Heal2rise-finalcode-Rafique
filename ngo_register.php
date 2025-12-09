<?php
include('config.php');

$ngo_name = $registration_number = $address = $contact_person = $phone = $email = $password = $service_areas = "";
$ngo_name_err = $email_err = $password_err = "";
$registration_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["ngo_name"]))) {
        $ngo_name_err = "Please enter NGO name.";
    } else {
        $ngo_name = trim($_POST["ngo_name"]);
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
        $sql = "SELECT id FROM ngos WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $email_err = "This email is already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter password.";
    } elseif (strlen(trim($_POST["password"])) < 3) {
        $password_err = "Password must be at least 3 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    $registration_number = !empty($_POST["registration_number"]) ? trim($_POST["registration_number"]) : NULL;
    $address = !empty($_POST["address"]) ? trim($_POST["address"]) : NULL;
    $contact_person = !empty($_POST["contact_person"]) ? trim($_POST["contact_person"]) : NULL;
    $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : NULL;
    $service_areas = !empty($_POST["service_areas"]) ? trim($_POST["service_areas"]) : NULL;

    if (empty($ngo_name_err) && empty($email_err) && empty($password_err)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO ngos 
                (ngo_name, registration_number, address, contact_person, phone, email, password, service_areas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssss",
            $ngo_name,
            $registration_number,
            $address,
            $contact_person,
            $phone,
            $email,
            $hashed_password,
            $service_areas
        );

        if (mysqli_stmt_execute($stmt)) {
            $registration_msg = '<div class="alert alert-success text-center">NGO registered successfully. Pending admin verification.</div>';
            $ngo_name = $registration_number = $address = $contact_person =
            $phone = $email = $password = $service_areas = "";
        } else {
            $registration_msg = '<div class="alert alert-danger text-center">Error: Unable to register NGO.</div>';
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NGO Registration</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto shadow-sm" style="max-width: 800px;">
        <div class="card-body">
            <h3 class="text-center mb-4">NGO Registration</h3>
            <?php echo $registration_msg; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>NGO Name</label>
                        <input type="text" name="ngo_name"
                               class="form-control <?php echo (!empty($ngo_name_err)) ? 'is-invalid' : ''; ?>"
                               value="<?php echo $ngo_name; ?>">
                        <span class="invalid-feedback"><?php echo $ngo_name_err; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Email</label>
                        <input type="email" name="email"
                               class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                               value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Password</label>
                        <input type="password" name="password"
                               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Registration Number</label>
                        <input type="text" name="registration_number" class="form-control"
                               value="<?php echo $registration_number; ?>">
                    </div>

                    <div class="form-group col-md-6">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control"
                               value="<?php echo $contact_person; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo $phone; ?>">
                    </div>

                    <div class="form-group col-md-6">
                        <label>Service Areas</label>
                        <input type="text" name="service_areas" class="form-control"
                               value="<?php echo $service_areas; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo $address; ?></textarea>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button class="btn btn-primary px-4">Register NGO</button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>

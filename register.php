<?php
include('config.php');

$name = $email = $password = $phone = $emergency_contact = $gender = $age = $role = "";
$name_err = $email_err = $password_err = "";
$registration_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your full name.";
    } else {
        $name = trim($_POST["name"]);
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        $sql = "SELECT id FROM users WHERE email = ?";
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
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 3) {
        $password_err = "Password must be at least 3 characters long.";
    } else {
        $password = trim($_POST["password"]);
    }

    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : NULL;
    $emergency_contact = !empty($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : NULL;
    $gender = !empty($_POST['gender']) ? trim($_POST['gender']) : NULL;
    $age = !empty($_POST['age']) ? intval($_POST['age']) : NULL;

    // NEW ROLE FIELD
    $role = !empty($_POST['role']) ? trim($_POST['role']) : 'user';

    if (empty($name_err) && empty($email_err) && empty($password_err)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password, phone, emergency_contact, gender, age, role)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssis",
            $name,
            $email,
            $hashed_password,
            $phone,
            $emergency_contact,
            $gender,
            $age,
            $role
        );

        if (mysqli_stmt_execute($stmt)) {
            $registration_msg = '<div class="alert alert-success text-center">Registration successful!</div>';
            $name = $email = $password = $phone = $emergency_contact = $gender = $age = $role = "";
        } else {
            $registration_msg = '<div class="alert alert-danger text-center">Error: Unable to register. Please try again.</div>';
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
    <title>User Registration</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto shadow-sm" style="max-width: 700px;">
        <div class="card-body">
            <h3 class="text-center mb-4">User Registration</h3>
            <?php echo $registration_msg; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Full Name</label>
                        <input type="text" name="name"
                               class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>"
                               value="<?php echo $name; ?>">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
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

                <!-- Phone + Emergency Contact -->
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo $phone; ?>">
                    </div>

                    <div class="form-group col-md-6">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control"
                               value="<?php echo $emergency_contact; ?>">
                    </div>
                </div>

                <!-- Gender + Age -->
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <option value="male"   <?php echo ($gender=='male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($gender=='female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other"  <?php echo ($gender=='other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Age</label>
                        <input type="number" name="age" class="form-control"
                               value="<?php echo $age; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>User Role</label>
                        <select name="role" class="form-control">
                            <option value="user" <?php echo ($role=='user') ? 'selected' : ''; ?>>User</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <input type="submit" class="btn btn-primary px-4" value="Register">
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>

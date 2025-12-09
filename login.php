<?php
include('config.php');
session_start();

$email = $password = $role = "";
$email_err = $password_err = $login_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate Role
    if (empty($_POST["role"])) {
        $login_msg = '<div class="alert alert-danger text-center">Please select a role.</div>';
    } else {
        $role = $_POST["role"];
    }

    // Proceed if no errors
    if (empty($email_err) && empty($password_err) && !empty($role)) {

        if ($role == 'ngo') {
            $sql = "SELECT id, ngo_name, email, password, status FROM ngos WHERE email = ?";
        } elseif ($role == 'ngo_staff') {
            $sql = "SELECT id, full_name, email, password_hash, status FROM ngo_staff WHERE email = ?";
        } else {
            $sql = "SELECT id, name, email, password, status FROM users WHERE email = ? AND role = ?";
        }

        $stmt = mysqli_prepare($conn, $sql);

        if ($role == 'ngo' || $role == 'ngo_staff') {
            mysqli_stmt_bind_param($stmt, "s", $email);
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $email, $role);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {

                if ($role == 'ngo') {
                    mysqli_stmt_bind_result($stmt, $id, $name, $email_db, $hashed_password, $status);
                } elseif ($role == 'ngo_staff') {
                    mysqli_stmt_bind_result($stmt, $id, $name, $email_db, $hashed_password, $status);
                } else {
                    mysqli_stmt_bind_result($stmt, $id, $name, $email_db, $hashed_password, $status);
                }

                mysqli_stmt_fetch($stmt);

                // Check account status
                if (($role == 'user' && $status != 'active') || ($role == 'ngo' && $status != 'verified') || ($role == 'ngo_staff' && $status != 'active')) {
                    $login_msg = '<div class="alert alert-danger text-center">Your account is not active/verified yet.</div>';
                } elseif (password_verify($password, $hashed_password)) {

                    // Start session and store info
                    $_SESSION["id"] = $id;
                    $_SESSION["name"] = $name;
                    $_SESSION["email"] = $email_db;
                    $_SESSION["role"] = $role;

                    // Redirect based on role
                    if ($role == 'user') {
                        header("Location: user/dashboard.php");
                    } elseif ($role == 'ngo') {
                        header("Location: ngo/dashboard.php");
                    } elseif ($role == 'ngo_staff') {
                        header("Location: ngo_staff/staff_dashboard.php");
                    }
                    exit();

                } else {
                    $password_err = "Invalid password.";
                }

            } else {
                $email_err = "No account found with that email and role.";
            }
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
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto shadow-sm" style="max-width: 500px;">
        <div class="card-body">
            <h3 class="text-center mb-3">Login</h3>
            <?php echo $login_msg; ?>

            <form method="post" action="">

                <div class="form-group">
                    <label>Email</label>
                    <input type="text" name="email"
                           class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                           value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password"
                           class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Login as</label>
                    <select name="role" class="form-control">
                        <option value="">Select Role</option>
                        <option value="user" <?php echo ($role=='user')?'selected':''; ?>>User</option>
                        <option value="ngo" <?php echo ($role=='ngo')?'selected':''; ?>>NGO</option>
                        <option value="ngo_staff" <?php echo ($role=='ngo_staff')?'selected':''; ?>>NGO Staff</option>
                    </select>
                </div>

                <div class="form-group text-center mt-4">
                    <input type="submit" value="Login" class="btn btn-primary px-4">
                </div>

            </form>

            <p class="text-center mt-3">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

</body>
</html>

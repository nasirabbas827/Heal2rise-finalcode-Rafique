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

        // Determine Table and Query based on role
        if ($role == 'ngo') {
            $sql = "SELECT id, ngo_name, email, password, status FROM ngos WHERE email = ?";
        } elseif ($role == 'team_members') {
            $sql = "SELECT id, full_name, email, password_hash, status FROM team_members WHERE email = ?";
        } elseif ($role == 'counselor') {
            $sql = "SELECT id, full_name, email, password_hash, status FROM counselors WHERE email = ?";
        } else {
            // Standard user, psychiatrist, etc. from users table
            $sql = "SELECT id, name, email, password, status FROM users WHERE email = ? AND role = ?";
        }

        $stmt = mysqli_prepare($conn, $sql);

        // Bind parameters based on role query structure
        if (in_array($role, ['ngo', 'team_members', 'counselor'])) {
            mysqli_stmt_bind_param($stmt, "s", $email);
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $email, $role);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $name, $email_db, $hashed_password, $status);
                mysqli_stmt_fetch($stmt);

                // Check account status (Active/Verified)
                $is_active = true;
                if ($role == 'ngo' && $status != 'verified') $is_active = false;
                if (in_array($role, ['user', 'team_members', 'counselor']) && $status != 'active') $is_active = false;

                if (!$is_active) {
                    $login_msg = '<div class="alert alert-danger text-center">Your account is not active/verified yet.</div>';
                } elseif (password_verify($password, $hashed_password)) {

                    // Start session and store info
                    $_SESSION["id"] = $id;
                    $_SESSION["name"] = $name;
                    $_SESSION["email"] = $email_db;
                    $_SESSION["role"] = $role;

                    // Redirect based on role
                    switch ($role) {
                        case 'user':
                            header("Location: user/dashboard.php");
                            break;
                        case 'ngo':
                            header("Location: ngo/dashboard.php");
                            break;
                        case 'team_members':
                            header("Location: team_members/staff_dashboard.php");
                            break;
                        case 'counselor':
                            header("Location: counselor/dashboard.php");
                            break;
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
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto shadow border-0" style="max-width: 450px;">
        <div class="card-body p-4">
            <h3 class="text-center font-weight-bold mb-4">Account Login</h3>
            <?php echo $login_msg; ?>

            <form method="post" action="">

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email"
                           class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($email); ?>">
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
                    <select name="role" class="form-control shadow-sm">
                        <option value="">Select your role</option>
                        <option value="user" <?php echo ($role=='user')?'selected':''; ?>>User / Patient</option>
                        <option value="counselor" <?php echo ($role=='counselor')?'selected':''; ?>>Professional Counselor</option>
                        <option value="ngo" <?php echo ($role=='ngo')?'selected':''; ?>>NGO Partner</option>
                        <option value="team_members" <?php echo ($role=='team_members')?'selected':''; ?>>Internal Team Member</option>
                    </select>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold">Login to Dashboard</button>
                </div>

            </form>

            <div class="text-center mt-3">
                <p class="small">Don't have an account? <a href="register.php" class="text-primary font-weight-bold">Register here</a></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
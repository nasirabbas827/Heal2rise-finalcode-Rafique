<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['case_title']);
    $desc  = trim($_POST['description']);
    $cat   = trim($_POST['problem_category']);
    $sev   = $_POST['severity'] ?? null;
    $risk  = $_POST['risk_level'] ?? null;

    if ($title && $desc && $cat) {

        $stmt = $conn->prepare("
            INSERT INTO cases 
            (case_title, description, user_id, problem_category, severity, risk_level, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param("ssisss", $title, $desc, $user_id, $cat, $sev, $risk);

        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>Case submitted successfully</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error submitting case</div>";
        }

    } else {
        $msg = "<div class='alert alert-warning'>Fill all required fields</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Submit Case</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">

</head>

<body>

<?php include('navbar.php'); ?>

<div class="container py-5">

    <h3 class="text-center mb-4">Submit New Case</h3>

    <?= $msg ?>

    <div class="card">
        <div class="card-body">

            <form method="post">

                <div class="form-group">
                    <label>Case Title</label>
                    <input type="text" name="case_title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label>Problem Category</label>
                    <select name="problem_category" class="form-control" required>
                        <option value="">Select</option>
                        <option>Depression</option>
                        <option>Anxiety</option>
                        <option>Stress</option>
                        <option>Family Issues</option>
                        <option>Addiction</option>
                        <option>Other</option>
                    </select>
                </div>

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label>Severity</label>
                        <select name="severity" class="form-control">
                            <option value="">Select</option>
                            <option>Low</option>
                            <option>Medium</option>
                            <option>High</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Risk Level</label>
                        <select name="risk_level" class="form-control">
                            <option value="">Select</option>
                            <option>Low</option>
                            <option>Medium</option>
                            <option>High</option>
                        </select>
                    </div>

                </div>

                <div class="text-center">
                    <button class="btn btn-success">Submit Case</button>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>
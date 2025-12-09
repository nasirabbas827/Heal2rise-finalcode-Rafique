<?php
session_start();
include('config.php');

// Ensure user is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];
$name = $_SESSION["name"];

// Check if ngo_id is provided
if (!isset($_POST['ngo_id']) && !isset($_GET['ngo_id'])) {
    header("location: dashboard.php");
    exit;
}

$ngo_id = $_POST['ngo_id'] ?? $_GET['ngo_id'];

// Fetch NGO details
$stmt = $conn->prepare("SELECT * FROM ngos WHERE id=? AND status='verified'");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$ngo_result = $stmt->get_result();
$ngo = $ngo_result->fetch_assoc();

if (!$ngo) {
    echo "<p class='text-center mt-5'>NGO not found or not verified.</p>";
    exit;
}

// Handle case submission
$submit_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_case'])) {

    $case_title = trim($_POST['case_title']);
    $description = trim($_POST['description']);
    $problem_category = trim($_POST['problem_category']);
    $severity = trim($_POST['severity']);
    $risk_level = trim($_POST['risk_level']);

    if ($case_title && $description) {
        $stmt = $conn->prepare("INSERT INTO cases 
            (case_title, description, user_id, assigned_ngo_id, problem_category, severity, risk_level) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", 
            $case_title, $description, $user_id, $ngo_id, $problem_category, $severity, $risk_level
        );

        if ($stmt->execute()) {
            $submit_msg = '<div class="alert alert-success text-center">Case submitted successfully!</div>';
        } else {
            $submit_msg = '<div class="alert alert-danger text-center">Failed to submit case. Try again.</div>';
        }
    } else {
        $submit_msg = '<div class="alert alert-warning text-center">Case title and description are required.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Case</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <h2 class="text-center mb-4">Submit Case to <?= htmlspecialchars($ngo['ngo_name']) ?></h2>

    <?php echo $submit_msg; ?>

    <!-- NGO Details -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($ngo['ngo_name']) ?></h5>
            <p><strong>Contact Person:</strong> <?= htmlspecialchars($ngo['contact_person']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($ngo['phone']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($ngo['email']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($ngo['address']) ?></p>
            <p><strong>Service Areas:</strong> <?= htmlspecialchars($ngo['service_areas']) ?></p>
            <p><strong>Capacity:</strong> <?= htmlspecialchars($ngo['capacity']) ?></p>
        </div>
    </div>

    <!-- Case Submission Form -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="ngo_id" value="<?= $ngo_id ?>">

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Case Title</label>
                        <input type="text" name="case_title" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Problem Category</label>
                        <input type="text" name="problem_category" class="form-control">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Severity</label>
                        <select name="severity" class="form-control">
                            <option value="">Select</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Risk Level</label>
                        <select name="risk_level" class="form-control">
                            <option value="">Select</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" name="submit_case" class="btn btn-success px-4">Submit Case</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include('config.php');

// Check user login
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Check if case ID is provided
if (!isset($_GET['id'])) {
    header("location: view_cases.php");
    exit;
}

$case_id = intval($_GET['id']);
$edit_msg = '';

// Fetch case
$stmt = $conn->prepare("SELECT * FROM cases WHERE id=? AND user_id=? AND status='pending'");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$case = $result->fetch_assoc();

if (!$case) {
    echo "<p class='text-center mt-5'>Case not found or cannot be edited.</p>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_title = trim($_POST['case_title']);
    $description = trim($_POST['description']);
    $problem_category = trim($_POST['problem_category']);
    $severity = trim($_POST['severity']);
    $risk_level = trim($_POST['risk_level']);

    if ($case_title && $description) {
        $stmt = $conn->prepare("
            UPDATE cases 
            SET case_title=?, description=?, problem_category=?, severity=?, risk_level=? 
            WHERE id=? AND user_id=? AND status='pending'
        ");
        $stmt->bind_param("ssssssi", $case_title, $description, $problem_category, $severity, $risk_level, $case_id, $user_id);
        if ($stmt->execute()) {
            $edit_msg = '<div class="alert alert-success text-center">Case updated successfully.</div>';
            $case['case_title'] = $case_title;
            $case['description'] = $description;
            $case['problem_category'] = $problem_category;
            $case['severity'] = $severity;
            $case['risk_level'] = $risk_level;
        } else {
            $edit_msg = '<div class="alert alert-danger text-center">Failed to update case.</div>';
        }
    } else {
        $edit_msg = '<div class="alert alert-warning text-center">Title and description are required.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Case</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <h2 class="text-center mb-4">Edit Case</h2>

    <?php if(!empty($edit_msg)) echo $edit_msg; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Case Title</label>
                        <input type="text" name="case_title" class="form-control" value="<?= htmlspecialchars($case['case_title']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($case['description']) ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Problem Category</label>
                        <input type="text" name="problem_category" class="form-control" value="<?= htmlspecialchars($case['problem_category']) ?>">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Severity</label>
                        <select name="severity" class="form-control">
                            <option value="">Select</option>
                            <option value="Low" <?= ($case['severity']=='Low')?'selected':'' ?>>Low</option>
                            <option value="Medium" <?= ($case['severity']=='Medium')?'selected':'' ?>>Medium</option>
                            <option value="High" <?= ($case['severity']=='High')?'selected':'' ?>>High</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Risk Level</label>
                        <select name="risk_level" class="form-control">
                            <option value="">Select</option>
                            <option value="Low" <?= ($case['risk_level']=='Low')?'selected':'' ?>>Low</option>
                            <option value="Medium" <?= ($case['risk_level']=='Medium')?'selected':'' ?>>Medium</option>
                            <option value="High" <?= ($case['risk_level']=='High')?'selected':'' ?>>High</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-success px-4">Update Case</button>
                    <a href="view_cases.php" class="btn btn-secondary px-4">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

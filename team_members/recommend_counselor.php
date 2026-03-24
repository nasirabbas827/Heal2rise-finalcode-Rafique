<?php
session_start();
include('../config.php'); 

// Only team members access
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'team_members') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];
$staff_name = $_SESSION["name"];

$case_id = intval($_GET['case_id'] ?? 0);
$msg = "";

// Validate case exists and is assigned to this staff
$stmt = $conn->prepare("SELECT id, case_title FROM cases WHERE id=? AND assigned_staff_id=?");
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$case_result = $stmt->get_result();
if ($case_result->num_rows !== 1) {
    echo "<script>alert('Case not found or not assigned to you'); window.location='staff_dashboard.php';</script>";
    exit;
}
$case = $case_result->fetch_assoc();
$stmt->close();

// Fetch all active counselors
$counselors_result = $conn->query("SELECT id, full_name, specialization FROM counselors WHERE status='active' ORDER BY full_name ASC");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $counselor_id = intval($_POST['counselor_id']);

    // Check if already recommended
    $stmt = $conn->prepare("SELECT id FROM case_counselor WHERE case_id=? AND counselor_id=?");
    $stmt->bind_param("ii", $case_id, $counselor_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    if ($check_result->num_rows > 0) {
        $msg = '<div class="alert alert-warning text-center">This counselor has already been recommended for this case.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO case_counselor (case_id, counselor_id, recommended_by) VALUES (?,?,?)");
        $stmt->bind_param("iii", $case_id, $counselor_id, $staff_id);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success text-center">Counselor recommended successfully!</div>';
        } else {
            $msg = '<div class="alert alert-danger text-center">Error recommending counselor. Please try again.</div>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recommend Counselor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="card mx-auto shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="mb-4">Recommend Counselor for Case: <?= htmlspecialchars($case['case_title']) ?></h2>
            <?= $msg ?>
            <form method="post">
                <div class="form-group">
                    <label>Select Counselor</label>
                    <select name="counselor_id" class="form-control" required>
                        <option value="">-- Select Counselor --</option>
                        <?php while($counselor = $counselors_result->fetch_assoc()): ?>
                            <option value="<?= $counselor['id'] ?>">
                                <?= htmlspecialchars($counselor['full_name']) ?> (<?= htmlspecialchars($counselor['specialization']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Recommend Counselor</button>
                    <a href="staff_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </form>

            <!-- Show already recommended counselors -->
            <h5 class="mt-4">Already Recommended Counselors:</h5>
            <?php
            $stmt = $conn->prepare("
                SELECT c.full_name, c.specialization, cc.recommended_at
                FROM case_counselor cc
                JOIN counselors c ON cc.counselor_id = c.id
                WHERE cc.case_id=?
            ");
            $stmt->bind_param("i", $case_id);
            $stmt->execute();
            $recommended_result = $stmt->get_result();
            if ($recommended_result->num_rows > 0):
            ?>
                <ul class="list-group">
                    <?php while($rec = $recommended_result->fetch_assoc()): ?>
                        <li class="list-group-item">
                            <?= htmlspecialchars($rec['full_name']) ?> (<?= htmlspecialchars($rec['specialization']) ?>)
                            - Recommended At: <?= $rec['recommended_at'] ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No counselors recommended yet for this case.</p>
            <?php endif; ?>
            <?php $stmt->close(); ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
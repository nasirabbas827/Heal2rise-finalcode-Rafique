<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'ngo') {
    header("location: ../login.php");
    exit;
}

$ngo_id = $_SESSION['id'];

if (!isset($_GET['case_id'])) {
    header("location: ngo_dashboard.php");
    exit;
}

$case_id = intval($_GET['case_id']);

// Fetch case info
$stmt = $conn->prepare("SELECT case_title, assigned_ngo_id FROM cases WHERE id=? AND assigned_ngo_id=?");
$stmt->bind_param("ii", $case_id, $ngo_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "Case not found.";
    exit;
}

// Fetch skill programs
$stmt = $conn->prepare("SELECT * FROM skill_programs WHERE ngo_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$skills = $stmt->get_result();
$stmt->close();

// Fetch progress notes
$stmt = $conn->prepare("
    SELECT pn.*, st.full_name AS staff_name 
    FROM progress_notes pn 
    LEFT JOIN ngo_staff st ON pn.staff_id = st.id 
    WHERE pn.case_id=? ORDER BY pn.note_date DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$notes = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Skills & Progress - <?= htmlspecialchars($case['case_title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">
    <h2>Skills Programs for Case: <?= htmlspecialchars($case['case_title']) ?></h2>

    <?php if($skills->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Program Name</th>
                <th>Description</th>
                <th>Duration (weeks)</th>
                <th>Mode</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($s = $skills->fetch_assoc()): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['program_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($s['description'])) ?></td>
                <td><?= $s['duration_weeks'] ?></td>
                <td><?= htmlspecialchars($s['mode']) ?></td>
                <td><?= $s['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No skill programs available for this case.</p>
    <?php endif; ?>

    <h3 class="mt-5">Progress Notes</h3>
    <?php if($notes->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Note Date</th>
                <th>Description</th>
                <th>Overall Progress</th>
                <th>Added By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($n = $notes->fetch_assoc()): ?>
            <tr>
                <td><?= $n['id'] ?></td>
                <td><?= $n['note_date'] ?></td>
                <td><?= nl2br(htmlspecialchars($n['description'])) ?></td>
                <td><?= htmlspecialchars($n['overall_progress']) ?></td>
                <td><?= $n['staff_name'] ?? 'User' ?></td>
                <td><?= $n['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No progress notes added yet.</p>
    <?php endif; ?>

</div>
</body>
</html>

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
$stmt = $conn->prepare("SELECT case_title FROM cases WHERE id=? AND assigned_ngo_id=?");
$stmt->bind_param("ii", $case_id, $ngo_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "Case not found.";
    exit;
}

// Fetch sessions
$stmt = $conn->prepare("
    SELECT s.*, st.full_name AS staff_name
    FROM counseling_sessions s
    LEFT JOIN ngo_staff st ON s.staff_id = st.id
    WHERE s.case_id=?
    ORDER BY s.session_date DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$sessions = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Counseling Sessions - <?= htmlspecialchars($case['case_title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">
    <h2>Counseling Sessions for: <?= htmlspecialchars($case['case_title']) ?></h2>

    <?php if($sessions->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Notes</th>
                <th>Next Session</th>
                <th>Meeting Link</th>
            </tr>
        </thead>
        <tbody>
            <?php while($s = $sessions->fetch_assoc()): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['staff_name']) ?></td>
                <td><?= $s['session_date'] ?></td>
                <td><?= htmlspecialchars($s['session_type']) ?></td>
                <td><?= $s['duration_minutes'] ?? '-' ?></td>
                <td><?= nl2br(htmlspecialchars($s['notes'])) ?></td>
                <td><?= $s['next_session'] ?? '-' ?></td>
                <td><?= $s['meeting_link'] ? "<a href='{$s['meeting_link']}' target='_blank'>Link</a>" : '-' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No sessions added yet.</p>
    <?php endif; ?>

</div>
</body>
</html>

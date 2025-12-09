<?php
include('config.php');
session_start();

// Only admin access
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: view_cases.php");
    exit;
}

$case_id = intval($_GET['id']);

// Fetch case info with user and NGO
$stmt = $conn->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email, n.ngo_name
    FROM cases c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE c.id=?
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "Case not found.";
    exit;
}

// Fetch counseling sessions
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

// Fetch skill programs
$stmt = $conn->prepare("
    SELECT * FROM skill_programs 
    WHERE ngo_id=? ORDER BY created_at DESC
");
$stmt->bind_param("i", $case['assigned_ngo_id']);
$stmt->execute();
$skills = $stmt->get_result();
$stmt->close();

// Fetch progress notes
$stmt = $conn->prepare("
    SELECT pn.*, st.full_name AS staff_name
    FROM progress_notes pn
    LEFT JOIN ngo_staff st ON pn.staff_id = st.id
    WHERE pn.case_id=?
    ORDER BY pn.note_date DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$notes = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Case Details - <?= htmlspecialchars($case['case_title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container py-5">
    <h2>Case Details: <?= htmlspecialchars($case['case_title']) ?></h2>
    <p><strong>User:</strong> <?= htmlspecialchars($case['user_name']) ?> (<?= htmlspecialchars($case['user_email']) ?>)</p>
    <p><strong>NGO:</strong> <?= htmlspecialchars($case['ngo_name'] ?? '-') ?></p>
    <p><strong>Status:</strong> <?= ucfirst($case['status']) ?></p>
    <p><strong>Created At:</strong> <?= $case['created_at'] ?></p>

    <hr>
    <h3>Counseling Sessions</h3>
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
        <p>No counseling sessions available.</p>
    <?php endif; ?>

    <hr>
    <h3>Skill Programs</h3>
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
        <p>No skill programs available.</p>
    <?php endif; ?>

    <hr>
    <h3>Progress Notes</h3>
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

    <a href="view_cases.php" class="btn btn-secondary mt-3">Back to Cases</a>
</div>

</body>
</html>

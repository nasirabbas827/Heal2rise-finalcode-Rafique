<?php
session_start();
include('../config.php');

// Check if NGO staff is logged in
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'ngo_staff') {
    header("location: ../login.php");
    exit;
}

$staff_id = $_SESSION["id"];

if (!isset($_GET['case_id'])) {
    header("location: staff_dashboard.php");
    exit;
}

$case_id = intval($_GET['case_id']);

// Fetch case info
$stmt = $conn->prepare("
    SELECT c.case_title, u.name AS user_name, n.ngo_name
    FROM cases c
    JOIN users u ON c.user_id = u.id
    JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE c.id = ? AND c.assigned_staff_id = ?
");
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$case_result = $stmt->get_result();
$case = $case_result->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "<p class='text-center mt-5'>Case not found or not assigned to you.</p>";
    exit;
}

// Handle add/edit
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_date = $_POST['session_date'];
    $session_type = trim($_POST['session_type']);
    $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : NULL;
    $notes = trim($_POST['notes']);
    $next_session = !empty($_POST['next_session']) ? $_POST['next_session'] : NULL;
    $meeting_link = !empty($_POST['meeting_link']) ? trim($_POST['meeting_link']) : NULL;

    if ($edit_id > 0) {
        $stmt = $conn->prepare("
            UPDATE counseling_sessions 
            SET session_date=?, session_type=?, duration_minutes=?, notes=?, next_session=?, meeting_link=? 
            WHERE id=? AND staff_id=?
        ");
        $stmt->bind_param("ssisssii", $session_date, $session_type, $duration, $notes, $next_session, $meeting_link, $edit_id, $staff_id);
        $msg = $stmt->execute() ? "Session updated successfully!" : "Error updating session.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO counseling_sessions
            (case_id, staff_id, session_date, session_type, duration_minutes, notes, next_session, meeting_link)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iississs", $case_id, $staff_id, $session_date, $session_type, $duration, $notes, $next_session, $meeting_link);
        $msg = $stmt->execute() ? "Session added successfully!" : "Error adding session.";
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM counseling_sessions WHERE id=? AND staff_id=?");
    $stmt->bind_param("ii", $delete_id, $staff_id);
    if ($stmt->execute()) {
        $msg = "Session deleted successfully!";
    } else {
        $msg = "Error deleting session.";
    }
    $stmt->close();
}

// If editing, fetch existing session
$session_data = [];
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM counseling_sessions WHERE id=? AND staff_id=?");
    $stmt->bind_param("ii", $edit_id, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session_data = $result->fetch_assoc();
    $stmt->close();
}

// Fetch all sessions for the case
$stmt = $conn->prepare("
    SELECT s.*, st.full_name AS staff_name
    FROM counseling_sessions s
    JOIN ngo_staff st ON s.staff_id = st.id
    WHERE s.case_id = ?
    ORDER BY s.session_date DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$sessions_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Case Sessions</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

<script>
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this session?')) {
        window.location='sessions.php?case_id=<?= $case_id ?>&delete='+id;
    }
}
</script>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container py-5">
<h2 class="mb-4 text-center">Counseling Sessions for: <?= htmlspecialchars($case['case_title']) ?></h2>
<p><strong>User:</strong> <?= htmlspecialchars($case['user_name']) ?> | <strong>NGO:</strong> <?= htmlspecialchars($case['ngo_name']) ?></p>

<?php if(!empty($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

<div class="card mb-4">
<div class="card-body">
<h5 class="card-title"><?= $edit_id>0 ? "Edit Session" : "Add New Session" ?></h5>
<form method="post" action="">
<div class="form-row">
<div class="form-group col-md-6">
<label>Session Date & Time</label>
<input type="datetime-local" name="session_date" class="form-control" required value="<?= $session_data['session_date'] ?? '' ?>">
</div>
<div class="form-group col-md-6">
<label>Session Type</label>
<input type="text" name="session_type" class="form-control" required value="<?= htmlspecialchars($session_data['session_type'] ?? '') ?>">
</div>
</div>
<div class="form-row">
<div class="form-group col-md-6">
<label>Duration (minutes)</label>
<input type="number" name="duration" class="form-control" value="<?= $session_data['duration_minutes'] ?? '' ?>">
</div>
<div class="form-group col-md-6">
<label>Next Session (optional)</label>
<input type="datetime-local" name="next_session" class="form-control" value="<?= $session_data['next_session'] ?? '' ?>">
</div>
</div>
<div class="form-group">
<label>Meeting Link (optional)</label>
<input type="url" name="meeting_link" class="form-control" value="<?= htmlspecialchars($session_data['meeting_link'] ?? '') ?>">
</div>
<div class="form-group">
<label>Notes</label>
<textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($session_data['notes'] ?? '') ?></textarea>
</div>
<button type="submit" class="btn btn-primary"><?= $edit_id>0 ? "Update Session" : "Add Session" ?></button>
<?php if($edit_id>0): ?>
<a href="sessions.php?case_id=<?= $case_id ?>" class="btn btn-secondary">Cancel</a>
<?php endif; ?>
</form>
</div>
</div>

<h4>All Sessions</h4>
<?php if ($sessions_result->num_rows > 0): ?>
<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="thead-dark">
<tr>
<th>ID</th>
<th>Date</th>
<th>Type</th>
<th>Duration</th>
<th>Notes</th>
<th>Next Session</th>
<th>Meeting Link</th>
<th>Created By</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while ($session = $sessions_result->fetch_assoc()): ?>
<tr>
<td><?= $session['id'] ?></td>
<td><?= $session['session_date'] ?></td>
<td><?= htmlspecialchars($session['session_type']) ?></td>
<td><?= $session['duration_minutes'] ?? '-' ?></td>
<td><?= nl2br(htmlspecialchars($session['notes'])) ?></td>
<td><?= $session['next_session'] ?? '-' ?></td>
<td><?= $session['meeting_link'] ? "<a href='{$session['meeting_link']}' target='_blank'>Link</a>" : '-' ?></td>
<td><?= htmlspecialchars($session['staff_name']) ?></td>
<td>
<a href="sessions.php?case_id=<?= $case_id ?>&edit=<?= $session['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
<button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $session['id'] ?>)">Delete</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php else: ?>
<p class="text-center">No sessions added yet.</p>
<?php endif; ?>

<div class="text-center mt-3">
<a href="staff_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

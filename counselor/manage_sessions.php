<?php
session_start();
include('../config.php');

// 1. Security Check
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'counselor') {
    header("location: ../login.php");
    exit;
}

$counselor_id = $_SESSION["id"];
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

if ($case_id === 0) {
    die("Invalid Case ID.");
}

// 2. Verify counselor is assigned to this case
$check_stmt = $conn->prepare("SELECT id FROM case_counselor WHERE case_id = ? AND counselor_id = ?");
$check_stmt->bind_param("ii", $case_id, $counselor_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    die("Unauthorized: You are not assigned to this case.");
}
$check_stmt->close();

$msg = "";

// 3. Handle Form Submissions (Create & Update)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_type = $_POST['session_type'];
    $session_date = $_POST['session_date'];
    $duration = intval($_POST['duration_minutes']);
    $notes = $_POST['notes'];
    $next_session = !empty($_POST['next_session']) ? $_POST['next_session'] : NULL;
    $meeting_link = $_POST['meeting_link'];

    if (isset($_POST['session_id']) && !empty($_POST['session_id'])) {
        // UPDATE
        $s_id = intval($_POST['session_id']);
        $sql = "UPDATE counseling_sessions SET session_date=?, session_type=?, duration_minutes=?, notes=?, next_session=?, meeting_link=? WHERE id=? AND counselor_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisssii", $session_date, $session_type, $duration, $notes, $next_session, $meeting_link, $s_id, $counselor_id);
    } else {
        // CREATE
        $sql = "INSERT INTO counseling_sessions (case_id, counselor_id, session_date, session_type, duration_minutes, notes, next_session, meeting_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iississs", $case_id, $counselor_id, $session_date, $session_type, $duration, $notes, $next_session, $meeting_link);
    }

    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Session saved successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// 4. Handle Delete
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM counseling_sessions WHERE id = ? AND counselor_id = ?");
    $stmt->bind_param("ii", $del_id, $counselor_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_sessions.php?case_id=$case_id");
    exit;
}

// 5. Fetch all sessions for this case
$stmt = $conn->prepare("SELECT * FROM counseling_sessions WHERE case_id = ? AND counselor_id = ? ORDER BY session_date DESC");
$stmt->bind_param("ii", $case_id, $counselor_id);
$stmt->execute();
$sessions = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sessions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body class="bg-light">
<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h3>Manage Sessions for Case #<?= $case_id ?></h3>
    </div>

    <?= $msg ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 id="form-title">Add New Session</h5>
                    <form method="POST" id="session-form">
                        <input type="hidden" name="session_id" id="session_id">
                        
                        <div class="form-group">
                            <label>Session Type</label>
                            <select name="session_type" id="session_type" class="form-control" required>
                                <option value="Initial Consultation">Initial Consultation</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Crisis Intervention">Crisis Intervention</option>
                                <option value="Final Assessment">Final Assessment</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Date & Time</label>
                            <input type="datetime-local" name="session_date" id="session_date" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Duration (Mins)</label>
                            <input type="number" name="duration_minutes" id="duration_minutes" class="form-control" placeholder="e.g. 45">
                        </div>

                        <div class="form-group">
                            <label>Meeting Link (Optional)</label>
                            <input type="url" name="meeting_link" id="meeting_link" class="form-control" placeholder="https://zoom.us/j/...">
                        </div>

                        <div class="form-group">
                            <label>Session Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Next Session (Optional)</label>
                            <input type="datetime-local" name="next_session" id="next_session" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Save Session</button>
                        <button type="button" onclick="resetForm()" class="btn btn-link btn-block btn-sm">Clear / Add New</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sessions->num_rows > 0): ?>
                                <?php while($s = $sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, y H:i', strtotime($s['session_date'])) ?></td>
                                    <td><span class="badge badge-info"><?= $s['session_type'] ?></span></td>
                                    <td class="small"><?= substr(htmlspecialchars($s['notes']), 0, 50) ?>...</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editSession(<?= json_encode($s) ?>)'>Edit</button>
                                        <a href="?case_id=<?= $case_id ?>&delete_id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Del</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4">No sessions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editSession(data) {
    document.getElementById('form-title').innerText = "Edit Session";
    document.getElementById('session_id').value = data.id;
    document.getElementById('session_type').value = data.session_type;
    document.getElementById('duration_minutes').value = data.duration_minutes;
    document.getElementById('notes').value = data.notes;
    document.getElementById('meeting_link').value = data.meeting_link;
    
    // Format date for datetime-local input
    if(data.session_date) {
        document.getElementById('session_date').value = data.session_date.replace(" ", "T").substring(0, 16);
    }
    if(data.next_session) {
        document.getElementById('next_session').value = data.next_session.replace(" ", "T").substring(0, 16);
    }
}

function resetForm() {
    document.getElementById('form-title').innerText = "Add New Session";
    document.getElementById('session-form').reset();
    document.getElementById('session_id').value = "";
}
</script>
</body>
</html>
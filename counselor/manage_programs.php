<?php
session_start();
include('../config.php');

// Security Check
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== 'counselor') {
    header("location: ../login.php");
    exit;
}

$counselor_id = $_SESSION["id"];
$msg = "";

// Get Case ID from URL (Mandatory for assigning a program)
if (!isset($_GET['case_id'])) {
    die("<div class='container mt-5 alert alert-danger'>Error: Case ID is required to manage programs.</div>");
}
$case_id = intval($_GET['case_id']);

// Fetch Case Details for Header
$case_stmt = $conn->prepare("SELECT case_title FROM cases WHERE id = ?");
$case_stmt->bind_param("i", $case_id);
$case_stmt->execute();
$case_info = $case_stmt->get_result()->fetch_assoc();
$case_stmt->close();

// Handle Create & Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p_name = $_POST['program_name'];
    $desc = $_POST['description'];
    $duration = intval($_POST['duration_weeks']);
    $mode = $_POST['mode'];

    if (!empty($_POST['program_id'])) {
        // UPDATE: Match by ID, Counselor, AND Case
        $p_id = intval($_POST['program_id']);
        $stmt = $conn->prepare("UPDATE skill_programs SET program_name=?, description=?, duration_weeks=?, mode=? WHERE id=? AND counselor_id=? AND case_id=?");
        $stmt->bind_param("ssisiii", $p_name, $desc, $duration, $mode, $p_id, $counselor_id, $case_id);
    } else {
        // CREATE: Include case_id
        $stmt = $conn->prepare("INSERT INTO skill_programs (counselor_id, case_id, program_name, description, duration_weeks, mode) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissis", $counselor_id, $case_id, $p_name, $desc, $duration, $mode);
    }

    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success shadow-sm">Program saved and assigned to case successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger">Error saving program: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM skill_programs WHERE id = ? AND counselor_id = ? AND case_id = ?");
    $stmt->bind_param("iii", $del_id, $counselor_id, $case_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_programs.php?case_id=" . $case_id);
    exit;
}

// Fetch Programs assigned to THIS specific Case
$stmt = $conn->prepare("SELECT * FROM skill_programs WHERE counselor_id = ? AND case_id = ? ORDER BY created_at DESC");
$stmt->bind_param("ii", $counselor_id, $case_id);
$stmt->execute();
$programs = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Rehab Program | <?= htmlspecialchars($case_info['case_title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-weight-bold"><i class="fas fa-tasks text-primary"></i> Skill Programs</h3>
            <p class="text-muted">Assigning modules to Case: <span class="text-primary font-weight-bold"><?= htmlspecialchars($case_info['case_title']) ?></span></p>
        </div>
        <a href="counselor_dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?= $msg ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 id="form-title" class="font-weight-bold mb-3">Assign New Program</h5>
                    <form method="POST" id="program-form">
                        <input type="hidden" name="program_id" id="program_id">
                        
                        <div class="form-group">
                            <label class="small font-weight-bold">Program Name</label>
                            <input type="text" name="program_name" id="program_name" class="form-control" placeholder="e.g. Cognitive Behavioral Therapy Track" required>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Mode</label>
                                    <select name="mode" id="mode" class="form-control">
                                        <option value="Online">Online</option>
                                        <option value="In-Person">In-Person</option>
                                        <option value="Hybrid">Hybrid</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Duration (Weeks)</label>
                                    <input type="number" name="duration_weeks" id="duration_weeks" class="form-control" min="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold">Rehab Description / Goals</label>
                            <textarea name="description" id="description" class="form-control" rows="4" placeholder="Outline what the user needs to achieve..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block shadow-sm">Save & Assign to Case</button>
                        <button type="button" onclick="resetForm()" class="btn btn-link btn-block btn-sm text-muted">Reset Form</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h5 class="font-weight-bold mb-3">Programs for this Patient</h5>
            <?php if ($programs->num_rows > 0): ?>
                <?php while($p = $programs->fetch_assoc()): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h6 class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($p['program_name']) ?></h6>
                            <span class="badge badge-light border text-primary"><?= $p['mode'] ?></span>
                        </div>
                        <p class="small text-muted"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <span class="small text-secondary"><i class="fas fa-clock mr-1"></i> <?= $p['duration_weeks'] ?> Weeks</span>
                            <div>
                                <button class="btn btn-sm btn-link text-info" onclick='editProgram(<?= json_encode($p) ?>)'>Edit</button>
                                <a href="?case_id=<?= $case_id ?>&delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('Delete this assignment?')">Remove</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center bg-white p-5 rounded shadow-sm border">
                    <i class="fas fa-clipboard-list fa-3x text-light mb-3"></i>
                    <p class="text-muted mb-0">No programs assigned to this specific case yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function editProgram(data) {
    document.getElementById('form-title').innerText = "Update Assigned Program";
    document.getElementById('program_id').value = data.id;
    document.getElementById('program_name').value = data.program_name;
    document.getElementById('mode').value = data.mode;
    document.getElementById('duration_weeks').value = data.duration_weeks;
    document.getElementById('description').value = data.description;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetForm() {
    document.getElementById('form-title').innerText = "Assign New Program";
    document.getElementById('program-form').reset();
    document.getElementById('program_id').value = "";
}
</script>
</body>
</html>
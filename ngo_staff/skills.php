<?php
session_start();
include('../config.php');

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
    SELECT c.case_title, n.ngo_name
    FROM cases c
    JOIN ngos n ON c.assigned_ngo_id = n.id
    WHERE c.id = ? AND c.assigned_staff_id = ?
");
$stmt->bind_param("ii", $case_id, $staff_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "<p class='text-center mt-5'>Case not found or not assigned to you.</p>";
    exit;
}

// Handle add/edit skill
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $program_name = trim($_POST['program_name']);
    $description = trim($_POST['description']);
    $duration_weeks = !empty($_POST['duration_weeks']) ? intval($_POST['duration_weeks']) : NULL;
    $mode = $_POST['mode'] ?? 'Online';

    if ($edit_id > 0) {
        $stmt = $conn->prepare("UPDATE skill_programs SET program_name=?, description=?, duration_weeks=?, mode=? WHERE id=? AND ngo_id=(SELECT assigned_ngo_id FROM cases WHERE id=?)");
        $stmt->bind_param("ssisii", $program_name, $description, $duration_weeks, $mode, $edit_id, $case_id);
        $msg = $stmt->execute() ? "Skill updated successfully!" : "Error updating skill.";
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO skill_programs (ngo_id, program_name, description, duration_weeks, mode) VALUES ((SELECT assigned_ngo_id FROM cases WHERE id=?), ?, ?, ?, ?)");
        $stmt->bind_param("issis", $case_id, $program_name, $description, $duration_weeks, $mode);
        $msg = $stmt->execute() ? "Skill added successfully!" : "Error adding skill.";
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM skill_programs WHERE id=? AND ngo_id=(SELECT assigned_ngo_id FROM cases WHERE id=?)");
    $stmt->bind_param("ii", $delete_id, $case_id);
    $stmt->execute();
    $msg = "Skill deleted successfully!";
    $stmt->close();
}

// Fetch all skills for this case's NGO
$stmt = $conn->prepare("SELECT * FROM skill_programs WHERE ngo_id=(SELECT assigned_ngo_id FROM cases WHERE id=?) ORDER BY created_at DESC");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$skills_result = $stmt->get_result();
$stmt->close();

// Fetch skill data for editing
$skill_data = [];
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM skill_programs WHERE id=? AND ngo_id=(SELECT assigned_ngo_id FROM cases WHERE id=?)");
    $stmt->bind_param("ii", $edit_id, $case_id);
    $stmt->execute();
    $skill_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Skills Tracking</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

<script>
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this skill?')) {
        window.location='skills.php?case_id=<?= $case_id ?>&delete='+id;
    }
}
</script>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container py-5">
<h2 class="mb-4 text-center">Skills Tracking for Case: <?= htmlspecialchars($case['case_title']) ?></h2>
<p><strong>NGO:</strong> <?= htmlspecialchars($case['ngo_name']) ?></p>

<?php if(!empty($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

<div class="card mb-4">
<div class="card-body">
<h5 class="card-title"><?= $edit_id>0 ? "Edit Skill" : "Add New Skill" ?></h5>
<form method="post" action="">
<div class="form-group">
<label>Program Name</label>
<input type="text" name="program_name" class="form-control" required value="<?= htmlspecialchars($skill_data['program_name'] ?? '') ?>">
</div>
<div class="form-group">
<label>Description</label>
<textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($skill_data['description'] ?? '') ?></textarea>
</div>
<div class="form-group">
<label>Duration (weeks)</label>
<input type="number" name="duration_weeks" class="form-control" value="<?= $skill_data['duration_weeks'] ?? '' ?>">
</div>
<div class="form-group">
<label>Mode</label>
<select name="mode" class="form-control">
<option value="Online" <?= ($skill_data['mode'] ?? '') === 'Online' ? 'selected' : '' ?>>Online</option>
<option value="Offline" <?= ($skill_data['mode'] ?? '') === 'Offline' ? 'selected' : '' ?>>Offline</option>
<option value="Hybrid" <?= ($skill_data['mode'] ?? '') === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
</select>
</div>
<button type="submit" class="btn btn-primary"><?= $edit_id>0 ? "Update Skill" : "Add Skill" ?></button>
<?php if($edit_id>0): ?>
<a href="skills.php?case_id=<?= $case_id ?>" class="btn btn-secondary">Cancel</a>
<?php endif; ?>
</form>
</div>
</div>

<h4>All Skills</h4>
<?php if ($skills_result->num_rows > 0): ?>
<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="thead-dark">
<tr>
<th>ID</th>
<th>Program Name</th>
<th>Description</th>
<th>Duration (weeks)</th>
<th>Mode</th>
<th>Created At</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($skill = $skills_result->fetch_assoc()): ?>
<tr>
<td><?= $skill['id'] ?></td>
<td><?= htmlspecialchars($skill['program_name']) ?></td>
<td><?= nl2br(htmlspecialchars($skill['description'])) ?></td>
<td><?= $skill['duration_weeks'] ?></td>
<td><?= htmlspecialchars($skill['mode']) ?></td>
<td><?= $skill['created_at'] ?></td>
<td>
<a href="skills.php?case_id=<?= $case_id ?>&edit=<?= $skill['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
<button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $skill['id'] ?>)">Delete</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php else: ?>
<p class="text-center">No skill programs added yet.</p>
<?php endif; ?>

<div class="text-center mt-3">
<a href="staff_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];

if (!isset($_GET['case_id'])) {
    header("location: view_cases.php");
    exit;
}

$case_id = intval($_GET['case_id']);

// Fetch case info
$stmt = $conn->prepare("SELECT case_title, assigned_ngo_id FROM cases WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$case) {
    echo "Case not found.";
    exit;
}

// Handle adding progress note
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_note'])) {
        $note_date = $_POST['note_date'];
        $description = trim($_POST['description']);
        $overall_progress = trim($_POST['overall_progress']);

        $stmt = $conn->prepare("INSERT INTO progress_notes (case_id, staff_id, note_date, description, overall_progress) VALUES (?, 0, ?, ?, ?)");
        $stmt->bind_param("isss", $case_id, $note_date, $description, $overall_progress);
        if ($stmt->execute()) {
            $success_msg = "Progress note added successfully!";
        } else {
            $error_msg = "Error adding progress note.";
        }
        $stmt->close();
    }

    if (isset($_POST['edit_note'])) {
        $note_id = intval($_POST['note_id']);
        $note_date = $_POST['note_date'];
        $description = trim($_POST['description']);
        $overall_progress = trim($_POST['overall_progress']);

        $stmt = $conn->prepare("UPDATE progress_notes SET note_date=?, description=?, overall_progress=? WHERE id=? AND case_id=?");
        $stmt->bind_param("sssii", $note_date, $description, $overall_progress, $note_id, $case_id);
        if ($stmt->execute()) {
            $success_msg = "Progress note updated successfully!";
        } else {
            $error_msg = "Error updating progress note.";
        }
        $stmt->close();
    }
}

// Handle deletion of progress note
if (isset($_GET['delete_note'])) {
    $note_id = intval($_GET['delete_note']);
    $delete_stmt = $conn->prepare("DELETE FROM progress_notes WHERE id=? AND case_id=?");
    $delete_stmt->bind_param("ii", $note_id, $case_id);
    $delete_stmt->execute();
    if ($delete_stmt->affected_rows > 0) {
        $success_msg = "Progress note deleted successfully.";
    } else {
        $error_msg = "Unable to delete progress note.";
    }
    $delete_stmt->close();
}

// Fetch skill programs
$stmt = $conn->prepare("SELECT * FROM skill_programs WHERE ngo_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $case['assigned_ngo_id']);
$stmt->execute();
$skills_result = $stmt->get_result();
$stmt->close();

// Fetch progress notes
$stmt = $conn->prepare("SELECT pn.*, st.full_name AS staff_name FROM progress_notes pn LEFT JOIN ngo_staff st ON pn.staff_id = st.id WHERE pn.case_id=? ORDER BY pn.note_date DESC");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$notes_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Skills & Progress for <?= htmlspecialchars($case['case_title']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container py-5">

<h2>Skills Programs for Case: <?= htmlspecialchars($case['case_title']) ?></h2>

<?php if(!empty($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
<?php if(!empty($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

<!-- Skill Programs Table -->
<?php if($skills_result->num_rows > 0): ?>
<table class="table table-bordered table-striped">
<thead>
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
<?php while($skill = $skills_result->fetch_assoc()): ?>
<tr>
<td><?= $skill['id'] ?></td>
<td><?= htmlspecialchars($skill['program_name']) ?></td>
<td><?= nl2br(htmlspecialchars($skill['description'])) ?></td>
<td><?= $skill['duration_weeks'] ?></td>
<td><?= htmlspecialchars($skill['mode']) ?></td>
<td><?= $skill['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>No skill programs available for this case.</p>
<?php endif; ?>

<!-- Progress Notes Section -->
<h3 class="mt-5">Progress Notes</h3>
<button class="btn btn-success mb-3" data-toggle="modal" data-target="#addNoteModal">Add Progress Note</button>

<?php if($notes_result->num_rows > 0): ?>
<table class="table table-bordered table-striped">
<thead>
<tr>
<th>ID</th>
<th>Note Date</th>
<th>Description</th>
<th>Overall Progress</th>
<th>Added By</th>
<th>Created At</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($note = $notes_result->fetch_assoc()): ?>
<tr>
<td><?= $note['id'] ?></td>
<td><?= $note['note_date'] ?></td>
<td><?= nl2br(htmlspecialchars($note['description'])) ?></td>
<td><?= htmlspecialchars($note['overall_progress']) ?></td>
<td><?= $note['staff_name'] ?? 'User' ?></td>
<td><?= $note['created_at'] ?></td>
<td>
    <button class="btn btn-sm btn-primary mb-1 editBtn" 
            data-id="<?= $note['id'] ?>" 
            data-date="<?= $note['note_date'] ?>" 
            data-description="<?= htmlspecialchars($note['description'], ENT_QUOTES) ?>" 
            data-progress="<?= htmlspecialchars($note['overall_progress'], ENT_QUOTES) ?>">
            Edit
    </button>
    <a href="?case_id=<?= $case_id ?>&delete_note=<?= $note['id'] ?>" 
       class="btn btn-sm btn-danger mb-1" 
       onclick="return confirm('Are you sure you want to delete this note?');">
       Delete
    </a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>No progress notes added yet.</p>
<?php endif; ?>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Progress Note</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Note Date</label>
          <input type="date" name="note_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-group">
          <label>Overall Progress</label>
          <textarea name="overall_progress" class="form-control" rows="1"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_note" class="btn btn-primary">Add Note</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Progress Note</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="note_id" id="edit_note_id">
        <div class="form-group">
          <label>Note Date</label>
          <input type="date" name="note_date" id="edit_note_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-group">
          <label>Overall Progress</label>
          <textarea name="overall_progress" id="edit_overall_progress" class="form-control" rows="1"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit_note" class="btn btn-primary">Update Note</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<a href="view_cases.php" class="btn btn-secondary mt-3">Back to Cases</a>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    $('.editBtn').click(function(){
        var id = $(this).data('id');
        var date = $(this).data('date');
        var desc = $(this).data('description');
        var progress = $(this).data('progress');

        $('#edit_note_id').val(id);
        $('#edit_note_date').val(date);
        $('#edit_description').val(desc);
        $('#edit_overall_progress').val(progress);

        $('#editNoteModal').modal('show');
    });
});
</script>

</div>
</body>
</html>

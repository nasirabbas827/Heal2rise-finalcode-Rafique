<?php
session_start();
include('config.php'); // Include your database connection file

// 1. Authentication Check and Admin ID setup
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// =======================================================================
// 2. Database Queries for Dashboard Statistics
// =======================================================================

// Initialize variables with placeholders for demonstration
$total_users = 0;
$total_ngos = 0;
$total_cases = 0;
$active_cases = 0;
$total_sessions = 0;
$pending_closure_requests = 0;
$recent_users = null;
$recent_cases = null;
$recent_notes = null;
$user_roles_labels = ['User', 'Counselor', 'Psychiatrist'];
$user_roles_data = [0, 0, 0];
$case_status_labels = ['Pending', 'Approved', 'Rejected'];
$case_status_data = [0, 0, 0];
$case_trend_labels = [];
$case_trend_data = [];

// Assume $conn is the database connection object from 'config.php'
if (isset($conn)) {
    try {
        // --- Single Value Stats ---
        $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;
        $total_ngos = $conn->query("SELECT COUNT(*) FROM ngos")->fetch_row()[0] ?? 0;
        $total_cases = $conn->query("SELECT COUNT(*) FROM cases")->fetch_row()[0] ?? 0;
        // Cases that are approved and not closed
        $active_cases = $conn->query("SELECT COUNT(*) FROM cases WHERE status = 'approved' AND closed_at IS NULL")->fetch_row()[0] ?? 0;
        $total_sessions = $conn->query("SELECT COUNT(*) FROM counseling_sessions")->fetch_row()[0] ?? 0;
        $pending_closure_requests = $conn->query("SELECT COUNT(*) FROM closure_requests WHERE status = 'pending'")->fetch_row()[0] ?? 0;

        // --- User Role Distribution Data ---
        $role_counts = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $temp_roles = [];
        while ($row = $role_counts->fetch_assoc()) {
            $temp_roles[$row['role']] = (int) $row['count'];
        }
        $user_roles_labels = array_keys($temp_roles);
        $user_roles_data = array_values($temp_roles);

        // --- Case Status Distribution Data (based on the 'cases' status column) ---
        $status_counts = $conn->query("SELECT status, COUNT(*) as count FROM cases GROUP BY status");
        $temp_statuses = [];
        while ($row = $status_counts->fetch_assoc()) {
            $temp_statuses[$row['status']] = (int) $row['count'];
        }
        $case_status_labels = array_keys($temp_statuses);
        $case_status_data = array_values($temp_statuses);


        // --- Case Submission Trend (Last 7 Days) ---
        $case_trend_query = "
            SELECT 
                DATE(created_at) as submission_date, 
                COUNT(*) as case_count
            FROM cases
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY submission_date
            ORDER BY submission_date ASC
        ";
        $trend_result = $conn->query($case_trend_query);
        $trend_map = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $trend_map[$date] = 0;
        }
        while ($row = $trend_result->fetch_assoc()) {
            $trend_map[$row['submission_date']] = (int) $row['case_count'];
        }
        $case_trend_labels = array_map(fn($date) => date('M d', strtotime($date)), array_keys($trend_map));
        $case_trend_data = array_values($trend_map);

        // --- Recent Registrations (Users) ---
        $recent_users = $conn->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");

        // --- Recent Case Submissions ---
        $recent_cases_query = "
            SELECT c.case_title, u.name as user_name, c.created_at, c.status
            FROM cases c
            JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC
            LIMIT 5
        ";
        $recent_cases = $conn->query($recent_cases_query);

        // --- Recent Progress Notes ---
        $recent_notes_query = "
            SELECT pn.note_date, pn.description, c.case_title, s.full_name as staff_name
            FROM progress_notes pn
            JOIN cases c ON pn.case_id = c.id
            LEFT JOIN ngo_staff s ON pn.staff_id = s.id
            ORDER BY pn.created_at DESC
            LIMIT 5
        ";
        $recent_notes = $conn->query($recent_notes_query);

    } catch (Exception $e) {
        // Handle database errors (optional: log the error)
        // echo "Database Error: " . $e->getMessage();
    }
} else {
    // Fallback placeholder data if connection fails
    $total_users = 1250;
    $total_ngos = 45;
    $total_cases = 890;
    $active_cases = 310;
    $total_sessions = 5200;
    $pending_closure_requests = 12;
    $user_roles_labels = ['User', 'Counselor', 'Psychiatrist'];
    $user_roles_data = [900, 200, 150];
    $case_status_labels = ['Pending', 'Approved', 'Rejected'];
    $case_status_data = [150, 600, 140];
    $case_trend_labels = ['Dec 03', 'Dec 04', 'Dec 05', 'Dec 06', 'Dec 07', 'Dec 08', 'Dec 09'];
    $case_trend_data = [5, 12, 18, 9, 25, 14, 11];
    // Set recent data results to empty for placeholders
    $recent_users = new class { public function num_rows() { return 0; } public function fetch_assoc() { return null; } };
    $recent_cases = new class { public function num_rows() { return 0; } public function fetch_assoc() { return null; } };
    $recent_notes = new class { public function num_rows() { return 0; } public function fetch_assoc() { return null; } };

}


// Prepare chart data for JavaScript (ensure it's valid JSON)
$user_roles_labels_json = json_encode($user_roles_labels);
$user_roles_data_json = json_encode($user_roles_data);
$case_status_labels_json = json_encode($case_status_labels);
$case_status_data_json = json_encode($case_status_data);
$case_trend_labels_json = json_encode($case_trend_labels);
$case_trend_data_json = json_encode($case_trend_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Mental Health Support</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #17a2b8; /* Info Blue - Supportive */
            --secondary-color: #6c757d;
            --success-color: #28a745; /* Green - Positive Outcome */
            --warning-color: #ffc107; /* Yellow - Attention/Pending */
            --danger-color: #dc3545; /* Red - High Risk */
            --bg-light: #f4f6f9;
            --card-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            --accent: #17a2b8; 
            --highlight: #6f42c1; /* Purple - Mental Health/Counseling */
        }
        
        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--accent), var(--highlight));
            color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.3);
        }

        /* Card styles remain mostly the same, adjusted for the new theme */
        .card-stats {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .card-stats .card-header-icon {
            background: linear-gradient(45deg, var(--primary-color), var(--highlight));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            position: absolute;
            top: -20px;
            right: 20px;
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Override specific icon background for better visual distinction */
        .card-stats:nth-child(2) .card-header-icon { background: linear-gradient(45deg, var(--success-color), #45b78d); } /* NGOs */
        .card-stats:nth-child(3) .card-header-icon { background: linear-gradient(45deg, var(--danger-color), #c82333); } /* Active Cases */
        .card-stats:nth-child(4) .card-header-icon { background: linear-gradient(45deg, var(--primary-color), #007bff); } /* Total Sessions */
        .card-stats:nth-child(5) .card-header-icon { background: linear-gradient(45deg, var(--warning-color), #ff9800); } /* Total Cases */
        .card-stats:nth-child(6) .card-header-icon { background: linear-gradient(45deg, var(--highlight), #5a19b8); } /* Pending Closures */
        
        .card-stats .stats-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #343a40;
            line-height: 1;
        }
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }
        .alert-info { border-left-color: var(--primary-color); }
        .alert-success { border-left-color: var(--success-color); }
        .alert-warning { border-left-color: var(--warning-color); }
        .alert-danger { border-left-color: var(--danger-color); }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
<?php 
// This include is for the admin navigation bar - assuming it's correctly linked
include('admin_navbar.php'); 
?>

<div class="container-fluid mt-4">
    <div class="card welcome-card mb-5 shadow">
        <div class="card-body text-center py-4">
            <h1 class="mb-1 font-weight-bold text-white">Mental Health Support Admin Center </h1>
            <p class="lead mb-0">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"] ?? 'Admin'); ?>!</strong> Monitor the support network and cases.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 col-lg-2 mb-3">
            <div class="card card-stats">
                <div class="card-header-icon"><i class="fas fa-users fa-2x"></i></div>
                <div class="card-body">
                    <h5 class="stats-title">Total Users</h5>
                    <h3 class="stats-value"><?php echo $total_users; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2 mb-3">
            <div class="card card-stats">
                <div class="card-header-icon" style="background: linear-gradient(45deg, var(--success-color), #45b78d);"><i class="fas fa-handshake fa-2x"></i></div>
                <div class="card-body">
                    <h5 class="stats-title">Total NGOs</h5>
                    <h3 class="stats-value"><?php echo $total_ngos; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2 mb-3">
            <div class="card card-stats">
                 <div class="card-header-icon" style="background: linear-gradient(45deg, var(--danger-color), #c82333);"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                <div class="card-body">
                    <h5 class="stats-title">Active Cases</h5>
                    <h3 class="stats-value"><?php echo $active_cases; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2 mb-3">
            <div class="card card-stats">
                <div class="card-header-icon" style="background: linear-gradient(45deg, var(--primary-color), #007bff);"><i class="fas fa-comments fa-2x"></i></div>
                <div class="card-body">
                    <h5 class="stats-title">Sessions Logged</h5>
                    <h3 class="stats-value"><?php echo $total_sessions; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2 mb-3">
            <div class="card card-stats">
                <div class="card-header-icon" style="background: linear-gradient(45deg, var(--warning-color), #ff9800);"><i class="fas fa-folder-open fa-2x"></i></div>
                <div class="card-body">
                    <h5 class="stats-title">Total Cases</h5>
                    <h3 class="stats-value"><?php echo $total_cases; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2 mb-3">
            <div class="card card-stats">
                <div class="card-header-icon" style="background: linear-gradient(45deg, var(--highlight), #5a19b8);"><i class="fas fa-gavel fa-2x"></i></div>
                <div class="card-body">
                    <h5 class="stats-title">Pending Closures</h5>
                    <h3 class="stats-value"><?php echo $pending_closure_requests; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Role Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Case Status Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="caseStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Case Submission Trend (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="caseTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Registrations</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                        <ul class="list-unstyled">
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                <li class="alert alert-info mb-3">
                                    <p class="mb-1"><strong><?php echo htmlspecialchars($user['name']); ?></strong> (<?php echo ucfirst($user['role']); ?>)</p>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <small>Joined: <?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">No recent users.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Case Submissions</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($recent_cases && $recent_cases->num_rows > 0): ?>
                        <ul class="list-unstyled">
                            <?php while ($case = $recent_cases->fetch_assoc()): ?>
                                <?php
                                    $alert_class = match($case['status']) {
                                        'approved' => 'alert-success',
                                        'rejected' => 'alert-danger',
                                        default => 'alert-warning', // pending
                                    };
                                ?>
                                <li class="alert <?php echo $alert_class; ?> mb-3">
                                    <p class="mb-1"><strong><?php echo htmlspecialchars($case['case_title']); ?></strong></p>
                                    <p class="mb-1 small text-muted">Submitted by: <?php echo htmlspecialchars($case['user_name']); ?> </p>
                                    <small>Status:  <?php echo ucfirst($case['status']); ?>  | Filed: <?php echo date('M d, Y', strtotime($case['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">No recent cases submitted.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Latest Progress Notes</h5>
                </div>
                <div class="card-body notification-list">
                    <?php if ($recent_notes && $recent_notes->num_rows > 0): ?>
                        <ul class="list-unstyled">
                            <?php while ($note = $recent_notes->fetch_assoc()): ?>
                                <li class="alert alert-success mb-3">
                                    <p class="mb-1">Note on Case: <strong><?php echo htmlspecialchars($note['case_title'] ?? 'N/A'); ?></strong></p>
                                    <p class="mb-1 small text-muted font-italic">Staff: <?php echo htmlspecialchars($note['staff_name'] ?? 'Admin'); ?></p>
                                    <p class="mb-1 small text-muted font-italic">"<?php echo htmlspecialchars(substr($note['description'], 0, 40)) . '..."'; ?></p>
                                    <small>Date: <?php echo date('M d, Y', strtotime($note['note_date'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">No recent progress notes.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // PHP variables passed to JavaScript
    const userRoleLabels = <?php echo $user_roles_labels_json; ?>;
    const userRoleData = <?php echo $user_roles_data_json; ?>;
    const caseStatusLabels = <?php echo $case_status_labels_json; ?>;
    const caseStatusData = <?php echo $case_status_data_json; ?>;
    const caseTrendLabels = <?php echo $case_trend_labels_json; ?>;
    const caseTrendData = <?php echo $case_trend_data_json; ?>;

    // Default color palette for charts (Mental Health/Support theme)
    const primaryColor = '#17a2b8'; // Info
    const successColor = '#28a745'; // Success
    const dangerColor = '#dc3545'; // Danger
    const warningColor = '#ffc107'; // Warning
    const highlightColor = '#6f42c1'; // Purple/Counseling

    // 1. User Role Distribution Chart
    const userRoleChartCtx = document.getElementById('userRoleChart').getContext('2d');
    new Chart(userRoleChartCtx, {
        type: 'pie',
        data: {
            labels: userRoleLabels,
            datasets: [{
                data: userRoleData,
                backgroundColor: [primaryColor, successColor, highlightColor], 
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 } } }
            }
        }
    });

    // 2. Case Status Distribution Chart
    const caseStatusChartCtx = document.getElementById('caseStatusChart').getContext('2d');
    new Chart(caseStatusChartCtx, {
        type: 'doughnut',
        data: {
            labels: caseStatusLabels,
            datasets: [{
                label: 'Case Status Count',
                data: caseStatusData,
                backgroundColor: [warningColor, successColor, dangerColor],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 } } }
            }
        }
    });

    // 3. Case Submission Trend Chart
    const caseTrendChartCtx = document.getElementById('caseTrendChart').getContext('2d');
    new Chart(caseTrendChartCtx, {
        type: 'line',
        data: {
            labels: caseTrendLabels,
            datasets: [{
                label: 'New Cases Submitted',
                data: caseTrendData,
                borderColor: primaryColor,
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { display: true, position: 'top' }
            }
        }
    });
</script>
</body>
</html>
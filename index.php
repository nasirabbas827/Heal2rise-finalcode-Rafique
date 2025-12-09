<?php
session_start();
include('config.php');

// Fetch simple statistics for the landing page (e.g., number of verified NGOs)
$verified_ngos_count = 0;
if (isset($conn)) {
    try {
        $verified_ngos_count = $conn->query("SELECT COUNT(*) FROM ngos WHERE status='verified'")->fetch_row()[0] ?? 0;
    } catch (Exception $e) {
        // Fallback
        $verified_ngos_count = 50; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heal2Rise Book | Confidential Mental Health Support</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        :root {
            /* Calming/Supportive Theme */
            --primary-color: #4a90e2; /* Soothing Blue */
            --accent-color: #6f42c1; /* Supportive Purple */
            --success-color: #28a745;
            --bg-light: #f8f9fa;
        }
        .hero-section {
            height: 650px;
            /* Placeholder for a relevant background image: people connecting, a peaceful setting, etc. */
            background: linear-gradient(rgba(74, 144, 226, 0.7), rgba(111, 66, 193, 0.8)), url('./images/support_background.jpg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 0 0 40px 40px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .hero-section .btn-primary { 
            background-color: var(--accent-color); border-color: var(--accent-color); padding: 15px 40px; font-size: 1.3rem; border-radius: 30px; 
            transition: all 0.3s;
        }
        .hero-section .btn-primary:hover { 
            background-color: var(--primary-color); border-color: var(--primary-color); transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .card { border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .section-title { color: var(--accent-color); margin-bottom: 40px; font-weight: 700; position: relative; }
        .section-title::after { content: ''; width: 80px; height: 5px; background-color: var(--primary-color); position: absolute; bottom: -15px; left: 50%; transform: translateX(-50%); border-radius: 3px; }
        .feature-icon { color: var(--primary-color); }
        footer { background-color: #343a40; color: white; padding: 30px 0; border-top: 5px solid var(--accent-color); }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="hero-section">
    <h1 class="display-3 fw-bold text-white mb-3">Heal2Rise Book 📖</h1>
    <h2 class="lead mb-4 text-white-50">Confidential Support to  Heal, Regain Confidence, and Rise Again. </h2>
    <p class="lead mb-5 text-white">Connect with verified NGOs, counselors, and psychiatrists in a secure, private environment.</p>
    <?php if(!isset($_SESSION['id'])): ?>
        <a href="login.php" class="btn btn-primary shadow-lg"><i class="fas fa-hand-holding-heart mr-2"></i> Find Confidential Support / Login</a>
        <p class="mt-3 small text-white-50">Are you an NGO? <a href="login.php" class="text-warning font-weight-bold">Login Here</a></p>
    <?php else: ?>
        <a href="" class="btn btn-primary shadow-lg"><i class="fas fa-tachometer-alt mr-2"></i> Go to My Dashboard</a>
    <?php endif; ?>
</div>

<div class="section pt-5 pb-0">
    <div class="container">
        <h2 class="text-center section-title">Our 5-Step Path to Empowerment</h2>
        <div class="row text-center my-5">
            <div class="col-md-3 mb-4">
                <div class="card card-hover h-100 p-3 bg-light">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-3x mb-3 feature-icon"></i>
                        <h5 class="card-title text-primary">1. Secure Registration</h5>
                        <p class="card-text small">Start with a  private and confidential  signup. Your safety is our priority.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card card-hover h-100 p-3 bg-light">
                    <div class="card-body">
                        <i class="fas fa-handshake fa-3x mb-3 feature-icon" style="color: var(--accent-color);"></i>
                        <h5 class="card-title" style="color: var(--accent-color);">2. NGO Connection</h5>
                        <p class="card-text small">The system  auto-connects you  with a verified NGO specialized in your area of need.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card card-hover h-100 p-3 bg-light">
                    <div class="card-body">
                        <i class="fas fa-comments fa-3x mb-3 feature-icon"></i>
                        <h5 class="card-title text-primary">3. Counseling & Support</h5>
                        <p class="card-text small">Receive regular online sessions with a  psychiatrist or counselor .</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card card-hover h-100 p-3 bg-light">
                    <div class="card-body">
                        <i class="fas fa-tasks fa-3x mb-3 feature-icon" style="color: var(--accent-color);"></i>
                        <h5 class="card-title" style="color: var(--accent-color);">4. Skill Development</h5>
                        <p class="card-text small">Track your  progress in skill development  and empowerment initiatives.</p>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-center lead mb-5 mt-3">... Leading to  5. Rehabilitation & Case Closure  when you are fully empowered.</p>
    </div>
</div>

<div class="section bg-light py-5">
    <div class="container">
        <h2 class="text-center section-title">Why Trust Heal2Rise Book?</h2>
        <div class="row pt-4">
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-3 border-0 shadow-lg" style="border-left: 5px solid var(--success-color);">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-4x mb-3 text-success"></i>
                        <h3 class="display-4 font-weight-bold" style="color: var(--success-color);"><?= $verified_ngos_count ?>+</h3>
                        <h5 class="card-title">Verified NGO Partners</h5>
                        <p class="card-text small">Access support from pre-approved, certified social welfare organizations.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-3 card-hover">
                    <div class="card-body">
                        <i class="fas fa-lock fa-3x mb-3 feature-icon"></i>
                        <h5 class="card-title text-primary">Absolute Privacy</h5>
                        <p class="card-text">We guarantee  complete confidentiality  and secure data handling for all users seeking help.</p>
                        <hr>
                        <a href="" class="small text-muted">Read our Privacy Policy</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-3 card-hover">
                    <div class="card-body">
                        <i class="fas fa-user-tie fa-3x mb-3 feature-icon" style="color: var(--accent-color);"></i>
                        <h5 class="card-title" style="color: var(--accent-color);">Personalized Team Member</h5>
                        <p class="card-text">Once connected, a dedicated team member is  assigned to monitor your progress  continuously.</p>
                        <hr>
                        <a href="" class="small text-muted">View Verified NGOs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center">
    <div class="container">
        <p class="mb-0">© 2025 Heal2Rise Book. Empowering Lives.</p>
        <div class="small mt-2">
            <a href=" " class="text-white-50 mx-2">Terms of Service</a> |
            <a href=" " class="text-white-50 mx-2">Contact Us</a>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
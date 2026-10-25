<?php
// user/dashboard.php - User Dashboard with Dual Toggle Step Tracking

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user profile
$user = getUserById($user_id);

// Handle step start toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step_start_update'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $step_number = intval($_POST['step_number']);
        $started = isset($_POST['started']) ? 1 : 0;
        
        if ($step_number >= 1 && $step_number <= 11) {
            $stmt = $db->prepare("UPDATE user_steps SET started=?, started_at=? WHERE user_id=? AND step_number=?");
            $started_at = $started ? date('Y-m-d H:i:s') : NULL;
            $stmt->bind_param("isii", $started, $started_at, $user_id, $step_number);
            
            if ($stmt->execute()) {
                $success = 'Step ' . $step_number . ' start status updated';
            }
        }
    }
}

// Handle step completion toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step_complete_update'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $step_number = intval($_POST['step_number']);
        $completed = isset($_POST['completed']) ? 1 : 0;
        
        if ($step_number >= 1 && $step_number <= 11) {
            // Get current step status
            $check = $db->query("SELECT started FROM user_steps WHERE user_id=$user_id AND step_number=$step_number")->fetch_assoc();
            
            // If trying to complete but not started, show error
            if ($completed && !$check['started']) {
                $error = 'Please mark step as started before completing it';
            } else {
                $stmt = $db->prepare("UPDATE user_steps SET completed=?, completed_at=? WHERE user_id=? AND step_number=?");
                $completed_at = $completed ? date('Y-m-d H:i:s') : NULL;
                $stmt->bind_param("isii", $completed, $completed_at, $user_id, $step_number);
                
                if ($stmt->execute()) {
                    $success = 'Step ' . $step_number . ' completion status updated';
                }
            }
        }
    }
}

// Get user steps
$user_steps = getUserSteps($user_id);
$completed_steps = count(array_filter($user_steps, function($s) { return $s['completed']; }));

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Devotee Portal</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-summary { background: #83b7ce; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .profile-summary h2 { margin-bottom: 15px; color: #333; }
        .profile-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 15px; }
        .info-item { border-left: 4px solid #ff9800; padding-left: 15px; }
        .info-label { font-size: 12px; color: white; text-transform: uppercase; }
        .info-value { font-size: 16px; color: #333; font-weight: bold; margin-top: 5px; }
        .progress-bar { width: 100%; height: 30px; background: #eee; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #ff9800, #ffb74d); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; }
        .step-card { background: white; padding: 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .step-header { background: linear-gradient(135deg, #ff9800, #ffb74d); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .step-header h2 { margin: 0; font-size: 20px; }
        .step-list { padding: 0; }
        .step-item { background: #eeeeee33; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; transition: background 0.2s; }
        .step-item:last-child { border-bottom: none; }
        .step-item:hover { background: #eee; }
        
        .step-toggle-container { display: flex; flex-direction: column; gap: 8px; }
        .toggle-row { display: flex; align-items: center; gap: 10px; }
        .step-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: #ff9800; }
        .toggle-label { font-size: 13px; font-weight: 500; color: #666; min-width: 80px; }
        
        .step-label { flex: 1; font-size: 15px; font-weight: 500; color: #333; }
        .step-dates { font-size: 12px; color: #999; min-width: 180px; text-align: right; }
        .step-dates div { margin-bottom: 4px; }
        
        .step-item.started .step-label { color: #2196f3; }
        .step-item.completed .step-label { text-decoration: line-through; color: #4caf50; }
        
        .quick-links { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        
        @media (max-width: 768px) {
            .step-item { flex-direction: column; align-items: flex-start; }
            .step-dates { width: 100%; text-align: left; }
            .step-toggle-container { width: 100%; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <div class="logo-text"><span class="emoji">🙏</span> Devotees Portal</div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name" style="font-size: 16px"><?php echo $_SESSION['user_name']; ?></div>
                <div class="userrole" style="font-size: 18px">SLM Registration Number: <?php echo $user['serial_number']; ?></div>
            </div>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="page-title">📊 Welcome, <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>!</h1>
    
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <!-- Profile Summary -->
    <div class="profile-summary">
        <h2>👤 Your Profile</h2>
        <div class="profile-info">
            <div class="info-item">
                <div class="info-label">📱 Phone</div>
                <div class="info-value"><?php echo $user['phone']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">✉️ Email</div>
                <div class="info-value"><?php echo $user['email']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">📍 City</div>
                <div class="info-value"><?php echo $user['town_city']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">📅 Member Since</div>
                <div class="info-value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
            </div>
        </div>
        
        <div class="quick-links">
            <a href="profile.php" class="btn btn-small">Edit Profile</a>
            <a href="downloads.php" class="btn btn-small btn-secondary">📥 Downloads</a>
        </div>
    </div>
    
    <!-- ========================================
     RULES IMAGES SECTION (NEW)
     ======================================== -->
<div class="container mt-5">
    <div class="rules-section">
        <h2>📋 Rules & Guidelines</h2>
        
        <div class="rules-images-container">
            <div class="rules-image-box">
                <img src="<?php echo BASE_URL; ?>images/rules-1.png"
                     alt="Task Rules - Part 1"
                     title="Task Rules - Part 1">
                <div class="rules-image-label">Card - Front</div>
                <div class="rules-image-actions">
                    <a href="<?php echo BASE_URL; ?>images/rules-1.png" target="_blank" class="btn btn-small">👁️ View</a>
                    <a href="<?php echo BASE_URL; ?>download-image.php?file=rules-1.png" class="btn btn-small btn-secondary">⬇️ Download</a>
                </div>
            </div>

            <div class="rules-image-box">
                <img src="<?php echo BASE_URL; ?>images/rules-2.png"
                     alt="Task Rules - Part 2"
                     title="Task Rules - Part 2">
                <div class="rules-image-label">Card - Back</div>
                <div class="rules-image-actions">
                    <a href="<?php echo BASE_URL; ?>images/rules-2.png" target="_blank" class="btn btn-small">👁️ View</a>
                    <a href="<?php echo BASE_URL; ?>download-image.php?file=rules-2.png" class="btn btn-small btn-secondary">⬇️ Download</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END RULES IMAGES SECTION -->
    
    <!-- Progress Summary -->
    <div style="background: #ffffff33; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 15px;">📈 Your Progress</h2>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="flex: 1;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($completed_steps / 11) * 100; ?>%;">
                        <?php echo $completed_steps; ?> / 11
                    </div>
                </div>
            </div>
            <div style="font-size: 24px; font-weight: bold; color: #ff9800; min-width: 100px; text-align: right;">
                <?php echo round(($completed_steps / 11) * 100); ?>%
            </div>
        </div>
    </div>
    
    <!-- Steps/Tasks Checklist with Dual Toggle -->
    <div class="step-card">
        <div class="step-header">
            <h2>✅ Your Tasks (11 Parayanam)</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Mark when you start and when you complete each parayanam</p>
        </div>
        
        <div class="step-list">
            <?php foreach ($user_steps as $index => $step): ?>
            <div class="step-item <?php echo $step['started'] ? 'started' : ''; ?> <?php echo $step['completed'] ? 'completed' : ''; ?>">
                
                <div class="step-toggle-container">
                    <!-- Start Toggle -->
                    <form method="POST" class="toggle-row">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="step_start_update" value="1">
                        <input type="hidden" name="step_number" value="<?php echo $step['step_number']; ?>">
                        
                        <input type="checkbox" class="step-checkbox" name="started" value="1"
                            <?php echo $step['started'] ? 'checked' : ''; ?>
                            <?php echo $step['completed'] ? 'disabled' : ''; ?>
                            onchange="if(confirm('Update start status for Step <?php echo $step['step_number']; ?>?')) { this.form.submit(); }">
                        <span class="toggle-label">🟢 Started</span>
                    </form>
                    
                    <!-- Complete Toggle -->
                    <form method="POST" class="toggle-row">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="step_complete_update" value="1">
                        <input type="hidden" name="step_number" value="<?php echo $step['step_number']; ?>">
                        
                        <input type="checkbox" class="step-checkbox" name="completed" value="1"
                            <?php echo $step['completed'] ? 'checked' : ''; ?>
                            <?php echo !$step['started'] ? 'disabled' : ''; ?>
                            onchange="if(confirm('Update completion status for Step <?php echo $step['step_number']; ?>?')) { this.form.submit(); }">
                        <span class="toggle-label">✅ Completed</span>
                    </form>
                </div>
                
                <span class="step-label">Parayanam <?php echo $step['step_number']; ?></span>
                
                <div class="step-dates">
                    <div><strong>Started:</strong> <?php echo $step['started_at'] ? date('d M Y, H:i', strtotime($step['started_at'])) : '-'; ?></div>
                    <div><strong>Completed:</strong> <?php echo $step['completed_at'] ? date('d M Y, H:i', strtotime($step['completed_at'])) : '-'; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
    <p>📞 <?php echo CONTACT_PHONE; ?> | 📧 <?php echo CONTACT_EMAIL; ?></p>
</footer>

<script src="../js/script.js"></script>
</body>
</html>
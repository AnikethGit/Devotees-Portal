<?php
// user/dashboard.php - User Dashboard with Step Tracking

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user profile
$user = getUserById($user_id);

// Handle step update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step_update'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $step_number = intval($_POST['step_number']);
        $completed = isset($_POST['completed']) ? 1 : 0;
        
        if ($step_number >= 1 && $step_number <= 11) {
            updateUserStep($user_id, $step_number, $completed);
            $success = 'Step ' . $step_number . ' updated successfully';
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-summary { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .profile-summary h2 { margin-bottom: 15px; color: #333; }
        .profile-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 15px; }
        .info-item { border-left: 4px solid #ff9800; padding-left: 15px; }
        .info-label { font-size: 12px; color: #999; text-transform: uppercase; }
        .info-value { font-size: 16px; color: #333; font-weight: bold; margin-top: 5px; }
        .progress-bar { width: 100%; height: 30px; background: #eee; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #ff9800, #ffb74d); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; }
        .step-card { background: white; padding: 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .step-header { background: linear-gradient(135deg, #ff9800, #ffb74d); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .step-header h2 { margin: 0; font-size: 20px; }
        .step-list { padding: 0; }
        .step-item { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; transition: background 0.2s; }
        .step-item:last-child { border-bottom: none; }
        .step-item:hover { background: #f9f9f9; }
        .step-checkbox { width: 22px; height: 22px; cursor: pointer; accent-color: #ff9800; }
        .step-label { flex: 1; font-size: 15px; font-weight: 500; color: #333; }
        .step-date { font-size: 12px; color: #999; min-width: 120px; text-align: right; }
        .step-item.completed .step-label { text-decoration: line-through; color: #999; }
        .quick-links { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
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
                <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                <div class="user-role">Serial No: <?php echo $user['serial_number']; ?></div>
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
    
    <!-- Progress Summary -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
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
    
    <!-- Steps/Tasks Checklist -->
    <div class="step-card">
        <div class="step-header">
            <h2>✅ Your Tasks (11 Steps)</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Mark each step as complete when you finish</p>
        </div>
        
        <form method="POST" class="step-list" id="stepsForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="step_update" value="1">
            
            <?php foreach ($user_steps as $index => $step): ?>
            <div class="step-item <?php echo $step['completed'] ? 'completed' : ''; ?>" id="step<?php echo $step['step_number']; ?>">
                <input type="checkbox" class="step-checkbox" 
                    <?php echo $step['completed'] ? 'checked' : ''; ?>
                    onchange="submitStepUpdate(<?php echo $step['step_number']; ?>, this.checked)"
                    title="Click to mark as complete">
                
                <span class="step-label">Step <?php echo $step['step_number']; ?></span>
                
                <span class="step-date">
                    <?php echo $step['completed_at'] ? date('d M Y, H:i', strtotime($step['completed_at'])) : '-'; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
    <p>📞 <?php echo CONTACT_PHONE; ?> | 📧 <?php echo CONTACT_EMAIL; ?></p>
</footer>

<script src="../js/script.js"></script>
<script>
function submitStepUpdate(stepNumber, isChecked) {
    const form = document.getElementById('stepsForm');
    const formData = new FormData(form);
    
    formData.append('step_number', stepNumber);
    if (isChecked) {
        formData.append('completed', '1');
    }
    
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Optionally show confirmation
        const stepElement = document.getElementById('step' + stepNumber);
        if (isChecked) {
            stepElement.classList.add('completed');
        } else {
            stepElement.classList.remove('completed');
        }
        
        // Reload to show updated progress
        setTimeout(() => {
            window.location.reload();
        }, 500);
    })
    .catch(error => console.error('Error:', error));
}
</script>

</body>
</html>
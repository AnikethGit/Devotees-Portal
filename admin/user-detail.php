<?php
// admin/user-detail.php - User Profile & Dual Toggle Step Management

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();
$error = '';
$success = '';

// Get user from phone (encoded in URL)
$phone = isset($_GET['id']) ? base64_decode($_GET['id']) : null;
if (!$phone) {
    header('Location: users.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND role = 'user'");
$stmt->bind_param("s", $phone);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $town = sanitize($_POST['town'] ?? '');
        $house_no = sanitize($_POST['house_no'] ?? '');
        $locality = sanitize($_POST['locality'] ?? '');
        $postal_code = sanitize($_POST['postal_code'] ?? '');
        
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, house_no=?, locality_street=?, town_city=?, postal_code=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $email, $house_no, $locality, $town, $postal_code, $user['id']);
        
        if ($stmt->execute()) {
            $success = 'User profile updated successfully';
            $db->query("INSERT INTO admin_logs (admin_id, action, affected_user_id, description) VALUES ({$_SESSION['user_id']}, 'EDIT_USER', {$user['id']}, 'Profile updated')");
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle step start toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_step_start') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $step_number = intval($_POST['step_number']);
        $started = isset($_POST['started']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE user_steps SET started=?, started_at=? WHERE user_id=? AND step_number=?");
        $started_at = $started ? date('Y-m-d H:i:s') : NULL;
        $stmt->bind_param("isii", $started, $started_at, $user['id'], $step_number);
        
        if ($stmt->execute()) {
            $success = 'Step ' . $step_number . ' start status updated';
            $db->query("INSERT INTO admin_logs (admin_id, action, affected_user_id, description) VALUES ({$_SESSION['user_id']}, 'MODIFY_STEP', {$user['id']}, 'Step $step_number start status updated')");
        } else {
            $error = 'Failed to update step';
        }
    }
}

// Handle step completion toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_step_complete') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $step_number = intval($_POST['step_number']);
        $completed = isset($_POST['completed']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE user_steps SET completed=?, completed_at=? WHERE user_id=? AND step_number=?");
        $completed_at = $completed ? date('Y-m-d H:i:s') : NULL;
        $stmt->bind_param("isii", $completed, $completed_at, $user['id'], $step_number);
        
        if ($stmt->execute()) {
            $success = 'Step ' . $step_number . ' completion status updated';
            $db->query("INSERT INTO admin_logs (admin_id, action, affected_user_id, description) VALUES ({$_SESSION['user_id']}, 'MODIFY_STEP', {$user['id']}, 'Step $step_number completion status updated')");
        } else {
            $error = 'Failed to update step';
        }
    }
}

// Get user steps (includes any parayanams the user added themselves)
$user_steps = getUserSteps($user['id']);

// Get login history
$login_logs = $db->query("SELECT login_time, logout_time FROM login_logs WHERE user_id = {$user['id']} ORDER BY login_time DESC LIMIT 10");

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Detail - Admin Panel</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .two-column { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #6998b1a8; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .info-row { display: flex; gap: 20px; margin-bottom: 10px; }
        .info-label { font-weight: bold; color: rgba(19, 52, 59, 1); min-width: 150px; }
        .info-value { color: #ffffff; }
        
        .step-row { display: flex; align-items: center; gap: 15px; padding: 12px; border-bottom: 1px solid #eee; }
        .step-row:last-child { border-bottom: none; }
        .step-number { font-weight: bold; min-width: 80px; }
        .step-toggles { display: flex; flex-direction: column; gap: 8px; min-width: 150px; }
        .toggle-item { display: flex; align-items: center; gap: 8px; }
        .toggle-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #ff9800; }
        .toggle-item label { font-size: 13px; cursor: pointer; }
        .step-timestamps { flex: 1; font-size: 12px; color: #666; }
        .step-timestamps div { margin-bottom: 4px; }
        
        @media (max-width: 768px) {
            .two-column { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr !important; }
            .step-row { flex-direction: column; align-items: flex-start; }
            .step-toggles, .step-timestamps { width: 100%; }
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
                <div class="user-name">Admin: <?php echo $_SESSION['user_name']; ?></div>
            </div>
            <a href="users.php" class="btn btn-secondary">← Back</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="page-title">👤 User Details - <?php echo htmlspecialchars($user['name']); ?></h1>
    
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <!-- Profile Information Section -->
    <div class="two-column">
        <div class="info-box">
            <h2>📋 Profile Information</h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label>SLM Registration Number</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['serial_number']); ?>" disabled style="background: ;">
                </div>
                
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" value="<?php echo $user['phone']; ?>" disabled style="background: ;">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Email address (optional)">
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>House No.</label>
                        <input type="text" name="house_no" value="<?php echo htmlspecialchars($user['house_no']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Locality/Street</label>
                        <input type="text" name="locality" value="<?php echo htmlspecialchars($user['locality_street']); ?>">
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Town/City *</label>
                        <input type="text" name="town" value="<?php echo htmlspecialchars($user['town_city']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
            </form>
        </div>
        
        <!-- Status Card -->
        <div class="info-box">
            <h2>ℹ️ Account Status</h2>
            
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span style="display: inline-block; padding: 5px 10px; border-radius: 4px; background: <?php echo $user['status'] === 'active' ? '#e8f5e9' : '#ffebee'; ?>; color: <?php echo $user['status'] === 'active' ? '#2e7d32' : '#c62828'; ?>;">
                        <?php echo strtoupper($user['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Role:</div>
                <div class="info-value"><?php echo ucfirst($user['role']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Registered:</div>
                <div class="info-value"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Last Updated:</div>
                <div class="info-value"><?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Steps Management Section with Dual Toggles -->
    <div style="background: #e9eef1a8; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2>✅ Parayanam Tracking & Modification (Dual Toggle)</h2>
        
        <div style="background: #eee; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ff9800;">
            <p style="margin: 0; color: #666;">Toggle start and completion status separately for each step. Each toggle saves its own timestamp. Users can add extra parayanams from their own dashboard.</p>
        </div>
        
        <div style="overflow-x: auto;">
            <?php foreach ($user_steps as $step): ?>
            <div class="step-row">
                <div class="step-number">Parayanam <?php echo $step['step_number']; ?></div>
                
                <div class="step-toggles">
                    <!-- Start Toggle -->
                    <form method="POST" class="toggle-item">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_step_start">
                        <input type="hidden" name="step_number" value="<?php echo $step['step_number']; ?>">
                        
                        <input type="checkbox" 
                               id="start_<?php echo $step['step_number']; ?>" 
                               name="started" 
                               value="1" 
                               <?php echo $step['started'] ? 'checked' : ''; ?>
                               onchange="if(confirm('Update start status for Step <?php echo $step['step_number']; ?>?')) { this.form.submit(); }">
                        <label for="start_<?php echo $step['step_number']; ?>">🟢 Started</label>
                    </form>
                    
                    <!-- Complete Toggle -->
                    <form method="POST" class="toggle-item">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_step_complete">
                        <input type="hidden" name="step_number" value="<?php echo $step['step_number']; ?>">
                        
                        <input type="checkbox" 
                               id="complete_<?php echo $step['step_number']; ?>" 
                               name="completed" 
                               value="1" 
                               <?php echo $step['completed'] ? 'checked' : ''; ?>
                               onchange="if(confirm('Update completion status for Step <?php echo $step['step_number']; ?>?')) { this.form.submit(); }">
                        <label for="complete_<?php echo $step['step_number']; ?>">✅ Completed</label>
                    </form>
                </div>
                
                <div class="step-timestamps">
                    <div><strong>Started:</strong> <?php echo $step['started_at'] ? date('d M Y, H:i', strtotime($step['started_at'])) : 'Not started'; ?></div>
                    <div><strong>Completed:</strong> <?php echo $step['completed_at'] ? date('d M Y, H:i', strtotime($step['completed_at'])) : 'Not completed'; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php 
        $total_steps = count($user_steps);
        $started_count = count(array_filter($user_steps, function($s) { return $s['started']; }));
        $completed_count = count(array_filter($user_steps, function($s) { return $s['completed']; }));
        $progress = $total_steps > 0 ? ($completed_count / $total_steps) * 100 : 0;
        ?>
        
        <div style="margin-top: 20px; padding: 15px; background: #666; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <strong>Started:</strong> <?php echo $started_count; ?> / <?php echo $total_steps; ?>
                </div>
                <div>
                    <strong>Completed:</strong> <?php echo $completed_count; ?> / <?php echo $total_steps; ?> (<?php echo round($progress); ?>%)
                </div>
            </div>
            <div style="width: 100%; height: 20px; background: #eee; border-radius: 4px; overflow: hidden;">
                <div style="width: <?php echo $progress; ?>%; height: 100%; background: linear-gradient(90deg, #ff9800, #ffb74d); transition: width 0.3s;"></div>
            </div>
        </div>
    </div>
    
    <!-- Login History Section -->
    <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>🔐 Login History (Last 10)</h2>
        
        <?php if ($login_logs->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $login_logs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('d M Y H:i:s', strtotime($log['login_time'])); ?></td>
                    <td><?php echo $log['logout_time'] ? date('d M Y H:i:s', strtotime($log['logout_time'])) : '-'; ?></td>
                    <td>
                        <?php 
                        if ($log['logout_time']) {
                            $duration = strtotime($log['logout_time']) - strtotime($log['login_time']);
                            $hours = floor($duration / 3600);
                            $minutes = floor(($duration % 3600) / 60);
                            echo ($hours ? $hours . 'h ' : '') . $minutes . 'min';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; background: <?php echo $log['logout_time'] ? '#e8f5e9' : '#fff3e0'; ?>; color: <?php echo $log['logout_time'] ? '#2e7d32' : '#e65100'; ?>; font-size: 12px;">
                            <?php echo $log['logout_time'] ? 'Completed' : 'Active'; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: #999; padding: 20px;">No login history found</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
</footer>

<script src="../js/script.js"></script>
</body>
</html>

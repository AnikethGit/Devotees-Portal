<?php
// user/profile.php - Edit User Profile

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user profile
$user = getUserById($user_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $house_no = sanitize($_POST['house_no'] ?? '');
        $locality = sanitize($_POST['locality'] ?? '');
        $town = sanitize($_POST['town'] ?? '');
        $postal_code = sanitize($_POST['postal_code'] ?? '');
        
        if (empty($name) || empty($phone) || empty($town)) {
            $error = 'Please fill all required fields';
        } else if (!empty($email) && !validateEmail($email)) {
            $error = 'Invalid email address';
        } else {
            $result = updateUserProfile($user_id, $name, $email, $phone, $house_no, $locality, $town, $postal_code);
            if ($result['success']) {
                $success = 'Profile updated successfully!';
                // Refresh user data
                $user = getUserById($user_id);
                // Update session
                $_SESSION['user_name'] = $name;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Devotee Portal</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
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
    <h1 class="page-title">✏️ Edit Your Profile</h1>
    
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <div style="max-width: 600px; background: #6998b1a8; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>SLM Registration Number</label>
                <input type="text" value="<?php echo $user['serial_number']; ?>" disabled>
                <small style="color: #999;">This cannot be changed</small>
            </div>
            
            <div class="form-group">
                <label>Full Name <span style="color: #ff9800;">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone Number <span style="color: #ff9800;">*</span></label>
                <input type="text" name="phone" value="<?php echo $user['phone']; ?>" placeholder="10-digit mobile number" maxlength="10">
                <small style="color: #999;">Your login phone number</small>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="your@email.com (optional)">
            </div>
            
            <div class="form-group">
                <label>House No./Flat No.</label>
                <input type="text" name="house_no" value="<?php echo htmlspecialchars($user['house_no']); ?>">
            </div>
            
            <div class="form-group">
                <label>Locality / Street</label>
                <input type="text" name="locality" value="<?php echo htmlspecialchars($user['locality_street']); ?>">
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Town / City <span style="color: #ff9800;">*</span></label>
                    <input type="text" name="town" value="<?php echo htmlspecialchars($user['town_city']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>" maxlength="6">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn" style="flex: 1;">Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none;">Cancel</a>
            </div>
        </form>
        
        <hr style="margin: 30px 0; border: 1px solid #eee;">
        
        <div style="background: #ffffff80; padding: 15px; border-radius: 4px; border-left: 4px solid #ff9800;">
            <h3 style="margin-top: 0;">ℹ️ Account Information</h3>
            <p style="margin: 10px 0; color: #666; font-size: 14px;">
                <strong>Member Since:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?>
            </p>
            <p style="margin: 10px 0; color: #666; font-size: 14px;">
                <strong>Last Updated:</strong> <?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?>
            </p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="dashboard.php" style="color: #ff9800; text-decoration: none;">← Back to Dashboard</a>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
</footer>

</body>
</html>
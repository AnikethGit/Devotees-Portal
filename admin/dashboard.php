<?php
// admin/dashboard.php - Admin Dashboard

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

// Get statistics
$db = getDB();

// Total users
$users_result = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_users = $users_result->fetch_assoc()['total'];

// Total active users (visited in last 7 days)
$active_users = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM login_logs WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];

// Total downloads
$downloads = $db->query("SELECT COUNT(*) as total FROM downloads WHERE status = 'active'")->fetch_assoc()['total'];

// Recent registrations
$recent_users = $db->query("SELECT serial_number, name, phone, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sadguru Bharadwaja Portal</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <div class="logo-text">
                <span class="emoji">🙏</span>
                Devotees Portal
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name">Admin: <?php echo $_SESSION['user_name']; ?></div>
                <div class="user-role">👤 Administrator</div>
            </div>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="page-title">📊 Admin Dashboard</h1>
    
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-title">👥 Total Devotees</div>
            <div style="font-size: 32px; color: #ff9800; font-weight: bold;"><?php echo $total_users; ?></div>
            <p style="color: #666; margin-top: 10px;">Registered users in the system</p>
        </div>
        
        <div class="card">
            <div class="card-title">🟢 Active Users (7 Days)</div>
            <div style="font-size: 32px; color: #4caf50; font-weight: bold;"><?php echo $active_users; ?></div>
            <p style="color: #666; margin-top: 10px;">Users who logged in recently</p>
        </div>
        
        <div class="card">
            <div class="card-title">📥 Downloads</div>
            <div style="font-size: 32px; color: #2196f3; font-weight: bold;"><?php echo $downloads; ?></div>
            <p style="color: #666; margin-top: 10px;">Available files for users</p>
        </div>
    </div>
    
    <div style="background: #ffffffe0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: #333;">⚙️ Admin Controls</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="users.php" class="btn" style="text-align: center; padding: 15px;">👥 Manage Users</a>
            <a href="downloads.php" class="btn" style="text-align: center; padding: 15px;">📁 Manage Downloads</a>
            <a href="export-data.php" class="btn" style="text-align: center; padding: 15px;">📊 Export Data</a>
            <a href="#" class="btn" style="text-align: center; padding: 15px;">⚙️ Settings</a>
        </div>
    </div>
    
    <div style="background: #e9eef1a8; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-bottom: 20px; color: #333;">📝 Recent Registrations</h2>
        
        <table>
            <thead>
                <tr>
                    <th>SLM Registration No</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $recent_users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['serial_number']; ?></td>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['phone']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td><a href="user-detail.php?id=<?php echo base64_encode($user['phone']); ?>" class="btn btn-small">View</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal. All rights reserved.</p>
    <p>📞 <?php echo CONTACT_PHONE; ?> | 📧 <?php echo CONTACT_EMAIL; ?></p>
</footer>

</body>
</html>
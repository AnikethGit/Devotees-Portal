<?php
// admin/export-data.php - Export User Data to CSV

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Handle export
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $data = exportUsersData($search);
    $csv = arrayToCSV($data);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="devotees_data_' . date('Y-m-d_H-i-s') . '.csv"');
    echo $csv;
    exit;
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - Admin Panel</title>
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
                <div class="user-name">Admin: <?php echo $_SESSION['user_name']; ?></div>
            </div>
            <a href="dashboard.php" class="btn">← Dashboard</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="page-title">📊 Export User Data</h1>
    
    <div style="background: #ffffff30; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px;">
        <h2>Download User Data as CSV</h2>
        <p style="color: #c4c5c5; margin-bottom: 20px;">Export all user information in Excel-compatible CSV format for backup and analysis.</p>
        
        <form method="GET" style="display: flex; flex-direction: column; gap: 20px;">
            <div class="form-group">
                <label>Filter by Name or Phone (Optional)</label>
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div style="background: #eeeeeebf; padding: 15px; border-radius: 4px; border-left: 4px solid #ff9800;">
                <strong>Include columns:</strong>
                <div style="margin-top: 10px; color: #666; font-size: 14px;">
                    ✓ Serial Number<br>
                    ✓ Name<br>
                    ✓ Phone<br>
                    ✓ Email<br>
                    ✓ Address (House, Locality, Town, Code)<br>
                    ✓ Registration Date
                </div>
            </div>
            
            <input type="hidden" name="action" value="download">
            <button type="submit" class="btn" style="width: 100%; padding: 15px; font-size: 16px;">
                📥 Export as CSV
            </button>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center;">
            💡 CSV files can be opened in Excel, Google Sheets, or any spreadsheet application
        </p>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
</footer>

</body>
</html>
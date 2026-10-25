<?php
// user/downloads.php - Download Available Files

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();

// Get all downloads
$downloads = getAllDownloads();

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloads - Devotee Portal</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .download-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 4px solid #ff9800;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        
        .download-info {
            flex: 1;
        }
        
        .download-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .download-details {
            font-size: 12px;
            color: #999;
            margin: 5px 0;
        }
        
        .download-description {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }
        
        .download-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .file-type-pdf { color: #ff5252; }
        .file-type-xls { color: #4caf50; }
        .file-type-xlsx { color: #4caf50; }
        .file-type-doc { color: #2196f3; }
        .file-type-docx { color: #2196f3; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 600px) {
            .download-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn {
                width: 100%;
            }
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
                <div class="userrole" style="font-size: 18px">SLM Registration Number: <?php 
                    $user = getUserById($_SESSION['user_id']);
                    echo $user['serial_number'];
                ?></div>
            </div>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="page-title">📥 Available Downloads</h1>
    
    <?php if (count($downloads) > 0): ?>
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #ff9800;">
            <p style="margin: 0; color: #666;">
                📌 <strong><?php echo count($downloads); ?></strong> files available for download. Click on any file to download it.
            </p>
        </div>
        
        <div style="max-width: 900px;">
            <?php foreach ($downloads as $file): ?>
            <div class="download-card">
                <div style="display: flex; align-items: center; flex: 1; min-width: 0;">
                    <span class="download-icon file-type-<?php echo strtolower($file['file_type']); ?>">
                        <?php 
                        $icons = [
                            'pdf' => '📄',
                            'xls' => '📊',
                            'xlsx' => '📊',
                            'doc' => '📝',
                            'docx' => '📝'
                        ];
                        echo $icons[strtolower($file['file_type'])] ?? '📎';
                        ?>
                    </span>
                    
                    <div class="download-info">
                        <div class="download-name"><?php echo htmlspecialchars($file['original_filename']); ?></div>
                        <div class="download-details">
                            Type: <?php echo strtoupper($file['file_type']); ?> | 
                            Size: <?php echo round($file['file_size'] / 1024 / 1024, 2); ?> MB | 
                            Uploaded: <?php echo date('d M Y', strtotime($file['uploaded_at'])); ?>
                        </div>
                        <?php if (!empty($file['description'])): ?>
                        <div class="download-description"><?php echo htmlspecialchars($file['description']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <a href="<?php echo BASE_URL . 'download-file.php?id=' . $file['id']; ?>"
                   class="btn btn-small"
                   title="Download <?php echo htmlspecialchars($file['original_filename']); ?>">
                    ⬇️ Download
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2 style="color: #999;">No Downloads Available</h2>
                <p>There are currently no files available for download.</p>
                <p style="font-size: 12px; color: #bbb;">Check back soon!</p>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="dashboard.php" style="color: #ff9800; text-decoration: none;">← Back to Dashboard</a>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
    <p>📞 <?php echo CONTACT_PHONE; ?> | 📧 <?php echo CONTACT_EMAIL; ?></p>
</footer>

</body>
</html>
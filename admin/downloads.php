<?php
// admin/downloads.php - File Upload & Management

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();
$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $description = sanitize($_POST['description'] ?? '');
        $result = uploadFile($_FILES['file'], $description);
        
        if ($result['success']) {
            $success = 'File uploaded successfully';
            $db->query("INSERT INTO admin_logs (admin_id, action, description) VALUES ({$_SESSION['user_id']}, 'UPLOAD_FILE', 'File uploaded: " . escapeSQLString($_FILES['file']['name']) . "')");
        } else {
            $error = $result['message'];
        }
    }
}

// Handle file delete
if (isset($_POST['delete_file'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $file_id = intval($_POST['file_id']);
        $result = deleteDownload($file_id, $_SESSION['user_id']);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get all downloads
$downloads = getAllDownloads();
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Downloads - Admin Panel</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .file-upload-box {
            border: 3px dashed #ff9800;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #faf8f3;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-box:hover {
            background: #f5f1e8;
            border-color: #ffb74d;
        }
        
        .file-upload-box.dragover {
            background: #fff3e0;
            border-color: #ff9800;
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .file-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #ff9800;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .file-meta {
            font-size: 12px;
            color: #999;
        }
        
        .file-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .file-type-icon {
            font-size: 24px;
        }
        
        .empty-files {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .file-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .file-actions {
                width: 100%;
            }
            
            .file-actions button {
                flex: 1;
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
                <div class="user-name">Admin: <?php echo $_SESSION['user_name']; ?></div>
            </div>
            <a href="dashboard.php" class="btn">← Dashboard</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="page-title">📁 Manage Download Files</h1>
    
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <!-- Upload Section -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="color: #c4c5c5">📤 Upload New File</h2>
        <p style="color: #666; margin-bottom: 20px;">Upload PDF, Excel, or Word documents for users to download (max 50MB)</p>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="file-upload-box" id="dropZone" onclick="document.getElementById('fileInput').click();">
                <input type="file" id="fileInput" name="file" required onchange="handleFileSelect(event)" style="display: none;">
                
                <div class="upload-icon">📄</div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 16px; font-weight: bold;">Click to upload file</p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 14px;">or drag & drop here</p>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">PDF, XLS, XLSX, DOC, DOCX (up to 50MB)</p>
                <span id="fileName" style="display: none; color: #ff9800; margin-top: 10px; font-weight: bold; font-size: 14px;"></span>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>File Description (Optional)</label>
                <textarea name="description" placeholder="Add a description for this file (what users should know about it)..." style="height: 80px;"></textarea>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; padding: 12px; font-size: 16px;">Upload File</button>
        </form>
    </div>
    
    <!-- Files List Section -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #c4c5c5">📋 Available Download Files (<?php echo count($downloads); ?>)</h2>
        
        <?php if (count($downloads) > 0): ?>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ff9800;">
                <p style="margin: 0; color: #666;">📌 <strong><?php echo count($downloads); ?></strong> files are available for users to download</p>
            </div>
            
            <div>
                <?php foreach ($downloads as $file): ?>
                <div class="file-item">
                    <div style="font-size: 32px;">
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
                    </div>
                    
                    <div class="file-info">
                        <div class="file-name"><?php echo htmlspecialchars($file['original_filename']); ?></div>
                        <div class="file-meta">
                            📦 <?php echo round($file['file_size'] / 1024 / 1024, 2); ?> MB | 
                            📅 <?php echo date('d M Y', strtotime($file['uploaded_at'])); ?> | 
                            🏷️ <?php echo strtoupper($file['file_type']); ?>
                        </div>
                        <?php if (!empty($file['description'])): ?>
                        <div class="file-meta" style="margin-top: 8px; color: #666; font-style: italic;">
                            💬 <?php echo htmlspecialchars(substr($file['description'], 0, 60)) . (strlen($file['description']) > 60 ? '...' : ''); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="file-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                            <input type="hidden" name="delete_file" value="1">
                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this file? Users will no longer be able to download it.');">🗑️ Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-files">
                <p style="font-size: 48px; margin: 0;">📭</p>
                <p style="margin: 15px 0 0 0;">No files uploaded yet</p>
                <p style="margin: 10px 0 0 0; font-size: 14px;">Upload your first file using the form above</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
</footer>

<script src="../js/script.js"></script>
<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

// Drag and drop handlers
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    fileInput.files = e.dataTransfer.files;
    handleFileSelect(null);
});

function handleFileSelect(event) {
    const file = fileInput.files[0];
    if (file) {
        const fileName = document.getElementById('fileName');
        fileName.textContent = '✓ Selected: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        fileName.style.display = 'block';
    }
}
</script>

</body>
</html>
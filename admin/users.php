<?php
// admin/users.php - User Management

require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$db = getDB();
$error = '';
$success = '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $town = sanitize($_POST['town'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $error = 'Password is required';
        } else {
            $result = registerUser($name, $phone, $email, '', '', $town, '', $password);
            if ($result['success']) {
                $success = 'User added successfully with Serial No: ' . $result['serial_number'];
                // Log admin action
                $db->query("INSERT INTO admin_logs (admin_id, action, affected_user_id, description) VALUES ({$_SESSION['user_id']}, 'ADD_USER', (SELECT id FROM users WHERE phone = '" . formatPhone($phone) . "'), 'New user added')");
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Handle Delete User
if (isset($_POST['delete_user'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed';
    } else {
        $user_id = intval($_POST['user_id']);
        $result = deleteUser($user_id, $_SESSION['user_id']);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get all users
$users_data = getAllUsers($search, $page, 10);
$users = $users_data['users'];
$total_pages = $users_data['pages'];
$current_page = $users_data['current_page'];

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="icon" href="../images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #567575; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
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
    <h1 class="page-title">👥 Manage Users</h1>
    
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <div class="search-container">
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
            <input type="text" name="search" class="search-input" placeholder="Search by name, phone, email or serial number" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn">Search</button>
            <a href="users.php" class="btn btn-secondary" style="text-decoration: none;">Clear</a>
            <button type="button" class="btn btn-success" onclick="openAddUserModal()" style="margin-left: auto;">+ Add New User</button>
        </form>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <table>
            <thead>
                <tr>
                    <th>SLM Registration No</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo $user['serial_number']; ?></strong></td>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['phone']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="user-detail.php?id=<?php echo base64_encode($user['phone']); ?>" class="btn btn-small">View</a>
                        <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo $user['name']; ?>')" class="btn btn-small btn-danger">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">« First</a>
            <a href="?page=<?php echo $current_page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">‹ Prev</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $current_page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next ›</a>
            <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Last »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal for Add User -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddUserModal()">&times;</span>
        <h2>Add New User</h2>
        
        <form method="POST" onsubmit="return validateAddUserForm()">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required placeholder="Full name">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="phone" required placeholder="10-digit number" maxlength="10">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Email address (optional)">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Town/City *</label>
                    <input type="text" name="town" required placeholder="Town/City">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Set password">
                </div>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">Add User</button>
        </form>
    </div>
</div>

<!-- Modal for Delete Confirmation -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2>Confirm Delete</h2>
        <p>Are you sure you want to delete this user: <strong id="deleteName"></strong>?</p>
        <p style="color: #d32f2f; font-size: 12px;">⚠️ This action cannot be undone.</p>
        
        <form method="POST" id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="user_id" id="deleteUserId">
            <input type="hidden" name="delete_user" value="1">
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-danger" style="flex: 1;">Delete</button>
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../js/script.js"></script>
<script>
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'block';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}

function confirmDelete(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteName').textContent = userName;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function validateAddUserForm() {
    const phone = document.querySelector('[name="phone"]').value.trim();
    if (!/^[6-9]\d{9}$/.test(phone)) {
        alert('Please enter a valid 10-digit phone number');
        return false;
    }
    return true;
}

// Close modal when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById('addUserModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
    modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<footer>
    <p>&copy; 2025 Sadguru Bharadwaja Devotees Portal</p>
</footer>
</body>
</html>
<?php
// functions.php - Helper functions

require_once 'config.php';

// Maximum number of parayanams a user can have (default 11 + user-added)
if (!defined('MAX_STEPS')) {
    define('MAX_STEPS', 50);
}

// Database Connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Database Connection Error: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Generate CSRF Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF Token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Hash Password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Verify Password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Validate Phone Number (Indian)
function validatePhone($phone) {
    // Remove any non-numeric characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Check if it's exactly 10 digits
    if (strlen($phone) === 10 && preg_match('/^[6-9]\d{9}$/', $phone)) {
        return true;
    }
    return false;
}

// Format Phone Number
function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    return substr($phone, -10);
}

// Validate Email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Sanitize Input
function sanitize($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// SQL Escape
function escapeSQLString($str) {
    $db = getDB();
    return $db->real_escape_string($str);
}

// Get Next Serial Number
function getNextSerialNumber() {
    $db = getDB();
    $result = $db->query("SELECT MAX(CAST(serial_number AS UNSIGNED)) as max_serial FROM users");
    $row = $result->fetch_assoc();
    $nextNumber = ($row['max_serial'] ?? 400) + 1;
    return str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

// Register User
function registerUser($name, $phone, $email, $house_no, $locality, $town, $postal_code, $password) {
    $db = getDB();
    
    // Validate inputs
    if (empty($name) || empty($phone) || empty($password)) {
        return ['success' => false, 'message' => 'Name, phone number and password are required'];
    }

    if (!validatePhone($phone)) {
        return ['success' => false, 'message' => 'Invalid phone number. Please enter a valid 10-digit Indian phone number'];
    }

    // Email is optional — only validate if provided
    if (!empty($email) && !validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }

    // Check if phone already exists
    $phone = formatPhone($phone);
    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Phone number already registered'];
    }

    // Check if email already exists (only if provided)
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
    }

    // Store NULL in DB when no email provided (UNIQUE allows multiple NULLs)
    $email = !empty($email) ? $email : null;
    
    // Get serial number
    $serial_number = getNextSerialNumber();
    $password_hash = hashPassword($password);
    
    // Insert user
    $stmt = $db->prepare("INSERT INTO users (serial_number, name, phone, email, house_no, locality_street, town_city, postal_code, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param("sssssssss", $serial_number, $name, $phone, $email, $house_no, $locality, $town, $postal_code, $password_hash);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Initialize user steps (11 default parayanams; users can add more later)
        for ($i = 1; $i <= 11; $i++) {
            $db->query("INSERT INTO user_steps (user_id, step_number, completed) VALUES ($user_id, $i, FALSE)");
        }
        
        // Send confirmation email (only if email was provided)
        if (!empty($email)) {
            sendRegistrationEmail($email, $name);
        }
        
        // Send SMS notification
        sendRegistrationSMS($phone, $name);
        
        return ['success' => true, 'message' => 'Registration successful! Please log in.', 'serial_number' => $serial_number];
    } else {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Login User
function loginUser($phone, $password) {
    $db = getDB();
    
    $phone = formatPhone($phone);
    $stmt = $db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE phone = ? AND status = 'active'");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid phone number or password'];
    }
    
    $user = $result->fetch_assoc();
    
    if (!verifyPassword($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid phone number or password'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Log login
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $stmt = $db->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user['id'], $ip_address, $user_agent);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
}

// Send Registration Email
function sendRegistrationEmail($email, $name) {
    $subject = "Welcome to Sadguru Bharadwaja Portal";
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2>Welcome, {$name}!</h2>
        <p>Thank you for registering with Sadguru Bharadwaja Portal.</p>
        <p>Your account has been successfully created. You can now log in using your phone number and password.</p>
        <p><strong>Important:</strong> Please keep your login credentials secure and do not share them with anyone.</p>
        <hr>
        <p style='color: #666; font-size: 12px;'>
            For support, contact us at " . CONTACT_EMAIL . " or " . CONTACT_PHONE . "
        </p>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_EMAIL . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Send Registration SMS
function sendRegistrationSMS($phone, $name) {
    // Implement your SMS service here (Twilio, AWS SNS, etc.)
    // For now, this is a placeholder
    $message = "Welcome to Sadguru Bharadwaja Portal, {$name}! Your account has been successfully created.";
    
    // Example implementation with Twilio (uncomment and configure to use)
    // require_once 'vendor/autoload.php';
    // $twilio = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
    // $twilio->messages->create('+91' . $phone, ['from' => SMS_FROM_NUMBER, 'body' => $message]);
    
    return true; // Placeholder
}

// Get User By ID
function getUserById($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get User Steps
function getUserSteps($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM user_steps WHERE user_id = ? ORDER BY step_number");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Add a new parayanam step for a user (user-initiated, add-only)
function addUserStep($user_id) {
    $db = getDB();
    $user_id = intval($user_id);

    // Enforce upper limit
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM user_steps WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];

    if ($count >= MAX_STEPS) {
        return ['success' => false, 'message' => 'Maximum of ' . MAX_STEPS . ' parayanams reached'];
    }

    // Next number = current max + 1. UNIQUE (user_id, step_number) blocks duplicates on double-click.
    $stmt = $db->prepare(
        "INSERT INTO user_steps (user_id, step_number, started, completed)
         SELECT ?, COALESCE(MAX(step_number), 0) + 1, 0, 0 FROM user_steps WHERE user_id = ?"
    );
    $stmt->bind_param("ii", $user_id, $user_id);

    try {
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'New parayanam added successfully'];
        }
    } catch (mysqli_sql_exception $e) {
        error_log('addUserStep failed: ' . $e->getMessage());
    }
    return ['success' => false, 'message' => 'Could not add parayanam. Please try again.'];
}

// Update User Step
function updateUserStep($user_id, $step_number, $completed) {
    $db = getDB();
    $completed_at = $completed ? date('Y-m-d H:i:s') : NULL;
    
    $stmt = $db->prepare("UPDATE user_steps SET completed = ?, completed_at = ? WHERE user_id = ? AND step_number = ?");
    $stmt->bind_param("isii", $completed, $completed_at, $user_id, $step_number);
    return $stmt->execute();
}

// Update User Profile
function updateUserProfile($user_id, $name, $email, $phone, $house_no, $locality, $town, $postal_code) {
    $db = getDB();
    
    // Email is optional — only validate if provided
    if (!empty($email) && !validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }

    // Check if email is already used by another user (only if provided)
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already in use'];
        }
    }

    $email = !empty($email) ? $email : null;
    
    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, house_no = ?, locality_street = ?, town_city = ?, postal_code = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $name, $email, $phone, $house_no, $locality, $town, $postal_code, $user_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

// Get All Users (Admin)
function getAllUsers($search = '', $page = 1, $limit = 10) {
    $db = getDB();
    $offset = ($page - 1) * $limit;
    
    $where = "WHERE role = 'user'";
    if (!empty($search)) {
        $search = escapeSQLString($search);
        $where .= " AND (name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%' OR serial_number LIKE '%$search%')";
    }
    
    $result = $db->query("SELECT * FROM users $where ORDER BY serial_number ASC LIMIT $offset, $limit");
    $countResult = $db->query("SELECT COUNT(*) as total FROM users $where");
    $count = $countResult->fetch_assoc()['total'];
    
    return [
        'users' => $result->fetch_all(MYSQLI_ASSOC),
        'total' => $count,
        'pages' => ceil($count / $limit),
        'current_page' => $page
    ];
}

// Delete User (Admin)
function deleteUser($user_id, $admin_id) {
    $db = getDB();
    
    // Don't delete if it's admin user
    $user = getUserById($user_id);
    if ($user['role'] === 'admin') {
        return ['success' => false, 'message' => 'Cannot delete admin user'];
    }
    
    // Soft delete - set status to inactive
    $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Log admin action
        $db->query("INSERT INTO admin_logs (admin_id, action, affected_user_id, description) VALUES ($admin_id, 'DELETE_USER', $user_id, 'User deleted')");
        return ['success' => true, 'message' => 'User deleted successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to delete user'];
}

// Upload File (Admin)
function uploadFile($file, $description = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'message' => 'File type not allowed. Only PDF, Excel, Word documents are allowed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit of 50MB'];
    }
    
    // Create unique filename
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $filepath = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $db = getDB();
        $original_filename = basename($file['name']);
        $file_size = $file['size'];
        $admin_id = $_SESSION['user_id'];
        
        $stmt = $db->prepare("INSERT INTO downloads (filename, original_filename, file_type, file_path, file_size, uploaded_by, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $filename, $original_filename, $file_ext, $filepath, $file_size, $admin_id, $description);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'File uploaded successfully', 'file_id' => $stmt->insert_id];
        }
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Get All Downloads
function getAllDownloads() {
    $db = getDB();
    $result = $db->query("SELECT * FROM downloads WHERE status = 'active' ORDER BY uploaded_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Delete Download (Admin)
function deleteDownload($download_id, $admin_id) {
    $db = getDB();
    
    $db->query("UPDATE downloads SET status = 'deleted' WHERE id = $download_id");
    $db->query("INSERT INTO admin_logs (admin_id, action, description) VALUES ($admin_id, 'DELETE_DOWNLOAD', 'Download file deleted (ID: $download_id)')");
    
    return ['success' => true, 'message' => 'File deleted successfully'];
}

// Export User Data (Admin)
function exportUsersData($search = '') {
    $db = getDB();
    
    $where = "WHERE role = 'user' AND status = 'active'";
    if (!empty($search)) {
        $search = escapeSQLString($search);
        $where .= " AND (name LIKE '%$search%' OR phone LIKE '%$search%')";
    }
    
    $result = $db->query("SELECT serial_number, name, phone, email, house_no, locality_street, town_city, postal_code, created_at FROM users $where ORDER BY serial_number ASC");
    
    $data = [];
    $data[] = ['Serial No', 'Name', 'Phone', 'Email', 'House No', 'Locality/Street', 'Town/City', 'Postal Code', 'Registered Date'];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $row['serial_number'],
            $row['name'],
            $row['phone'],
            $row['email'],
            $row['house_no'],
            $row['locality_street'],
            $row['town_city'],
            $row['postal_code'],
            $row['created_at']
        ];
    }
    
    return $data;
}

// Create CSV from data
function arrayToCSV($data) {
    $output = fopen('php://memory', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}

?>

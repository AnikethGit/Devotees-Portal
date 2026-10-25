<?php
// register.php - User Registration

require_once 'config.php';
require_once 'functions.php';
require_once 'session.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $house_no = sanitize($_POST['house_no'] ?? '');
        $locality = sanitize($_POST['locality'] ?? '');
        $town = sanitize($_POST['town'] ?? '');
        $postal_code = sanitize($_POST['postal_code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            $result = registerUser($name, $phone, $email, $house_no, $locality, $town, $postal_code, $password);
            if ($result['success']) {
                $success = $result['message'];
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
    <title>Register - Sadguru Bharadwaja Devotees Portal</title>
    <link rel="icon" href="images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .register-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="phone"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        input:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 5px rgba(255, 152, 0, 0.3);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #ff9800;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #e68900;
        }
        
        .login-link {
            color: #c4c5c5;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .login-link a {
            color: #ff9800;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .required {
            color: #ff9800;
        }
        
        @media (max-width: 600px) {
            .register-container {
                margin: 20px;
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="register-container">
    <h1>🙏 Register as Devotee</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="register.php" onsubmit="return validateForm()">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label for="name">Full Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" required placeholder="Enter your full name">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="text" id="phone" name="phone" required placeholder="10-digit mobile number" maxlength="10">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="your@email.com (optional)">
            </div>
        </div>
        
        <div class="form-group">
            <label for="house_no">House No./Flat No.</label>
            <input type="text" id="house_no" name="house_no" placeholder="e.g., 123 or Flat 4">
        </div>
        
        <div class="form-group">
            <label for="locality">Locality / Street</label>
            <input type="text" id="locality" name="locality" placeholder="e.g., Main Road, Gandhi Nagar">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="town">Town / City <span class="required">*</span></label>
                <input type="text" id="town" name="town" required placeholder="Your town or city">
            </div>
            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" placeholder="e.g., 770001" maxlength="6">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required placeholder="At least 6 characters">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">
            </div>
        </div>
        
        <button type="submit">Register Now</button>
    </form>
    
    <div class="login-link">
        Already registered? <a href="login.php">Login here</a>
    </div>
</div>

<script src="js/script.js"></script>
<script>
function validateForm() {
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validate phone
    if (!/^[6-9]\d{9}$/.test(phone)) {
        alert('Please enter a valid 10-digit phone number starting with 6, 7, 8, or 9');
        return false;
    }
    
    // Validate email only if provided
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }

    // Validate password match
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }
    
    // Validate password length
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>
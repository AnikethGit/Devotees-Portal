<?php
// login.php - User Login

require_once 'config.php';
require_once 'functions.php';
require_once 'session.php';

$error = '';
$session_expired = isset($_GET['session_expired']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $result = loginUser($phone, $password);
        if ($result['success']) {
            if ($result['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
        } else {
            $error = $result['message'];
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
    <title>Login - Sadguru Bharadwaja Devotees Portal</title>
    <link rel="icon" href="images/LogoNGO.png" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #fff5e1 0%, #ffe0b2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
        }
        
        .login-container {
            max-width: 500px;
            width: 90%;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .logo {
            text-align: center;
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 5px rgba(255, 152, 0, 0.3);
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff9800 0%, #e68900 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: #ff9800;
            text-decoration: none;
            font-weight: bold;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border-color: #f99;
        }
        
        .alert-warning {
            background: #fef3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        .contact-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 30px;
            font-size: 12px;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
        }
        
        .contact-info p {
            margin: 5px 0;
        }
        
        @media (max-width: 600px) {
            .login-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .logo {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo">🙏</div>
    <h1>Sadguru Bharadwaja</h1>
    <div class="subtitle">Devotees Portal</div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($session_expired): ?>
        <div class="alert alert-warning">⏱️ Your session has expired. Please log in again.</div>
    <?php endif; ?>
    
    <form method="POST" action="login.php" onsubmit="return validateLoginForm()">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" required placeholder="Enter your 10-digit mobile number" maxlength="10">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
    
    <div class="contact-info">
        <p><strong>Need Help?</strong></p>
        <p>📞 <?php echo CONTACT_PHONE; ?></p>
        <p>📧 <?php echo CONTACT_EMAIL; ?></p>
    </div>
</div>

<script src="js/script.js"></script>
<script>
function validateLoginForm() {
    const phone = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;
    
    if (!/^\d{10}$/.test(phone)) {
        alert('Please enter a valid 10-digit phone number');
        return false;
    }
    
    if (password.length === 0) {
        alert('Please enter your password');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>
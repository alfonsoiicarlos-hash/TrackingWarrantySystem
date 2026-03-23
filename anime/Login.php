<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}


$error = '';
$username = '';
$is_cooldown = false;
$cooldown_time = 0;

if (isset($_SESSION['login_cooldown'])) {
    if (time() < $_SESSION['login_cooldown']) {
        $is_cooldown = true;
        $cooldown_time = $_SESSION['login_cooldown'] - time();
    } else {
        unset($_SESSION['login_cooldown']);
        unset($_SESSION['failed_attempts']);
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_cooldown) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
  
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
       
            unset($_SESSION['failed_attempts']);
            unset($_SESSION['login_cooldown']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
         
            $_SESSION['login_success'] = true;
            header("Location: dashboard.php");
            exit();
        } else {
       
            $_SESSION['failed_attempts'] = isset($_SESSION['failed_attempts']) ? $_SESSION['failed_attempts'] + 1 : 1;
          
            if ($_SESSION['failed_attempts'] >= 3) {
                $cooldown_end = time() + 180;
                $_SESSION['login_cooldown'] = $cooldown_end;
                $is_cooldown = true;
                $cooldown_time = 180;
                
                $error = "Too many failed attempts. Please wait 3 minutes.";
            } else {
                $error = "Invalid username or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --secondary: #059669;
            --accent: #34d399;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f8fafc;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: url('bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            filter: blur(3px);
            z-index: -1;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 15px;
            transition: all 0.3s ease;
            background: white;
            color: #1f2937;
            font-weight: 500;
        }
        
        .form-control::placeholder {
            color: #9ca3af;
        }
        
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.3rem rgba(52, 211, 153, 0.25);
            transform: scale(1.02);
            background: white;
            color: #1f2937;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            border-radius: 12px 0 0 12px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 15px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-login:hover:not(:disabled)::before {
            left: 100%;
        }
        
        .btn-login:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .cooldown-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 20px;
        }
        
        .countdown-timer {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--error), var(--warning));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }
        
        .countdown-label {
            text-align: center;
            color: #6b7280;
            margin-top: 15px;
            font-size: 1.1rem;
        }
        
        .password-toggle {
            cursor: pointer;
            background: #f8f9fa;
            border: 2px solid #e5e7eb;
            border-left: none;
            border-radius: 0 12px 12px 0;
            transition: all 0.3s ease;
            color: #6b7280;
        }
        
        .password-toggle:hover {
            background: #e9ecef;
        }
        
        .form-label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            max-width: 350px;
            display: flex;
            align-items: center;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        
        .notification.error {
            background: linear-gradient(135deg, var(--error), #dc2626);
        }
        
        .notification.warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }
        
        .notification i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            margin-left: 15px;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Notification Container -->
    <div id="notificationContainer"></div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-container">
                    <?php if ($is_cooldown): ?>
                    <div class="cooldown-overlay">
                        <div>
                            <div class="countdown-timer" id="countdownTimer">03:00</div>
                            <div class="countdown-label">
                                <i class="bi bi-shield-exclamation me-2"></i>
                                Too many failed attempts<br>Please wait before trying again
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="login-header">
                        <h2><i class="bi bi-pc-display-horizontal me-2"></i> Anime Computer Services</h2>
                        <p class="mb-0 mt-2">Welcome back!</p>
                    </div>
                    
                    <div class="login-form">
                        <form id="loginForm" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-4">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>" 
                                           <?php echo $is_cooldown ? 'disabled' : 'required'; ?>
                                           placeholder="Enter your username">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           <?php echo $is_cooldown ? 'disabled' : 'required'; ?>
                                           placeholder="Enter your password">
                                    <span class="input-group-text password-toggle" id="passwordToggle">
                                        <i class="bi bi-eye-fill" id="passwordIcon"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-login text-white py-3" id="loginButton" 
                                        <?php echo $is_cooldown ? 'disabled' : ''; ?>>
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    <span id="loginButtonText">
                                        <?php echo $is_cooldown ? 'Login Disabled' : 'Login to Dashboard'; ?>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable back and forward buttons
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function() {
            history.pushState(null, null, document.URL);
        });

        // Prevent navigation using keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Disable F5 refresh
            if (e.key === 'F5') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+R (Refresh)
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+Shift+R (Hard Refresh)
            if (e.ctrlKey && e.shiftKey && e.key === 'R') {
                e.preventDefault();
                return false;
            }
            
            // Disable Backspace key for navigation (except in form fields)
            if (e.key === 'Backspace' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                e.preventDefault();
                return false;
            }
        });

        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable text selection
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });

        // Notification system
        function showNotification(message, type = 'success') {
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationId = 'notification-' + Date.now();
            
            const notification = document.createElement('div');
            notification.id = notificationId;
            notification.className = `notification ${type}`;
            
            let icon = 'bi-check-circle-fill';
            if (type === 'error') icon = 'bi-exclamation-circle-fill';
            if (type === 'warning') icon = 'bi-exclamation-triangle-fill';
            
            notification.innerHTML = `
                <i class="bi ${icon}"></i>
                <span>${message}</span>
                <button class="notification-close" onclick="closeNotification('${notificationId}')">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            notificationContainer.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Auto close after 5 seconds
            setTimeout(() => {
                closeNotification(notificationId);
            }, 5000);
        }
        
        function closeNotification(id) {
            const notification = document.getElementById(id);
            if (notification) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }
        
        // Cooldown functionality
        const COOLDOWN_DURATION = 180;
        let cooldownEndTime = <?php echo $is_cooldown ? ($_SESSION['login_cooldown'] * 1000) : 'null'; ?>;
        
        // Save initial cooldown state to localStorage
        if (cooldownEndTime) {
            localStorage.setItem('loginCooldownEnd', cooldownEndTime);
        } else {
            const storedCooldown = localStorage.getItem('loginCooldownEnd');
            if (storedCooldown && parseInt(storedCooldown) > Date.now()) {
                cooldownEndTime = parseInt(storedCooldown);
                window.location.href = window.location.href;
            } else {
                localStorage.removeItem('loginCooldownEnd');
            }
        }

        function startCooldown() {
            cooldownEndTime = Date.now() + (COOLDOWN_DURATION * 1000);
            localStorage.setItem('loginCooldownEnd', cooldownEndTime);
            updateCooldownDisplay();
        }

        function updateCooldownDisplay() {
            if (!cooldownEndTime) return;

            const now = Date.now();
            const timeLeft = cooldownEndTime - now;

            if (timeLeft <= 0) {
                localStorage.removeItem('loginCooldownEnd');
                window.location.reload();
                return;
            }

            const minutes = Math.floor(timeLeft / 1000 / 60);
            const seconds = Math.floor((timeLeft / 1000) % 60);
            
            const countdownElement = document.getElementById('countdownTimer');
            if (countdownElement) {
                countdownElement.textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }

            document.title = `(${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}) Login - Anime PC Warranty`;
            setTimeout(updateCooldownDisplay, 1000);
        }

        // Start countdown if in cooldown
        if (cooldownEndTime) {
            updateCooldownDisplay();
        }

        // Password toggle functionality
        document.getElementById('passwordToggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('bi-eye-fill');
                passwordIcon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('bi-eye-slash-fill');
                passwordIcon.classList.add('bi-eye-fill');
            }
        });

        // Show notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($error && !$is_cooldown): ?>
            setTimeout(() => {
                showNotification('<?php echo $error; ?>', 'error');
            }, 500);
            <?php endif; ?>

            <?php if ($is_cooldown): ?>
            setTimeout(() => {
                showNotification('Too many failed login attempts. Please wait 3 minutes.', 'warning');
            }, 500);
            <?php endif; ?>

            // Check for successful login redirect parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login') === 'success') {
                setTimeout(() => {
                    showNotification('Login successful! Welcome back to Anime PC Warranty System', 'success');
                }, 500);
                
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 3000);
            }
        });

        // Enhanced form validation with animation
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            if (cooldownEndTime) {
                event.preventDefault();
                return;
            }

            let username = document.getElementById('username');
            let password = document.getElementById('password');
            let valid = true;

            // Remove previous validation states
            username.classList.remove('is-invalid', 'animate__animated', 'animate__shakeX');
            password.classList.remove('is-invalid', 'animate__animated', 'animate__shakeX');

            if (username.value.trim() === '') {
                username.classList.add('is-invalid', 'animate__animated', 'animate__shakeX');
                valid = false;
            }

            if (password.value.trim() === '') {
                password.classList.add('is-invalid', 'animate__animated', 'animate__shakeX');
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
                
                setTimeout(() => {
                    showNotification('Please fill in all required fields.', 'error');
                }, 100);
            } else {
                // Add loading state to button
                const loginBtn = document.getElementById('loginButton');
                const loginText = document.getElementById('loginButtonText');
                loginBtn.disabled = true;
                loginText.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Logging in...';
            }
        });

        // Input field animations
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('animate__animated', 'animate__pulse');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('animate__animated', 'animate__pulse');
            });
        });
    </script>
</body>
</html>
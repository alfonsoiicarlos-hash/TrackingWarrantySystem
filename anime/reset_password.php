<?php
session_start();
include 'config.php';

if (!isset($_SESSION['otp_verified'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    }
    else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $pdo->prepare("UPDATE users 
            SET password=?, otp_code=NULL, otp_expires=NULL, otp_attempts=0 
            WHERE id=?")
            ->execute([$hashedPassword, $_SESSION['reset_user']]);

        unset($_SESSION['otp_verified']);
        unset($_SESSION['reset_user']);

        $success = "Password successfully reset.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

<div class="card p-4 shadow" style="width:400px">
<h4>Reset Password</h4>

<?php if($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert alert-success">
<?php echo $success; ?>
<div class="mt-2">
<a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
</div>
</div>
<?php else: ?>

<form method="POST">
<div class="mb-3">
<label>New Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
<label>Confirm Password</label>
<input type="password" name="confirm_password" class="form-control" required>
</div>

<button class="btn btn-success w-100">Reset Password</button>
</form>

<?php endif; ?>

</div>
</body>
</html>

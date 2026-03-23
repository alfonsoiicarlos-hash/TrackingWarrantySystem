<?php
session_start();
include 'config.php';

if (!isset($_SESSION['reset_user'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = $_POST['otp'];

    $stmt = $pdo->prepare("SELECT otp_code, otp_expires, otp_attempts FROM users WHERE id=?");
    $stmt->execute([$_SESSION['reset_user']]);
    $user = $stmt->fetch();

    if ($user['otp_attempts'] >= 3) {
        $error = "Too many attempts. Request new OTP.";
    }
    elseif ($user['otp_code'] == $otp && strtotime($user['otp_expires']) > time()) {

        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    }
    else {
        $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE id=?")
            ->execute([$_SESSION['reset_user']]);

        $error = "Invalid or expired OTP.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Verify OTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

<div class="card p-4 shadow" style="width:400px">
<h4>Enter OTP</h4>

<?php if($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
<input type="text" name="otp" class="form-control mb-3" placeholder="6-digit code" required>
<button class="btn btn-success w-100">Verify</button>
</form>

</div>
</body>
</html>

<?php
session_start();
include 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $username = trim($_POST['username']);

    $stmt = $pdo->prepare("SELECT id, phone FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && !empty($user['phone'])) {

        $otp = random_int(100000, 999999);
        $expires = date("Y-m-d H:i:s", time() + 300); // 5 minutes

        $pdo->prepare("UPDATE users 
            SET otp_code=?, otp_expires=?, otp_attempts=0 
            WHERE id=?")
            ->execute([$otp, $expires, $user['id']]);

        // ===== SEMAPHORE SMS =====
        $apiKey = "YOUR_SEMAPHORE_API_KEY";
        $number = $user['phone'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://semaphore.co/api/v4/messages");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'apikey' => $apiKey,
            'number' => $number,
            'message' => "Your Anime PC reset code is: $otp",
            'sendername' => "AnimePC"
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        $_SESSION['reset_user'] = $user['id'];

        header("Location: verify_otp.php");
        exit();
    }

    $message = "If the account exists, an OTP was sent.";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

<div class="card p-4 shadow" style="width:400px">
<h4 class="mb-3">Forgot Password</h4>

<?php if($message): ?>
<div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="mb-3">
<label>Username</label>
<input type="text" name="username" class="form-control" required>
</div>

<button class="btn btn-success w-100">Send OTP</button>
</form>

<div class="text-center mt-3">
<a href="login.php">Back to Login</a>
</div>

</div>
</body>
</html>

<?php
include 'config.php';
if(!isset($_SESSION['reset_id'])) exit;
if($_SERVER['REQUEST_METHOD']=='POST'){
$hash=password_hash($_POST['password'],PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password=? WHERE id=?")
->execute([$hash,$_SESSION['reset_id']]);
unset($_SESSION['reset_id']);
header("Location:login.php");
}
?>
<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h3>Create New Password</h3>
<form method="POST">
<input type="password" name="password" class="form-control" required>
<button class="btn btn-success mt-3">Save</button>
</form>

</body>
</html>

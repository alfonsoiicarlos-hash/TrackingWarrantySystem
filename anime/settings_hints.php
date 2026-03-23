<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) exit;
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $stmt=$pdo->prepare("UPDATE users SET
        hint1_question=?, hint1_answer=?,
        hint2_question=?, hint2_answer=?,
        hint3_question=?, hint3_answer=?
        WHERE id=?");
    $stmt->execute([
        $_POST['q1'], password_hash($_POST['a1'],PASSWORD_DEFAULT),
        $_POST['q2'], password_hash($_POST['a2'],PASSWORD_DEFAULT),
        $_POST['q3'], password_hash($_POST['a3'],PASSWORD_DEFAULT),
        $_SESSION['user_id']
    ]);
    $saved=true;
}
?>
<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h3>Password Hint Settings</h3>
<?php if(isset($saved)): ?><div class="alert alert-success">Saved!</div><?php endif; ?>

<form method="POST">
<?php for($i=1;$i<=3;$i++): ?>
<label>Hint <?= $i ?> Question</label>
<input name="q<?= $i ?>" class="form-control" required>
<label>Hint <?= $i ?> Answer</label>
<input name="a<?= $i ?>" class="form-control" required>
<hr>
<?php endfor; ?>
<button class="btn btn-primary">Save</button>
</form>

</body>
</html>

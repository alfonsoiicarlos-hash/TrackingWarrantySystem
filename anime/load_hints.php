<?php
include 'config.php';
$stmt=$pdo->prepare("SELECT * FROM users WHERE username=?");
$stmt->execute([$_POST['username']]);
$u=$stmt->fetch();
if(!$u){ echo "User not found"; exit; }
?>

<form method="POST" action="verify_hints.php">
<input type="hidden" name="id" value="<?= $u['id'] ?>">

<label><?= $u['hint1_question'] ?></label>
<input name="a1" class="form-control" required>

<label><?= $u['hint2_question'] ?></label>
<input name="a2" class="form-control" required>

<label><?= $u['hint3_question'] ?></label>
<input name="a3" class="form-control" required>

<button class="btn btn-success mt-3">Verify</button>
</form>

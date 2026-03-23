<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question']);
    $answer = password_hash(trim($_POST['answer']), PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET hint_question=?, hint_answer=? WHERE id=?");
    $stmt->execute([$question, $answer, $_SESSION['user_id']]);

    $message = "Hint successfully saved!";
}
?>

<form method="POST">
    <h3>Set Password Hint</h3>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <label>Hint Question</label>
    <input type="text" name="question" class="form-control" required>

    <label class="mt-2">Hint Answer</label>
    <input type="text" name="answer" class="form-control" required>

    <button class="btn btn-primary mt-3">Save Hint</button>
</form>

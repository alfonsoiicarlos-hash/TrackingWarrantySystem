<?php
include 'config.php';
$stmt=$pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_POST['id']]);
$u=$stmt->fetch();

if(
password_verify($_POST['a1'],$u['hint1_answer']) &&
password_verify($_POST['a2'],$u['hint2_answer']) &&
password_verify($_POST['a3'],$u['hint3_answer'])
){
$_SESSION['reset_id']=$u['id'];
header("Location:new_password.php");
}else{
echo "Incorrect answers";
}

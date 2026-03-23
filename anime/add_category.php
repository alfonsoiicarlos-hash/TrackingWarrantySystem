<?php
include 'config.php';
checkAuth();

$name = trim($_POST['category_name']);

$stmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
$stmt->execute([$name]);

header("Location: products.php");
exit;

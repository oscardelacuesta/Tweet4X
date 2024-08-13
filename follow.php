<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$follower_id = $_SESSION['user_id'];
$followed_id = $_POST['followed_id'] ?? null;

if ($followed_id && $follower_id != $followed_id) {
    // Verificar si ya se sigue al usuario
    $checkFollowStmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
    $checkFollowStmt->execute([$follower_id, $followed_id]);

    if (!$checkFollowStmt->fetch()) {
        // Insertar la nueva relación de seguimiento
        $followStmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $followStmt->execute([$follower_id, $followed_id]);
    }
}

header('Location: profile.php?id=' . $followed_id);
exit;
?>

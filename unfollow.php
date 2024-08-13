<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $follower_id = $_SESSION['user_id'];
    $followed_id = filter_input(INPUT_POST, 'followed_id', FILTER_SANITIZE_NUMBER_INT);

    if ($followed_id) {
        // Eliminar la relación de seguimiento de la base de datos
        $stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = :follower_id AND followed_id = :followed_id');
        $stmt->execute([':follower_id' => $follower_id, ':followed_id' => $followed_id]);

        if ($stmt->rowCount() > 0) {
            echo 'Unfollowed successfully';
        } else {
            echo 'Error al dejar de seguir';
        }
    } else {
        echo 'Invalid user ID';
    }
} else {
    echo 'Invalid request';
}
?>

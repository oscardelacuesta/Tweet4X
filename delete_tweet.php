<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tweet_id = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    // Verificar que el tweet pertenece al usuario actual antes de eliminarlo
    $stmt = $pdo->prepare('SELECT * FROM tweets WHERE id = :tweet_id AND user_id = :user_id');
    $stmt->execute(['tweet_id' => $tweet_id, 'user_id' => $user_id]);
    $tweet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tweet) {
        try {
            // Iniciar una transacción
            $pdo->beginTransaction();

            // Eliminar las notificaciones relacionadas con el tweet
            $deleteNotificationsStmt = $pdo->prepare('DELETE FROM notifications WHERE tweet_id = :tweet_id');
            $deleteNotificationsStmt->execute(['tweet_id' => $tweet_id]);

            // Eliminar todos los retweets relacionados con el tweet
            $deleteRetweetsStmt = $pdo->prepare('DELETE FROM tweets WHERE retweet_id = :tweet_id');
            $deleteRetweetsStmt->execute(['tweet_id' => $tweet_id]);

            // Finalmente, eliminar el tweet original
            $deleteStmt = $pdo->prepare('DELETE FROM tweets WHERE id = :tweet_id');
            $deleteStmt->execute(['tweet_id' => $tweet_id]);

            // Confirmar la transacción
            $pdo->commit();

            // Redirigir a la página principal después de eliminar
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            // Revertir la transacción si ocurre un error
            $pdo->rollBack();
            die("Error al eliminar el tweet: " . $e->getMessage());
        }
    } else {
        // Si el tweet no pertenece al usuario actual, redirigir con un mensaje de error
        header('Location: index.php?error=No permission to delete this tweet');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}


<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener notificaciones del usuario
$notifications = $pdo->prepare("SELECT notifications.*, tweets.content, users.username FROM notifications JOIN tweets ON notifications.tweet_id = tweets.id JOIN users ON tweets.user_id = users.id WHERE notifications.user_id = ? AND notifications.is_read = FALSE ORDER BY notifications.created_at DESC");
$notifications->execute([$_SESSION['user_id']]);
$notifications = $notifications->fetchAll(PDO::FETCH_ASSOC);

// Marcar todas las notificaciones como leídas
$pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$_SESSION['user_id']]);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h2>Your Notifications</h2>

    <?php if ($notifications): ?>
        <ul>
            <?php foreach ($notifications as $notification): ?>
                <li>
                    <strong><?php echo htmlspecialchars($notification['username']); ?></strong>
                    <?php if ($notification['type'] == 'mention'): ?>
                        mentioned you in a tweet: "<?php echo htmlspecialchars($notification['content']); ?>"
                    <?php elseif ($notification['type'] == 'retweet'): ?>
                        retweeted your tweet: "<?php echo htmlspecialchars($notification['content']); ?>"
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No new notifications.</p>
    <?php endif; ?>
</body>
</html>

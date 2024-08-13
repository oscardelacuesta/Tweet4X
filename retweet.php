<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && isset($_POST['tweet_id'])) {
    $originalTweetId = $_POST['tweet_id'];

    // Verifica si el tweet existe
    $stmt = $pdo->prepare("SELECT * FROM tweets WHERE id = ?");
    $stmt->execute([$originalTweetId]);
    $originalTweet = $stmt->fetch();

    if ($originalTweet) {
        // Inserta el retweet4x en la base de datos
        $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content, retweet_id) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'Retweeted', $originalTweetId]);
        $retweetId = $pdo->lastInsertId();

        // Crear notificación de retweet4x
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, tweet_id) VALUES (?, 'retweet', ?)");
        $stmt->execute([$originalTweet['user_id'], $retweetId]);

        // Devuelve el HTML del retweet4x
        $username = $pdo->query("SELECT username FROM users WHERE id = ".$_SESSION['user_id'])->fetchColumn();
        $created_at = date('Y-m-d H:i:s');

        echo "<div class='tweet'>";
        echo "<p><strong>".htmlspecialchars($username)."</strong> ($created_at)</p>";
        echo "<p>Retweet4x from Tweet ID: ".$originalTweetId."</p>";
        echo "<p>".htmlspecialchars($originalTweet['content'])."</p>";
        if ($originalTweet['media_type'] == 'image') {
            echo "<img src='".htmlspecialchars($originalTweet['media_path'])."' alt='Tweet Media' style='max-width: 100%;'><br>";
        } elseif ($originalTweet['media_type'] == 'video') {
            echo "<video controls style='max-width: 100%;'><source src='".htmlspecialchars($originalTweet['media_path'])."' type='video/mp4'>Your browser does not support the video tag.</video><br>";
        }
        echo "<button class='retweet' data-tweet-id='$retweetId'>Retweet4x</button>";
        echo "<hr>";
        echo "</div>";
    } else {
        echo "Tweet not found.";
    }
}
?>

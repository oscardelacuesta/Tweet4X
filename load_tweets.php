<?php
include 'db.php';
session_start();

$tweets = $pdo->query("SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at FROM tweets JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($tweets as $tweet) {
    echo "<div class='tweet'>";
    echo "<p><strong>".htmlspecialchars($tweet['username'])."</strong> (".$tweet['created_at'].")</p>";
    if ($tweet['retweet_id']) {
        echo "<p>Retweet4x from Tweet ID: ".$tweet['retweet_id']."</p>";
        $originalTweet = $pdo->query("SELECT content, media_type, media_path FROM tweets WHERE id = ".$tweet['retweet_id'])->fetch();
        echo "<p>".htmlspecialchars($originalTweet['content'])."</p>";
        if ($originalTweet['media_type'] == 'image') {
            echo "<img src='".htmlspecialchars($originalTweet['media_path'])."' alt='Tweet Media' style='max-width: 100%;'><br>";
        } elseif ($originalTweet['media_type'] == 'video') {
            echo "<video controls style='max-width: 100%;'><source src='".htmlspecialchars($originalTweet['media_path'])."' type='video/mp4'>Your browser does not support the video tag.</video><br>";
        }
    } else {
        echo "<p>".htmlspecialchars($tweet['content'])."</p>";
        if ($tweet['media_type'] == 'image') {
            echo "<img src='".htmlspecialchars($tweet['media_path'])."' alt='Tweet Media' style='max-width: 100%;'><br>";
        } elseif ($tweet['media_type'] == 'video') {
            echo "<video controls style='max-width: 100%;'><source src='".htmlspecialchars($tweet['media_path'])."' type='video/mp4'>Your browser does not support the video tag.</video><br>";
        }
    }
    echo "<button class='retweet' data-tweet-id='".$tweet['id']."'>Retweet4x</button>";
    echo "<hr>";
    echo "</div>";
}
?>

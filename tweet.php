<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Error: No está autenticado.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Validar y limpiar la entrada del usuario
$content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$media_type = null;
$media_path = null;

if (!empty($content) || isset($_FILES['media'])) {
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['media']['tmp_name'];
        $fileName = $_FILES['media']['name'];
        $fileSize = $_FILES['media']['size'];
        $fileType = $_FILES['media']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'mp4');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/tweets/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $media_type = strpos($fileType, 'image') !== false ? 'image' : 'video';
                $media_path = $dest_path;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content, media_type, media_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $content, $media_type, $media_path]);

    // Devolver el nuevo tweet como respuesta AJAX
    $tweet_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                           FROM tweets 
                           JOIN users ON tweets.user_id = users.id 
                           WHERE tweets.id = ?");
    $stmt->execute([$tweet_id]);
    $tweet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tweet) {
        echo '<div class="tweet">';
        echo '<p><strong>' . htmlspecialchars($tweet['username']) . '</strong> (' . $tweet['created_at'] . ')</p>';
        if ($tweet['retweet_id']) {
            echo '<p>Retweet del Tweet ID: ' . $tweet['retweet_id'] . '</p>';
        }
        echo '<p>' . htmlspecialchars($tweet['content']) . '</p>';
        if ($tweet['media_type'] == 'image') {
            echo '<img src="' . htmlspecialchars($tweet['media_path']) . '" alt="Medios del Tweet" class="tweet-media">';
        } elseif ($tweet['media_type'] == 'video') {
            echo '<video controls class="tweet-media"><source src="' . htmlspecialchars($tweet['media_path']) . '" type="video/mp4">Tu navegador no soporta la etiqueta de video.</video>';
        }
        echo '<button class="retweet" data-tweet-id="' . $tweet['id'] . '">Retweet4X</button>';
        echo '<hr></div>';
    }
}
?>

<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario actual
$user_id = $_SESSION['user_id'];
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Establecer la imagen de perfil predeterminada si no hay ninguna
$profile_picture = $user['profile_picture'] ?? 'tweet4X.jpg';
$profile_picture_path = 'uploads/profile_pictures/' . htmlspecialchars($profile_picture, ENT_QUOTES, 'UTF-8');

// Si el usuario no tiene una imagen de perfil, se usa la imagen del logo de Tweet4X
if (!file_exists($profile_picture_path) || empty($user['profile_picture'])) {
    $profile_picture_path = 'imgs/tweet4X.jpg';
}

// Inicializar variables de búsqueda
$searchQuery = '';
$searchType = '';

// Realizar la búsqueda si se envía una consulta
if (isset($_GET['search'])) {
    $searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $searchType = $_GET['search_type'] ?? 'content';

    if ($searchType === 'user') {
        $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.user_id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                                     FROM tweets 
                                     JOIN users ON tweets.user_id = users.id 
                                     WHERE users.username LIKE ? 
                                     ORDER BY tweets.created_at DESC");
        $tweetsStmt->execute(["%$searchQuery%"]);
    } elseif ($searchType === 'hashtag') {
        $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.user_id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                                     FROM tweets 
                                     JOIN users ON tweets.user_id = users.id 
                                     WHERE tweets.content LIKE ? 
                                     ORDER BY tweets.created_at DESC");
        $tweetsStmt->execute(["%#$searchQuery%"]);
    } elseif ($searchType === 'date') {
        $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.user_id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                                     FROM tweets 
                                     JOIN users ON tweets.user_id = users.id 
                                     WHERE DATE(tweets.created_at) = ? 
                                     ORDER BY tweets.created_at DESC");
        $tweetsStmt->execute([$searchQuery]);
    } else {
        $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.user_id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                                     FROM tweets 
                                     JOIN users ON tweets.user_id = users.id 
                                     WHERE tweets.content LIKE ? 
                                     ORDER BY tweets.created_at DESC");
        $tweetsStmt->execute(["%$searchQuery%"]);
    }
} else {
    // Obtener los tweets
    $tweetsStmt = $pdo->query("SELECT tweets.id, tweets.user_id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                               FROM tweets 
                               JOIN users ON tweets.user_id = users.id 
                               ORDER BY tweets.created_at DESC");
}
$tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas del usuario
$followerCount = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
$followerCount->execute([$user_id]);
$followerCount = $followerCount->fetchColumn();

$followingCount = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$followingCount->execute([$user_id]);
$followingCount = $followingCount->fetchColumn();

$mentionCount = $pdo->prepare("SELECT COUNT(*) FROM mentions WHERE user_id = ?");
$mentionCount->execute([$user_id]);
$mentionCount = $mentionCount->fetchColumn();

$retweetCount = $pdo->prepare("SELECT COUNT(*) FROM tweets WHERE retweet_id IN (SELECT id FROM tweets WHERE user_id = ?)");
$retweetCount->execute([$user_id]);
$retweetCount = $retweetCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tweet4X</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0 0 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        nav ul li {
            margin: 0 10px;
        }

        nav ul li a {
            text-decoration: none;
            color: #7B68EE;
            font-weight: bold;
            padding-bottom: 10px;
            display: block;
            position: relative;
        }

        nav ul li a:hover {
            color: #9370DB;
        }

        header img.profile-picture {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin-right: 20px;
        }

        .stats {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .stat-item {
            text-align: center;
            margin: 0 15px;
            font-size: 16px;
            color: #333;
        }

        .stat-item i {
            font-size: 24px;
            color: #7B68EE;
            display: block;
            margin-bottom: 5px;
        }

        .tweet-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .tweet-actions button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .tweet-actions button[type="submit"]:hover {
            background-color: #45a049;
        }

        .tweet-actions input[type="file"] {
            cursor: pointer;
            width: 100%;
        }

        .search-bar {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            align-items: center;
        }

        .search-bar input[type="text"] {
            padding: 8px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        .search-bar select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        .search-bar button {
            padding: 8px 16px;
            border: none;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-bar button:hover {
            background-color: #0056b3;
        }

        .stat-item a {
            color: #7B68EE;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-item a:hover {
            color: #9370DB;
        }

        .stat-item a i {
            margin-right: 5px;
        }
    </style>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <header>
            <img src="<?php echo $profile_picture_path; ?>" alt="Foto de Perfil" class="profile-picture">
            <h2>¡Bienvenido a Tweet4X, <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
        </header>

        <!-- Barra de navegación -->
        <nav>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i>Inicio</a></li>
                <li><a href="timeline.php"><i class="fas fa-stream"></i>Mi Línea de Tiempo</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i>Mi Perfil</a></li>
                <li><a href="followers.php"><i class="fas fa-users"></i>Seguidores</a></li>
                <li><a href="following.php"><i class="fas fa-user-friends"></i>Seguidos</a></li>
                <li><a href="mentions.php"><i class="fas fa-at"></i>Menciones</a></li>
                <li><a href="retweets.php"><i class="fas fa-retweet"></i>Retweets</a></li>
                <li><a href="options.php"><i class="fas fa-cog"></i>Opciones</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</a></li>
            </ul>
        </nav>

        <!-- Estadísticas del usuario -->
        <div class="stats">
            <div class="stat-item">
                <i class="fas fa-users"></i>
                <a href="followers.php"><i class="fas fa-users"></i> Seguidores: <?php echo $followerCount; ?></a>
            </div>
            <div class="stat-item">
                <i class="fas fa-user-friends"></i>
                <a href="following.php"><i class="fas fa-user-friends"></i> Seguidos: <?php echo $followingCount; ?></a>
            </div>
            <div class="stat-item">
                <i class="fas fa-at"></i>
                <a href="mentions.php"><i class="fas fa-at"></i> Menciones: <?php echo $mentionCount; ?></a>
            </div>
            <div class="stat-item">
                <i class="fas fa-retweet"></i>
                <a href="retweets.php"><i class="fas fa-retweet"></i> Retweets: <?php echo $retweetCount; ?></a>
            </div>
        </div>

        <!-- Barra de búsqueda -->
        <div class="search-bar">
            <form method="get" action="index.php">
                <input type="text" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                <select name="search_type">
                    <option value="content" <?php if ($searchType === 'content') echo 'selected'; ?>>Contenido</option>
                    <option value="user" <?php if ($searchType === 'user') echo 'selected'; ?>>Usuario</option>
                    <option value="hashtag" <?php if ($searchType === 'hashtag') echo 'selected'; ?>>Hashtag</option>
                    <option value="date" <?php if ($searchType === 'date') echo 'selected'; ?>>Fecha</option>
                </select>
                <button type="submit">Buscar</button>
            </form>
        </div>

        <!-- Formulario para crear un tweet -->
        <form id="tweetForm" enctype="multipart/form-data">
            <textarea name="content" id="content" required placeholder="¿Qué está pasando?" maxlength="280"></textarea><br>
            <div class="tweet-actions">
                <button type="submit">Tweetear para Tweet4X</button>
                <input type="file" name="media" id="media" accept="image/*,video/*">
            </div>
        </form>

        <!-- Últimos tweets -->
        <h3>Últimos Tweets</h3>
        <div id="tweets">
            <?php foreach ($tweets as $tweet): ?>
                <div class="tweet">
                    <p>
                        <strong><a href="user_tweets.php?user_id=<?php echo $tweet['user_id']; ?>"><?php echo htmlspecialchars($tweet['username']); ?></a></strong> 
                        (<?php echo $tweet['created_at']; ?>)
                    </p>
                    <?php if ($tweet['retweet_id']): ?>
                        <p>Retweet del Tweet ID: <?php echo $tweet['retweet_id']; ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($tweet['content']); ?></p>
                    <?php if ($tweet['media_type'] == 'image'): ?>
                        <img src="<?php echo htmlspecialchars($tweet['media_path']); ?>" alt="Medios del Tweet" class="tweet-media">
                    <?php elseif ($tweet['media_type'] == 'video'): ?>
                        <video controls class="tweet-media">
                            <source src="<?php echo htmlspecialchars($tweet['media_path']); ?>" type="video/mp4">
                            Tu navegador no soporta la etiqueta de video.
                        </video>
                    <?php endif; ?>
                    <?php if ($tweet['user_id'] == $user_id): ?>
                        <form method="post" action="delete_tweet.php" style="display:inline;">
                            <input type="hidden" name="tweet_id" value="<?php echo $tweet['id']; ?>">
                            <button type="submit">Eliminar</button>
                        </form>
                    <?php endif; ?>
                    <button class="retweet" data-tweet-id="<?php echo $tweet['id']; ?>">Retweet4X</button>
                    <hr>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // AJAX para enviar el tweet con imagen o video
        $('#tweetForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: 'tweet.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log("Respuesta del servidor:", response); // Depuración
                    $('#tweets').prepend(response);
                    $('#content').val('');
                    $('#media').val('');
                },
                error: function(xhr, status, error) {
                    console.error("Error en la solicitud AJAX:", status, error);
                    console.log(xhr.responseText);
                }
            });
        });

        // Enviar el tweet al presionar Enter
        $('#content').on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                $('#tweetForm').submit();
            }
        });

        // Función para cargar tweets nuevos cada 10 segundos
        function loadNewTweets() {
            $.ajax({
                url: 'load_tweets.php',
                method: 'GET',
                success: function(response) {
                    $('#tweets').html(response);
                }
            });
        }

        // Cargar tweets cada 10 segundos
        setInterval(loadNewTweets, 10000);

        // AJAX para retweet4x
        $(document).on('click', '.retweet', function() {
            var tweetId = $(this).data('tweet-id');

            $.ajax({
                url: 'retweet.php',
                method: 'POST',
                data: { tweet_id: tweetId },
                success: function(response) {
                    $('#tweets').prepend(response);
                }
            });
        });
    </script>
</body>
</html>
<?php include 'footer.php'; ?>

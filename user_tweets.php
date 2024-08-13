<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    header('Location: index.php');
    exit;
}

// Obtener información del usuario
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

// Verificar si el usuario actual sigue al usuario de los tweets
$checkFollowStmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
$checkFollowStmt->execute([$current_user_id, $user_id]);
$isFollowing = $checkFollowStmt->fetch();

// Manejar la solicitud de seguir o dejar de seguir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['follow']) && !$isFollowing) {
        $followStmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $followStmt->execute([$current_user_id, $user_id]);
        $isFollowing = true; // Actualizar el estado
    } elseif (isset($_POST['unfollow']) && $isFollowing) {
        $unfollowStmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $unfollowStmt->execute([$current_user_id, $user_id]);
        $isFollowing = false; // Actualizar el estado
    }
}

// Buscar tweets si se envía una consulta de búsqueda
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                                 FROM tweets 
                                 JOIN users ON tweets.user_id = users.id 
                                 WHERE tweets.user_id = ? AND tweets.content LIKE ? 
                                 ORDER BY tweets.created_at DESC");
    $tweetsStmt->execute([$user_id, "%$searchQuery%"]);
} else {
    // Obtener los tweets del usuario especificado
    $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.username, tweets.created_at 
                                 FROM tweets 
                                 JOIN users ON tweets.user_id = users.id 
                                 WHERE tweets.user_id = ? 
                                 ORDER BY tweets.created_at DESC");
    $tweetsStmt->execute([$user_id]);
}

$tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tweet4X - Tweets de <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Estilos para los botones de seguir/dejar de seguir */
        .follow-btn, .unfollow-btn {
            margin-top: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .follow-btn {
            background-color: #4CAF50;
            color: white;
        }

        .follow-btn:hover {
            background-color: #45a049;
        }

        .unfollow-btn {
            background-color: #FF6347;
            color: white;
        }

        .unfollow-btn:hover {
            background-color: #FF4500;
        }

        /* Ajuste del menú para que ocupe una sola línea */
        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0 0 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            border-bottom: 2px solid #7B68EE;
        }

        nav ul li {
            margin: 0 10px;
        }

        nav ul li a {
            text-decoration: none;
            color: #7B68EE;
            font-weight: bold;
        }

        nav ul li a:hover {
            color: #9370DB;
        }

        /* Estilo para el buscador */
        .search-bar {
            margin-bottom: 20px;
            text-align: center;
        }

        .search-bar input[type="text"] {
            padding: 8px;
            width: 60%;
            max-width: 400px;
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
    </style>

    <style>
    header h2 {
        margin-bottom: 10px; /* Añadir un margen inferior para separar el nombre de usuario de la descripción */
    }

    .user-description {
        margin-top: 50; /* Evita cualquier margen superior si lo hubiera */
        font-size: 16px; /* Ajusta el tamaño de la fuente de la descripción */
        color: #666; /* Color de texto más claro para la descripción */
        white-space: pre-wrap; /* Mantiene los saltos de línea dentro de la descripción */
    }
</style>

</head>
<body>
    <div class="container">
        <!-- Menú de opciones -->
        <nav>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i>Inicio</a></li>
                <li><a href="timeline.php"><i class="fas fa-stream"></i>Mi Línea</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i>Mi Perfil</a></li>
                <li><a href="followers.php"><i class="fas fa-users"></i>Seguidores</a></li>
                <li><a href="following.php"><i class="fas fa-user-friends"></i>Seguidos</a></li>
                <li><a href="mentions.php"><i class="fas fa-at"></i>Menciones</a></li>
                <li><a href="retweets.php"><i class="fas fa-retweet"></i>Retweets</a></li>
                <li><a href="options.php"><i class="fas fa-cog"></i>Opciones</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</a></li>
            </ul>
        </nav>

        <header>
            <h2>Tweets de <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></h2> <br/><br/>
       
        </header>
             <p class="user-description"><?php echo nl2br(htmlspecialchars($user['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p> <!-- Mostrar la descripción del usuario -->
        <!-- Mostrar los botones de seguir/dejar de seguir -->
        <div class="follow-section">
            <?php if ($current_user_id !== $user_id): ?>
                <form method="post">
                    <?php if ($isFollowing): ?>
                        <button type="submit" name="unfollow" class="unfollow-btn">Dejar de seguir</button>
                    <?php else: ?>
                        <button type="submit" name="follow" class="follow-btn">Seguir</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>

        <!-- Buscador de tweets -->
        <div class="search-bar">
            <form method="get">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="search" placeholder="Buscar tweets..." value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <div id="tweets">
            <?php if (empty($tweets)): ?>
                <p>No se encontraron tweets.</p>
            <?php else: ?>
                <?php foreach ($tweets as $tweet): ?>
                    <div class="tweet">
                        <p><strong><?php echo htmlspecialchars($tweet['username']); ?></strong> (<?php echo $tweet['created_at']; ?>)</p>
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
                        <hr>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

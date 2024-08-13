<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario actual
$user_id = $_SESSION['user_id'];
$userStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Establecer la imagen de perfil predeterminada si no hay ninguna
$profile_picture = $user['profile_picture'] ?? 'tweet4X.jpg';
$profile_picture_path = 'uploads/profile_pictures/' . htmlspecialchars($profile_picture, ENT_QUOTES, 'UTF-8');

// Si el usuario no tiene una imagen de perfil, se usa la imagen del logo de Tweet4X
if (!file_exists($profile_picture_path) || empty($user['profile_picture'])) {
    $profile_picture_path = 'imgs/tweet4X.jpg';
}

// Buscar tweets si se envía una consulta de búsqueda
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.id AS user_id, users.username, tweets.created_at 
                                 FROM tweets 
                                 JOIN users ON tweets.user_id = users.id 
                                 WHERE tweets.content LIKE ? 
                                 ORDER BY tweets.created_at DESC");
    $tweetsStmt->execute(["%$searchQuery%"]);
} else {
    // Obtener los tweets de todos los usuarios, ordenados por fecha de creación
    $tweetsStmt = $pdo->prepare("SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, tweets.retweet_id, users.id AS user_id, users.username, tweets.created_at 
                                 FROM tweets 
                                 JOIN users ON tweets.user_id = users.id 
                                 ORDER BY tweets.created_at DESC");
    $tweetsStmt->execute();
}
$tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Línea de Tiempo</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Aumentar el ancho del contenedor */
        .container {
            width: 95%; /* Ajuste del ancho al 95% */
            max-width: 1200px; /* Ancho máximo aumentado */
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
            display: flex; /* Usar flexbox para alinear en una sola línea */
            flex-wrap: wrap; /* Permitir que las opciones se envuelvan si es necesario */
            justify-content: center; /* Centrar el menú */
        }

        nav ul li {
            margin: 0 10px; /* Espaciado entre opciones */
        }

        nav ul li a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        nav ul li a:hover {
            text-decoration: underline;
        }

        header img.profile-picture {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin-right: 20px;
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
</head>
<body>
    <div class="container">
        <header>
            <img src="<?php echo $profile_picture_path; ?>" alt="Foto de Perfil" class="profile-picture">
            <h2>Línea de Tiempo General</h2>
        </header>

        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li> <!-- Opción añadida para acceder al index -->
                <li><a href="timeline.php">Mi Línea de Tiempo</a></li>
                <li><a href="profile.php">Mi Perfil</a></li>
                <li><a href="followers.php">Seguidores</a></li>
                <li><a href="following.php">Seguidos</a></li>
                <li><a href="mentions.php">Menciones</a></li>
                <li><a href="retweets.php">Retweets</a></li>
                <li><a href="options.php">Opciones</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>

        <!-- Buscador de tweets -->
        <div class="search-bar">
            <form method="get">
                <input type="text" name="search" placeholder="Buscar tweets..." value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <h3>Últimos Tweets</h3>
        <div id="tweets">
            <?php foreach ($tweets as $tweet): ?>
                <div class="tweet">
                    <p>
                        <strong><a href="user_tweets.php?user_id=<?php echo $tweet['user_id']; ?>"><?php echo htmlspecialchars($tweet['username']); ?></a></strong>
                        (<?php echo $tweet['created_at']; ?>)
                    </p>
                    <?php if ($tweet['retweet_id']): ?>
                        <p>Retweet desde el Tweet ID: <?php echo $tweet['retweet_id']; ?></p>
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
        </div>
    </div>
</body>
</html>
<?php include 'footer.php'; ?>

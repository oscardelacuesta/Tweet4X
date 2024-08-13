<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Si se ha enviado una consulta de búsqueda
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

// Obtener los tweets en los que el usuario ha sido mencionado, filtrando por la búsqueda si se ha proporcionado
$stmt = $pdo->prepare("
    SELECT tweets.id, tweets.content, tweets.media_type, tweets.media_path, users.id AS user_id, users.username, tweets.created_at
    FROM mentions
    JOIN tweets ON mentions.tweet_id = tweets.id
    JOIN users ON tweets.user_id = users.id
    WHERE mentions.user_id = :user_id
    AND (tweets.content LIKE :searchQuery OR users.username LIKE :searchQuery)
    ORDER BY tweets.created_at DESC
");
$stmt->execute([
    'user_id' => $_SESSION['user_id'],
    'searchQuery' => '%' . $searchQuery . '%'
]);
$mentions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menciones</title>
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

        .search-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-bar input[type="text"] {
            width: 80%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-bar button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-bar button:hover {
            background-color: #0056b3;
        }

        .tweet strong {
            color: #007bff;
            text-decoration: none;
        }

        .tweet strong:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Menciones</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="timeline.php">Mi Línea de Tiempo</a></li>
                    <li><a href="followers.php">Seguidores</a></li>
                    <li><a href="following.php">Seguidos</a></li>
                    <li><a href="retweets.php">Retweets</a></li>
                    <li><a href="options.php">Opciones</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <!-- Barra de búsqueda -->
            <div class="search-bar">
                <form method="get" action="mentions.php">
                    <input type="text" name="search" placeholder="Buscar menciones..." value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit">Buscar</button>
                </form>
            </div>

            <div id="mentions">
                <?php if (empty($mentions)): ?>
                    <p>No se encontraron menciones.</p>
                <?php else: ?>
                    <?php foreach ($mentions as $mention): ?>
                        <div class="tweet">
                            <p><strong><a href="user_tweets.php?user_id=<?php echo $mention['user_id']; ?>"><?php echo htmlspecialchars($mention['username']); ?></a></strong> (<?php echo $mention['created_at']; ?>)</p>
                            <p><?php echo htmlspecialchars($mention['content']); ?></p>
                            <?php if ($mention['media_type'] == 'image'): ?>
                                <img src="<?php echo htmlspecialchars($mention['media_path']); ?>" alt="Medios del Tweet" style="max-width: 100%;">
                            <?php elseif ($mention['media_type'] == 'video'): ?>
                                <video controls style="max-width: 100%;">
                                    <source src="<?php echo htmlspecialchars($mention['media_path']); ?>" type="video/mp4">
                                    Tu navegador no soporta la etiqueta de video.
                                </video>
                            <?php endif; ?>
                            <hr>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

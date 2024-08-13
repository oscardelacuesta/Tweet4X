<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener la lista de personas a las que sigue el usuario con sus descripciones
$stmt = $pdo->prepare("
    SELECT users.id, users.username, users.descripcion
    FROM follows
    JOIN users ON follows.followed_id = users.id
    WHERE follows.follower_id = :user_id
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$following = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para filtrar la lista de personas seguidas
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $following = array_filter($following, function($user) use ($searchTerm) {
        return stripos($user['username'], $searchTerm) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguidos</title>
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

        .user-list {
            margin-top: 20px;
        }

        .user-list ul {
            list-style-type: none;
            padding: 0;
        }

        .user-list ul li {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .user-list ul li a {
            font-weight: bold;
            color: #007bff;
        }

        .user-list ul li p {
            margin: 5px 0 0;
            color: #555;
        }

        .search-form {
            margin-bottom: 20px;
            text-align: center;
        }

        .search-form input[type="text"] {
            padding: 8px;
            width: 250px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .search-form button {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Seguidos</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="timeline.php">Mi Línea de Tiempo</a></li>
                    <li><a href="followers.php">Seguidores</a></li>
                    <li><a href="mentions.php">Menciones</a></li>
                    <li><a href="retweets.php">Retweets</a></li>
                    <li><a href="options.php">Opciones</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <h2>Personas que Sigues</h2>

            <!-- Formulario de búsqueda -->
            <div class="search-form">
                <form method="get" action="following.php">
                    <input type="text" name="search" placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit">Buscar</button>
                </form>
            </div>

            <div class="user-list">
                <ul>
                    <?php if (empty($following)): ?>
                        <li>No sigues a nadie.</li>
                    <?php else: ?>
                        <?php foreach ($following as $followed): ?>
                            <li>
                                <a href="profile.php?id=<?php echo $followed['id']; ?>"><?php echo htmlspecialchars($followed['username']); ?></a>
                                <p><?php echo htmlspecialchars($followed['descripcion']); ?></p>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </main>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

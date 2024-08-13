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

// Obtener la lista de seguidores con descripción
$search = $_GET['search'] ?? '';

$query = "
    SELECT users.id, users.username, users.descripcion
    FROM follows
    JOIN users ON follows.follower_id = users.id
    WHERE follows.followed_id = :user_id
";

$params = ['user_id' => $_SESSION['user_id']];

if (!empty($search)) {
    $query .= " AND (users.username LIKE :search OR users.descripcion LIKE :search)";
    $params['search'] = "%$search%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguidores</title>
    <link rel="stylesheet" href="css/styles.css">
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
            color: #007bff;
            font-weight: bold;
        }

        nav ul li a:hover {
            text-decoration: underline;
        }

        .follower-item {
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        .follower-item h3 {
            margin: 0;
            font-size: 18px;
        }

        .follower-item p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }

        .search-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-bar input[type="text"] {
            width: 50%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Seguidores</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="timeline.php">Mi Línea de Tiempo</a></li>
                    <li><a href="following.php">Seguidos</a></li>
                    <li><a href="mentions.php">Menciones</a></li>
                    <li><a href="retweets.php">Retweets</a></li>
                    <li><a href="options.php">Opciones</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </header>

        <div class="search-bar">
            <form method="get" action="followers.php">
                <input type="text" name="search" placeholder="Buscar por nombre o descripción" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <main>
            <h2>Tus Seguidores</h2>
            <ul>
                <?php if (empty($followers)): ?>
                    <li>No tienes seguidores.</li>
                <?php else: ?>
                    <?php foreach ($followers as $follower): ?>
                        <li class="follower-item">
                            <h3><a href="profile.php?id=<?php echo $follower['id']; ?>"><?php echo htmlspecialchars($follower['username']); ?></a></h3>
                            <p><?php echo htmlspecialchars($follower['descripcion']); ?></p>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </main>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$profile_id = $_GET['id'] ?? $user_id;

// Verificar si el usuario ya sigue a esta persona
$checkFollowStmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
$checkFollowStmt->execute([$user_id, $profile_id]);
$isFollowing = $checkFollowStmt->fetch();

// Obtener datos del usuario actual o del perfil que se está visitando
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$profile_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

// Determinar la imagen de perfil
$profile_picture = $user['profile_picture'] ?? 'default.png';
$profile_picture_path = 'uploads/profile_pictures/' . htmlspecialchars($profile_picture, ENT_QUOTES, 'UTF-8');

// Verificar si la imagen de perfil existe, si no, usar la imagen por defecto
if (!file_exists($profile_picture_path) || empty($user['profile_picture'])) {
    $profile_picture_path = 'imgs/tweet4X.jpg'; // Ruta a la imagen por defecto
}

// Obtener la lista de seguidores y seguidos
$followersStmt = $pdo->prepare("
    SELECT users.id, users.username, users.descripcion 
    FROM follows 
    JOIN users ON follows.follower_id = users.id 
    WHERE follows.followed_id = ?
");
$followersStmt->execute([$profile_id]);
$followers = $followersStmt->fetchAll(PDO::FETCH_ASSOC);

$followingStmt = $pdo->prepare("
    SELECT users.id, users.username, users.descripcion 
    FROM follows 
    JOIN users ON follows.followed_id = users.id 
    WHERE follows.follower_id = ?
");
$followingStmt->execute([$profile_id]);
$following = $followingStmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar la solicitud de seguimiento o dejar de seguir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['follow']) && !$isFollowing) {
        $followStmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $followStmt->execute([$user_id, $profile_id]);
        header("Location: profile.php?id=$profile_id");
        exit;
    } elseif (isset($_POST['unfollow']) && $isFollowing) {
        $unfollowStmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $unfollowStmt->execute([$user_id, $profile_id]);
        header("Location: profile.php?id=$profile_id");
        exit;
    }
}

// Obtener la imagen de perfil del usuario actual si está en su propio perfil
$current_userStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$current_userStmt->execute([$user_id]);
$current_user = $current_userStmt->fetch(PDO::FETCH_ASSOC);

// Si el usuario actual no tiene imagen de perfil, usar la imagen por defecto
$current_profile_picture = $current_user['profile_picture'] ?? 'default.png';
$current_profile_picture_path = 'uploads/profile_pictures/' . htmlspecialchars($current_profile_picture, ENT_QUOTES, 'UTF-8');

if (!file_exists($current_profile_picture_path) || empty($current_user['profile_picture'])) {
    $current_profile_picture_path = 'imgs/tweet4X.jpg';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
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

        .profile-picture {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin-right: 20px;
        }

        .profile-details {
            text-align: center;
            margin: 20px 0;
        }

        .user-list {
            margin-top: 40px;
        }

        .user-list h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .user-list ul {
            list-style-type: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .user-list ul li {
            margin: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            width: 200px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .user-list ul li img {
            border-radius: 50%;
            width: 50px;
            height: 50px;
        }

        .user-list ul li button {
            margin-top: 10px;
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .user-list ul li button:hover {
            background-color: #45a049;
        }

        .unfollow-btn {
            background-color: #FF6347;
        }

        .unfollow-btn:hover {
            background-color: #FF4500;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="<?php echo $current_profile_picture_path; ?>" alt="Foto de Perfil" class="profile-picture">
            <h2><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($user['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
        </header>

        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="timeline.php">Mi Línea</a></li>
                <li><a href="profile.php">Mi Perfil</a></li>
                <li><a href="followers.php">Seguidores</a></li>
                <li><a href="following.php">Seguidos</a></li>
                <li><a href="mentions.php">Menciones</a></li>
                <li><a href="retweets.php">Retweets</a></li>
                <li><a href="options.php">Opciones</a></li>
                <li><a href="logout.php">Cerrar</a></li>
            </ul>
        </nav>

        <div class="profile-details">
            <p><strong>Nombre de usuario:</strong> <?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Fecha de nacimiento:</strong> <?php echo htmlspecialchars($user['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

            <?php if ($profile_id != $user_id): ?>
                <?php if ($isFollowing): ?>
                    <form action="profile.php?id=<?php echo $profile_id; ?>" method="post">
                        <button type="submit" name="unfollow" class="unfollow-btn">Dejar de Seguir</button>
                    </form>
                <?php else: ?>
                    <form action="profile.php?id=<?php echo $profile_id; ?>" method="post">
                        <button type="submit" name="follow">Seguir</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Listado de usuarios seguidos -->
        <div class="user-list">
            <h3>Personas que sigues</h3>
            <ul>
                <?php foreach ($following as $followedUser): ?>
                    <li>
                        <img src="uploads/profile_pictures/<?php echo htmlspecialchars($followedUser['profile_picture'] ?? 'default.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de Perfil">
                        <p><strong><?php echo htmlspecialchars($followedUser['username'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <p><?php echo nl2br(htmlspecialchars($followedUser['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <form action="profile.php?id=<?php echo $followedUser['id']; ?>" method="get">
                            <button type="submit">Ver Perfil</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Listado de seguidores -->
        <div class="user-list">
            <h3>Tus Seguidores</h3>
            <ul>
                <?php foreach ($followers as $follower): ?>
                    <li>
                        <img src="uploads/profile_pictures/<?php echo htmlspecialchars($follower['profile_picture'] ?? 'default.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de Perfil">
                        <p><strong><?php echo htmlspecialchars($follower['username'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <p><?php echo nl2br(htmlspecialchars($follower['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <form action="profile.php?id=<?php echo $follower['id']; ?>" method="get">
                            <button type="submit">Ver Perfil</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

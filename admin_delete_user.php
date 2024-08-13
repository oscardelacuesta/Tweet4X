<?php
include 'db.php';
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== '1') { // Asegúrate de que el ID del admin sea 1
    header('Location: login.php');
    exit;
}

$search = '';
$user = null;
$error = null;
$success = null;

// Manejo del formulario de búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Buscar al usuario por nombre de usuario o correo electrónico
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :search OR email = :search");
    $stmt->execute([':search' => $search]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'Usuario no encontrado.';
    }
}

// Manejo de la eliminación del usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);

    // Eliminar el usuario y todos los datos asociados
    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Eliminar menciones del usuario
        $stmt = $pdo->prepare("DELETE FROM mentions WHERE user_id = :user_id OR tweet_id IN (SELECT id FROM (SELECT id FROM tweets WHERE user_id = :user_id) AS temp)");
        $stmt->execute([':user_id' => $user_id_to_delete]);

        // Eliminar retweets del usuario
        $stmt = $pdo->prepare("DELETE FROM tweets WHERE retweet_id IN (SELECT id FROM (SELECT id FROM tweets WHERE user_id = :user_id) AS temp)");
        $stmt->execute([':user_id' => $user_id_to_delete]);

        // Eliminar notificaciones del usuario
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = :user_id OR tweet_id IN (SELECT id FROM (SELECT id FROM tweets WHERE user_id = :user_id) AS temp)");
        $stmt->execute([':user_id' => $user_id_to_delete]);

        // Eliminar follows del usuario
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = :user_id OR followed_id = :user_id");
        $stmt->execute([':user_id' => $user_id_to_delete]);

        // Eliminar tweets del usuario
        $stmt = $pdo->prepare("DELETE FROM tweets WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id_to_delete]);

        // Eliminar la foto de perfil del usuario si existe
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id_to_delete]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data && $user_data['profile_picture'] && file_exists('uploads/profile_pictures/' . $user_data['profile_picture'])) {
            unlink('uploads/profile_pictures/' . $user_data['profile_picture']);
        }

        // Eliminar el usuario
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id_to_delete]);

        // Confirmar la transacción
        $pdo->commit();

        $success = 'Usuario eliminado exitosamente.';
        $user = null; // Restablecer los datos del usuario
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error al eliminar el usuario: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Eliminar Usuario</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h2>Eliminar Usuario</h2>
        </header>

        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
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

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php elseif ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="post" action="admin_delete_user.php">
            <label for="search">Buscar usuario por nombre de usuario o correo electrónico:</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" required>
            <button type="submit">Buscar</button>
        </form>

        <?php if ($user): ?>
            <h3>Datos del Usuario</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Nombre de Usuario:</strong> <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Fecha de Nacimiento:</strong> <?php echo htmlspecialchars($user['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?></p>

            <form method="post" action="admin_delete_user.php">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" name="delete_user" onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.');">Eliminar Usuario</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

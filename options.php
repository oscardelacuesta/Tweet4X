<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Inicializar variables para mostrar en el formulario
$username = '';
$email = '';
$date_of_birth = '';
$profile_picture = 'default.png';
$descripcion = ''; // Nueva variable para la descripción

// Obtener datos del usuario
$userStmt = $pdo->prepare("SELECT username, email, date_of_birth, profile_picture, descripcion FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $username = $user['username'];
    $email = $user['email'] ?? '';
    $date_of_birth = $user['date_of_birth'] ?? '';
    $descripcion = $user['descripcion'] ?? ''; // Obtener la descripción
    $profile_picture = $user['profile_picture'] ?? 'default.png';

    // Verificar si la imagen de perfil existe, si no, usar la imagen por defecto
    $profile_picture_path = 'uploads/profile_pictures/' . htmlspecialchars($profile_picture, ENT_QUOTES, 'UTF-8');
    if (!file_exists($profile_picture_path) || empty($user['profile_picture'])) {
        $profile_picture_path = 'imgs/tweet4X.jpg'; // Ruta a la imagen por defecto
    }
} else {
    $profile_picture_path = 'imgs/tweet4X.jpg'; // Ruta a la imagen por defecto
}

// Manejo del formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Filtrar la descripción
    $current_password = $_POST['current_password'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_new_password = $_POST['confirm_new_password'] ?? null;

    // Verificar si el correo electrónico ya está en uso por otro usuario
    $emailCheckStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheckStmt->execute([$email, $user_id]);
    if ($emailCheckStmt->fetch()) {
        $error = 'El correo electrónico ya está en uso por otro usuario.';
    } else {
        // Crear carpeta específica para el usuario si no existe
        $userUploadDir = 'uploads/profile_pictures/' . $user_id;
        if (!file_exists($userUploadDir)) {
            mkdir($userUploadDir, 0777, true);
        }

        // Verificar y manejar la imagen de perfil
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $profile_picture = basename($_FILES['profile_picture']['name']);
            $uploadFile = $userUploadDir . '/' . $profile_picture;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
                $profile_picture = $user_id . '/' . $profile_picture; // Guardar la ruta relativa
            } else {
                $error = 'Error al subir la imagen.';
            }
        }

        // Actualizar la contraseña si se ha solicitado
        if ($current_password && $new_password && $confirm_new_password) {
            // Verificar la contraseña actual
            $passwordStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $passwordStmt->execute([$user_id]);
            $stored_password = $passwordStmt->fetchColumn();

            if (password_verify($current_password, $stored_password)) {
                if ($new_password === $confirm_new_password) {
                    $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $updatePasswordStmt = $pdo->prepare("UPDATE users SET password = :new_password WHERE id = :id");
                    $updatePasswordStmt->execute([
                        ':new_password' => $hashed_new_password,
                        ':id' => $user_id
                    ]);
                    $success = 'Contraseña actualizada correctamente.';
                } else {
                    $error = 'La nueva contraseña y la confirmación no coinciden.';
                }
            } else {
                $error = 'La contraseña actual no es correcta.';
            }
        }

        // Actualizar los datos del usuario en la base de datos si no hay errores
        if (!isset($error)) {
            $updateStmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, date_of_birth = :date_of_birth, profile_picture = :profile_picture, descripcion = :descripcion WHERE id = :id");
            $updateStmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':date_of_birth' => $date_of_birth,
                ':profile_picture' => $profile_picture,
                ':descripcion' => $descripcion, // Actualizar la descripción
                ':id' => $user_id
            ]);

            $success = 'Datos actualizados correctamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opciones</title>
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

        .profile-picture-preview {
            display: block;
            margin-top: 10px;
            width: 150px; /* Ancho de la imagen de vista previa */
            height: auto;
            border-radius: 50%;
        }

        .admin-options {
            margin-top: 20px;
            text-align: center;
        }

        .admin-options a {
            color: red;
            text-decoration: none;
            font-weight: bold;
        }

        .admin-options a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2>Actualiza tus Opciones</h2>
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

        <form method="post" enctype="multipart/form-data">
            <label for="username">Nombre de usuario:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="email">Correo electrónico:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="date_of_birth">Fecha de nacimiento:</label>
            <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo htmlspecialchars($date_of_birth, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" id="descripcion"><?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?></textarea>

            <label for="profile_picture">Foto de perfil:</label>
            <input type="file" name="profile_picture" id="profile_picture">

            <label for="current_password">Contraseña Actual:</label>
            <input type="password" name="current_password" id="current_password">

            <label for="new_password">Nueva Contraseña:</label>
            <input type="password" name="new_password" id="new_password">

            <label for="confirm_new_password">Confirmar Nueva Contraseña:</label>
            <input type="password" name="confirm_new_password" id="confirm_new_password">

            <button type="submit">Guardar cambios</button>
        </form>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <img src="<?php echo htmlspecialchars($profile_picture_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de Perfil" class="profile-picture-preview">

        <!-- Opciones de administrador -->
        <?php if ($user_id === '1'): ?>  <!-- Reemplaza '1' con el ID de tu usuario administrador -->
            <div class="admin-options">
                <h3>Opciones de Administrador</h3>
                <a href="admin_delete_user.php">Eliminar Usuario</a> <!-- Enlace a la página de eliminación de usuarios -->
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

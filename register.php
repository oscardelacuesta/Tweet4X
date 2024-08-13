<?php
include 'db.php';

$ip_address = $_SERVER['REMOTE_ADDR'];

// Verificar si ha habido más de 5 registros desde esta IP en la última hora
$stmt = $pdo->prepare("SELECT COUNT(*) FROM registration_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 1 HOUR)");
$stmt->execute([$ip_address]);
$attempt_count = $stmt->fetchColumn();

if ($attempt_count >= 5) {
    $error = 'Has alcanzado el límite de registros por hora. Por favor, intenta más tarde.';
} else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verificación del honeypot
        if (!empty($_POST['honeypot'])) {
            $error = 'Registro detectado como spam.';
        } else {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Validación básica
            if ($password !== $confirm_password) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                // Verificar si el usuario ya existe
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username OR email = :email');
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $error = 'El nombre de usuario o el correo electrónico ya están en uso.';
                } else {
                    // Insertar el nuevo usuario en la base de datos
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $insertStmt = $pdo->prepare('INSERT INTO users (username, email, password, date_of_birth, profile_picture, descripcion) VALUES (:username, :email, :password, :date_of_birth, :profile_picture, :descripcion)');
                    $insertStmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password' => $hashed_password,
                        ':date_of_birth' => $date_of_birth,
                        ':profile_picture' => 'default.png', // Asignar una imagen de perfil predeterminada
                        ':descripcion' => $descripcion
                    ]);

                    // Registrar el intento de registro
                    $pdo->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)")->execute([$ip_address]);

                    header('Location: login.php');
                    exit;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Tweet4X</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Ocultar el campo honeypot con CSS */
        .honeypot {
            display: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Registro</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="post" action="register.php">
            <label for="username">Nombre de usuario:</label>
            <input type="text" name="username" id="username" required>

            <label for="email">Correo electrónico:</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirmar contraseña:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <label for="date_of_birth">Fecha de nacimiento:</label>
            <input type="date" name="date_of_birth" id="date_of_birth" required>

            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" id="descripcion"></textarea>

            <!-- Campo Honeypot -->
            <input type="text" name="honeypot" class="honeypot" placeholder="No llenar este campo">

            <button type="submit">Registrarse</button>
        </form>

        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>

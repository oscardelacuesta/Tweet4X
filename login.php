<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'];

    if (preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o password no válidos.';
        }
    } else {
        $error = 'Formato del usuario no válido. Sólo letras, numeros y guiones bajos son permitidos.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>:: Tweet4X :: La red social libre, gratuita y alternativa de la Generación X ::</title>
    <!-- Forzar la actualización del CSS -->
    <link rel="stylesheet" href="css/styles.css?v=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center; /* Centrar texto e imagen */
        }
        .login-container h1 {
            margin-bottom: 20px;
        }
        .login-container label {
            display: block;
            margin-bottom: 5px;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .login-container button {
            background-color: #6c63ff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .login-container button:hover {
            background-color: #5a54f0;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        /* Estilo para la imagen */
        .login-container img {
            width: 50%; /* Ancho del 50% del contenedor */
            margin: 20px 0; /* Espacio arriba y abajo */
            max-width: 100%; /* Asegura que no se desborde del contenedor */
            height: auto; /* Mantiene la proporción de la imagen */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>:: Tweet4X ::</h1> 
        <h3>La red social libre, gratuita y alternativa de la Generación X ::</h3> 
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Login</button>
        </form>

        <p>¿Todavía no tienes cuenta ?<a href="register.php"> Registrate aquí</a></p>

        <!-- Imagen añadida aquí -->
        <img src="imgs/tweet4X.jpg" alt="Tweet4X Logo">
    </div>
</body>
</html>

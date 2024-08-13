<?php
$host = 'db5016201241.hosting-data.io'; // Nombre del servicio en docker-compose
$dbname = 'dbs13186729';
$user = 'dbu1812785';
$pass = 'Etweet1234;;';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}
?>

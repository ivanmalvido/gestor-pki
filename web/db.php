<?php
// db.php - Conexión a la base de datos SQLite

define('RUTA_BD', '/var/lib/pki-data/pki.sqlite');

function conectar_bd() {
    try {
        $pdo = new PDO('sqlite:' . RUTA_BD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die('Error conectando a la base de datos: ' . $e->getMessage());
    }
}
?>
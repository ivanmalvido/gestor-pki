<?php
// logout.php - Cierra la sesión

session_start();

if (isset($_SESSION['usuario_id'])) {
    require_once 'db.php';
    try {
        $pdo = conectar_bd();
        $stmt = $pdo->prepare("INSERT INTO log_auditoria (usuario_id, accion, ip) VALUES (:uid, 'logout', :ip)");
        $stmt->execute([
            ':uid' => $_SESSION['usuario_id'],
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        // Si el log falla, seguimos con el logout igual
    }
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
?>
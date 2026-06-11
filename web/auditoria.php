<?php
// auditoria.php - Registro de acciones en log_auditoria

function registrar_auditoria(PDO $pdo, string $accion, string $detalles = '') {
    $stmt = $pdo->prepare(
        "INSERT INTO log_auditoria (usuario_id, accion, detalles, ip) VALUES (:uid, :acc, :det, :ip)"
    );
    $stmt->execute([
        ':uid' => $_SESSION['usuario_id'] ?? null,
        ':acc' => $accion,
        ':det' => $detalles,
        ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
}

<?php
// init_db.php - LANZAR UNA VEZ DESDE EL NAVEGADOR Y BORRAR DESPUÉS

require_once 'db.php';

$pdo = conectar_bd();

// Tabla de usuarios (admin y operadores)
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    rol TEXT NOT NULL DEFAULT 'operador',
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

// Certificados emitidos
$pdo->exec("CREATE TABLE IF NOT EXISTS certificados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    serial TEXT NOT NULL UNIQUE,
    nombre_comun TEXT NOT NULL,
    tipo TEXT NOT NULL,
    estado TEXT NOT NULL DEFAULT 'vigente',
    emitido_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en TEXT,
    revocado_en TEXT,
    motivo_revocacion TEXT
)");

// CSRs pendientes de firmar
$pdo->exec("CREATE TABLE IF NOT EXISTS solicitudes_pendientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ruta_csr TEXT NOT NULL,
    nombre_solicitante TEXT NOT NULL,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

// Log de auditoría
$pdo->exec("CREATE TABLE IF NOT EXISTS log_auditoria (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    accion TEXT NOT NULL,
    detalles TEXT,
    ip TEXT,
    ts TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

echo "Tablas creadas correctamente.<br><br>";

// Crear admin inicial solo si no hay usuarios
$total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

if ($total == 0) {
    $nombre_admin = 'admin';
    $pass_admin = 'admin1234';
    $hash = password_hash($pass_admin, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, password_hash, rol) VALUES (:n, :h, 'admin')");
    $stmt->execute([':n' => $nombre_admin, ':h' => $hash]);
    
    echo "Usuario administrador creado:<br>";
    echo "Nombre: <b>$nombre_admin</b><br>";
    echo "Contraseña: <b>$pass_admin</b><br><br>";
    echo "<b>Cambia la contraseña después del primer login.</b><br>";
} else {
    echo "Ya existen usuarios en la base de datos.<br>";
}

?>
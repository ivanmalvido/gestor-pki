<?php
// login.php - Página de login

require_once 'db.php';
session_start();

// Si ya está logueado, al panel
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if ($nombre === '' || $pass === '') {
        $error = 'Rellena ambos campos.';
    } else {
        try {
            $pdo = conectar_bd();
            $stmt = $pdo->prepare("SELECT id, password_hash, rol FROM usuarios WHERE nombre = :nombre");
            $stmt->execute([':nombre' => $nombre]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($pass, $usuario['password_hash'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $nombre;
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['ultima_actividad'] = time();
                
                // Registrar en el log de auditoría
                $log = $pdo->prepare("INSERT INTO log_auditoria (usuario_id, accion, ip) VALUES (:uid, 'login', :ip)");
                $log->execute([
                    ':uid' => $usuario['id'],
                    ':ip' => $_SERVER['REMOTE_ADDR']
                ]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
        }
    }
}

$expirada = isset($_GET['expirada']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestor PKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container" style="max-width: 400px; margin-top: 100px;">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title mb-4 text-center">Gestor PKI</h2>
                
                <?php if ($expirada): ?>
                    <div class="alert alert-warning">La sesión ha expirado por inactividad.</div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Usuario</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
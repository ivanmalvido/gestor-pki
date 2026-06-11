<?php
// solicitar.php - Solicitar un nuevo certificado (dos modos)
require_once 'auth.php';
require_once 'db.php';
require_once 'auditoria.php';

comprobar_sesion();
$pdo = conectar_bd();

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modo = $_POST['modo'] ?? '';
    $tipo = $_POST['tipo'] ?? 'server';
    if (!in_array($tipo, ['server', 'client'])) $tipo = 'server';

    if ($modo === 'servidor') {
        $cn = trim($_POST['cn'] ?? '');
        $ou = trim($_POST['ou'] ?? 'IF3-03');

        if ($cn === '' || !preg_match('/^[a-zA-Z0-9.\-]+$/', $cn)) {
            $error = 'CN inválido (sólo letras, números, punto, guión)';
        } else {
            $base = '/srv/pki-exchange/csr/' . uniqid('req_', false);
            $key_path = $base . '.key';
            $csr_path = $base . '.csr';
            $subject = sprintf('/C=ES/ST=Galicia/O=ASIR/OU=%s/CN=%s', $ou, $cn);

            $cmd = sprintf(
                'openssl req -new -newkey rsa:2048 -nodes -keyout %s -out %s -subj %s 2>&1',
                escapeshellarg($key_path),
                escapeshellarg($csr_path),
                escapeshellarg($subject)
            );
            exec($cmd, $out, $rc);

            if ($rc !== 0) {
                $error = 'Error generando CSR: ' . htmlspecialchars(implode("\n", $out));
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO solicitudes_pendientes (ruta_csr, nombre_solicitante, tipo) VALUES (?, ?, ?)"
                );
                $stmt->execute([$csr_path, $_SESSION['nombre'], $tipo]);
                registrar_auditoria($pdo, 'solicitud_servidor', "CN=$cn tipo=$tipo");

                $key_basename = basename($key_path);
                $mensaje = "Solicitud creada. Tu clave privada debes descargarla AHORA: "
                         . "<a href='descargar.php?tipo=key&f=" . urlencode($key_basename)
                         . "' class='btn btn-warning btn-sm'>Descargar clave privada</a>";
            }
        }
    } elseif ($modo === 'subir') {
        if (!isset($_FILES['csr']) || $_FILES['csr']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error subiendo el archivo CSR.';
        } else {
            $contenido = file_get_contents($_FILES['csr']['tmp_name']);
            if (strpos($contenido, '-----BEGIN CERTIFICATE REQUEST-----') === false) {
                $error = 'El archivo no parece un CSR PEM válido.';
            } else {
                $csr_path = '/srv/pki-exchange/csr/' . uniqid('upl_', false) . '.csr';
                file_put_contents($csr_path, $contenido);
                chmod($csr_path, 0644);

                $stmt = $pdo->prepare(
                    "INSERT INTO solicitudes_pendientes (ruta_csr, nombre_solicitante, tipo) VALUES (?, ?, ?)"
                );
                $stmt->execute([$csr_path, $_SESSION['nombre'], $tipo]);
                registrar_auditoria($pdo, 'solicitud_subido', "csr=$csr_path tipo=$tipo");
                $mensaje = 'CSR subido correctamente. Pendiente de firma por el administrador.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar certificado - Gestor PKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Gestor PKI</a>
        <div class="text-light">
            <?= htmlspecialchars($_SESSION['nombre']) ?>
            <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Salir</a>
        </div>
    </div>
</nav>
<div class="container">
    <h1>Solicitar certificado</h1>
    <a href="index.php" class="btn btn-secondary btn-sm mb-3">← Volver</a>

    <?php if ($mensaje): ?><div class="alert alert-success"><?= $mensaje ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Modo A: el servidor genera la clave</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="modo" value="servidor">
                        <div class="mb-3">
                            <label class="form-label">Common Name (CN)</label>
                            <input type="text" name="cn" class="form-control" required pattern="[a-zA-Z0-9.\-]+">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OU</label>
                            <input type="text" name="ou" class="form-control" value="IF3-03">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="server">Servidor (TLS)</option>
                                <option value="client">Cliente</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Generar solicitud</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Modo B: ya tengo mi CSR</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="modo" value="subir">
                        <div class="mb-3">
                            <label class="form-label">Archivo CSR (PEM)</label>
                            <input type="file" name="csr" class="form-control" accept=".csr,.pem,.txt" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="server">Servidor (TLS)</option>
                                <option value="client">Cliente</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Enviar solicitud</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
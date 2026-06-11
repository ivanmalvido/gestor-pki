<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'auditoria.php';
require_once 'pki_ssh.php';

comprobar_sesion();
if (!es_admin()) { http_response_code(403); die('Acceso denegado: solo administradores'); }

$pdo = conectar_bd();
$mensaje = '';
$error   = '';

$motivos = [
    'unspecified'          => 'No especificado',
    'keyCompromise'        => 'Clave comprometida',
    'superseded'           => 'Sustituido por otro',
    'cessationOfOperation' => 'Cese de operación',
    'affiliationChanged'   => 'Cambio de afiliación',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial = trim($_POST['serial'] ?? '');
    $motivo = $_POST['motivo'] ?? 'unspecified';

    if (!array_key_exists($motivo, $motivos)) {
        $error = 'Motivo no válido';
    } elseif ($serial === '' || !preg_match('/^[0-9A-Fa-f]+$/', $serial)) {
        $error = 'Serial no válido';
    } else {
        $serial_up = strtoupper($serial);
        $res = ejecutar_pki("revoke $serial_up $motivo");

        if (!$res['ok']) {
            $error = 'La revocación falló: ' . htmlspecialchars($res['salida']);
        } else {
            $upd = $pdo->prepare("UPDATE certificados SET estado='revocado', revocado_en=CURRENT_TIMESTAMP, motivo_revocacion=? WHERE serial=?");
            $upd->execute([$motivo, $serial_up]);
            registrar_auditoria($pdo, 'revocacion', "serial=$serial_up motivo=$motivo");
            $mensaje = 'Certificado revocado correctamente. Serial: ' . $serial_up;
        }
    }
}

$vigentes = $pdo->query("SELECT * FROM certificados WHERE estado='vigente' ORDER BY emitido_en DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Revocar - Gestor PKI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4"><div class="container">
<a class="navbar-brand" href="index.php">Gestor PKI</a>
<div class="text-light"><?= htmlspecialchars($_SESSION['nombre']) ?>
<a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Salir</a></div>
</div></nav>
<div class="container">
<h1>Revocar certificado</h1>
<a href="index.php" class="btn btn-secondary btn-sm mb-3">Volver</a>
<?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<?php if (empty($vigentes)): ?>
<p class="text-muted">No hay certificados vigentes para revocar.</p>
<?php else: ?>
<form method="POST" class="row g-3 mb-4">
<div class="col-md-6">
<label class="form-label">Certificado vigente</label>
<select name="serial" class="form-select" required>
<option value="">-- Selecciona --</option>
<?php foreach ($vigentes as $c): ?>
<option value="<?= htmlspecialchars($c['serial']) ?>">
<?= htmlspecialchars($c['nombre_comun']) ?> (<?= $c['tipo'] ?>) - <?= htmlspecialchars(substr($c['serial'],0,16)) ?>...
</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<label class="form-label">Motivo</label>
<select name="motivo" class="form-select">
<?php foreach ($motivos as $valor => $etiqueta): ?>
<option value="<?= $valor ?>"><?= htmlspecialchars($etiqueta) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-2 d-flex align-items-end">
<button class="btn btn-danger w-100" onclick="return confirm('¿Confirmar revocación?')">Revocar</button>
</div>
</form>
<?php endif; ?>
</div>
</body>
</html>
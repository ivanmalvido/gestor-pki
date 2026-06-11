<?php
// firmar.php - Admin firma una solicitud pendiente
require_once 'auth.php';
require_once 'db.php';
require_once 'auditoria.php';
require_once 'pki_ssh.php';

comprobar_sesion();
if (!es_admin()) { http_response_code(403); die('Acceso denegado: solo administradores'); }

$pdo = conectar_bd();
$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $tipo   = $_POST['tipo'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');

    if (!in_array($tipo, ['server', 'client'])) {
        $error = 'Tipo invalido';
    } elseif ($nombre === '' || !preg_match('/^[a-zA-Z0-9.\-_]+$/', $nombre)) {
        $error = 'Nombre invalido';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM solicitudes_pendientes WHERE id = ?");
        $stmt->execute([$id]);
        $sol = $stmt->fetch();

        if (!$sol) {
            $error = 'Solicitud no encontrada';
        } else {
            $res = ejecutar_pki("sign {$sol['ruta_csr']} $tipo $nombre");
            if (!$res['ok']) {
                $error = 'Firma fallo: ' . htmlspecialchars($res['salida']);
            } elseif (!preg_match('|OK (/srv/pki-exchange/certs/\S+\.crt)|', $res['salida'], $m)) {
                $error = 'Respuesta inesperada: ' . htmlspecialchars($res['salida']);
            } else {
                $cert_path = $m[1];
                exec('openssl x509 -in ' . escapeshellarg($cert_path) . ' -noout -serial -enddate -subject', $info_out);
                $info = implode("\n", $info_out);

                $serial = '';
                $expira = null;
                $cn = '';
                if (preg_match('/serial=([0-9A-Fa-f]+)/', $info, $m2)) $serial = strtoupper($m2[1]);
                if (preg_match('/notAfter=(.+)/', $info, $m3)) $expira = date('Y-m-d H:i:s', strtotime(trim($m3[1])));
                if (preg_match('/CN\s*=\s*([^,\/\n]+)/', $info, $m4)) $cn = trim($m4[1]);

                $ins = $pdo->prepare("INSERT INTO certificados (serial, nombre_comun, tipo, estado, expira_en, archivo) VALUES (?, ?, ?, 'vigente', ?, ?)");
                $ins->execute([$serial, $cn, $tipo, $expira, basename($cert_path)]);

                $del = $pdo->prepare("DELETE FROM solicitudes_pendientes WHERE id = ?");
                $del->execute([$id]);

                registrar_auditoria($pdo, 'firma', "CN=$cn serial=$serial tipo=$tipo");
                $mensaje = "Certificado firmado correctamente. Serial: " . $serial;
            }
        }
    }
}

$pendientes = $pdo->query("SELECT * FROM solicitudes_pendientes ORDER BY creado_en DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Firmar - Gestor PKI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4"><div class="container">
<a class="navbar-brand" href="index.php">Gestor PKI</a>
<div class="text-light"><?= htmlspecialchars($_SESSION['nombre']) ?>
<a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Salir</a></div>
</div></nav>
<div class="container">
<h1>Firmar solicitudes</h1>
<a href="index.php" class="btn btn-secondary btn-sm mb-3">Volver</a>
<?php if ($mensaje): ?><div class="alert alert-success"><?= $mensaje ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<?php if (empty($pendientes)): ?>
<p class="text-muted">No hay solicitudes pendientes.</p>
<?php else: ?>
<table class="table table-striped">
<thead><tr><th>ID</th><th>CSR</th><th>Solicitante</th><th>Tipo</th><th>Fecha</th><th>Firmar</th></tr></thead>
<tbody>
<?php foreach ($pendientes as $p): ?>
<tr>
<td><?= $p['id'] ?></td>
<td><code><?= htmlspecialchars(basename($p['ruta_csr'])) ?></code></td>
<td><?= htmlspecialchars($p['nombre_solicitante']) ?></td>
<td><?= htmlspecialchars($p['tipo']) ?></td>
<td><?= htmlspecialchars($p['creado_en']) ?></td>
<td>
<form method="POST" class="d-flex gap-2">
<input type="hidden" name="id" value="<?= $p['id'] ?>">
<select name="tipo" class="form-select form-select-sm" style="width:auto">
<option value="server" <?= $p['tipo']==='server'?'selected':'' ?>>server</option>
<option value="client" <?= $p['tipo']==='client'?'selected':'' ?>>client</option>
</select>
<input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre" required style="width:160px">
<button class="btn btn-success btn-sm">Firmar</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</body>
</html>
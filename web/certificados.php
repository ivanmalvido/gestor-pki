<?php
// certificados.php - Listado de certificados emitidos
require_once 'auth.php';
require_once 'db.php';
comprobar_sesion();

$pdo = conectar_bd();
$filtro = $_GET['filtro'] ?? 'todos';

$where = '';
if ($filtro === 'vigentes')  $where = "WHERE estado='vigente'";
if ($filtro === 'revocados') $where = "WHERE estado='revocado'";

$certs = $pdo->query("SELECT * FROM certificados $where ORDER BY emitido_en DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Certificados - Gestor PKI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4"><div class="container">
<a class="navbar-brand" href="index.php">Gestor PKI</a>
<div class="text-light"><?= htmlspecialchars($_SESSION['nombre']) ?>
<a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Salir</a></div>
</div></nav>
<div class="container">
<h1>Certificados emitidos</h1>
<a href="index.php" class="btn btn-secondary btn-sm mb-3">Volver</a>

<div class="mb-3">
<a href="?filtro=todos" class="btn btn-sm <?= $filtro==='todos'?'btn-primary':'btn-outline-primary' ?>">Todos</a>
<a href="?filtro=vigentes" class="btn btn-sm <?= $filtro==='vigentes'?'btn-success':'btn-outline-success' ?>">Vigentes</a>
<a href="?filtro=revocados" class="btn btn-sm <?= $filtro==='revocados'?'btn-danger':'btn-outline-danger' ?>">Revocados</a>
</div>

<table class="table table-sm table-striped">
<thead><tr><th>Serial</th><th>CN</th><th>Tipo</th><th>Estado</th><th>Emitido</th><th>Expira</th><th>Accion</th></tr></thead>
<tbody>
<?php foreach ($certs as $c): ?>
<tr>
<td><code><?= htmlspecialchars(substr($c['serial'],0,16)) ?>...</code></td>
<td><?= htmlspecialchars($c['nombre_comun']) ?></td>
<td><?= htmlspecialchars($c['tipo']) ?></td>
<td>
<?php if ($c['estado'] === 'vigente'): ?>
<span class="badge bg-success">Vigente</span>
<?php else: ?>
<span class="badge bg-danger" title="<?= htmlspecialchars($c['motivo_revocacion'] ?? '') ?>">Revocado</span>
<?php endif; ?>
</td>
<td><?= htmlspecialchars($c['emitido_en']) ?></td>
<td><?= htmlspecialchars($c['expira_en'] ?? '') ?></td>
<td>
<?php if ($c['archivo']): ?>
<a href="descargar.php?tipo=cert&f=<?= urlencode($c['archivo']) ?>" class="btn btn-sm btn-primary">CRT</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<hr class="my-4">
<h5>Descargas publicas</h5>
<ul>
<li><a href="descargar.php?tipo=root">Certificado raiz (Root CA)</a></li>
<li><a href="descargar.php?tipo=intermedia">Certificado intermedia (TLS CA)</a></li>
<li><a href="descargar.php?tipo=cadena">Cadena completa (root + intermedia)</a></li>
<li><a href="descargar.php?tipo=crl">CRL actual</a></li>
</ul>
</div>
</body>
</html>
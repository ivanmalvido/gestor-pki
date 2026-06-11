<?php
// index.php - Panel principal
require_once 'auth.php';
require_once 'db.php';

comprobar_sesion();

$pdo = conectar_bd();
$total_certs = $pdo->query("SELECT COUNT(*) FROM certificados")->fetchColumn();
$pendientes  = $pdo->query("SELECT COUNT(*) FROM solicitudes_pendientes")->fetchColumn();
$revocados   = $pdo->query("SELECT COUNT(*) FROM certificados WHERE estado = 'revocado'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel - Gestor PKI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4"><div class="container">
<span class="navbar-brand">Gestor PKI</span>
<div class="text-light">
Conectado: <b><?= htmlspecialchars($_SESSION['nombre']) ?></b>
(<?= htmlspecialchars($_SESSION['rol']) ?>)
<a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Salir</a>
</div>
</div></nav>

<div class="container">
<h1>Panel</h1>
<div class="row mt-4">
<div class="col-md-4">
<div class="card text-center"><div class="card-body">
<h5>Certificados emitidos</h5>
<p class="display-4"><?= $total_certs ?></p>
</div></div>
</div>
<div class="col-md-4">
<div class="card text-center"><div class="card-body">
<h5>Solicitudes pendientes</h5>
<p class="display-4"><?= $pendientes ?></p>
</div></div>
</div>
<div class="col-md-4">
<div class="card text-center"><div class="card-body">
<h5>Revocados</h5>
<p class="display-4"><?= $revocados ?></p>
</div></div>
</div>
</div>

<h3 class="mt-5">Acciones</h3>
<div class="d-flex gap-2 flex-wrap mb-4">
<a href="solicitar.php" class="btn btn-primary">Solicitar certificado</a>
<a href="certificados.php" class="btn btn-info">Ver certificados</a>
<?php if (es_admin()): ?>
<a href="firmar.php" class="btn btn-success">Firmar pendientes</a>
<a href="revocar.php" class="btn btn-danger">Revocar certificado</a>
<?php endif; ?>
</div>

<h3 class="mt-4">Descargas publicas</h3>
<ul>
<li><a href="descargar.php?tipo=root">Certificado raiz</a></li>
<li><a href="descargar.php?tipo=intermedia">Certificado intermedia</a></li>
<li><a href="descargar.php?tipo=cadena">Cadena completa</a></li>
<li><a href="descargar.php?tipo=crl">CRL actual</a></li>
</ul>
</div>
</body>
</html>
<?php
// descargar.php - Sirve certificados, claves y artefactos publicos de la CA
$tipo = $_GET['tipo'] ?? '';

// Tipos publicos (no requieren sesion): root, intermedia, cadena, crl
$publicos = ['root', 'intermedia', 'cadena', 'crl'];
if (!in_array($tipo, $publicos)) {
    require_once 'auth.php';
    comprobar_sesion();
}

$borrar = false;

switch ($tipo) {
    case 'root':
        $path = '/srv/pki-exchange/public/root-ca.crt';
        $name = 'root-ca.crt';
        $mime = 'application/x-pem-file';
        break;
    case 'intermedia':
        $path = '/srv/pki-exchange/public/tls-ca.crt';
        $name = 'tls-ca.crt';
        $mime = 'application/x-pem-file';
        break;
    case 'cadena':
        $path = '/srv/pki-exchange/public/tls-ca-chain.pem';
        $name = 'tls-ca-chain.pem';
        $mime = 'application/x-pem-file';
        break;
    case 'crl':
        $path = '/srv/pki-exchange/crl/tls-ca.crl';
        $name = 'tls-ca.crl';
        $mime = 'application/pkix-crl';
        break;
    case 'cert':
        $f = $_GET['f'] ?? '';
        if (!preg_match('/^[a-zA-Z0-9._-]+\.crt$/', $f)) { http_response_code(400); die('Nombre invalido'); }
        $path = '/srv/pki-exchange/certs/' . $f;
        $name = $f;
        $mime = 'application/x-pem-file';
        break;
    case 'key':
        $f = $_GET['f'] ?? '';
        if (!preg_match('/^[a-zA-Z0-9._-]+\.key$/', $f)) { http_response_code(400); die('Nombre invalido'); }
        $path = '/srv/pki-exchange/csr/' . $f;
        $name = $f;
        $mime = 'application/x-pem-file';
        $borrar = true;
        break;
    default:
        http_response_code(400);
        die('Tipo invalido');
}

if (!file_exists($path)) {
    http_response_code(404);
    die('Archivo no encontrado: ' . htmlspecialchars($name));
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store');
readfile($path);

if ($borrar) {
    @unlink($path);
}
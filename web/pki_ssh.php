<?php
// pki_ssh.php - Helper para invocar el script remoto en ct-ca via SSH

define('PKI_SSH_KEY',  '/var/lib/pkiweb-ssh/id_ed25519');
define('PKI_SSH_KH',   '/var/lib/pkiweb-ssh/known_hosts');
define('PKI_SSH_USER', 'pkiops');
define('PKI_SSH_HOST', '10.0.3.20');

function ejecutar_pki($comando_remoto) {
    $cmd = sprintf(
        'ssh -i %s -o UserKnownHostsFile=%s -o BatchMode=yes -o ConnectTimeout=5 %s@%s %s 2>&1',
        escapeshellarg(PKI_SSH_KEY),
        escapeshellarg(PKI_SSH_KH),
        PKI_SSH_USER,
        PKI_SSH_HOST,
        escapeshellarg($comando_remoto)
    );
    exec($cmd, $salida, $rc);
    return [
        'ok'     => ($rc === 0),
        'codigo' => $rc,
        'salida' => implode("\n", $salida),
    ];
}
<?php
// auth.php - Funciones de autenticación

define('TIEMPO_INACTIVIDAD', 1800); // 30 minutos

function comprobar_sesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ¿Está logueado?
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // ¿Caducó la sesión por inactividad?
    if (isset($_SESSION['ultima_actividad']) && 
        (time() - $_SESSION['ultima_actividad'] > TIEMPO_INACTIVIDAD)) {
        session_unset();
        session_destroy();
        header('Location: login.php?expirada=1');
        exit;
    }
    
    $_SESSION['ultima_actividad'] = time();
}

function es_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
?>
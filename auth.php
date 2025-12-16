<?php
// Archivo para proteger páginas que requieren login

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        redirect('login.php');
    }
}

function verificarAdmin() {
    verificarLogin();
    if ($_SESSION['usuario_rol'] !== 'admin') {
        showAlert('Acceso denegado. Solo administradores pueden acceder a esta función.', 'error');
        redirect('index.php');
    }
}

function getNombreUsuario() {
    return isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario';
}

function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}
?>
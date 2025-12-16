<?php
session_start();

// Configuraciones generales
define('SITE_NAME', 'Agenda Nails - Gestión de Salón');
define('SITE_URL', 'http://localhost/agendanails');
define('TIMEZONE', 'America/Santiago');

// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

// Funciones auxiliares
function formatCurrency($amount) {
    return '$' . number_format($amount, 0, ',', '.');
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}
?>
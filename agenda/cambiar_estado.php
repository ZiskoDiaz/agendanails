<?php
require_once '../config/config.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$cita_id = (int)$_POST['cita_id'];
$nuevo_estado = $_POST['nuevo_estado'];

$estados_validos = ['pendiente', 'confirmada', 'en_proceso', 'completada', 'cancelada'];

if (!$cita_id || !in_array($nuevo_estado, $estados_validos)) {
    showAlert('Datos inválidos', 'error');
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Obtener datos de la cita
    $stmt = $db->prepare("SELECT * FROM citas WHERE id = ?");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita) {
        showAlert('Cita no encontrada', 'error');
        redirect('index.php');
    }
    
    // Actualizar estado
    $stmt = $db->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $cita_id]);
    
    $mensaje_estado = ucfirst(str_replace('_', ' ', $nuevo_estado));
    showAlert("Cita marcada como: $mensaje_estado", 'success');
    
    // Redirigir a la vista de la cita
    redirect('ver_cita.php?id=' . $cita_id);
    
} catch (Exception $e) {
    showAlert('Error al cambiar estado: ' . $e->getMessage(), 'error');
    redirect('index.php');
}
?>
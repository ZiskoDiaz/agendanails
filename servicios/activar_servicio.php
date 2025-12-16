<?php
require_once '../config/config.php';
require_once '../config/database.php';

$servicio_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$servicio_id) {
    showAlert('Servicio no encontrado', 'error');
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que el servicio existe
    $stmt = $db->prepare("SELECT nombre FROM servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servicio) {
        showAlert('Servicio no encontrado', 'error');
        redirect('index.php');
    }
    
    // Activar servicio
    $stmt = $db->prepare("UPDATE servicios SET activo = 1 WHERE id = ?");
    $stmt->execute([$servicio_id]);
    
    showAlert('Servicio "' . htmlspecialchars($servicio['nombre']) . '" activado exitosamente', 'success');
    
} catch (Exception $e) {
    showAlert('Error al activar servicio: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>
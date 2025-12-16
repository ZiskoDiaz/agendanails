<?php
require_once '../config/config.php';
require_once '../config/database.php';

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    showAlert('Cliente no encontrada', 'error');
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que la cliente existe
    $stmt = $db->prepare("SELECT nombre FROM clientas WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        showAlert('Cliente no encontrada', 'error');
        redirect('index.php');
    }
    
    // Activar cliente
    $stmt = $db->prepare("UPDATE clientas SET activa = 1 WHERE id = ?");
    $stmt->execute([$cliente_id]);
    
    showAlert('Cliente ' . htmlspecialchars($cliente['nombre']) . ' activada exitosamente', 'success');
    
} catch (Exception $e) {
    showAlert('Error al activar cliente: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>
<?php
require_once '../config/config.php';
require_once '../config/database.php';

$proveedor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$proveedor_id) {
    showAlert('Proveedor no encontrado', 'error');
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que el proveedor existe
    $stmt = $db->prepare("SELECT nombre FROM proveedores WHERE id = ?");
    $stmt->execute([$proveedor_id]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$proveedor) {
        showAlert('Proveedor no encontrado', 'error');
        redirect('index.php');
    }
    
    // Desactivar proveedor
    $stmt = $db->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?");
    $stmt->execute([$proveedor_id]);
    
    showAlert('Proveedor "' . htmlspecialchars($proveedor['nombre']) . '" desactivado exitosamente', 'success');
    
} catch (Exception $e) {
    showAlert('Error al desactivar proveedor: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>
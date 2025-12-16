<?php
require_once '../config/config.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

$insumo_id = (int)$_POST['insumo_id'];
$tipo = $_POST['tipo'];
$cantidad = (int)$_POST['cantidad'];
$motivo = $_POST['motivo'];

if (!$insumo_id || !$tipo || !$cantidad || !$motivo) {
    showAlert('Datos incompletos para ajustar stock', 'error');
    redirect('index.php');
}

$db->beginTransaction();

try {
    // Obtener stock actual
    $stmt = $db->prepare("SELECT stock_actual, nombre FROM insumos WHERE id = ? AND activo = 1");
    $stmt->execute([$insumo_id]);
    $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$insumo) {
        throw new Exception('Insumo no encontrado');
    }
    
    $stock_anterior = $insumo['stock_actual'];
    $nuevo_stock = $stock_anterior;
    
    // Calcular nuevo stock
    if ($tipo == 'entrada') {
        $nuevo_stock += $cantidad;
    } else {
        $nuevo_stock -= $cantidad;
        
        // Verificar que no quede stock negativo
        if ($nuevo_stock < 0) {
            throw new Exception('No hay suficiente stock disponible. Stock actual: ' . $stock_anterior);
        }
    }
    
    // Actualizar stock
    $stmt = $db->prepare("UPDATE insumos SET stock_actual = ? WHERE id = ?");
    $stmt->execute([$nuevo_stock, $insumo_id]);
    
    // Registrar movimiento
    $stmt = $db->prepare("
        INSERT INTO movimientos_inventario (insumo_id, tipo, cantidad, motivo, usuario) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$insumo_id, $tipo, $cantidad, $motivo, 'Usuario']);
    
    $db->commit();
    
    $mensaje = 'Stock ajustado exitosamente. ';
    $mensaje .= htmlspecialchars($insumo['nombre']) . ': ';
    $mensaje .= $stock_anterior . ' → ' . $nuevo_stock;
    
    showAlert($mensaje, 'success');
    
} catch (Exception $e) {
    $db->rollBack();
    showAlert('Error al ajustar stock: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>
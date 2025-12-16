<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['disponible' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$fecha = $_POST['fecha'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';
$cita_id = $_POST['cita_id'] ?? null; // Para editar citas existentes

if (!$fecha || !$hora_inicio || !$hora_fin) {
    echo json_encode(['disponible' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Query para buscar conflictos
    $query = "
        SELECT c.id, cl.nombre as cliente_nombre, c.hora_inicio, c.hora_fin 
        FROM citas c 
        JOIN clientas cl ON c.cliente_id = cl.id
        WHERE c.fecha = ? AND c.estado != 'cancelada'";
    
    $params = [$fecha];
    
    // Si estamos editando, excluir la cita actual
    if ($cita_id) {
        $query .= " AND c.id != ?";
        $params[] = $cita_id;
    }
    
    $query .= " AND (
        (? >= c.hora_inicio AND ? < c.hora_fin) OR
        (? > c.hora_inicio AND ? <= c.hora_fin) OR
        (? <= c.hora_inicio AND ? >= c.hora_fin)
    )";
    
    $params = array_merge($params, [
        $hora_inicio, $hora_inicio,
        $hora_fin, $hora_fin,
        $hora_inicio, $hora_fin
    ]);
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $conflictos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($conflictos)) {
        $conflicto = $conflictos[0];
        echo json_encode([
            'disponible' => false,
            'mensaje' => "Ya hay una cita con " . $conflicto['cliente_nombre'] . 
                        " de " . date('H:i', strtotime($conflicto['hora_inicio'])) . 
                        " a " . date('H:i', strtotime($conflicto['hora_fin']))
        ]);
    } else {
        echo json_encode([
            'disponible' => true,
            'mensaje' => 'Horario disponible'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'disponible' => false,
        'mensaje' => 'Error al validar horario: ' . $e->getMessage()
    ]);
}
?>
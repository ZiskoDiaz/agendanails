<?php
require_once 'config/config.php';
require_once 'config/database.php';

$page_title = 'Dashboard';

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas para el dashboard
$stats = [];

// Total de clientas activas
$stmt = $db->query("SELECT COUNT(*) as total FROM clientas WHERE activa = 1");
$stats['clientas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Citas de hoy
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE fecha = ? AND estado != 'cancelada'");
$stmt->execute([$today]);
$stats['citas_hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Citas pendientes
$stmt = $db->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente' AND fecha >= CURDATE()");
$stats['citas_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Insumos con stock bajo
$stmt = $db->query("SELECT COUNT(*) as total FROM insumos WHERE stock_actual <= stock_minimo AND activo = 1");
$stats['stock_bajo'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Próximas citas (hoy)
$stmt = $db->prepare("
    SELECT c.*, cl.nombre as cliente_nombre, c.hora_inicio, c.hora_fin, c.estado
    FROM citas c 
    JOIN clientas cl ON c.cliente_id = cl.id 
    WHERE c.fecha = ? AND c.estado != 'cancelada'
    ORDER BY c.hora_inicio ASC
    LIMIT 5
");
$stmt->execute([$today]);
$proximas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Insumos con stock bajo
$stmt = $db->query("
    SELECT i.*, c.nombre as categoria_nombre 
    FROM insumos i 
    LEFT JOIN categorias_insumos c ON i.categoria_id = c.id 
    WHERE i.stock_actual <= i.stock_minimo AND i.activo = 1
    ORDER BY (i.stock_actual / i.stock_minimo) ASC
    LIMIT 5
");
$insumos_stock_bajo = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container">
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-icon">👥</div>
            <div class="dashboard-number"><?php echo $stats['clientas']; ?></div>
            <div class="dashboard-label">Clientas Activas</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-icon">📅</div>
            <div class="dashboard-number"><?php echo $stats['citas_hoy']; ?></div>
            <div class="dashboard-label">Citas Hoy</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-icon">⏰</div>
            <div class="dashboard-number"><?php echo $stats['citas_pendientes']; ?></div>
            <div class="dashboard-label">Citas Pendientes</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-icon">📦</div>
            <div class="dashboard-number"><?php echo $stats['stock_bajo']; ?></div>
            <div class="dashboard-label">Stock Bajo</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Próximas citas de hoy -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Citas de Hoy</h2>
                <a href="agenda/index.php" class="btn btn-sm btn-primary">Ver Agenda</a>
            </div>
            
            <?php if (!empty($proximas_citas)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximas_citas as $cita): ?>
                                <tr>
                                    <td><?php echo formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['cliente_nombre']); ?></td>
                                    <td><span class="status-<?php echo $cita['estado']; ?>"><?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No hay citas programadas para hoy</p>
            <?php endif; ?>
        </div>

        <!-- Insumos con stock bajo -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Stock Bajo</h2>
                <a href="insumos/index.php" class="btn btn-sm btn-primary">Ver Inventario</a>
            </div>
            
            <?php if (!empty($insumos_stock_bajo)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos_stock_bajo as $insumo): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($insumo['nombre']); ?>
                                        <?php if ($insumo['categoria_nombre']): ?>
                                            <br><small style="color: #666;"><?php echo htmlspecialchars($insumo['categoria_nombre']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: <?php echo $insumo['stock_actual'] == 0 ? '#dc2626' : '#d97706'; ?>">
                                        <?php echo $insumo['stock_actual']; ?>
                                    </td>
                                    <td><?php echo $insumo['stock_minimo']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 2rem;">Todos los productos tienen stock suficiente</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Accesos Rápidos</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="agenda/nueva_cita.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center;">
                📅 Nueva Cita
            </a>
            <a href="clientas/nueva_cliente.php" class="btn btn-secondary" style="padding: 1.5rem; text-align: center;">
                👤 Nueva Cliente
            </a>
            <a href="insumos/nuevo_insumo.php" class="btn btn-success" style="padding: 1.5rem; text-align: center;">
                📦 Nuevo Insumo
            </a>
            <a href="proveedores/nuevo_proveedor.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center;">
                🏪 Nuevo Proveedor
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
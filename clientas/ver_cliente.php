<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Detalle de Cliente';

$database = new Database();
$db = $database->getConnection();

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    showAlert('Cliente no encontrada', 'error');
    redirect('index.php');
}

// Obtener datos de la cliente
$stmt = $db->prepare("SELECT * FROM clientas WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    showAlert('Cliente no encontrada', 'error');
    redirect('index.php');
}

// Obtener estadísticas de la cliente
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_citas,
        COUNT(CASE WHEN estado = 'completada' THEN 1 END) as citas_completadas,
        COUNT(CASE WHEN fecha >= CURDATE() AND estado != 'cancelada' THEN 1 END) as citas_futuras,
        MAX(fecha) as ultima_cita,
        SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_gastado
    FROM citas 
    WHERE cliente_id = ?
");
$stmt->execute([$cliente_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener últimas citas
$stmt = $db->prepare("
    SELECT c.*, GROUP_CONCAT(s.nombre SEPARATOR ', ') as servicios
    FROM citas c
    LEFT JOIN cita_servicios cs ON c.id = cs.cita_id
    LEFT JOIN servicios s ON cs.servicio_id = s.id
    WHERE c.cliente_id = ?
    GROUP BY c.id
    ORDER BY c.fecha DESC, c.hora_inicio DESC
    LIMIT 10
");
$stmt->execute([$cliente_id]);
$ultimas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Detalle de Cliente</h2>
            <div style="display: flex; gap: 1rem;">
                <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-primary">Editar</a>
                <a href="../agenda/nueva_cita.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-success">Nueva Cita</a>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Información de la cliente -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #ff6b9d;">Información Personal</h3>
                
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <?php echo htmlspecialchars($cliente['telefono']) ?: 'No registrado'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <?php echo htmlspecialchars($cliente['email']) ?: 'No registrado'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <span class="status-<?php echo $cliente['activa'] ? 'confirmada' : 'cancelada'; ?>">
                            <?php echo $cliente['activa'] ? 'Cliente Activa' : 'Cliente Inactiva'; ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Registro</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <?php echo formatDateTime($cliente['fecha_registro']); ?>
                    </div>
                </div>

                <?php if ($cliente['notas']): ?>
                    <div class="form-group">
                        <label class="form-label">Notas</label>
                        <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                            <?php echo nl2br(htmlspecialchars($cliente['notas'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Estadísticas -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #ff6b9d;">Estadísticas</h3>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number"><?php echo $stats['total_citas']; ?></div>
                        <div class="dashboard-label">Total Citas</div>
                    </div>
                    
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number"><?php echo $stats['citas_completadas']; ?></div>
                        <div class="dashboard-label">Completadas</div>
                    </div>
                    
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number"><?php echo $stats['citas_futuras']; ?></div>
                        <div class="dashboard-label">Futuras</div>
                    </div>
                    
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number"><?php echo formatCurrency($stats['total_gastado']); ?></div>
                        <div class="dashboard-label">Total Gastado</div>
                    </div>
                </div>

                <?php if ($stats['ultima_cita']): ?>
                    <div class="form-group">
                        <label class="form-label">Última Cita</label>
                        <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                            <?php echo formatDate($stats['ultima_cita']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Historial de citas -->
    <?php if (!empty($ultimas_citas)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Historial de Citas</h2>
                <a href="historial_citas.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-primary">Ver Todas</a>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Servicios</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_citas as $cita): ?>
                            <tr>
                                <td><?php echo formatDate($cita['fecha']); ?></td>
                                <td><?php echo formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']); ?></td>
                                <td><?php echo $cita['servicios'] ?: 'Sin servicios'; ?></td>
                                <td><?php echo formatCurrency($cita['total']); ?></td>
                                <td>
                                    <span class="status-<?php echo $cita['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div style="text-align: center; padding: 3rem; color: #666;">
                <h3>Sin historial de citas</h3>
                <p>Esta cliente aún no tiene citas registradas.</p>
                <a href="../agenda/nueva_cita.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-primary">Agendar Primera Cita</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
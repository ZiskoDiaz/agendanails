<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Historial de Citas';

$database = new Database();
$db = $database->getConnection();

$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

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

// Obtener todas las citas de la cliente
$stmt = $db->prepare("
    SELECT c.*, GROUP_CONCAT(s.nombre SEPARATOR ', ') as servicios
    FROM citas c
    LEFT JOIN cita_servicios cs ON c.id = cs.cita_id
    LEFT JOIN servicios s ON cs.servicio_id = s.id
    WHERE c.cliente_id = ?
    GROUP BY c.id
    ORDER BY c.fecha DESC, c.hora_inicio DESC
");
$stmt->execute([$cliente_id]);
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Historial de Citas - <?php echo htmlspecialchars($cliente['nombre']); ?></h2>
            <div style="display: flex; gap: 1rem;">
                <a href="../agenda/nueva_cita.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-primary">Nueva Cita</a>
                <a href="ver_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-secondary">Ver Cliente</a>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </div>

        <?php if (!empty($citas)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Servicios</th>
                            <th>Total</th>
                            <th>Abono</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $cita): ?>
                            <tr>
                                <td><?php echo formatDate($cita['fecha']); ?></td>
                                <td><?php echo formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']); ?></td>
                                <td><?php echo $cita['servicios'] ?: 'Sin servicios'; ?></td>
                                <td><?php echo formatCurrency($cita['total']); ?></td>
                                <td>
                                    <?php if ($cita['abono'] > 0): ?>
                                        <span style="color: #059669;"><?php echo formatCurrency($cita['abono']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #666;">Sin abono</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cita['saldo_pendiente'] > 0): ?>
                                        <span style="color: #dc2626;"><?php echo formatCurrency($cita['saldo_pendiente']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #059669;">Pagado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-<?php echo $cita['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../agenda/ver_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-secondary">Ver</a>
                                    <?php if ($cita['estado'] == 'completada'): ?>
                                        <a href="../agenda/generar_boleta.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-success">Boleta</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resumen -->
            <?php
            $total_citas = count($citas);
            $citas_completadas = count(array_filter($citas, function($c) { return $c['estado'] == 'completada'; }));
            $total_gastado = array_sum(array_column(array_filter($citas, function($c) { return $c['estado'] == 'completada'; }), 'total'));
            $total_abonos = array_sum(array_column($citas, 'abono'));
            $saldo_pendiente = array_sum(array_column($citas, 'saldo_pendiente'));
            ?>
            <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div style="text-align: center;">
                        <strong style="color: #ff6b9d; font-size: 1.2rem;"><?php echo $total_citas; ?></strong><br>
                        <small style="color: #666;">Total Citas</small>
                    </div>
                    <div style="text-align: center;">
                        <strong style="color: #059669; font-size: 1.2rem;"><?php echo $citas_completadas; ?></strong><br>
                        <small style="color: #666;">Completadas</small>
                    </div>
                    <div style="text-align: center;">
                        <strong style="color: #3b82f6; font-size: 1.2rem;"><?php echo formatCurrency($total_gastado); ?></strong><br>
                        <small style="color: #666;">Total Gastado</small>
                    </div>
                    <div style="text-align: center;">
                        <strong style="color: #059669; font-size: 1.2rem;"><?php echo formatCurrency($total_abonos); ?></strong><br>
                        <small style="color: #666;">Total Abonos</small>
                    </div>
                    <div style="text-align: center;">
                        <strong style="color: <?php echo $saldo_pendiente > 0 ? '#dc2626' : '#059669'; ?>; font-size: 1.2rem;"><?php echo formatCurrency($saldo_pendiente); ?></strong><br>
                        <small style="color: #666;">Saldo Pendiente</small>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <h3>Sin historial de citas</h3>
                <p>Esta cliente aún no tiene citas registradas.</p>
                <a href="../agenda/nueva_cita.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-primary">Agendar Primera Cita</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
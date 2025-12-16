<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Detalle de Cita';

$database = new Database();
$db = $database->getConnection();

$cita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cita_id) {
    showAlert('Cita no encontrada', 'error');
    redirect('index.php');
}

// Obtener datos de la cita
$stmt = $db->prepare("
    SELECT c.*, cl.nombre as cliente_nombre, cl.telefono, cl.email
    FROM citas c 
    JOIN clientas cl ON c.cliente_id = cl.id 
    WHERE c.id = ?
");
$stmt->execute([$cita_id]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    showAlert('Cita no encontrada', 'error');
    redirect('index.php');
}

// Obtener servicios de la cita
$stmt = $db->prepare("
    SELECT cs.*, s.nombre, s.descripcion
    FROM cita_servicios cs
    JOIN servicios s ON cs.servicio_id = s.id
    WHERE cs.cita_id = ?
    ORDER BY s.nombre
");
$stmt->execute([$cita_id]);
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener abonos de la cita
$stmt = $db->prepare("
    SELECT * FROM abonos 
    WHERE cita_id = ? 
    ORDER BY fecha_abono ASC
");
$stmt->execute([$cita_id]);
$abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Detalle de Cita</h2>
            <div style="display: flex; gap: 1rem;">
                <a href="editar_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-primary">Editar</a>
                <?php if ($cita['estado'] == 'completada'): ?>
                    <a href="generar_boleta.php?id=<?php echo $cita['id']; ?>" class="btn btn-success">Ver Boleta</a>
                <?php endif; ?>
                <a href="index.php?view=dia&fecha=<?php echo $cita['fecha']; ?>" class="btn btn-secondary">Volver a Agenda</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Información de la cita -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #ff6b9d;">Información de la Cita</h3>
                
                <div class="form-group">
                    <label class="form-label">Cliente</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <strong><?php echo htmlspecialchars($cita['cliente_nombre']); ?></strong>
                        <br><small style="color: #666;">
                            <?php echo htmlspecialchars($cita['telefono']); ?>
                            <?php if ($cita['email']): ?>
                                | <?php echo htmlspecialchars($cita['email']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Fecha</label>
                            <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <strong><?php echo formatDate($cita['fecha']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Horario</label>
                            <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <strong><?php echo formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <span class="status-<?php echo $cita['estado']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                        </span>
                    </div>
                </div>

                <?php if ($cita['notas']): ?>
                    <div class="form-group">
                        <label class="form-label">Notas</label>
                        <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                            <?php echo nl2br(htmlspecialchars($cita['notas'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Información financiera -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #ff6b9d;">Información Financiera</h3>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number"><?php echo formatCurrency($cita['total']); ?></div>
                        <div class="dashboard-label">Total</div>
                    </div>
                    
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number" style="color: #059669;"><?php echo formatCurrency($cita['abono']); ?></div>
                        <div class="dashboard-label">Abono</div>
                    </div>
                    
                    <div class="dashboard-card" style="padding: 1.5rem; grid-column: 1 / -1;">
                        <div class="dashboard-number" style="color: <?php echo $cita['saldo_pendiente'] > 0 ? '#dc2626' : '#059669'; ?>">
                            <?php echo formatCurrency($cita['saldo_pendiente']); ?>
                        </div>
                        <div class="dashboard-label">Saldo Pendiente</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Servicios -->
    <?php if (!empty($servicios)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Servicios</h2>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios as $servicio): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($servicio['descripcion'] ?: 'Sin descripción'); ?></td>
                                <td><?php echo formatCurrency($servicio['precio']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc;">
                            <td colspan="2" style="text-align: right;"><strong>TOTAL</strong></td>
                            <td><strong><?php echo formatCurrency($cita['total']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Historial de abonos -->
    <?php if (!empty($abonos)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Historial de Abonos</h2>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abonos as $abono): ?>
                            <tr>
                                <td><?php echo formatDateTime($abono['fecha_abono']); ?></td>
                                <td><strong style="color: #059669;"><?php echo formatCurrency($abono['monto']); ?></strong></td>
                                <td><?php echo ucfirst($abono['metodo_pago']); ?></td>
                                <td><?php echo htmlspecialchars($abono['notas'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Acciones rápidas -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <?php if ($cita['estado'] != 'completada' && $cita['estado'] != 'cancelada'): ?>
                <form method="POST" action="cambiar_estado.php" style="margin: 0;">
                    <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                    <input type="hidden" name="nuevo_estado" value="completada">
                    <button type="submit" class="btn btn-success" style="width: 100%; padding: 1rem;">
                        ✓ Marcar Completada
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($cita['estado'] != 'cancelada'): ?>
                <form method="POST" action="cambiar_estado.php" style="margin: 0;">
                    <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                    <input type="hidden" name="nuevo_estado" value="cancelada">
                    <button type="submit" class="btn btn-danger" style="width: 100%; padding: 1rem;" onclick="return confirmarEliminacion('¿Cancelar esta cita?')">
                        ✗ Cancelar Cita
                    </button>
                </form>
            <?php endif; ?>
            
            <a href="../clientas/ver_cliente.php?id=<?php echo $cita['cliente_id']; ?>" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                👤 Ver Cliente
            </a>
            
            <a href="nueva_cita.php?cliente_id=<?php echo $cita['cliente_id']; ?>" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                📅 Nueva Cita
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
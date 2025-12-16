<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Agenda';

$database = new Database();
$db = $database->getConnection();

// Obtener fecha actual o la fecha seleccionada
$fecha_actual = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$view = isset($_GET['view']) ? $_GET['view'] : 'mes';

// Obtener citas según la vista
if ($view == 'dia') {
    // Vista día
    $stmt = $db->prepare("
        SELECT c.*, cl.nombre as cliente_nombre, cl.telefono,
               GROUP_CONCAT(s.nombre SEPARATOR ', ') as servicios
        FROM citas c 
        JOIN clientas cl ON c.cliente_id = cl.id 
        LEFT JOIN cita_servicios cs ON c.id = cs.cita_id
        LEFT JOIN servicios s ON cs.servicio_id = s.id
        WHERE c.fecha = ?
        GROUP BY c.id
        ORDER BY c.hora_inicio ASC
    ");
    $stmt->execute([$fecha_actual]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($view == 'semana') {
    // Vista semana
    $inicio_semana = date('Y-m-d', strtotime('monday this week', strtotime($fecha_actual)));
    $fin_semana = date('Y-m-d', strtotime('sunday this week', strtotime($fecha_actual)));
    
    $stmt = $db->prepare("
        SELECT c.*, cl.nombre as cliente_nombre, cl.telefono,
               GROUP_CONCAT(s.nombre SEPARATOR ', ') as servicios
        FROM citas c 
        JOIN clientas cl ON c.cliente_id = cl.id 
        LEFT JOIN cita_servicios cs ON c.id = cs.cita_id
        LEFT JOIN servicios s ON cs.servicio_id = s.id
        WHERE c.fecha BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY c.fecha ASC, c.hora_inicio ASC
    ");
    $stmt->execute([$inicio_semana, $fin_semana]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Vista mes
    $inicio_mes = date('Y-m-01', strtotime($fecha_actual));
    $fin_mes = date('Y-m-t', strtotime($fecha_actual));
    
    $stmt = $db->prepare("
        SELECT c.fecha, COUNT(*) as total_citas
        FROM citas c 
        WHERE c.fecha BETWEEN ? AND ? AND c.estado != 'cancelada'
        GROUP BY c.fecha
    ");
    $stmt->execute([$inicio_mes, $fin_mes]);
    $citas_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir a array asociativo por fecha
    $citas_por_fecha = [];
    foreach ($citas_mes as $cita) {
        $citas_por_fecha[$cita['fecha']] = $cita['total_citas'];
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Agenda - <?php echo ucfirst($view); ?></h2>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <!-- Navegación de vistas -->
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?view=dia&fecha=<?php echo $fecha_actual; ?>" class="btn <?php echo $view == 'dia' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Día</a>
                    <a href="?view=semana&fecha=<?php echo $fecha_actual; ?>" class="btn <?php echo $view == 'semana' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Semana</a>
                    <a href="?view=mes&fecha=<?php echo $fecha_actual; ?>" class="btn <?php echo $view == 'mes' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Mes</a>
                </div>
                <a href="nueva_cita.php" class="btn btn-primary">Nueva Cita</a>
            </div>
        </div>

        <!-- Navegación de fecha -->
        <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; margin-bottom: 2rem;">
            <?php
            $fecha_anterior = '';
            $fecha_siguiente = '';
            $titulo_fecha = '';
            
            if ($view == 'dia') {
                $fecha_anterior = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));
                $fecha_siguiente = date('Y-m-d', strtotime($fecha_actual . ' +1 day'));
                $titulo_fecha = formatDate($fecha_actual);
            } elseif ($view == 'semana') {
                $fecha_anterior = date('Y-m-d', strtotime($fecha_actual . ' -1 week'));
                $fecha_siguiente = date('Y-m-d', strtotime($fecha_actual . ' +1 week'));
                $inicio_semana = date('Y-m-d', strtotime('monday this week', strtotime($fecha_actual)));
                $fin_semana = date('Y-m-d', strtotime('sunday this week', strtotime($fecha_actual)));
                $titulo_fecha = formatDate($inicio_semana) . ' - ' . formatDate($fin_semana);
            } else {
                $fecha_anterior = date('Y-m-d', strtotime($fecha_actual . ' -1 month'));
                $fecha_siguiente = date('Y-m-d', strtotime($fecha_actual . ' +1 month'));
                $titulo_fecha = strftime('%B %Y', strtotime($fecha_actual));
            }
            ?>
            
            <a href="?view=<?php echo $view; ?>&fecha=<?php echo $fecha_anterior; ?>" class="btn btn-secondary btn-sm">‹ Anterior</a>
            <strong><?php echo $titulo_fecha; ?></strong>
            <a href="?view=<?php echo $view; ?>&fecha=<?php echo $fecha_siguiente; ?>" class="btn btn-secondary btn-sm">Siguiente ›</a>
            <a href="?view=<?php echo $view; ?>&fecha=<?php echo date('Y-m-d'); ?>" class="btn btn-primary btn-sm">Hoy</a>
        </div>

        <?php if ($view == 'mes'): ?>
            <!-- Vista Calendario -->
            <div id="calendar"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.calendar) {
                        calendar.currentDate = new Date('<?php echo $fecha_actual; ?>');
                        calendar.setAppointments(<?php echo json_encode($citas_por_fecha); ?>);
                    }
                });
            </script>
            
        <?php elseif ($view == 'semana'): ?>
            <!-- Vista Semana -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Citas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        $inicio_semana = date('Y-m-d', strtotime('monday this week', strtotime($fecha_actual)));
                        
                        for ($i = 0; $i < 7; $i++) {
                            $fecha_dia = date('Y-m-d', strtotime($inicio_semana . " +$i days"));
                            $citas_dia = array_filter($citas, function($cita) use ($fecha_dia) {
                                return $cita['fecha'] == $fecha_dia;
                            });
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $dias_semana[$i]; ?></strong><br>
                                    <small><?php echo formatDate($fecha_dia); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($citas_dia)): ?>
                                        <?php foreach ($citas_dia as $cita): ?>
                                            <div style="margin-bottom: 0.5rem; padding: 0.5rem; background: #fdf2f8; border-left: 3px solid #ff6b9d; border-radius: 4px;">
                                                <strong><?php echo formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']); ?></strong><br>
                                                <?php echo htmlspecialchars($cita['cliente_nombre']); ?>
                                                <span class="status-<?php echo $cita['estado']; ?>"><?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?></span>
                                                <br><small><?php echo $cita['servicios'] ?: 'Sin servicios'; ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <em style="color: #666;">Sin citas</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
        <?php else: ?>
            <!-- Vista Día -->
            <?php if (!empty($citas)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Servicios</th>
                                <th>Total/Abono</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas as $cita): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo formatTime($cita['hora_inicio']); ?></strong><br>
                                        <small><?php echo formatTime($cita['hora_fin']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cita['cliente_nombre']); ?></strong>
                                        <?php if ($cita['notas']): ?>
                                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($cita['notas'], 0, 30)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cita['telefono']); ?></td>
                                    <td><?php echo $cita['servicios'] ?: 'Sin servicios'; ?></td>
                                    <td>
                                        <strong><?php echo formatCurrency($cita['total']); ?></strong>
                                        <?php if ($cita['abono'] > 0): ?>
                                            <br><small style="color: #059669;">Abono: <?php echo formatCurrency($cita['abono']); ?></small>
                                            <br><small style="color: #dc2626;">Saldo: <?php echo formatCurrency($cita['saldo_pendiente']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo $cita['estado']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="ver_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-secondary">Ver</a>
                                        <a href="editar_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                        <?php if ($cita['estado'] == 'completada'): ?>
                                            <a href="generar_boleta.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-success">Boleta</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <h3>Sin citas programadas</h3>
                    <p>No hay citas para <?php echo formatDate($fecha_actual); ?></p>
                    <a href="nueva_cita.php?fecha=<?php echo $fecha_actual; ?>" class="btn btn-primary">Agendar Cita</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Resumen del día -->
        <?php if ($view == 'dia' && !empty($citas)): ?>
            <div style="margin-top: 2rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <?php
                $total_ingresos = array_sum(array_column($citas, 'total'));
                $citas_completadas = count(array_filter($citas, function($c) { return $c['estado'] == 'completada'; }));
                ?>
                <strong>Resumen del día:</strong>
                <?php echo count($citas); ?> citas programadas, 
                <?php echo $citas_completadas; ?> completadas, 
                Ingresos: <?php echo formatCurrency($total_ingresos); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
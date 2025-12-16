<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Nueva Cita';

$database = new Database();
$db = $database->getConnection();

// Obtener clientas activas
$stmt = $db->query("SELECT id, nombre FROM clientas WHERE activa = 1 ORDER BY nombre");
$clientas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener servicios activos
$stmt = $db->query("SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre");
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cliente_preseleccionada = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$fecha_preseleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();
    
    try {
        // Validar conflictos de horarios
        $stmt_conflicto = $db->prepare("
            SELECT c.id, cl.nombre as cliente_nombre, c.hora_inicio, c.hora_fin 
            FROM citas c 
            JOIN clientas cl ON c.cliente_id = cl.id
            WHERE c.fecha = ? AND c.estado != 'cancelada' 
            AND (
                (? >= c.hora_inicio AND ? < c.hora_fin) OR
                (? > c.hora_inicio AND ? <= c.hora_fin) OR
                (? <= c.hora_inicio AND ? >= c.hora_fin)
            )
        ");
        
        $stmt_conflicto->execute([
            $_POST['fecha'],
            $_POST['hora_inicio'], $_POST['hora_inicio'],
            $_POST['hora_fin'], $_POST['hora_fin'],
            $_POST['hora_inicio'], $_POST['hora_fin']
        ]);
        
        $conflictos = $stmt_conflicto->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($conflictos)) {
            $conflicto = $conflictos[0];
            throw new Exception(
                "¡Hora ocupada! Ya hay una cita programada con " . $conflicto['cliente_nombre'] . 
                " de " . formatTime($conflicto['hora_inicio']) . " a " . formatTime($conflicto['hora_fin'])
            );
        }
        
        // Calcular total
        $total = 0;
        $abono = (float)$_POST['abono'];
        
        if (isset($_POST['servicios']) && is_array($_POST['servicios'])) {
            foreach ($_POST['servicios'] as $servicio_id) {
                $stmt_precio = $db->prepare("SELECT precio FROM servicios WHERE id = ?");
                $stmt_precio->execute([$servicio_id]);
                $precio = $stmt_precio->fetch(PDO::FETCH_ASSOC)['precio'];
                $total += $precio;
            }
        }
        
        $saldo_pendiente = $total - $abono;
        
        // Insertar la cita
        $stmt = $db->prepare("
            INSERT INTO citas (cliente_id, fecha, hora_inicio, hora_fin, estado, notas, total, abono, saldo_pendiente) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['cliente_id'],
            $_POST['fecha'],
            $_POST['hora_inicio'],
            $_POST['hora_fin'],
            $_POST['estado'],
            $_POST['notas'],
            $total,
            $abono,
            $saldo_pendiente
        ]);
        
        $cita_id = $db->lastInsertId();
        
        // Insertar servicios de la cita
        if (isset($_POST['servicios']) && is_array($_POST['servicios'])) {
            $stmt_servicio = $db->prepare("
                INSERT INTO cita_servicios (cita_id, servicio_id, precio) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($_POST['servicios'] as $servicio_id) {
                $stmt_precio = $db->prepare("SELECT precio FROM servicios WHERE id = ?");
                $stmt_precio->execute([$servicio_id]);
                $precio = $stmt_precio->fetch(PDO::FETCH_ASSOC)['precio'];
                
                $stmt_servicio->execute([$cita_id, $servicio_id, $precio]);
            }
        }
        
        // Registrar abono si es mayor a 0
        if ($abono > 0) {
            $stmt_abono = $db->prepare("
                INSERT INTO abonos (cita_id, monto, metodo_pago, notas) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt_abono->execute([$cita_id, $abono, $_POST['metodo_pago'], 'Abono inicial']);
        }
        
        $db->commit();
        showAlert('Cita creada exitosamente', 'success');
        redirect('index.php?view=dia&fecha=' . $_POST['fecha']);
        
    } catch (Exception $e) {
        $db->rollBack();
        showAlert('Error al crear cita: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nueva Cita</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-cita');" id="form-cita">
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_id" class="form-control" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach ($clientas as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" <?php echo $cliente['id'] == $cliente_preseleccionada ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666;">
                            <a href="../clientas/nueva_cliente.php" target="_blank">Crear nueva cliente</a>
                        </small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Fecha *</label>
                        <input type="date" name="fecha" class="form-control" required value="<?php echo $fecha_preseleccionada; ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Hora Inicio *</label>
                        <input type="time" name="hora_inicio" class="form-control" required>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Hora Fin *</label>
                        <input type="time" name="hora_fin" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmada" selected>Confirmada</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="completada">Completada</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Servicios</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <?php foreach ($servicios as $servicio): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: white; border-radius: 4px; cursor: pointer;">
                            <input type="checkbox" name="servicios[]" value="<?php echo $servicio['id']; ?>" data-precio="<?php echo $servicio['precio']; ?>" onchange="calcularTotal()">
                            <div>
                                <strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong><br>
                                <small style="color: #666;">
                                    <?php echo formatCurrency($servicio['precio']); ?> - <?php echo $servicio['duracion_minutos']; ?> min
                                </small>
                                <?php if ($servicio['descripcion']): ?>
                                    <br><small style="color: #888;"><?php echo htmlspecialchars($servicio['descripcion']); ?></small>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <div style="padding: 1rem; background: #fdf2f8; border-radius: 8px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center;">
                        <div style="text-align: center;">
                            <strong>Total estimado: <span id="total-cita">$0</span></strong>
                        </div>
                        <div style="text-align: center;">
                            <strong>Saldo pendiente: <span id="saldo-pendiente">$0</span></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Abono (Opcional)</label>
                        <input type="number" name="abono" id="abono" class="form-control" min="0" step="1000" value="0" onchange="calcularSaldo()">
                        <small style="color: #666;">Monto que la cliente paga por adelantado</small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Método de Pago del Abono</label>
                        <select name="metodo_pago" class="form-control">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="3" placeholder="Observaciones especiales, alergias, preferencias..."></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Cita</button>
            </div>
        </form>
    </div>
</div>

<script>
let totalGlobal = 0;

// Calcular total automáticamente
function calcularTotal() {
    let total = 0;
    const servicios = document.querySelectorAll('input[name="servicios[]"]:checked');
    
    servicios.forEach(servicio => {
        total += parseFloat(servicio.dataset.precio || 0);
    });
    
    totalGlobal = total;
    document.getElementById('total-cita').textContent = formatCurrency(total);
    calcularSaldo();
}

// Calcular saldo pendiente
function calcularSaldo() {
    const abono = parseFloat(document.getElementById('abono').value) || 0;
    const saldo = Math.max(0, totalGlobal - abono);
    
    document.getElementById('saldo-pendiente').textContent = formatCurrency(saldo);
    
    // Validar que el abono no sea mayor al total
    if (abono > totalGlobal && totalGlobal > 0) {
        alert('El abono no puede ser mayor al total del servicio');
        document.getElementById('abono').value = totalGlobal;
        calcularSaldo();
    }
}

// Validar disponibilidad de horario
async function validarHorario() {
    const fecha = document.querySelector('input[name="fecha"]').value;
    const horaInicio = document.querySelector('input[name="hora_inicio"]').value;
    const horaFin = document.querySelector('input[name="hora_fin"]').value;
    
    if (!fecha || !horaInicio || !horaFin) return;
    
    try {
        const response = await fetch('validar_horario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `fecha=${fecha}&hora_inicio=${horaInicio}&hora_fin=${horaFin}`
        });
        
        const result = await response.json();
        
        if (!result.disponible) {
            alert('¡Hora ocupada! ' + result.mensaje);
            document.querySelector('input[name="hora_inicio"]').value = '';
            document.querySelector('input[name="hora_fin"]').value = '';
        }
    } catch (error) {
        console.error('Error validando horario:', error);
    }
}

// Validar horas
document.querySelector('input[name="hora_fin"]').addEventListener('change', function() {
    const horaInicio = document.querySelector('input[name="hora_inicio"]').value;
    const horaFin = this.value;
    
    if (horaInicio && horaFin && horaFin <= horaInicio) {
        alert('La hora de fin debe ser posterior a la hora de inicio');
        this.value = '';
        return;
    }
    
    validarHorario();
});

document.querySelector('input[name="hora_inicio"]').addEventListener('change', function() {
    const horaFin = document.querySelector('input[name="hora_fin"]').value;
    if (horaFin) {
        validarHorario();
    }
});

// Calcular total inicial
document.addEventListener('DOMContentLoaded', function() {
    calcularTotal();
    calcularSaldo();
});
</script>

<?php require_once '../includes/footer.php'; ?>
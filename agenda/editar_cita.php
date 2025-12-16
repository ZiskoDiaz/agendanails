<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Editar Cita';

$database = new Database();
$db = $database->getConnection();

$cita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cita_id) {
    showAlert('Cita no encontrada', 'error');
    redirect('index.php');
}

// Obtener datos de la cita
$stmt = $db->prepare("SELECT * FROM citas WHERE id = ?");
$stmt->execute([$cita_id]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    showAlert('Cita no encontrada', 'error');
    redirect('index.php');
}

// Obtener clientas activas
$stmt = $db->query("SELECT id, nombre FROM clientas WHERE activa = 1 ORDER BY nombre");
$clientas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener servicios activos
$stmt = $db->query("SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre");
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener servicios de la cita actual
$stmt = $db->prepare("SELECT servicio_id FROM cita_servicios WHERE cita_id = ?");
$stmt->execute([$cita_id]);
$servicios_cita = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();
    
    try {
        // Validar conflictos de horarios (excluyendo la cita actual)
        $stmt_conflicto = $db->prepare("
            SELECT c.id, cl.nombre as cliente_nombre, c.hora_inicio, c.hora_fin 
            FROM citas c 
            JOIN clientas cl ON c.cliente_id = cl.id
            WHERE c.fecha = ? AND c.estado != 'cancelada' AND c.id != ?
            AND (
                (? >= c.hora_inicio AND ? < c.hora_fin) OR
                (? > c.hora_inicio AND ? <= c.hora_fin) OR
                (? <= c.hora_inicio AND ? >= c.hora_fin)
            )
        ");
        
        $stmt_conflicto->execute([
            $_POST['fecha'],
            $cita_id,
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
        
        // Actualizar la cita
        $stmt = $db->prepare("
            UPDATE citas 
            SET cliente_id = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, estado = ?, 
                notas = ?, total = ?, abono = ?, saldo_pendiente = ?
            WHERE id = ?
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
            $saldo_pendiente,
            $cita_id
        ]);
        
        // Eliminar servicios anteriores
        $stmt = $db->prepare("DELETE FROM cita_servicios WHERE cita_id = ?");
        $stmt->execute([$cita_id]);
        
        // Insertar nuevos servicios
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
        
        // Actualizar abono si cambió
        if ($abono != $cita['abono']) {
            if ($abono > $cita['abono']) {
                // Registrar nuevo abono (diferencia)
                $diferencia = $abono - $cita['abono'];
                $stmt_abono = $db->prepare("
                    INSERT INTO abonos (cita_id, monto, metodo_pago, notas) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_abono->execute([$cita_id, $diferencia, $_POST['metodo_pago'], 'Abono adicional']);
            }
        }
        
        $db->commit();
        showAlert('Cita actualizada exitosamente', 'success');
        redirect('ver_cita.php?id=' . $cita_id);
        
    } catch (Exception $e) {
        $db->rollBack();
        showAlert('Error al actualizar cita: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Editar Cita</h2>
            <div style="display: flex; gap: 1rem;">
                <a href="ver_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-secondary">Volver</a>
            </div>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-cita');" id="form-cita">
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_id" class="form-control" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach ($clientas as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" <?php echo $cliente['id'] == $cita['cliente_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Fecha *</label>
                        <input type="date" name="fecha" class="form-control" required value="<?php echo $cita['fecha']; ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Hora Inicio *</label>
                        <input type="time" name="hora_inicio" class="form-control" required value="<?php echo $cita['hora_inicio']; ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Hora Fin *</label>
                        <input type="time" name="hora_fin" class="form-control" required value="<?php echo $cita['hora_fin']; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="pendiente" <?php echo $cita['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="confirmada" <?php echo $cita['estado'] == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                    <option value="en_proceso" <?php echo $cita['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="completada" <?php echo $cita['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                    <option value="cancelada" <?php echo $cita['estado'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Servicios</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <?php foreach ($servicios as $servicio): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: white; border-radius: 4px; cursor: pointer;">
                            <input type="checkbox" name="servicios[]" value="<?php echo $servicio['id']; ?>" 
                                   data-precio="<?php echo $servicio['precio']; ?>" 
                                   <?php echo in_array($servicio['id'], $servicios_cita) ? 'checked' : ''; ?>
                                   onchange="calcularTotal()">
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
                            <strong>Total estimado: <span id="total-cita"><?php echo formatCurrency($cita['total']); ?></span></strong>
                        </div>
                        <div style="text-align: center;">
                            <strong>Saldo pendiente: <span id="saldo-pendiente"><?php echo formatCurrency($cita['saldo_pendiente']); ?></span></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Abono</label>
                        <input type="number" name="abono" id="abono" class="form-control" min="0" step="1000" 
                               value="<?php echo $cita['abono']; ?>" onchange="calcularSaldo()">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Método de Pago</label>
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
                <textarea name="notas" class="form-control" rows="3"><?php echo htmlspecialchars($cita['notas']); ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="ver_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Cita</button>
            </div>
        </form>
    </div>
</div>

<script>
let totalGlobal = <?php echo $cita['total']; ?>;

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

function calcularSaldo() {
    const abono = parseFloat(document.getElementById('abono').value) || 0;
    const saldo = Math.max(0, totalGlobal - abono);
    
    document.getElementById('saldo-pendiente').textContent = formatCurrency(saldo);
    
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
            body: `fecha=${fecha}&hora_inicio=${horaInicio}&hora_fin=${horaFin}&cita_id=<?php echo $cita_id; ?>`
        });
        
        const result = await response.json();
        
        if (!result.disponible) {
            alert('¡Hora ocupada! ' + result.mensaje);
            document.querySelector('input[name="hora_inicio"]').value = '<?php echo $cita['hora_inicio']; ?>';
            document.querySelector('input[name="hora_fin"]').value = '<?php echo $cita['hora_fin']; ?>';
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
        this.value = '<?php echo $cita['hora_fin']; ?>';
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

document.addEventListener('DOMContentLoaded', function() {
    calcularTotal();
    calcularSaldo();
});
</script>

<?php require_once '../includes/footer.php'; ?>
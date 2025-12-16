<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Editar Servicio';

$database = new Database();
$db = $database->getConnection();

$servicio_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$servicio_id) {
    showAlert('Servicio no encontrado', 'error');
    redirect('index.php');
}

// Obtener datos del servicio
$stmt = $db->prepare("SELECT * FROM servicios WHERE id = ?");
$stmt->execute([$servicio_id]);
$servicio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servicio) {
    showAlert('Servicio no encontrado', 'error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE servicios 
            SET nombre = ?, descripcion = ?, precio = ?, duracion_minutos = ?, activo = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['duracion_minutos'],
            isset($_POST['activo']) ? 1 : 0,
            $servicio_id
        ]);
        
        showAlert('Servicio actualizado exitosamente', 'success');
        redirect('index.php');
        
    } catch (Exception $e) {
        showAlert('Error al actualizar servicio: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Editar Servicio</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-servicio');" id="form-servicio">
            <div class="form-group">
                <label class="form-label">Nombre del Servicio *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($servicio['nombre']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción detallada del servicio..."><?php echo htmlspecialchars($servicio['descripcion']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Precio *</label>
                        <input type="number" name="precio" class="form-control" required min="0" step="100" value="<?php echo $servicio['precio']; ?>">
                        <small style="color: #666;">Precio en pesos chilenos</small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Duración (minutos) *</label>
                        <input type="number" name="duracion_minutos" class="form-control" required min="5" max="300" value="<?php echo $servicio['duracion_minutos']; ?>">
                        <small style="color: #666;">Tiempo estimado del servicio</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1" <?php echo $servicio['activo'] ? 'checked' : ''; ?>>
                    Servicio activo
                </label>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Servicio</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
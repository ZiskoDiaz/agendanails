<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Editar Proveedor';

$database = new Database();
$db = $database->getConnection();

$proveedor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$proveedor_id) {
    showAlert('Proveedor no encontrado', 'error');
    redirect('index.php');
}

// Obtener datos del proveedor
$stmt = $db->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->execute([$proveedor_id]);
$proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proveedor) {
    showAlert('Proveedor no encontrado', 'error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE proveedores 
            SET nombre = ?, contacto = ?, telefono = ?, email = ?, direccion = ?, activo = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['contacto'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['direccion'],
            isset($_POST['activo']) ? 1 : 0,
            $proveedor_id
        ]);
        
        showAlert('Proveedor actualizado exitosamente', 'success');
        redirect('index.php');
        
    } catch (Exception $e) {
        showAlert('Error al actualizar proveedor: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Editar Proveedor</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-proveedor');" id="form-proveedor">
            <div class="form-group">
                <label class="form-label">Nombre del Proveedor *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Persona de Contacto</label>
                        <input type="text" name="contacto" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($proveedor['contacto']); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($proveedor['telefono']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($proveedor['email']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Dirección</label>
                <textarea name="direccion" class="form-control" rows="2"><?php echo htmlspecialchars($proveedor['direccion']); ?></textarea>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1" <?php echo $proveedor['activo'] ? 'checked' : ''; ?>>
                    Proveedor activo
                </label>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
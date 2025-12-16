<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Editar Cliente';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE clientas 
            SET nombre = ?, telefono = ?, email = ?, notas = ?, activa = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['notas'],
            isset($_POST['activa']) ? 1 : 0,
            $cliente_id
        ]);
        
        showAlert('Cliente actualizada exitosamente', 'success');
        redirect('index.php');
        
    } catch (Exception $e) {
        showAlert('Error al actualizar cliente: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Editar Cliente</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-cliente');" id="form-cliente">
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($cliente['nombre']); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($cliente['email']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="3" placeholder="Información adicional sobre la cliente..."><?php echo htmlspecialchars($cliente['notas']); ?></textarea>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activa" value="1" <?php echo $cliente['activa'] ? 'checked' : ''; ?>>
                    Cliente activa
                </label>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
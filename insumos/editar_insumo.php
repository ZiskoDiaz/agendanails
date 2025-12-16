<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Editar Insumo';

$database = new Database();
$db = $database->getConnection();

$insumo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$insumo_id) {
    showAlert('Insumo no encontrado', 'error');
    redirect('index.php');
}

// Obtener datos del insumo
$stmt = $db->prepare("SELECT * FROM insumos WHERE id = ?");
$stmt->execute([$insumo_id]);
$insumo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$insumo) {
    showAlert('Insumo no encontrado', 'error');
    redirect('index.php');
}

// Obtener categorías y proveedores
$stmt = $db->query("SELECT * FROM categorias_insumos ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE insumos 
            SET nombre = ?, categoria_id = ?, proveedor_id = ?, precio_compra = ?, precio_venta = ?, 
                stock_minimo = ?, unidad_medida = ?, activo = ?
            WHERE id = ?
        ");
        
        $categoria_id = $_POST['categoria_id'] ?: null;
        $proveedor_id = $_POST['proveedor_id'] ?: null;
        $precio_compra = $_POST['precio_compra'] ?: null;
        $precio_venta = $_POST['precio_venta'] ?: null;
        
        $stmt->execute([
            $_POST['nombre'],
            $categoria_id,
            $proveedor_id,
            $precio_compra,
            $precio_venta,
            $_POST['stock_minimo'],
            $_POST['unidad_medida'],
            isset($_POST['activo']) ? 1 : 0,
            $insumo_id
        ]);
        
        showAlert('Insumo actualizado exitosamente', 'success');
        redirect('index.php');
        
    } catch (Exception $e) {
        showAlert('Error al actualizar insumo: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Editar Insumo</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-insumo');" id="form-insumo">
            <div class="form-group">
                <label class="form-label">Nombre del Producto *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($insumo['nombre']); ?>">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria['id'] == $insumo['categoria_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-control">
                            <option value="">Seleccionar proveedor...</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['id']; ?>" <?php echo $proveedor['id'] == $insumo['proveedor_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Precio de Compra</label>
                        <input type="number" name="precio_compra" class="form-control" min="0" step="100" value="<?php echo $insumo['precio_compra']; ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Precio de Venta</label>
                        <input type="number" name="precio_venta" class="form-control" min="0" step="100" value="<?php echo $insumo['precio_venta']; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Stock Actual</label>
                <input type="number" class="form-control" value="<?php echo $insumo['stock_actual']; ?>" readonly>
                <small style="color: #666;">Para modificar el stock usa el botón "Stock" en la lista de insumos</small>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Stock Mínimo *</label>
                        <input type="number" name="stock_minimo" class="form-control" required min="1" value="<?php echo $insumo['stock_minimo']; ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Unidad de Medida *</label>
                        <select name="unidad_medida" class="form-control" required>
                            <option value="unidad" <?php echo $insumo['unidad_medida'] == 'unidad' ? 'selected' : ''; ?>>Unidad</option>
                            <option value="ml" <?php echo $insumo['unidad_medida'] == 'ml' ? 'selected' : ''; ?>>Mililitros (ml)</option>
                            <option value="g" <?php echo $insumo['unidad_medida'] == 'g' ? 'selected' : ''; ?>>Gramos (g)</option>
                            <option value="kg" <?php echo $insumo['unidad_medida'] == 'kg' ? 'selected' : ''; ?>>Kilogramos (kg)</option>
                            <option value="paquete" <?php echo $insumo['unidad_medida'] == 'paquete' ? 'selected' : ''; ?>>Paquete</option>
                            <option value="caja" <?php echo $insumo['unidad_medida'] == 'caja' ? 'selected' : ''; ?>>Caja</option>
                            <option value="set" <?php echo $insumo['unidad_medida'] == 'set' ? 'selected' : ''; ?>>Set</option>
                            <option value="par" <?php echo $insumo['unidad_medida'] == 'par' ? 'selected' : ''; ?>>Par</option>
                            <option value="metro" <?php echo $insumo['unidad_medida'] == 'metro' ? 'selected' : ''; ?>>Metro</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1" <?php echo $insumo['activo'] ? 'checked' : ''; ?>>
                    Producto activo
                </label>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Insumo</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
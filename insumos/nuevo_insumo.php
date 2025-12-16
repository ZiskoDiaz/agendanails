<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Nuevo Insumo';

$database = new Database();
$db = $database->getConnection();

// Obtener categorías y proveedores
$stmt = $db->query("SELECT * FROM categorias_insumos ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();
    
    try {
        // Insertar insumo
        $stmt = $db->prepare("
            INSERT INTO insumos (nombre, categoria_id, proveedor_id, precio_compra, precio_venta, 
                                stock_actual, stock_minimo, unidad_medida) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
            $_POST['stock_actual'],
            $_POST['stock_minimo'],
            $_POST['unidad_medida']
        ]);
        
        $insumo_id = $db->lastInsertId();
        
        // Registrar movimiento inicial si hay stock
        if ($_POST['stock_actual'] > 0) {
            $stmt = $db->prepare("
                INSERT INTO movimientos_inventario (insumo_id, tipo, cantidad, motivo, usuario) 
                VALUES (?, 'entrada', ?, 'Stock inicial', 'Sistema')
            ");
            $stmt->execute([$insumo_id, $_POST['stock_actual']]);
        }
        
        $db->commit();
        showAlert('Insumo creado exitosamente', 'success');
        redirect('index.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        showAlert('Error al crear insumo: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nuevo Insumo</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-insumo');" id="form-insumo">
            <div class="form-group">
                <label class="form-label">Nombre del Producto *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" placeholder="Ej: Esmalte rojo cereza">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666;">
                            <a href="../categorias/nueva_categoria.php" target="_blank">Crear nueva categoría</a>
                        </small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-control">
                            <option value="">Seleccionar proveedor...</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['id']; ?>">
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666;">
                            <a href="../proveedores/nuevo_proveedor.php" target="_blank">Crear nuevo proveedor</a>
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Precio de Compra</label>
                        <input type="number" name="precio_compra" class="form-control" min="0" step="100" placeholder="5000">
                        <small style="color: #666;">Precio al que compras el producto</small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Precio de Venta</label>
                        <input type="number" name="precio_venta" class="form-control" min="0" step="100" placeholder="8000">
                        <small style="color: #666;">Precio al que vendes el producto</small>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Stock Actual *</label>
                        <input type="number" name="stock_actual" class="form-control" required min="0" value="0">
                        <small style="color: #666;">Cantidad disponible actualmente</small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Stock Mínimo *</label>
                        <input type="number" name="stock_minimo" class="form-control" required min="1" value="5">
                        <small style="color: #666;">Cantidad mínima antes de alertar</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Unidad de Medida *</label>
                <select name="unidad_medida" class="form-control" required>
                    <option value="unidad">Unidad</option>
                    <option value="ml">Mililitros (ml)</option>
                    <option value="g">Gramos (g)</option>
                    <option value="kg">Kilogramos (kg)</option>
                    <option value="paquete">Paquete</option>
                    <option value="caja">Caja</option>
                    <option value="set">Set</option>
                    <option value="par">Par</option>
                    <option value="metro">Metro</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Insumo</button>
            </div>
        </form>
    </div>

    <!-- Productos sugeridos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Productos Típicos de Manicura</h2>
        </div>
        
        <p style="margin-bottom: 1.5rem; color: #666;">
            Algunos productos comunes que podrías necesitar gestionar:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Esmaltes</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Esmaltes comunes (diversos colores)</li>
                    <li>Esmaltes semipermanentes</li>
                    <li>Base coat</li>
                    <li>Top coat</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Herramientas</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Limas de uñas</li>
                    <li>Cortacutículas</li>
                    <li>Empujacutículas</li>
                    <li>Pinceles para nail art</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Productos de Cuidado</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Aceite para cutículas</li>
                    <li>Crema hidratante</li>
                    <li>Removedor de esmalte</li>
                    <li>Deshidratante de uñas</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Suministros</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Algodón</li>
                    <li>Papel aluminio</li>
                    <li>Separadores de dedos</li>
                    <li>Guantes desechables</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Extensiones</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Tips de uñas</li>
                    <li>Formas para extensiones</li>
                    <li>Gel constructor</li>
                    <li>Pegamento para tips</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Decoración</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Glitter</li>
                    <li>Stickers para uñas</li>
                    <li>Strass y piedras</li>
                    <li>Cintas decorativas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
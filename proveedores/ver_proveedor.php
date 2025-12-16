<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Detalle de Proveedor';

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

// Obtener productos del proveedor
$stmt = $db->prepare("
    SELECT i.*, c.nombre as categoria_nombre 
    FROM insumos i 
    LEFT JOIN categorias_insumos c ON i.categoria_id = c.id 
    WHERE i.proveedor_id = ? AND i.activo = 1
    ORDER BY i.nombre ASC
");
$stmt->execute([$proveedor_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas del proveedor
$total_productos = count($productos);
$productos_stock_bajo = count(array_filter($productos, function($p) { return $p['stock_actual'] <= $p['stock_minimo']; }));

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Detalle de Proveedor</h2>
            <div style="display: flex; gap: 1rem;">
                <a href="editar_proveedor.php?id=<?php echo $proveedor['id']; ?>" class="btn btn-primary">Editar</a>
                <a href="../insumos/nuevo_insumo.php?proveedor_id=<?php echo $proveedor['id']; ?>" class="btn btn-success">Agregar Producto</a>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Información del proveedor -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #ff6b9d;">Información del Proveedor</h3>
                
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <strong><?php echo htmlspecialchars($proveedor['nombre']); ?></strong>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Contacto</label>
                            <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <?php echo htmlspecialchars($proveedor['contacto']) ?: 'No registrado'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <?php echo htmlspecialchars($proveedor['telefono']) ?: 'No registrado'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <?php echo htmlspecialchars($proveedor['email']) ?: 'No registrado'; ?>
                    </div>
                </div>

                <?php if ($proveedor['direccion']): ?>
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                            <?php echo nl2br(htmlspecialchars($proveedor['direccion'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <span class="status-<?php echo $proveedor['activo'] ? 'confirmada' : 'cancelada'; ?>">
                            <?php echo $proveedor['activo'] ? 'Proveedor Activo' : 'Proveedor Inactivo'; ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Registro</label>
                    <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                        <?php echo formatDateTime($proveedor['created_at']); ?>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #ff6b9d;">Estadísticas</h3>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number"><?php echo $total_productos; ?></div>
                        <div class="dashboard-label">Productos</div>
                    </div>
                    
                    <div class="dashboard-card" style="padding: 1.5rem;">
                        <div class="dashboard-number" style="color: <?php echo $productos_stock_bajo > 0 ? '#dc2626' : '#059669'; ?>">
                            <?php echo $productos_stock_bajo; ?>
                        </div>
                        <div class="dashboard-label">Stock Bajo</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de productos -->
    <?php if (!empty($productos)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Productos del Proveedor</h2>
                <a href="../insumos/index.php?proveedor=<?php echo $proveedor['id']; ?>" class="btn btn-primary">Ver Todos</a>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Precio Compra</th>
                            <th>Precio Venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                    <br><small style="color: #666;">Unidad: <?php echo htmlspecialchars($producto['unidad_medida']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?: 'Sin categoría'); ?></td>
                                <td>
                                    <strong <?php echo $producto['stock_actual'] <= $producto['stock_minimo'] ? 'style="color: #dc2626;"' : ''; ?>>
                                        <?php echo $producto['stock_actual']; ?>
                                    </strong>
                                    <small style="color: #666;">/ <?php echo $producto['stock_minimo']; ?> mín</small>
                                    <?php if ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                        <br><small style="color: #dc2626; font-weight: bold;">STOCK BAJO</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $producto['precio_compra'] ? formatCurrency($producto['precio_compra']) : '-'; ?></td>
                                <td><?php echo $producto['precio_venta'] ? formatCurrency($producto['precio_venta']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div style="text-align: center; padding: 3rem; color: #666;">
                <h3>Sin productos registrados</h3>
                <p>Este proveedor aún no tiene productos asociados.</p>
                <a href="../insumos/nuevo_insumo.php?proveedor_id=<?php echo $proveedor['id']; ?>" class="btn btn-primary">Agregar Primer Producto</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
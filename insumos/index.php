<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Gestión de Insumos';

$database = new Database();
$db = $database->getConnection();

// Filtros
$categoria_filter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir WHERE clause
$whereConditions = ["i.activo = 1"];
$params = [];

if ($categoria_filter) {
    $whereConditions[] = "i.categoria_id = ?";
    $params[] = $categoria_filter;
}

if ($stock_filter == 'bajo') {
    $whereConditions[] = "i.stock_actual <= i.stock_minimo";
} elseif ($stock_filter == 'agotado') {
    $whereConditions[] = "i.stock_actual = 0";
}

if ($search) {
    $whereConditions[] = "(i.nombre LIKE ? OR c.nombre LIKE ? OR p.nombre LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Obtener insumos
$query = "
    SELECT i.*, c.nombre as categoria_nombre, p.nombre as proveedor_nombre
    FROM insumos i
    LEFT JOIN categorias_insumos c ON i.categoria_id = c.id
    LEFT JOIN proveedores p ON i.proveedor_id = p.id
    $whereClause
    ORDER BY 
        CASE WHEN i.stock_actual = 0 THEN 0
             WHEN i.stock_actual <= i.stock_minimo THEN 1
             ELSE 2 END,
        i.nombre ASC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para filtro
$stmt = $db->query("SELECT * FROM categorias_insumos ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Gestión de Insumos</h2>
            <div style="display: flex; gap: 1rem;">
                <a href="nuevo_insumo.php" class="btn btn-primary">Nuevo Insumo</a>
                <a href="movimientos.php" class="btn btn-secondary">Ver Movimientos</a>
            </div>
        </div>

        <!-- Filtros -->
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div>
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="Nombre, categoría o proveedor..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div>
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-control">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria['id'] == $categoria_filter ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Stock</label>
                    <select name="stock" class="form-control">
                        <option value="">Todos</option>
                        <option value="bajo" <?php echo $stock_filter == 'bajo' ? 'selected' : ''; ?>>Stock Bajo</option>
                        <option value="agotado" <?php echo $stock_filter == 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-secondary">Filtrar</button>
                    <a href="index.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Tabla de insumos -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th>Stock</th>
                        <th>Precio Compra</th>
                        <th>Precio Venta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($insumos)): ?>
                        <?php foreach ($insumos as $insumo): ?>
                            <?php
                            $stock_class = '';
                            if ($insumo['stock_actual'] == 0) {
                                $stock_class = 'style="background-color: #fee2e2;"';
                            } elseif ($insumo['stock_actual'] <= $insumo['stock_minimo']) {
                                $stock_class = 'style="background-color: #fef3c7;"';
                            }
                            ?>
                            <tr <?php echo $stock_class; ?>>
                                <td>
                                    <strong><?php echo htmlspecialchars($insumo['nombre']); ?></strong>
                                    <br><small style="color: #666;">Unidad: <?php echo htmlspecialchars($insumo['unidad_medida']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($insumo['categoria_nombre'] ?: 'Sin categoría'); ?></td>
                                <td><?php echo htmlspecialchars($insumo['proveedor_nombre'] ?: 'Sin proveedor'); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <strong <?php echo $insumo['stock_actual'] <= $insumo['stock_minimo'] ? 'style="color: #dc2626;"' : ''; ?>>
                                            <?php echo $insumo['stock_actual']; ?>
                                        </strong>
                                        <small style="color: #666;">/ <?php echo $insumo['stock_minimo']; ?> mín</small>
                                    </div>
                                    <?php if ($insumo['stock_actual'] == 0): ?>
                                        <small style="color: #dc2626; font-weight: bold;">AGOTADO</small>
                                    <?php elseif ($insumo['stock_actual'] <= $insumo['stock_minimo']): ?>
                                        <small style="color: #d97706; font-weight: bold;">STOCK BAJO</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $insumo['precio_compra'] ? formatCurrency($insumo['precio_compra']) : '-'; ?></td>
                                <td><?php echo $insumo['precio_venta'] ? formatCurrency($insumo['precio_venta']) : '-'; ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <button onclick="showModal('modal-stock-<?php echo $insumo['id']; ?>')" class="btn btn-sm btn-success">Stock</button>
                                        <a href="editar_insumo.php?id=<?php echo $insumo['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal para ajustar stock -->
                            <div id="modal-stock-<?php echo $insumo['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3>Ajustar Stock - <?php echo htmlspecialchars($insumo['nombre']); ?></h3>
                                        <span class="close" onclick="hideModal('modal-stock-<?php echo $insumo['id']; ?>')">&times;</span>
                                    </div>
                                    
                                    <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                                        <strong>Stock actual: <?php echo $insumo['stock_actual']; ?> <?php echo htmlspecialchars($insumo['unidad_medida']); ?></strong>
                                    </div>
                                    
                                    <form method="POST" action="ajustar_stock.php">
                                        <input type="hidden" name="insumo_id" value="<?php echo $insumo['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Tipo de Movimiento</label>
                                            <select name="tipo" class="form-control" required>
                                                <option value="entrada">Entrada (Aumentar stock)</option>
                                                <option value="salida">Salida (Reducir stock)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Cantidad</label>
                                            <input type="number" name="cantidad" class="form-control" required min="1">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Motivo</label>
                                            <select name="motivo" class="form-control" required>
                                                <option value="Compra">Compra</option>
                                                <option value="Venta">Venta</option>
                                                <option value="Uso en servicio">Uso en servicio</option>
                                                <option value="Ajuste de inventario">Ajuste de inventario</option>
                                                <option value="Merma">Merma</option>
                                                <option value="Devolución">Devolución</option>
                                            </select>
                                        </div>
                                        
                                        <div style="display: flex; gap: 1rem; justify-content: end;">
                                            <button type="button" onclick="hideModal('modal-stock-<?php echo $insumo['id']; ?>')" class="btn btn-secondary">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Ajustar Stock</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                No se encontraron insumos con los criterios seleccionados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen -->
        <?php if (!empty($insumos)): ?>
            <?php
            $total_insumos = count($insumos);
            $stock_bajo = count(array_filter($insumos, function($i) { return $i['stock_actual'] <= $i['stock_minimo']; }));
            $agotados = count(array_filter($insumos, function($i) { return $i['stock_actual'] == 0; }));
            ?>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <strong>Resumen del inventario:</strong>
                <?php echo $total_insumos; ?> productos,
                <?php if ($stock_bajo > 0): ?>
                    <span style="color: #d97706;"><?php echo $stock_bajo; ?> con stock bajo</span>,
                <?php endif; ?>
                <?php if ($agotados > 0): ?>
                    <span style="color: #dc2626;"><?php echo $agotados; ?> agotados</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Gestión de Proveedores';

$database = new Database();
$db = $database->getConnection();

// Búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = $search ? "WHERE (nombre LIKE :search OR contacto LIKE :search OR telefono LIKE :search)" : "";

// Obtener proveedores
$query = "SELECT * FROM proveedores $whereClause ORDER BY nombre ASC";
$stmt = $db->prepare($query);

if ($search) {
    $stmt->bindParam(':search', $searchParam);
    $searchParam = '%' . $search . '%';
}

$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Gestión de Proveedores</h2>
            <a href="nuevo_proveedor.php" class="btn btn-primary">Nuevo Proveedor</a>
        </div>

        <!-- Búsqueda -->
        <div class="form-group">
            <form method="GET" style="display: flex; gap: 1rem; align-items: end;">
                <div style="flex: 1;">
                    <label class="form-label">Buscar proveedor</label>
                    <input type="text" name="search" class="form-control" placeholder="Nombre, contacto o teléfono..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-secondary">Buscar</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla de proveedores -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proveedores)): ?>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <?php
                            // Obtener cantidad de productos del proveedor
                            $stmt_count = $db->prepare("SELECT COUNT(*) as total FROM insumos WHERE proveedor_id = ? AND activo = 1");
                            $stmt_count->execute([$proveedor['id']]);
                            $productos_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($proveedor['nombre']); ?></strong>
                                    <?php if ($proveedor['direccion']): ?>
                                        <br><small style="color: #666;">📍 <?php echo htmlspecialchars($proveedor['direccion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($proveedor['contacto']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['email']); ?></td>
                                <td>
                                    <span class="status-<?php echo $proveedor['activo'] ? 'confirmada' : 'cancelada'; ?>">
                                        <?php echo $proveedor['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $productos_count; ?></strong> productos
                                    <?php if ($productos_count > 0): ?>
                                        <br><a href="../insumos/index.php?proveedor=<?php echo $proveedor['id']; ?>" style="font-size: 0.8rem; color: #ff6b9d;">Ver productos</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="ver_proveedor.php?id=<?php echo $proveedor['id']; ?>" class="btn btn-sm btn-secondary">Ver</a>
                                    <a href="editar_proveedor.php?id=<?php echo $proveedor['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <?php if ($proveedor['activo']): ?>
                                        <a href="desactivar_proveedor.php?id=<?php echo $proveedor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmarEliminacion('¿Desactivar este proveedor?')">Desactivar</a>
                                    <?php else: ?>
                                        <a href="activar_proveedor.php?id=<?php echo $proveedor['id']; ?>" class="btn btn-sm btn-success">Activar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                <?php echo $search ? 'No se encontraron proveedores con ese criterio de búsqueda' : 'No hay proveedores registrados'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen -->
        <?php if (!empty($proveedores)): ?>
            <?php
            $total_activos = count(array_filter($proveedores, function($p) { return $p['activo']; }));
            ?>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <strong>Total de proveedores: <?php echo count($proveedores); ?></strong>
                (<?php echo $total_activos; ?> activos)
                <?php if ($search): ?>
                    - Filtrados por búsqueda
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
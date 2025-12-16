<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Gestión de Clientas';

$database = new Database();
$db = $database->getConnection();

// Búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = $search ? "WHERE (nombre LIKE :search OR telefono LIKE :search OR email LIKE :search)" : "";

// Obtener clientas
$query = "SELECT * FROM clientas $whereClause ORDER BY nombre ASC";
$stmt = $db->prepare($query);

if ($search) {
    $stmt->bindParam(':search', $searchParam);
    $searchParam = '%' . $search . '%';
}

$stmt->execute();
$clientas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Gestión de Clientas</h2>
            <a href="nueva_cliente.php" class="btn btn-primary">Nueva Cliente</a>
        </div>

        <!-- Búsqueda -->
        <div class="form-group">
            <form method="GET" style="display: flex; gap: 1rem; align-items: end;">
                <div style="flex: 1;">
                    <label class="form-label">Buscar cliente</label>
                    <input type="text" name="search" class="form-control" placeholder="Nombre, teléfono o email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-secondary">Buscar</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla de clientas -->
        <div class="table-container">
            <table class="table" id="tabla-clientas">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Fecha Registro</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clientas)): ?>
                        <?php foreach ($clientas as $cliente): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong>
                                    <?php if ($cliente['notas']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars(substr($cliente['notas'], 0, 50)) . (strlen($cliente['notas']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                <td><?php echo formatDate($cliente['fecha_registro']); ?></td>
                                <td>
                                    <span class="status-<?php echo $cliente['activa'] ? 'confirmada' : 'cancelada'; ?>">
                                        <?php echo $cliente['activa'] ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="ver_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-secondary">Ver</a>
                                    <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <a href="historial_citas.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-success">Citas</a>
                                    <?php if ($cliente['activa']): ?>
                                        <a href="desactivar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmarEliminacion('¿Desactivar esta cliente?')">Desactivar</a>
                                    <?php else: ?>
                                        <a href="activar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-success">Activar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                <?php echo $search ? 'No se encontraron clientas con ese criterio de búsqueda' : 'No hay clientas registradas'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen -->
        <?php if (!empty($clientas)): ?>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <strong>Total de clientas: <?php echo count($clientas); ?></strong>
                <?php if ($search): ?>
                    (filtradas por búsqueda)
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
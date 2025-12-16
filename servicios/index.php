<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Gestión de Servicios';

$database = new Database();
$db = $database->getConnection();

// Obtener servicios
$stmt = $db->query("SELECT * FROM servicios ORDER BY nombre ASC");
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Gestión de Servicios</h2>
            <a href="nuevo_servicio.php" class="btn btn-primary">Nuevo Servicio</a>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Duración</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($servicios)): ?>
                        <?php foreach ($servicios as $servicio): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong>
                                    <?php if ($servicio['descripcion']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($servicio['descripcion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatCurrency($servicio['precio']); ?></td>
                                <td><?php echo $servicio['duracion_minutos']; ?> min</td>
                                <td>
                                    <span class="status-<?php echo $servicio['activo'] ? 'confirmada' : 'cancelada'; ?>">
                                        <?php echo $servicio['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="editar_servicio.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <?php if ($servicio['activo']): ?>
                                        <a href="desactivar_servicio.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmarEliminacion('¿Desactivar este servicio?')">Desactivar</a>
                                    <?php else: ?>
                                        <a href="activar_servicio.php?id=<?php echo $servicio['id']; ?>" class="btn btn-sm btn-success">Activar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: #666;">
                                No hay servicios registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Categorías de servicios predefinidas -->
        <?php if (empty($servicios)): ?>
        <div style="margin-top: 2rem; padding: 2rem; background: #f8fafc; border-radius: 8px;">
            <h3 style="color: #ff6b9d; margin-bottom: 1rem;">Servicios Sugeridos</h3>
            <p>Puedes comenzar agregando estos servicios básicos de manicura:</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Retiro</strong><br>
                    <small>Limpieza y retiro de esmalte anterior</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Limpieza</strong><br>
                    <small>Limpieza profunda de uñas</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Limado</strong><br>
                    <small>Limado y formado de uñas</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Corte de Cutícula</strong><br>
                    <small>Corte y arreglo de cutículas</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Esmaltado</strong><br>
                    <small>Aplicación de esmalte común o semipermanente</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Capping</strong><br>
                    <small>Refuerzo de uñas con gel</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Extensiones</strong><br>
                    <small>Colocación de extensiones</small>
                </div>
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                    <strong>Hidratación</strong><br>
                    <small>Tratamiento hidratante para uñas</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
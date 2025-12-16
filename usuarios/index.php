<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../auth.php';

// Solo administradores pueden acceder
verificarAdmin();

$page_title = 'Gestión de Usuarios';

$database = new Database();
$db = $database->getConnection();

// Obtener usuarios
$stmt = $db->query("SELECT * FROM usuarios ORDER BY nombre ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Gestión de Usuarios</h2>
            <a href="nuevo_usuario.php" class="btn btn-primary">Nuevo Usuario</a>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Último Acceso</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                <td>
                                    <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; <?php echo $usuario['rol'] == 'admin' ? 'background: #dbeafe; color: #1e40af;' : 'background: #f0fdf4; color: #059669;'; ?>">
                                        <?php echo $usuario['rol'] == 'admin' ? 'Administrador' : 'Manicurista'; ?>
                                    </span>
                                </td>
                                <td><?php echo $usuario['ultimo_acceso'] ? formatDateTime($usuario['ultimo_acceso']) : 'Nunca'; ?></td>
                                <td>
                                    <span class="status-<?php echo $usuario['activo'] ? 'confirmada' : 'cancelada'; ?>">
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                        <?php if ($usuario['activo']): ?>
                                            <a href="desactivar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmarEliminacion('¿Desactivar este usuario?')">Desactivar</a>
                                        <?php else: ?>
                                            <a href="activar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-success">Activar</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 0.8rem;">Tu cuenta</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                No hay usuarios registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
            <strong>Total de usuarios: <?php echo count($usuarios); ?></strong>
            (<?php echo count(array_filter($usuarios, function($u) { return $u['activo']; })); ?> activos)
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Información de Usuarios</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">👑 Administrador</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Acceso completo al sistema</li>
                    <li>Gestión de usuarios</li>
                    <li>Configuración de servicios e insumos</li>
                    <li>Reportes y estadísticas</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #059669;">
                <h4 style="color: #059669; margin-bottom: 1rem;">💅 Manicurista</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Gestión de agenda y citas</li>
                    <li>Registro de clientas</li>
                    <li>Generación de boletas</li>
                    <li>Consulta de inventario</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
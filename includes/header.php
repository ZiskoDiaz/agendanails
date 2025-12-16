<?php
require_once dirname(__DIR__) . '/auth.php';
verificarLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <?php echo SITE_NAME; ?>
            </div>
            <div class="user-info" style="display: flex; align-items: center; gap: 1rem;">
                <span>Hola, <strong><?php echo htmlspecialchars(getNombreUsuario()); ?></strong></span>
                <?php if (esAdmin()): ?>
                    <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">ADMIN</span>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/logout.php" style="color: white; text-decoration: none; padding: 0.5rem 1rem; background: rgba(255,255,255,0.2); border-radius: 4px; font-size: 0.9rem;">
                    Salir
                </a>
            </div>
        </div>
    </header>
    
    <nav class="nav">
        <div class="nav-container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="<?php echo SITE_URL; ?>/agenda/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/agenda/') !== false ? 'active' : ''; ?>">
                Agenda
            </a>
            <a href="<?php echo SITE_URL; ?>/clientas/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/clientas/') !== false ? 'active' : ''; ?>">
                Clientas
            </a>
            <a href="<?php echo SITE_URL; ?>/servicios/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/servicios/') !== false ? 'active' : ''; ?>">
                Servicios
            </a>
            <a href="<?php echo SITE_URL; ?>/insumos/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/insumos/') !== false ? 'active' : ''; ?>">
                Insumos
            </a>
            <a href="<?php echo SITE_URL; ?>/proveedores/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/proveedores/') !== false ? 'active' : ''; ?>">
                Proveedores
            </a>
            <?php if (esAdmin()): ?>
            <a href="<?php echo SITE_URL; ?>/usuarios/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/usuarios/') !== false ? 'active' : ''; ?>">
                Usuarios
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <?php
        $alert = getAlert();
        if ($alert): ?>
            <div class="container">
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <?php echo htmlspecialchars($alert['message']); ?>
                </div>
            </div>
        <?php endif; ?>
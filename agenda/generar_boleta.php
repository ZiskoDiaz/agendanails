<?php
require_once '../config/config.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$cita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cita_id) {
    showAlert('Cita no encontrada', 'error');
    redirect('index.php');
}

// Obtener datos de la cita
$stmt = $db->prepare("
    SELECT c.*, cl.nombre as cliente_nombre, cl.telefono, cl.email
    FROM citas c 
    JOIN clientas cl ON c.cliente_id = cl.id 
    WHERE c.id = ?
");
$stmt->execute([$cita_id]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    showAlert('Cita no encontrada', 'error');
    redirect('index.php');
}

// Obtener servicios de la cita
$stmt = $db->prepare("
    SELECT cs.*, s.nombre, s.descripcion
    FROM cita_servicios cs
    JOIN servicios s ON cs.servicio_id = s.id
    WHERE cs.cita_id = ?
    ORDER BY s.nombre
");
$stmt->execute([$cita_id]);
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar número de boleta único
$numero_boleta = 'NL' . date('Ymd') . str_pad($cita_id, 4, '0', STR_PAD_LEFT);

// Si se solicita descargar PDF
if (isset($_GET['pdf'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="boleta_' . $numero_boleta . '.pdf"');
    // Aquí se podría integrar una librería como TCPDF o FPDF
    // Por ahora redirigimos a la vista de impresión
    redirect('?id=' . $cita_id . '&print=1');
}

$is_print = isset($_GET['print']);

if (!$is_print) {
    $page_title = 'Boleta de Servicio';
    require_once '../includes/header.php';
}
?>

<?php if (!$is_print): ?>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Detalle de Servicio</h2>
            <div style="display: flex; gap: 1rem;">
                <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
                <a href="?id=<?php echo $cita_id; ?>&pdf=1" class="btn btn-success">Descargar PDF</a>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </div>
        
        <div id="boleta-content">
<?php else: ?>
<!DOCTYPE html>
<html>
<head>
    <title>Boleta <?php echo $numero_boleta; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ff6b9d; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #ff6b9d; margin-bottom: 10px; }
        .info-section { margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f8f9fa; }
        .total-section { text-align: right; font-size: 16px; font-weight: bold; margin-top: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<?php endif; ?>

            <!-- Header -->
            <div class="header">
                <div class="logo">💅 Agenda Nails</div>
                <div>Sistema de Gestión de Salón de Manicura</div>
                <div style="font-size: 12px; color: #666; margin-top: 10px;">
                    Teléfono: +56 9 XXXX XXXX | Email: info@agendanails.cl
                </div>
            </div>

            <!-- Información de la boleta -->
            <div class="info-section">
                <div class="info-row">
                    <strong>Boleta N°:</strong>
                    <span><?php echo $numero_boleta; ?></span>
                </div>
                <div class="info-row">
                    <strong>Fecha de Servicio:</strong>
                    <span><?php echo formatDate($cita['fecha']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Hora:</strong>
                    <span><?php echo formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Fecha de Emisión:</strong>
                    <span><?php echo formatDateTime(date('Y-m-d H:i:s')); ?></span>
                </div>
            </div>

            <!-- Información del cliente -->
            <div class="info-section">
                <h3 style="color: #ff6b9d; border-bottom: 1px solid #ff6b9d; padding-bottom: 5px;">Datos del Cliente</h3>
                <div class="info-row">
                    <strong>Nombre:</strong>
                    <span><?php echo htmlspecialchars($cita['cliente_nombre']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Teléfono:</strong>
                    <span><?php echo htmlspecialchars($cita['telefono']); ?></span>
                </div>
                <?php if ($cita['email']): ?>
                <div class="info-row">
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($cita['email']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Detalle de servicios -->
            <div class="info-section">
                <h3 style="color: #ff6b9d; border-bottom: 1px solid #ff6b9d; padding-bottom: 5px;">Detalle de Servicios</h3>
                
                <?php if (!empty($servicios)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th style="text-align: right;">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $subtotal = 0; ?>
                            <?php foreach ($servicios as $servicio): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($servicio['descripcion'] ?: 'Sin descripción'); ?></td>
                                    <td style="text-align: right;"><?php echo formatCurrency($servicio['precio']); ?></td>
                                </tr>
                                <?php $subtotal += $servicio['precio']; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fa;">
                                <td colspan="2" style="text-align: right;"><strong>TOTAL</strong></td>
                                <td style="text-align: right;"><strong><?php echo formatCurrency($subtotal); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">Sin servicios registrados</p>
                <?php endif; ?>
            </div>

            <!-- Notas -->
            <?php if ($cita['notas']): ?>
            <div class="info-section">
                <h3 style="color: #ff6b9d; border-bottom: 1px solid #ff6b9d; padding-bottom: 5px;">Observaciones</h3>
                <p><?php echo nl2br(htmlspecialchars($cita['notas'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Estado del servicio -->
            <div class="info-section">
                <div class="info-row">
                    <strong>Estado del Servicio:</strong>
                    <span style="color: #059669; font-weight: bold;">
                        <?php echo ucfirst(str_replace('_', ' ', $cita['estado'])); ?>
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 12px; color: #666;">
                <p>Gracias por confiar en nuestros servicios</p>
                <p>¡Esperamos verte pronto!</p>
                <div style="margin-top: 20px;">
                    <p>Este documento es una boleta de servicios prestados</p>
                    <p>Generado automáticamente por Agenda Nails - <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></p>
                </div>
            </div>

<?php if (!$is_print): ?>
        </div>
    </div>
</div>

<style>
@media print {
    .header, .nav, .card-header, .no-print {
        display: none !important;
    }
    
    .container {
        max-width: none;
        margin: 0;
        padding: 0;
    }
    
    .card {
        box-shadow: none;
        border: none;
        padding: 0;
    }
    
    body {
        font-size: 12px;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
<?php else: ?>
</body>
</html>
<?php endif; ?>
<?php
/**
 * Ver Factura - Visualizacion detallada de una factura
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$db = Database::getConnection();

// Obtener ID de la factura
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: facturas.php');
    exit;
}

// Cargar factura
$stmt = $db->prepare("SELECT * FROM facturas WHERE id = :id");
$stmt->execute([':id' => $id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: facturas.php');
    exit;
}

$pageTitle = 'Factura ' . $invoice['numero_factura'] . ' - ' . APP_NAME;
$baseUrl = '..';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-start">
        <div>
            <h2 class="fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Factura <?php echo htmlspecialchars($invoice['numero_factura']); ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="facturas.php">Facturas</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($invoice['numero_factura']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <?php echo getStatusBadge($invoice['estado']); ?>
        </div>
    </div>
</div>

<!-- Acciones -->
<div class="mb-4">
    <a href="editar-factura.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary">
        <i class="bi bi-pencil me-2"></i>Editar
    </a>
    <form method="POST" action="reenviar.php" class="d-inline">
        <input type="hidden" name="id" value="<?php echo $invoice['id']; ?>">
        <?php echo csrfField(); ?>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-envelope me-2"></i>Reenviar Email
        </button>
    </form>
    <?php
    $pdfFile = PDF_PATH . $invoice['numero_factura'] . '.pdf';
    if (file_exists($pdfFile)):
    ?>
    <a href="../uploads/pdf/<?php echo urlencode($invoice['numero_factura']); ?>.pdf" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-file-pdf me-2"></i>Descargar PDF
    </a>
    <?php endif; ?>
    <a href="facturas.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver a la lista
    </a>
</div>

<!-- Detalles de la Factura -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small">Informacion de la Factura</h6>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Numero:</dt>
                    <dd class="col-sm-7 fw-semibold"><?php echo htmlspecialchars($invoice['numero_factura']); ?></dd>

                    <dt class="col-sm-5">Fecha:</dt>
                    <dd class="col-sm-7"><?php echo date('d/m/Y', strtotime($invoice['fecha'])); ?></dd>

                    <dt class="col-sm-5">Estado:</dt>
                    <dd class="col-sm-7"><?php echo getStatusBadge($invoice['estado']); ?></dd>

                    <dt class="col-sm-5">Bodega:</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['numero_bodega']); ?></dd>

                    <dt class="col-sm-5">Periodo:</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['periodo_facturado']); ?></dd>

                    <dt class="col-sm-5">Valor:</dt>
                    <dd class="col-sm-7 fs-5 fw-bold text-success"><?php echo formatCurrency($invoice['valor']); ?></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small">Datos del Cliente</h6>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nombre:</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['nombre_cliente']); ?></dd>

                    <dt class="col-sm-5">Email:</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['email'] ?? 'No proporcionado'); ?></dd>

                    <dt class="col-sm-5">Telefono:</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['telefono'] ?? 'No proporcionado'); ?></dd>
                </dl>

                <h6 class="text-muted text-uppercase small mt-4">Informacion Adicional</h6>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-5">PDF Generado:</dt>
                    <dd class="col-sm-7">
                        <?php if ($invoice['pdf_generado']): ?>
                            <span class="badge bg-success">Si</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Creada:</dt>
                    <dd class="col-sm-7"><?php echo date('d/m/Y H:i', strtotime($invoice['fecha_creacion'])); ?></dd>

                    <dt class="col-sm-5">Verificacion:</dt>
                    <dd class="col-sm-7">
                        <a href="<?php echo generateValidationUrl($invoice['numero_factura']); ?>" target="_blank" class="small">
                            <i class="bi bi-link-45deg"></i> Ver pagina publica
                        </a>
                    </dd>
                </dl>
            </div>
        </div>

        <?php if (!empty($invoice['observaciones'])): ?>
        <div class="mt-4">
            <h6 class="text-muted text-uppercase small">Observaciones</h6>
            <hr>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoice['observaciones'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

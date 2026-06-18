<?php
/**
 * Editar Factura - Modificar factura existente
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/pdf_generator.php';

requireAuth();

$db = Database::getConnection();
$errors = [];

// Obtener ID de la factura
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: facturas.php');
    exit;
}

// Cargar factura existente
$stmt = $db->prepare("SELECT * FROM facturas WHERE id = :id");
$stmt->execute([':id' => $id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: facturas.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Solicitud no valida. Intente nuevamente.';
    } else {
        $fecha = sanitizeInput($_POST['fecha'] ?? '');
        $nombreCliente = sanitizeInput($_POST['nombre_cliente'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $telefono = sanitizeInput($_POST['telefono'] ?? '');
        $numeroBodega = sanitizeInput($_POST['numero_bodega'] ?? '');
        $periodoFacturado = sanitizeInput($_POST['periodo_facturado'] ?? '');
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $observaciones = sanitizeInput($_POST['observaciones'] ?? '');
        $estado = sanitizeInput($_POST['estado'] ?? 'pendiente');

        // Validaciones
        if (empty($fecha)) {
            $errors[] = 'La fecha es obligatoria.';
        }
        if (empty($nombreCliente)) {
            $errors[] = 'El nombre del cliente es obligatorio.';
        }
        if (empty($numeroBodega)) {
            $errors[] = 'El numero de bodega es obligatorio.';
        }
        if (empty($periodoFacturado)) {
            $errors[] = 'El periodo facturado es obligatorio.';
        }
        if ($valor === false || $valor < 0) {
            $errors[] = 'El valor debe ser un numero valido mayor o igual a cero.';
        }
        if (!in_array($estado, ['pendiente', 'pagada', 'vencida', 'anulada'])) {
            $errors[] = 'El estado seleccionado no es valido.';
        }

        if (empty($errors)) {
            try {
                // Detectar si campos clave cambiaron (para regenerar PDF)
                $keyFieldsChanged = (
                    $invoice['nombre_cliente'] !== $nombreCliente ||
                    $invoice['valor'] != $valor ||
                    $invoice['numero_bodega'] !== $numeroBodega ||
                    $invoice['periodo_facturado'] !== $periodoFacturado
                );

                $stmt = $db->prepare("
                    UPDATE facturas SET
                        fecha = :fecha,
                        nombre_cliente = :nombre_cliente,
                        email = :email,
                        telefono = :telefono,
                        numero_bodega = :numero_bodega,
                        periodo_facturado = :periodo_facturado,
                        valor = :valor,
                        observaciones = :observaciones,
                        estado = :estado
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':fecha'            => $fecha,
                    ':nombre_cliente'   => $nombreCliente,
                    ':email'            => $email,
                    ':telefono'         => $telefono,
                    ':numero_bodega'    => $numeroBodega,
                    ':periodo_facturado'=> $periodoFacturado,
                    ':valor'            => $valor,
                    ':observaciones'    => $observaciones,
                    ':estado'           => $estado,
                    ':id'               => $id,
                ]);

                // Regenerar PDF si campos clave cambiaron using InvoicePDF class
                if ($keyFieldsChanged) {
                    try {
                        $pdfGenerator = new InvoicePDF();
                        $updatedData = [
                            'numero_factura'   => $invoice['numero_factura'],
                            'fecha'            => $fecha,
                            'nombre_cliente'   => $nombreCliente,
                            'email'            => $email,
                            'telefono'         => $telefono,
                            'numero_bodega'    => $numeroBodega,
                            'periodo_facturado'=> $periodoFacturado,
                            'valor'            => $valor,
                            'observaciones'    => $observaciones,
                            'estado'           => $estado,
                        ];
                        $pdfResult = $pdfGenerator->generate($updatedData);
                        if ($pdfResult !== false) {
                            // Marcar PDF como generado en la base de datos
                            $stmtPdf = $db->prepare("UPDATE facturas SET pdf_generado = 1 WHERE id = :id");
                            $stmtPdf->execute([':id' => $id]);
                        }
                    } catch (\Throwable $e) {
                        // PDF generation not available - non-fatal
                    }
                }

                header('Location: facturas.php?msg=updated');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Error al actualizar la factura: ' . $e->getMessage();
            }
        }

        // Recargar datos del formulario con lo enviado
        $invoice['fecha'] = $fecha;
        $invoice['nombre_cliente'] = $nombreCliente;
        $invoice['email'] = $email;
        $invoice['telefono'] = $telefono;
        $invoice['numero_bodega'] = $numeroBodega;
        $invoice['periodo_facturado'] = $periodoFacturado;
        $invoice['valor'] = $valor;
        $invoice['observaciones'] = $observaciones;
        $invoice['estado'] = $estado;
    }
}

$pageTitle = 'Editar Factura - ' . APP_NAME;
$baseUrl = '..';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Factura</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="facturas.php">Facturas</a></li>
                <li class="breadcrumb-item active">Editar <?php echo htmlspecialchars($invoice['numero_factura']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle me-2"></i>Error:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="editar-factura.php?id=<?php echo $id; ?>" id="formEditarFactura" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-3">
                <!-- Numero de Factura -->
                <div class="col-md-6">
                    <label for="numero_factura" class="form-label fw-semibold">Numero de Factura</label>
                    <input type="text" class="form-control" id="numero_factura" 
                           value="<?php echo htmlspecialchars($invoice['numero_factura']); ?>" readonly disabled>
                </div>

                <!-- Fecha -->
                <div class="col-md-6">
                    <label for="fecha" class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="fecha" name="fecha" required
                           value="<?php echo htmlspecialchars($invoice['fecha']); ?>">
                </div>

                <!-- Nombre Cliente -->
                <div class="col-md-6">
                    <label for="nombre_cliente" class="form-label fw-semibold">Nombre del Cliente <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" required
                           value="<?php echo htmlspecialchars($invoice['nombre_cliente']); ?>">
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($invoice['email'] ?? ''); ?>">
                </div>

                <!-- Telefono -->
                <div class="col-md-6">
                    <label for="telefono" class="form-label fw-semibold">Telefono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono"
                           value="<?php echo htmlspecialchars($invoice['telefono'] ?? ''); ?>">
                </div>

                <!-- Numero Bodega -->
                <div class="col-md-6">
                    <label for="numero_bodega" class="form-label fw-semibold">Numero de Bodega <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="numero_bodega" name="numero_bodega" required
                           value="<?php echo htmlspecialchars($invoice['numero_bodega']); ?>">
                </div>

                <!-- Periodo Facturado -->
                <div class="col-md-6">
                    <label for="periodo_facturado" class="form-label fw-semibold">Periodo Facturado <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="periodo_facturado" name="periodo_facturado" required
                           value="<?php echo htmlspecialchars($invoice['periodo_facturado']); ?>">
                </div>

                <!-- Valor -->
                <div class="col-md-6">
                    <label for="valor" class="form-label fw-semibold">Valor <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="valor" name="valor" required
                               step="0.01" min="0"
                               value="<?php echo htmlspecialchars($invoice['valor']); ?>">
                    </div>
                </div>

                <!-- Estado -->
                <div class="col-md-6">
                    <label for="estado" class="form-label fw-semibold">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="pendiente" <?php echo $invoice['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="pagada" <?php echo $invoice['estado'] === 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                        <option value="vencida" <?php echo $invoice['estado'] === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                        <option value="anulada" <?php echo $invoice['estado'] === 'anulada' ? 'selected' : ''; ?>>Anulada</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div class="col-12">
                    <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($invoice['observaciones'] ?? ''); ?></textarea>
                </div>

                <!-- Botones -->
                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                    <a href="facturas.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

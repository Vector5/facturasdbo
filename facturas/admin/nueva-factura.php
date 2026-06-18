<?php
/**
 * Nueva Factura - Crear nueva factura
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/pdf_generator.php';
require_once __DIR__ . '/../libs/email_sender.php';

requireAuth();

$db = Database::getConnection();
$errors = [];
$success = '';

// Generar numero de factura automatico
$numeroFactura = generateInvoiceNumber();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Solicitud no valida. Intente nuevamente.';
    } else {
        // NOTE: Ignore posted numero_factura - regenerate server-side to prevent tampering
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
        if (!in_array($estado, ['pendiente', 'pagada', 'vencida'])) {
            $errors[] = 'El estado seleccionado no es valido.';
        }

        if (empty($errors)) {
            try {
                // Use retry-based insert that regenerates invoice number server-side
                $insertData = [
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

                $result = insertInvoiceWithRetry($insertData);
                $invoiceId = $result['id'];
                $numeroFactura = $result['numero_factura'];

                // Preparar datos para PDF y email
                $invoiceData = array_merge($insertData, [
                    'numero_factura' => $numeroFactura,
                ]);

                $warnings = [];

                // Intentar generar PDF
                $pdfGenerator = new InvoicePDF();
                $pdfPath = $pdfGenerator->generate($invoiceData);
                if ($pdfPath === false) {
                    $warnings[] = 'PDF no generado: ' . $pdfGenerator->getError();
                }

                // Intentar enviar email si hay direccion de correo
                if (!empty($email)) {
                    $emailSender = new InvoiceEmail();
                    $emailBody = $emailSender->buildInvoiceEmailBody($invoiceData);
                    $subject = 'Factura ' . $numeroFactura . ' - ' . COMPANY_NAME;
                    $sent = $emailSender->send($email, $subject, $emailBody, $pdfPath ?: null);
                    if (!$sent) {
                        $warnings[] = 'Correo no enviado: ' . $emailSender->getError();
                    }
                }

                // Guardar advertencias en sesion si las hay
                if (!empty($warnings)) {
                    $_SESSION['invoice_warnings'] = $warnings;
                }

                header('Location: facturas.php?msg=created');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Error al guardar la factura: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Nueva Factura - ' . APP_NAME;
$baseUrl = '..';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-plus-circle me-2"></i>Nueva Factura</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="facturas.php">Facturas</a></li>
                <li class="breadcrumb-item active">Nueva Factura</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle me-2"></i>Error:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="" id="formNuevaFactura" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-3">
                <!-- Numero de Factura -->
                <div class="col-md-6">
                    <label for="numero_factura" class="form-label fw-semibold">Numero de Factura</label>
                    <input type="text" class="form-control" id="numero_factura" name="numero_factura" 
                           value="<?php echo htmlspecialchars($numeroFactura); ?>" readonly>
                    <div class="form-text">Generado automaticamente</div>
                </div>

                <!-- Fecha -->
                <div class="col-md-6">
                    <label for="fecha" class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="fecha" name="fecha" required
                           value="<?php echo htmlspecialchars($_POST['fecha'] ?? date('Y-m-d')); ?>">
                </div>

                <!-- Nombre Cliente -->
                <div class="col-md-6">
                    <label for="nombre_cliente" class="form-label fw-semibold">Nombre del Cliente <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" required
                           value="<?php echo htmlspecialchars($_POST['nombre_cliente'] ?? ''); ?>"
                           placeholder="Nombre completo del cliente">
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="correo@ejemplo.com">
                </div>

                <!-- Telefono -->
                <div class="col-md-6">
                    <label for="telefono" class="form-label fw-semibold">Telefono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono"
                           value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                           placeholder="+00 000 000 0000">
                </div>

                <!-- Numero Bodega -->
                <div class="col-md-6">
                    <label for="numero_bodega" class="form-label fw-semibold">Numero de Bodega <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="numero_bodega" name="numero_bodega" required
                           value="<?php echo htmlspecialchars($_POST['numero_bodega'] ?? ''); ?>"
                           placeholder="Ej: B-001">
                </div>

                <!-- Periodo Facturado -->
                <div class="col-md-6">
                    <label for="periodo_facturado" class="form-label fw-semibold">Periodo Facturado <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="periodo_facturado" name="periodo_facturado" required
                           value="<?php echo htmlspecialchars($_POST['periodo_facturado'] ?? ''); ?>"
                           placeholder="Ej: Enero 2024 - Marzo 2024">
                </div>

                <!-- Valor -->
                <div class="col-md-6">
                    <label for="valor" class="form-label fw-semibold">Valor <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="valor" name="valor" required
                               step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_POST['valor'] ?? ''); ?>"
                               placeholder="0.00">
                    </div>
                </div>

                <!-- Estado -->
                <div class="col-md-6">
                    <label for="estado" class="form-label fw-semibold">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="pendiente" <?php echo ($_POST['estado'] ?? 'pendiente') === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="pagada" <?php echo ($_POST['estado'] ?? '') === 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                        <option value="vencida" <?php echo ($_POST['estado'] ?? '') === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div class="col-12">
                    <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                              placeholder="Notas adicionales sobre la factura..."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                </div>

                <!-- Botones -->
                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Factura
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

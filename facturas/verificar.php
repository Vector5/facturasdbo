<?php
/**
 * Verificacion Publica de Factura
 * Esta pagina NO requiere autenticacion.
 * Permite verificar la autenticidad de una factura por su numero.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$invoice = null;
$error = '';
$searched = false;

// Obtener numero de factura desde GET
$facturaNum = sanitizeInput($_GET['factura'] ?? '');

if (!empty($facturaNum)) {
    $searched = true;
    $db = Database::getConnection();

    $stmt = $db->prepare("SELECT numero_factura, fecha, nombre_cliente, numero_bodega, periodo_facturado, valor, estado FROM facturas WHERE numero_factura = :numero");
    $stmt->execute([':numero' => $facturaNum]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        $error = 'Factura no encontrada';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Verificacion de Factura - <?php echo COMPANY_NAME; ?>">
    <title>Verificar Factura - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h1 class="fw-bold text-primary">
                        <i class="bi bi-shield-check me-2"></i><?php echo COMPANY_NAME; ?>
                    </h1>
                    <p class="text-muted">Sistema de Verificacion de Facturas</p>
                </div>

                <!-- Search Form -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="input-group">
                                <input type="text" class="form-control" name="factura" 
                                       placeholder="Ingrese el numero de factura (Ej: BOD-2024-00001)"
                                       value="<?php echo htmlspecialchars($facturaNum); ?>" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i> Verificar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($searched && $invoice): ?>
                <!-- Factura Verificada -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="text-success fw-bold">Factura Verificada</h3>
                        <p class="text-muted">Esta factura es autentica y se encuentra registrada en nuestro sistema.</p>
                    </div>
                    <div class="card-body border-top">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-end">Numero de Factura:</dt>
                            <dd class="col-sm-7 fw-semibold"><?php echo htmlspecialchars($invoice['numero_factura']); ?></dd>

                            <dt class="col-sm-5 text-end">Fecha:</dt>
                            <dd class="col-sm-7"><?php echo date('d/m/Y', strtotime($invoice['fecha'])); ?></dd>

                            <dt class="col-sm-5 text-end">Cliente:</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['nombre_cliente']); ?></dd>

                            <dt class="col-sm-5 text-end">Bodega:</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['numero_bodega']); ?></dd>

                            <dt class="col-sm-5 text-end">Periodo:</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($invoice['periodo_facturado']); ?></dd>

                            <dt class="col-sm-5 text-end">Valor:</dt>
                            <dd class="col-sm-7 fw-bold"><?php echo formatCurrency($invoice['valor']); ?></dd>

                            <dt class="col-sm-5 text-end">Estado:</dt>
                            <dd class="col-sm-7"><?php echo getStatusBadge($invoice['estado']); ?></dd>
                        </dl>
                    </div>
                </div>
                <?php elseif ($searched && !empty($error)): ?>
                <!-- Factura No Encontrada -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="text-danger fw-bold">Factura No Encontrada</h3>
                        <p class="text-muted">El numero de factura ingresado no existe en nuestro sistema. Verifique el numero e intente nuevamente.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. Todos los derechos reservados.
                    </small>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

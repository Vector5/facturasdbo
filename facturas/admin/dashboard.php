<?php
/**
 * Dashboard - Panel de Administracion
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$db = Database::getConnection();

// Stats: Total facturado (suma de facturas pagadas)
$stmt = $db->query("SELECT COALESCE(SUM(valor), 0) AS total FROM facturas WHERE estado = 'pagada'");
$totalFacturado = $stmt->fetch()['total'];

// Stats: Total facturas emitidas
$stmt = $db->query("SELECT COUNT(*) AS total FROM facturas");
$totalEmitidas = $stmt->fetch()['total'];

// Stats: Facturas pagadas
$stmt = $db->query("SELECT COUNT(*) AS total FROM facturas WHERE estado = 'pagada'");
$totalPagadas = $stmt->fetch()['total'];

// Stats: Facturas pendientes
$stmt = $db->query("SELECT COUNT(*) AS total FROM facturas WHERE estado = 'pendiente'");
$totalPendientes = $stmt->fetch()['total'];

// Stats: Facturas vencidas
$stmt = $db->query("SELECT COUNT(*) AS total FROM facturas WHERE estado = 'vencida'");
$totalVencidas = $stmt->fetch()['total'];

// Ingresos mensuales (ultimos 6 meses)
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(fecha, '%Y-%m') AS mes,
        SUM(valor) AS total
    FROM facturas
    WHERE estado = 'pagada'
        AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
    ORDER BY mes ASC
");
$monthlyIncome = $stmt->fetchAll();

// Ultimas 10 facturas
$stmt = $db->query("SELECT * FROM facturas ORDER BY fecha_creacion DESC LIMIT 10");
$recentInvoices = $stmt->fetchAll();

$pageTitle = 'Dashboard - ' . APP_NAME;
$baseUrl = '..';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
        <p class="text-muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrador'); ?></p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3 rounded-circle p-3">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <div>
                    <div class="stat-value fs-5 fw-bold"><?php echo formatCurrency($totalFacturado); ?></div>
                    <div class="stat-label text-muted small">Total Facturado</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3 rounded-circle p-3">
                    <i class="bi bi-file-earmark-text fs-4"></i>
                </div>
                <div>
                    <div class="stat-value fs-5 fw-bold"><?php echo $totalEmitidas; ?></div>
                    <div class="stat-label text-muted small">Facturas Emitidas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3 rounded-circle p-3">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
                <div>
                    <div class="stat-value fs-5 fw-bold"><?php echo $totalPagadas; ?></div>
                    <div class="stat-label text-muted small">Facturas Pagadas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3 rounded-circle p-3">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div>
                    <div class="stat-value fs-5 fw-bold"><?php echo $totalPendientes; ?></div>
                    <div class="stat-label text-muted small">Facturas Pendientes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3 rounded-circle p-3">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
                <div>
                    <div class="stat-value fs-5 fw-bold"><?php echo $totalVencidas; ?></div>
                    <div class="stat-label text-muted small">Facturas Vencidas</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Income Chart Data (hidden for JS) -->
<div id="monthly-income-data" data-months='<?php echo htmlspecialchars(json_encode($monthlyIncome), ENT_QUOTES, 'UTF-8'); ?>' style="display:none;"></div>

<!-- Recent Invoices Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="bi bi-clock me-2"></i>Ultimas Facturas Emitidas</h5>
        <a href="<?php echo $baseUrl; ?>/admin/facturas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Bodega</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInvoices)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No hay facturas registradas
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentInvoices as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($invoice['numero_factura']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($invoice['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($invoice['nombre_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['numero_bodega']); ?></td>
                        <td><?php echo formatCurrency($invoice['valor']); ?></td>
                        <td><?php echo getStatusBadge($invoice['estado']); ?></td>
                        <td>
                            <a href="ver-factura.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="editar-factura.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

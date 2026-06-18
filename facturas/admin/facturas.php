<?php
/**
 * Lista de Facturas - Visualizar, buscar y filtrar facturas
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$db = Database::getConnection();

// Parametros de busqueda y filtro
$search = sanitizeInput($_GET['search'] ?? '');
$estadoFilter = sanitizeInput($_GET['estado'] ?? '');
$fechaDesde = sanitizeInput($_GET['fecha_desde'] ?? '');
$fechaHasta = sanitizeInput($_GET['fecha_hasta'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Construir query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(nombre_cliente LIKE :search OR numero_factura LIKE :search2 OR numero_bodega LIKE :search3)";
    $params[':search'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}

if (!empty($estadoFilter) && in_array($estadoFilter, ['pendiente', 'pagada', 'vencida', 'anulada'])) {
    $where[] = "estado = :estado";
    $params[':estado'] = $estadoFilter;
}

if (!empty($fechaDesde)) {
    $where[] = "fecha >= :fecha_desde";
    $params[':fecha_desde'] = $fechaDesde;
}

if (!empty($fechaHasta)) {
    $where[] = "fecha <= :fecha_hasta";
    $params[':fecha_hasta'] = $fechaHasta;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Contar total
$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM facturas $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = max(1, ceil($totalRecords / $perPage));

// Obtener facturas
$stmt = $db->prepare("SELECT * FROM facturas $whereClause ORDER BY fecha_creacion DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$invoices = $stmt->fetchAll();

// Mensajes flash
$msg = $_GET['msg'] ?? '';
$messages = [
    'created' => ['type' => 'success', 'text' => 'Factura creada exitosamente.'],
    'updated' => ['type' => 'success', 'text' => 'Factura actualizada exitosamente.'],
    'deleted' => ['type' => 'success', 'text' => 'Factura eliminada exitosamente.'],
    'sent'    => ['type' => 'success', 'text' => 'Email reenviado exitosamente.'],
    'send_error' => ['type' => 'warning', 'text' => 'No se pudo enviar el email. Verifique la configuracion.'],
];

$pageTitle = 'Facturas - ' . APP_NAME;
$baseUrl = '..';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Facturas</h2>
            <p class="text-muted mb-0"><?php echo $totalRecords; ?> factura(s) encontrada(s)</p>
        </div>
        <a href="nueva-factura.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nueva Factura
        </a>
    </div>
</div>

<?php if (isset($messages[$msg])): ?>
<div class="alert alert-<?php echo $messages[$msg]['type']; ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i><?php echo $messages[$msg]['text']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3" id="filterForm">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Cliente, No. factura o bodega...">
            </div>
            <div class="col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $estadoFilter === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="pagada" <?php echo $estadoFilter === 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                    <option value="vencida" <?php echo $estadoFilter === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                    <option value="anulada" <?php echo $estadoFilter === 'anulada' ? 'selected' : ''; ?>>Anulada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                       value="<?php echo htmlspecialchars($fechaDesde); ?>">
            </div>
            <div class="col-md-2">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                       value="<?php echo htmlspecialchars($fechaHasta); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="facturas.php" class="btn btn-outline-secondary" title="Limpiar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Facturas -->
<div class="card border-0 shadow-sm">
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
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No se encontraron facturas
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($invoice['numero_factura']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($invoice['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($invoice['nombre_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['numero_bodega']); ?></td>
                        <td><?php echo formatCurrency($invoice['valor']); ?></td>
                        <td><?php echo getStatusBadge($invoice['estado']); ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="ver-factura.php?id=<?php echo $invoice['id']; ?>" class="btn btn-outline-info" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar-factura.php?id=<?php echo $invoice['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="reenviar.php" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $invoice['id']; ?>">
                                    <button type="submit" class="btn btn-outline-success" title="Reenviar email">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-outline-danger btn-delete" 
                                        data-id="<?php echo $invoice['id']; ?>"
                                        data-numero="<?php echo htmlspecialchars($invoice['numero_factura']); ?>"
                                        title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Paginacion -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Paginacion de facturas" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal de confirmacion de eliminacion -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminacion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Esta seguro que desea eliminar la factura <strong id="deleteInvoiceNumber"></strong>?</p>
                <p class="text-danger"><small>Esta accion no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="eliminar-factura.php" id="deleteForm">
                    <input type="hidden" name="id" id="deleteInvoiceId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

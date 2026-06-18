<?php
/**
 * Dashboard - Panel de Administración
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

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

<div class="row g-4">
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Total Facturas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Pagadas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Vencidas</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

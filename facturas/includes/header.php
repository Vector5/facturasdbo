<?php
/**
 * Header - Cabecera HTML compartida
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Facturación - Bodega de Almacenes">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo rtrim($baseUrl ?? '', '/') . '/assets/css/style.css'; ?>" rel="stylesheet">
</head>
<body>
<?php if ($isAuthenticated): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo rtrim($baseUrl ?? '', '/') . '/admin/dashboard.php'; ?>">
            <img src="<?php echo rtrim($baseUrl ?? '', '/') . '/' . COMPANY_LOGO; ?>" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>" style="height: 32px; width: auto;" class="me-2" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block'"><span style="display:none"><i class="bi bi-building me-2"></i></span><?php echo APP_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo rtrim($baseUrl ?? '', '/') . '/admin/dashboard.php'; ?>">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo rtrim($baseUrl ?? '', '/') . '/admin/facturas.php'; ?>">
                        <i class="bi bi-file-earmark-text me-1"></i>Facturas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo rtrim($baseUrl ?? '', '/') . '/admin/nueva-factura.php'; ?>">
                        <i class="bi bi-plus-circle me-1"></i>Nueva Factura
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-light" href="<?php echo rtrim($baseUrl ?? '', '/') . '/logout.php'; ?>">
                        <i class="bi bi-box-arrow-right me-1"></i>Salir
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="<?php echo $isAuthenticated ? 'container-fluid mt-4' : ''; ?>">

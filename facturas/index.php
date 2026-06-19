<?php
/**
 * Página de Login - Autenticación por PIN
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya está autenticado, redirigir al dashboard
initSession();
if (checkSession()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
$locked = false;

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Solicitud no valida. Intente nuevamente.';
    } else {
        $pin = $_POST['pin'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

        // Verificar bloqueo por intentos
        if (isLockedOut($ip)) {
            $locked = true;
            $error = 'Demasiados intentos fallidos. Intente nuevamente en ' . PIN_LOCKOUT_MINUTES . ' minutos.';
        } else {
            $admin = verifyPin($pin);

            if ($admin !== false) {
                // Acceso exitoso
                recordAccess($ip, 'exitoso', $browser);
                initSession();
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['authenticated'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nombre'];
                $_SESSION['last_activity'] = time();
                header('Location: admin/dashboard.php');
                exit;
            } else {
                // Acceso fallido
                recordAccess($ip, 'fallido', $browser);
                $error = 'PIN incorrecto. Intente nuevamente.';
            }
        }
    }
}

// Verificar si hay mensaje de sesión expirada
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error = 'Su sesión ha expirado. Ingrese nuevamente.';
}

$baseUrl = '';
$pageTitle = APP_NAME . ' - Iniciar Sesión';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <img src="<?php echo COMPANY_LOGO; ?>" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>" style="max-height: 120px; width: auto;">
            </div>
            <h1 class="login-title"><?php echo APP_NAME; ?></h1>
            <p class="login-subtitle"><?php echo COMPANY_NAME; ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?php if (!$locked): ?>
        <form method="POST" action="index.php" class="login-form">
            <?php echo csrfField(); ?>
            <div class="mb-4">
                <label for="pin" class="form-label fw-semibold">Ingrese su PIN de acceso</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password"
                           class="form-control"
                           id="pin"
                           name="pin"
                           placeholder="****"
                           maxlength="10"
                           required
                           autofocus
                           autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
            </button>
        </form>
        <?php endif; ?>

        <div class="login-footer">
            <small class="text-muted">Acceso restringido a personal autorizado</small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

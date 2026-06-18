<?php
/**
 * Sistema de Autenticación por PIN
 * Incluye manejo de sesiones, verificación de PIN,
 * bloqueo por intentos fallidos y registro de accesos.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Inicia la sesión si no está iniciada
 */
function initSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica si la sesión es válida y no ha expirado
 *
 * @return bool True si la sesión es válida
 */
function checkSession(): bool
{
    initSession();

    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        return false;
    }

    if (!isset($_SESSION['last_activity'])) {
        return false;
    }

    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > SESSION_TIMEOUT) {
        logout();
        return false;
    }

    // Actualizar marca de tiempo de última actividad
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Requiere autenticación. Redirige al login si no hay sesión válida.
 */
function requireAuth(): void
{
    if (!checkSession()) {
        header('Location: ' . getBaseUrl() . '/index.php?error=session_expired');
        exit;
    }
}

/**
 * Obtiene la URL base de la aplicación
 *
 * @return string URL base sin trailing slash
 */
function getBaseUrl(): string
{
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Si estamos en admin/, subir un nivel
    if (basename($scriptDir) === 'admin') {
        return dirname($scriptDir);
    }
    return $scriptDir;
}

/**
 * Verifica un PIN contra la base de datos
 *
 * @param string $pin PIN ingresado por el usuario
 * @return array|false Datos del administrador si es correcto, false si no
 */
function verifyPin(string $pin): array|false
{
    $db = Database::getConnection();

    $stmt = $db->prepare('SELECT id, nombre, pin_hash FROM administradores');
    $stmt->execute();
    $admins = $stmt->fetchAll();

    foreach ($admins as $admin) {
        if (password_verify($pin, $admin['pin_hash'])) {
            return $admin;
        }
    }

    return false;
}

/**
 * Registra un intento de acceso en la base de datos
 *
 * @param string $ip Dirección IP del cliente
 * @param string $result Resultado: 'exitoso' o 'fallido'
 * @param string $browser Cadena del User-Agent
 */
function recordAccess(string $ip, string $result, string $browser): void
{
    $db = Database::getConnection();

    $stmt = $db->prepare(
        'INSERT INTO accesos (direccion_ip, resultado, navegador, fecha_hora) VALUES (:ip, :resultado, :navegador, NOW())'
    );
    $stmt->execute([
        ':ip'        => $ip,
        ':resultado' => $result,
        ':navegador' => substr($browser, 0, 255),
    ]);
}

/**
 * Verifica si una IP está bloqueada por exceder intentos fallidos
 *
 * @param string $ip Dirección IP a verificar
 * @return bool True si la IP está bloqueada
 */
function isLockedOut(string $ip): bool
{
    $db = Database::getConnection();

    $lockoutTime = date('Y-m-d H:i:s', time() - (PIN_LOCKOUT_MINUTES * 60));

    $stmt = $db->prepare(
        'SELECT COUNT(*) as intentos FROM accesos WHERE direccion_ip = :ip AND resultado = :resultado AND fecha_hora > :desde'
    );
    $stmt->execute([
        ':ip'        => $ip,
        ':resultado' => 'fallido',
        ':desde'     => $lockoutTime,
    ]);

    $row = $stmt->fetch();
    return ($row['intentos'] >= PIN_MAX_ATTEMPTS);
}

/**
 * Cierra la sesión del usuario
 */
function logout(): void
{
    initSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

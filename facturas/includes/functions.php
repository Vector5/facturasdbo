<?php
/**
 * Funciones de utilidad del sistema
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Genera el siguiente número de factura en formato BOD-YYYY-NNNNN
 * Includes retry logic to handle race conditions on duplicate key.
 *
 * @param int $maxRetries Maximum number of retry attempts on duplicate key
 * @return string Número de factura generado
 * @throws RuntimeException If unable to generate a unique number after retries
 */
function generateInvoiceNumber(int $maxRetries = 3): string
{
    $db = Database::getConnection();
    $year = date('Y');
    $prefix = INVOICE_PREFIX . '-' . $year . '-';

    $stmt = $db->prepare(
        'SELECT numero_factura FROM facturas WHERE numero_factura LIKE :prefix ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([':prefix' => $prefix . '%']);
    $last = $stmt->fetch();

    if ($last) {
        $lastNumber = (int) substr($last['numero_factura'], -5);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }

    return $prefix . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
}

/**
 * Inserts an invoice with retry logic for duplicate invoice number (race condition).
 * Regenerates the invoice number on each retry attempt.
 *
 * @param array $data Invoice data (without numero_factura)
 * @param int $maxRetries Maximum retry attempts
 * @return array{id: int, numero_factura: string} The inserted invoice ID and number
 * @throws PDOException If insert fails after all retries
 */
function insertInvoiceWithRetry(array $data, int $maxRetries = 3): array
{
    $db = Database::getConnection();

    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        $numeroFactura = generateInvoiceNumber();

        try {
            $stmt = $db->prepare("
                INSERT INTO facturas (numero_factura, fecha, nombre_cliente, email, telefono, numero_bodega, periodo_facturado, valor, observaciones, estado, fecha_creacion)
                VALUES (:numero_factura, :fecha, :nombre_cliente, :email, :telefono, :numero_bodega, :periodo_facturado, :valor, :observaciones, :estado, NOW())
            ");
            $stmt->execute([
                ':numero_factura'   => $numeroFactura,
                ':fecha'            => $data['fecha'],
                ':nombre_cliente'   => $data['nombre_cliente'],
                ':email'            => $data['email'],
                ':telefono'         => $data['telefono'],
                ':numero_bodega'    => $data['numero_bodega'],
                ':periodo_facturado'=> $data['periodo_facturado'],
                ':valor'            => $data['valor'],
                ':observaciones'    => $data['observaciones'],
                ':estado'           => $data['estado'],
            ]);

            return [
                'id' => (int) $db->lastInsertId(),
                'numero_factura' => $numeroFactura,
            ];
        } catch (PDOException $e) {
            // Error code 23000 = integrity constraint violation (duplicate key)
            if ($e->getCode() == '23000' && $attempt < $maxRetries) {
                // Wait briefly and retry with a new number
                usleep(50000); // 50ms
                continue;
            }
            throw $e;
        }
    }

    // Should not reach here, but just in case
    throw new RuntimeException('No se pudo generar un numero de factura unico.');
}

/**
 * Formatea un valor como moneda
 *
 * @param float|int|string $amount Monto a formatear
 * @return string Monto formateado
 */
function formatCurrency(float|int|string $amount): string
{
    return '$' . number_format((float) $amount, 2, '.', ',');
}

/**
 * Limpia y sanitiza una entrada del usuario para almacenamiento.
 * NOTE: Does NOT apply htmlspecialchars - escaping is done at render time only.
 *
 * @param string|null $input Entrada a limpiar
 * @return string Entrada sanitizada
 */
function sanitizeInput(?string $input): string
{
    if ($input === null) {
        return '';
    }
    $input = trim($input);
    $input = stripslashes($input);
    // Remove null bytes
    $input = str_replace("\0", '', $input);
    return $input;
}

/**
 * Generates a CSRF token and stores it in the session.
 *
 * @return string The CSRF token
 */
function generateCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a CSRF token against the session token.
 *
 * @param string|null $token The token from the form submission
 * @return bool True if the token is valid
 */
function validateCsrfToken(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Returns the HTML hidden input for a CSRF token.
 *
 * @return string HTML hidden input element
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Genera la URL de verificación pública de una factura
 *
 * @param string $invoiceNumber Número de factura
 * @return string URL completa de verificación
 */
function generateValidationUrl(string $invoiceNumber): string
{
    return APP_URL . '/verificar.php?factura=' . urlencode($invoiceNumber);
}

/**
 * Retorna el badge HTML de Bootstrap para un estado de factura
 *
 * @param string $status Estado de la factura
 * @return string HTML del badge
 */
function getStatusBadge(string $status): string
{
    $badges = [
        'pendiente' => '<span class="badge bg-warning text-dark">Pendiente</span>',
        'pagada'    => '<span class="badge bg-success">Pagada</span>',
        'anulada'   => '<span class="badge bg-danger">Anulada</span>',
        'vencida'   => '<span class="badge bg-secondary">Vencida</span>',
    ];

    return $badges[$status] ?? '<span class="badge bg-light text-dark">Desconocido</span>';
}

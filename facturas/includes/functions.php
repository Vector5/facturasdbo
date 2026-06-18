<?php
/**
 * Funciones de utilidad del sistema
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Genera el siguiente número de factura en formato BOD-YYYY-NNNNN
 *
 * @return string Número de factura generado
 */
function generateInvoiceNumber(): string
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
 * Limpia y sanitiza una entrada del usuario
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
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
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

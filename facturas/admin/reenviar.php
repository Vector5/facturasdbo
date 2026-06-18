<?php
/**
 * Reenviar Email - Reenvia la factura por correo electronico
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: facturas.php');
    exit;
}

$db = Database::getConnection();

// Obtener ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: facturas.php');
    exit;
}

// Cargar factura
$stmt = $db->prepare("SELECT * FROM facturas WHERE id = :id");
$stmt->execute([':id' => $id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: facturas.php');
    exit;
}

// Intentar enviar email
$sent = false;
if (!empty($invoice['email']) && function_exists('sendInvoiceEmail')) {
    try {
        $sent = sendInvoiceEmail($id);
    } catch (\Throwable $e) {
        $sent = false;
    }
}

// Redirigir con mensaje de estado
if ($sent) {
    header('Location: facturas.php?msg=sent');
} else {
    header('Location: facturas.php?msg=send_error');
}
exit;

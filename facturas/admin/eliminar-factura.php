<?php
/**
 * Eliminar Factura - Procesa la eliminacion de una factura
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

// Verificar que la factura existe
$stmt = $db->prepare("SELECT id, numero_factura FROM facturas WHERE id = :id");
$stmt->execute([':id' => $id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: facturas.php');
    exit;
}

// Eliminar archivo PDF si existe
$pdfFile = PDF_PATH . $invoice['numero_factura'] . '.pdf';
if (file_exists($pdfFile)) {
    unlink($pdfFile);
}

// Eliminar registro de la base de datos
$stmt = $db->prepare("DELETE FROM facturas WHERE id = :id");
$stmt->execute([':id' => $id]);

// Redirigir con mensaje de confirmacion
header('Location: facturas.php?msg=deleted');
exit;

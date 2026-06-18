<?php
/**
 * Descargar PDF - Sirve archivos PDF para descarga
 * Si el PDF no existe, intenta regenerarlo.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: facturas.php?error=invalid');
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare('SELECT * FROM facturas WHERE id = :id');
$stmt->execute([':id' => $id]);
$factura = $stmt->fetch();

if (!$factura) {
    header('Location: facturas.php?error=not_found');
    exit;
}

$filename = $factura['numero_factura'] . '.pdf';
$filepath = PDF_PATH . $filename;

// Sanitize filename for Content-Disposition header to prevent header injection
$safeFilename = preg_replace('/[^A-Za-z0-9\-_.]/', '', $filename);

// Si el PDF no existe, intentar regenerarlo
if (!file_exists($filepath)) {
    require_once __DIR__ . '/../libs/pdf_generator.php';

    $pdfGenerator = new InvoicePDF();
    $result = $pdfGenerator->generate($factura);

    if ($result === false) {
        // No se pudo generar el PDF
        $_SESSION['error_msg'] = 'No se pudo generar el PDF: ' . $pdfGenerator->getError();
        header('Location: ver-factura.php?id=' . $id);
        exit;
    }

    $filepath = $result;
}

// Verificar que el archivo existe antes de servirlo
if (!file_exists($filepath)) {
    $_SESSION['error_msg'] = 'El archivo PDF no se encontro en el servidor.';
    header('Location: ver-factura.php?id=' . $id);
    exit;
}

// Servir el archivo PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filepath);
exit;

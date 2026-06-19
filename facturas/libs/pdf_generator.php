<?php
/**
 * Generador de PDF para Facturas
 * Utiliza DomPDF si esta disponible, de lo contrario retorna error.
 */

require_once __DIR__ . '/../config/app.php';

class InvoicePDF
{
    private string $error = '';

    /**
     * Obtiene el ultimo mensaje de error
     *
     * @return string Mensaje de error
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Verifica si DomPDF esta instalado
     *
     * @return bool
     */
    private function isDomPdfAvailable(): bool
    {
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            return false;
        }
        require_once $autoloadPath;
        return class_exists('Dompdf\\Dompdf');
    }

    /**
     * Genera el PDF de una factura
     *
     * @param array $invoiceData Datos de la factura con claves:
     *   - numero_factura: string
     *   - fecha: string (YYYY-MM-DD)
     *   - nombre_cliente: string
     *   - email: string
     *   - telefono: string
     *   - numero_bodega: string
     *   - periodo_facturado: string
     *   - valor: float
     *   - estado: string
     *   - observaciones: string
     * @return string|false Ruta del archivo PDF generado o false en caso de error
     */
    public function generate(array $invoiceData): string|false
    {
        if (!$this->isDomPdfAvailable()) {
            $this->error = 'DomPDF no esta instalado. Ejecute "composer require dompdf/dompdf" en el servidor.';
            return false;
        }

        try {
            $html = $this->buildHtmlTemplate($invoiceData);

            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();

            $pdfContent = $dompdf->output();

            // Asegurar que el directorio de PDFs existe
            $pdfDir = PDF_PATH;
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0755, true);
            }

            $filename = $invoiceData['numero_factura'] . '.pdf';
            $filepath = $pdfDir . $filename;

            if (file_put_contents($filepath, $pdfContent) === false) {
                $this->error = 'No se pudo guardar el archivo PDF. Verifique los permisos del directorio uploads/pdf/.';
                return false;
            }

            return $filepath;
        } catch (\Throwable $e) {
            $this->error = 'Error al generar el PDF: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Construye la plantilla HTML para el PDF
     *
     * @param array $data Datos de la factura
     * @return string HTML completo
     */
    private function buildHtmlTemplate(array $data): string
    {
        $numeroFactura = htmlspecialchars($data['numero_factura'] ?? '');
        $fecha = htmlspecialchars($data['fecha'] ?? '');
        $nombreCliente = htmlspecialchars($data['nombre_cliente'] ?? '');
        $email = htmlspecialchars($data['email'] ?? '');
        $telefono = htmlspecialchars($data['telefono'] ?? '');
        $numeroBodega = htmlspecialchars($data['numero_bodega'] ?? '');
        $periodoFacturado = htmlspecialchars($data['periodo_facturado'] ?? '');
        $valor = number_format((float)($data['valor'] ?? 0), 2, '.', ',');
        $estado = htmlspecialchars(ucfirst($data['estado'] ?? 'pendiente'));
        $observaciones = htmlspecialchars($data['observaciones'] ?? '');

        $validationUrl = generateValidationUrl($data['numero_factura'] ?? '');
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($validationUrl);

        $fechaFormateada = date('d/m/Y', strtotime($data['fecha'] ?? 'now'));
        $fechaEmision = date('d/m/Y H:i');

        $companyName = COMPANY_NAME;
        $companyAddress = COMPANY_ADDRESS;
        $companyPhone = COMPANY_PHONE;
        $companyEmail = COMPANY_EMAIL;

        // Logo embebido como base64 para compatibilidad con DomPDF
        $logoPath = __DIR__ . '/../' . COMPANY_LOGO;
        $logoHtml = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath) ?: 'image/png';
            $logoHtml = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" style="max-height: 80px; width: auto; margin-bottom: 8px;"><br>';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {$numeroFactura}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .container {
            padding: 30px 40px;
        }
        .header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header-table {
            width: 100%;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
        }
        .company-info {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .invoice-title {
            text-align: right;
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        .invoice-number {
            text-align: right;
            font-size: 14px;
            color: #e74c3c;
            font-weight: bold;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background-color: #2c3e50;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        .total-row {
            background-color: #2c3e50 !important;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }
        .total-row td {
            border-bottom: none !important;
            padding: 12px;
        }
        .client-info-table {
            width: 100%;
        }
        .client-info-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .client-info-table .label {
            font-weight: bold;
            color: #555;
            width: 140px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pagada {
            background-color: #27ae60;
            color: #fff;
        }
        .status-pendiente {
            background-color: #f39c12;
            color: #fff;
        }
        .status-vencida {
            background-color: #95a5a6;
            color: #fff;
        }
        .footer {
            margin-top: 30px;
            border-top: 2px solid #2c3e50;
            padding-top: 20px;
        }
        .footer-table {
            width: 100%;
        }
        .qr-section {
            text-align: center;
        }
        .qr-section img {
            width: 120px;
            height: 120px;
        }
        .qr-text {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }
        .seal-section {
            text-align: center;
            padding: 15px;
        }
        .seal-box {
            border: 2px dashed #bdc3c7;
            padding: 20px;
            border-radius: 8px;
        }
        .seal-title {
            font-size: 10px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .seal-line {
            border-top: 1px solid #333;
            width: 180px;
            margin: 30px auto 5px auto;
        }
        .seal-name {
            font-size: 10px;
            color: #555;
        }
        .observations {
            background-color: #f8f9fa;
            border-left: 4px solid #2c3e50;
            padding: 10px 15px;
            margin-top: 15px;
            font-size: 11px;
        }
        .watermark {
            font-size: 9px;
            color: #999;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td style="width: 60%;">
                        {$logoHtml}
                        <div class="company-info">
                            {$companyAddress}<br>
                            Tel: {$companyPhone}<br>
                            Email: {$companyEmail}
                        </div>
                    </td>
                    <td style="width: 40%;">
                        <div class="invoice-title">FACTURA</div>
                        <div class="invoice-number">{$numeroFactura}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Informacion de la Factura -->
        <div class="section">
            <table class="client-info-table">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="section-title">Datos del Cliente</div>
                        <table class="client-info-table">
                            <tr>
                                <td class="label">Cliente:</td>
                                <td>{$nombreCliente}</td>
                            </tr>
                            <tr>
                                <td class="label">Email:</td>
                                <td>{$email}</td>
                            </tr>
                            <tr>
                                <td class="label">Telefono:</td>
                                <td>{$telefono}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="section-title">Detalles de Factura</div>
                        <table class="client-info-table">
                            <tr>
                                <td class="label">Fecha:</td>
                                <td>{$fechaFormateada}</td>
                            </tr>
                            <tr>
                                <td class="label">Emision:</td>
                                <td>{$fechaEmision}</td>
                            </tr>
                            <tr>
                                <td class="label">Estado:</td>
                                <td><span class="status-badge status-{$data['estado']}">{$estado}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Detalle de la Factura -->
        <div class="section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Bodega</th>
                        <th>Periodo</th>
                        <th style="text-align: right;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Renta de Bodega</td>
                        <td>{$numeroBodega}</td>
                        <td>{$periodoFacturado}</td>
                        <td style="text-align: right;">\${$valor}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">\${$valor}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Observaciones -->
        {$this->buildObservationsHtml($observaciones)}

        <!-- Pie de pagina con QR y Sello -->
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td style="width: 35%; vertical-align: top;">
                        <div class="qr-section">
                            <img src="{$qrUrl}" alt="QR de Verificacion">
                            <div class="qr-text">
                                Escanee para verificar<br>la autenticidad de esta factura
                            </div>
                        </div>
                    </td>
                    <td style="width: 65%; vertical-align: top;">
                        <div class="seal-section">
                            <div class="seal-box">
                                <div class="seal-title">Sello y Firma Digital</div>
                                <div class="seal-line"></div>
                                <div class="seal-name">{$companyName}</div>
                                <div class="seal-name">Documento generado electronicamente</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="watermark">
            Documento generado el {$fechaEmision} | Verifique en: {$validationUrl}
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Construye el HTML de observaciones si existen
     *
     * @param string $observaciones
     * @return string HTML de observaciones
     */
    private function buildObservationsHtml(string $observaciones): string
    {
        if (empty($observaciones)) {
            return '';
        }

        return '<div class="section">
            <div class="section-title">Observaciones</div>
            <div class="observations">' . $observaciones . '</div>
        </div>';
    }
}

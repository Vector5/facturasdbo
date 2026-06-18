<?php
/**
 * Envio de emails para Facturas
 * Utiliza PHPMailer si esta disponible, de lo contrario retorna error.
 */

require_once __DIR__ . '/../config/app.php';

class InvoiceEmail
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
     * Verifica si PHPMailer esta instalado
     *
     * @return bool
     */
    private function isPhpMailerAvailable(): bool
    {
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            return false;
        }
        require_once $autoloadPath;
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    /**
     * Envia un email con la factura adjunta
     *
     * @param string $to Direccion de correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $body Contenido HTML del correo
     * @param string|null $attachmentPath Ruta al archivo PDF adjunto (opcional)
     * @return bool True si se envio correctamente, false en caso de error
     */
    public function send(string $to, string $subject, string $body, ?string $attachmentPath = null): bool
    {
        if (!$this->isPhpMailerAvailable()) {
            $this->error = 'PHPMailer no esta instalado. Ejecute "composer require phpmailer/phpmailer" en el servidor.';
            return false;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Configuracion SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_PORT == 465
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            // Adjunto
            if ($attachmentPath !== null && file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath, basename($attachmentPath));
            }

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $this->error = 'Error al enviar el correo: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Genera el cuerpo HTML del correo de factura
     *
     * @param array $invoiceData Datos de la factura
     * @return string HTML del email
     */
    public function buildInvoiceEmailBody(array $invoiceData): string
    {
        $numeroFactura = htmlspecialchars($invoiceData['numero_factura'] ?? '');
        $fecha = date('d/m/Y', strtotime($invoiceData['fecha'] ?? 'now'));
        $nombreCliente = htmlspecialchars($invoiceData['nombre_cliente'] ?? '');
        $numeroBodega = htmlspecialchars($invoiceData['numero_bodega'] ?? '');
        $periodoFacturado = htmlspecialchars($invoiceData['periodo_facturado'] ?? '');
        $valor = number_format((float)($invoiceData['valor'] ?? 0), 2, '.', ',');
        $estado = htmlspecialchars(ucfirst($invoiceData['estado'] ?? 'pendiente'));
        $validationUrl = htmlspecialchars(generateValidationUrl($invoiceData['numero_factura'] ?? ''));

        $companyName = COMPANY_NAME;
        $companyEmail = COMPANY_EMAIL;
        $companyPhone = COMPANY_PHONE;
        $appName = APP_NAME;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {$numeroFactura}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #2c3e50; padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">{$companyName}</h1>
                            <p style="margin: 5px 0 0 0; color: #bdc3c7; font-size: 14px;">{$appName}</p>
                        </td>
                    </tr>

                    <!-- Saludo -->
                    <tr>
                        <td style="padding: 30px 40px 10px 40px;">
                            <h2 style="margin: 0; color: #2c3e50; font-size: 20px;">Estimado/a {$nombreCliente},</h2>
                            <p style="color: #555; font-size: 14px; line-height: 1.6; margin-top: 15px;">
                                Le informamos que se ha generado una nueva factura a su nombre. A continuacion encontrara el resumen de la misma:
                            </p>
                        </td>
                    </tr>

                    <!-- Resumen de Factura -->
                    <tr>
                        <td style="padding: 10px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="padding: 8px 0; color: #555; font-size: 13px; border-bottom: 1px solid #e9ecef;">
                                                    <strong>Factura No.:</strong>
                                                </td>
                                                <td style="padding: 8px 0; color: #e74c3c; font-size: 13px; font-weight: bold; text-align: right; border-bottom: 1px solid #e9ecef;">
                                                    {$numeroFactura}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #555; font-size: 13px; border-bottom: 1px solid #e9ecef;">
                                                    <strong>Fecha:</strong>
                                                </td>
                                                <td style="padding: 8px 0; color: #333; font-size: 13px; text-align: right; border-bottom: 1px solid #e9ecef;">
                                                    {$fecha}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #555; font-size: 13px; border-bottom: 1px solid #e9ecef;">
                                                    <strong>Bodega:</strong>
                                                </td>
                                                <td style="padding: 8px 0; color: #333; font-size: 13px; text-align: right; border-bottom: 1px solid #e9ecef;">
                                                    {$numeroBodega}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #555; font-size: 13px; border-bottom: 1px solid #e9ecef;">
                                                    <strong>Periodo:</strong>
                                                </td>
                                                <td style="padding: 8px 0; color: #333; font-size: 13px; text-align: right; border-bottom: 1px solid #e9ecef;">
                                                    {$periodoFacturado}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #555; font-size: 13px; border-bottom: 1px solid #e9ecef;">
                                                    <strong>Estado:</strong>
                                                </td>
                                                <td style="padding: 8px 0; color: #333; font-size: 13px; text-align: right; border-bottom: 1px solid #e9ecef;">
                                                    {$estado}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; color: #2c3e50; font-size: 16px;">
                                                    <strong>TOTAL:</strong>
                                                </td>
                                                <td style="padding: 12px 0; color: #2c3e50; font-size: 16px; font-weight: bold; text-align: right;">
                                                    \${$valor}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Mensaje adjunto -->
                    <tr>
                        <td style="padding: 20px 40px;">
                            <p style="color: #555; font-size: 14px; line-height: 1.6;">
                                Adjunto a este correo encontrara el comprobante en formato PDF. Puede verificar la autenticidad de este documento en el siguiente enlace:
                            </p>
                            <p style="text-align: center; margin: 15px 0;">
                                <a href="{$validationUrl}" style="display: inline-block; background-color: #2c3e50; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-size: 14px;">
                                    Verificar Factura
                                </a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2c3e50; padding: 25px 40px; text-align: center;">
                            <p style="margin: 0; color: #bdc3c7; font-size: 12px;">
                                {$companyName}<br>
                                Tel: {$companyPhone} | Email: {$companyEmail}
                            </p>
                            <p style="margin: 10px 0 0 0; color: #7f8c8d; font-size: 11px;">
                                Este es un correo generado automaticamente. Por favor no responda a este mensaje.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        return $html;
    }
}

<?php
/**
 * Configuración General de la Aplicación
 * Sistema de Facturación - Bodega de Almacenes
 */

// Nombre de la aplicación
define('APP_NAME', 'Sistema de Facturación');
define('APP_URL', 'https://tudominio.com/facturas'); // CHANGE THIS: Set your actual domain

// Datos de la empresa
define('COMPANY_NAME', 'Bodega de Almacenes');
define('COMPANY_ADDRESS', 'Dirección de la empresa');
define('COMPANY_PHONE', '+00 000 000 0000');
define('COMPANY_EMAIL', 'info@tudominio.com');
define('COMPANY_LOGO', 'assets/img/logo.png');

// Configuración de seguridad
define('PIN_MAX_ATTEMPTS', 5);
define('PIN_LOCKOUT_MINUTES', 15);
define('SESSION_TIMEOUT', 1800); // 30 minutos en segundos

// Configuración de facturas
define('INVOICE_PREFIX', 'BOD');

// Ruta de almacenamiento de PDFs
define('PDF_PATH', __DIR__ . '/../uploads/pdf/');

// Configuracion SMTP para envio de correos
// CHANGE THESE: Replace with your actual SMTP credentials before deployment
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'facturacion@tudominio.com');      // CHANGE THIS
define('SMTP_PASS', 'tu_contraseña_smtp');              // CHANGE THIS
define('SMTP_FROM_EMAIL', 'facturacion@tudominio.com'); // CHANGE THIS
define('SMTP_FROM_NAME', 'Bodega de Almacenes');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

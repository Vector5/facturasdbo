<?php
/**
 * Configuración General de la Aplicación
 * Sistema de Facturación - D-Bodega
 */

// Nombre de la aplicación
define('APP_NAME', 'Sistema de Facturación');
define('APP_URL', 'https://shinyapple.net/facturas');

// Datos de la empresa
define('COMPANY_NAME', 'D-Bodega');
define('COMPANY_ADDRESS', 'Envigado Antióquia');
define('COMPANY_PHONE', '+57');
define('COMPANY_EMAIL', '');
// IMPORTANTE: COMPANY_LOGO debe ser SOLO la ruta al archivo de imagen.
// NO poner etiquetas HTML aqui (ej: <img src="...">), porque el sistema
// genera su propia etiqueta <img> y se rompe si ya viene con HTML.
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
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'dbodega@shinyapple.net');
define('SMTP_PASS', 'EaNrN5uL5;');
define('SMTP_FROM_EMAIL', 'dbodega@shinyapple.net');
define('SMTP_FROM_NAME', 'D-Bodega');

// Zona horaria
date_default_timezone_set('America/Bogota');

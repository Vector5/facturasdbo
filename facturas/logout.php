<?php
/**
 * Cerrar Sesión
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

logout();

header('Location: index.php');
exit;

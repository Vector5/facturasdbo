<?php
/**
 * Configuración de Base de Datos
 * Sistema de Facturación - Bodega de Almacenes
 *
 * Modifique las constantes con los datos de su servidor.
 */

// Credenciales de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'facturas_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Clase Database - Conexión PDO singleton
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Obtener la instancia de conexión PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Error de conexión a la base de datos: ' . $e->getMessage());
                die('Error de conexión a la base de datos. Consulte los logs del servidor.');
            }
        }

        return self::$instance;
    }

    /**
     * Prevenir clonación
     */
    private function __clone()
    {
    }
}

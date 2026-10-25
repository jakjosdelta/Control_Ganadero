<?php
// Configuración de sesión ANTES de iniciarla
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '0'); // Cambiar a 1 en producción con HTTPS
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '7200'); // 2 horas

// Iniciar sesión (necesario para CSRF, flash messages, rate limit)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de conexión a MySQL local
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'control_ganadero');
define('DB_CHARSET', 'utf8mb4');

// Configuración de zona horaria
date_default_timezone_set('America/Guayaquil');

// Directorio para imágenes
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Cargar configuración de la app
require_once __DIR__ . '/app.php';

// Cargar utilidades de seguridad
require_once __DIR__ . '/../core/Security.php';

// Headers de seguridad (se envían antes de cualquier output)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // CSP básica - ajustar según necesidades (CDN de Bootstrap/FontAwesome)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Función auxiliar para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
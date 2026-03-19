<?php
/**
 * PHPUnit Test Bootstrap for myadmin-zonemta-mail
 *
 * Sets up the minimal environment needed for testing the ZoneMTA Mail plugin
 * without requiring the full MyAdmin application stack or MongoDB.
 */

// Try to load the package autoloader, fall back to parent project autoloader
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$autoloaderFound = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    // Register a minimal PSR-4 autoloader for the package namespace
    spl_autoload_register(function ($class) {
        $prefix = 'Detain\\MyAdminZoneMTAMail\\';
        $baseDir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Suppress deprecation/notice noise for cleaner test output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// Define constants the plugin expects when calling external functions
if (!defined('ZONEMTA_USERNAME')) {
    define('ZONEMTA_USERNAME', 'test_user');
}
if (!defined('ZONEMTA_PASSWORD')) {
    define('ZONEMTA_PASSWORD', 'test_pass');
}
if (!defined('ZONEMTA_HOST')) {
    define('ZONEMTA_HOST', 'localhost');
}
if (!defined('MXTOOLBOX_AUTH_TOKEN')) {
    define('MXTOOLBOX_AUTH_TOKEN', 'test_token');
}

// Set up $GLOBALS['tf'] stub so vendor get_service_define() works
$GLOBALS['tf'] = new class {
    /** @var object */
    public object $history;

    public function __construct()
    {
        $this->history = new class {
            public function add(...$args) {
                // No-op for testing
            }
        };
    }

    public function get_service_define(string $name)
    {
        $defines = [
            'MAIL_ZONEMTA' => 100,
        ];
        return $defines[$name] ?? 0;
    }
};

// Stub global functions the plugin calls

if (!function_exists('myadmin_log')) {
    function myadmin_log($section, $level, $message, $line = '', $file = '', $module = '', $id = '') {
        // No-op for testing
    }
}

if (!function_exists('function_requirements')) {
    function function_requirements($func) {
        // No-op for testing
    }
}

if (!function_exists('get_service_define')) {
    function get_service_define($name) {
        $defines = [
            'MAIL_ZONEMTA' => 100,
        ];
        return $defines[$name] ?? 0;
    }
}

if (!function_exists('get_module_settings')) {
    function get_module_settings($module) {
        return ['PREFIX' => 'mail', 'TABLE' => 'mail', 'TBLNAME' => 'Mail'];
    }
}

if (!function_exists('get_module_db')) {
    function get_module_db($module) {
        return new class {
            public function query($sql = '', $line = '', $file = '') {}
            public function next_record($type = null) { return false; }
            public function num_rows() { return 0; }
            public function f($n) { return 0; }
            public function getLastInsertId($table = '', $field = '') { return 1; }
            public function real_escape($str) { return addslashes($str); }
        };
    }
}

if (!function_exists('run_event')) {
    function run_event($event, $default = false, $module = '') {
        return $default;
    }
}

if (!function_exists('request_log')) {
    function request_log($module, $custid, $function, $service, $action, $data, $result, $id) {
        // No-op for testing
    }
}

if (!function_exists('mail_get_password')) {
    function mail_get_password($id, $custid) {
        return 'test_password_123';
    }
}

if (!function_exists('generate_password')) {
    function generate_password($length = 20, $chars = 'lud') {
        return 'generated_password_12';
    }
}

if (!function_exists('mail_welcome_email')) {
    function mail_welcome_email($id) {
        // No-op for testing
    }
}

if (!function_exists('api_register')) {
    function api_register($name, $params, $return, $description) {
        // No-op for testing
    }
}

// Provide a gettext stub if not available
if (!function_exists('_')) {
    function _($text) {
        return $text;
    }
}

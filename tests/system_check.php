<?php

/**
 * ISO 27001 Platform - System Check
 * Verifica configuración, BD, rutas y seguridad
 */

echo "===========================================\n";
echo "ISO 27001 Platform - System Check\n";
echo "===========================================\n\n";

$errors = [];
$warnings = [];

// 1. PHP Version
echo "[1] Checking PHP version... ";
if (version_compare(PHP_VERSION, '8.3.0', '>=')) {
    echo "✓ PHP " . PHP_VERSION . "\n";
} else {
    echo "✗ FAIL\n";
    $errors[] = "PHP 8.3+ required, current: " . PHP_VERSION;
}

// 2. Required Extensions
echo "[2] Checking PHP extensions... ";
$required = ['pdo', 'pdo_mysql', 'fileinfo', 'mbstring', 'openssl'];
$missing = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}
if (empty($missing)) {
    echo "✓ All required\n";
} else {
    echo "✗ Missing: " . implode(', ', $missing) . "\n";
    $errors[] = "Missing extensions: " . implode(', ', $missing);
}

// 3. .env file
echo "[3] Checking .env file... ";
if (file_exists(__DIR__ . '/../.env')) {
    echo "✓ Exists\n";
} else {
    echo "✗ NOT FOUND\n";
    $errors[] = ".env file missing - copy from .env.example";
}

// 4. Composer autoload
echo "[4] Checking Composer autoload... ";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "✓ Exists\n";
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    echo "✗ NOT FOUND\n";
    $errors[] = "Run: composer install";
    die("\nCannot continue without autoload.\n");
}

// 5. Load environment
echo "[5] Loading environment variables... ";
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    echo "✓ Loaded\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $errors[] = "Environment loading failed";
}

// 6. Database connection
echo "[6] Testing database connection... ";
try {
    $db = App\Core\Database::getInstance();
    $pdo = $db->getConnection();
    echo "✓ Connected\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $errors[] = "Database connection failed";
}

// 7. Check tables
echo "[7] Checking database tables... ";
if (isset($pdo)) {
    try {
        $result = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $required_tables = ['empresas', 'usuarios', 'controles', 'soa_entries', 'gap_items', 
                           'evidencias', 'requerimientos_base', 'audit_logs'];
        $missing_tables = array_diff($required_tables, $result);
        
        if (empty($missing_tables)) {
            echo "✓ All tables exist (" . count($result) . ")\n";
        } else {
            echo "✗ Missing: " . implode(', ', $missing_tables) . "\n";
            $warnings[] = "Run: php database/migrations/001_initial_schema.php";
        }
    } catch (Exception $e) {
        echo "✗ FAIL: " . $e->getMessage() . "\n";
        $errors[] = "Cannot check tables";
    }
}

// 8. Check seed data
echo "[8] Checking seed data... ";
if (isset($pdo)) {
    try {
        $dominios = $pdo->query("SELECT COUNT(*) FROM controles_dominio")->fetchColumn();
        $controles = $pdo->query("SELECT COUNT(*) FROM controles")->fetchColumn();
        $requerimientos = $pdo->query("SELECT COUNT(*) FROM requerimientos_base")->fetchColumn();
        
        if ($dominios == 4 && $controles == 93 && $requerimientos == 7) {
            echo "✓ Seeds OK (D:$dominios C:$controles R:$requerimientos)\n";
        } else {
            echo "⚠ Incomplete (D:$dominios C:$controles R:$requerimientos)\n";
            $warnings[] = "Expected: D:4 C:93 R:7";
        }
    } catch (Exception $e) {
        echo "✗ FAIL\n";
        $warnings[] = "Cannot verify seed data";
    }
}

// 9. Directory permissions
echo "[9] Checking directory permissions... ";
$dirs = [
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../storage/cache',
    __DIR__ . '/../storage/sessions',
    __DIR__ . '/../public/uploads'
];
$permission_errors = [];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_writable($dir)) {
        $permission_errors[] = basename(dirname($dir)) . '/' . basename($dir);
    }
}
if (empty($permission_errors)) {
    echo "✓ All writable\n";
} else {
    echo "✗ Not writable: " . implode(', ', $permission_errors) . "\n";
    $errors[] = "Fix permissions: chmod 775 " . implode(' ', $permission_errors);
}

// 10. Security config
echo "[10] Checking security config... ";
$app_key = $_ENV['APP_KEY'] ?? '';
if (strlen($app_key) >= 32) {
    echo "✓ APP_KEY set\n";
} else {
    echo "✗ APP_KEY too short or missing\n";
    $errors[] = "Generate APP_KEY: php -r \"echo bin2hex(random_bytes(16));\"";
}

// 11. Router test
echo "[11] Testing Router... ";
try {
    $router = new App\Core\Router();
    require __DIR__ . '/../routes/web.php';
    echo "✓ Routes loaded\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $errors[] = "Router initialization failed";
}

// 12. Session test
echo "[12] Testing Session... ";
try {
    App\Core\Session::start();
    App\Core\Session::put('test', 'value');
    $test = App\Core\Session::get('test');
    if ($test === 'value') {
        echo "✓ Working\n";
    } else {
        echo "✗ FAIL\n";
        $errors[] = "Session storage not working";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $errors[] = "Session initialization failed";
}

// 13. CSRF Token
echo "[13] Testing CSRF... ";
try {
    $token = generate_token(32);
    if (strlen($token) === 64) { // 32 bytes = 64 hex chars
        echo "✓ Token generation OK\n";
    } else {
        echo "✗ FAIL\n";
        $errors[] = "CSRF token generation failed";
    }
} catch (Exception $e) {
    echo "✗ FAIL\n";
    $errors[] = "CSRF helper not available";
}

// Results
echo "\n===========================================\n";
echo "RESULTS\n";
echo "===========================================\n";

if (empty($errors) && empty($warnings)) {
    echo "✓ ALL CHECKS PASSED - System ready\n";
    exit(0);
}

if (!empty($warnings)) {
    echo "\n⚠ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". $warning\n";
    }
}

if (!empty($errors)) {
    echo "\n✗ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
    echo "\nFix errors before proceeding.\n";
    exit(1);
}

echo "\nSystem has warnings but can proceed.\n";
exit(0);

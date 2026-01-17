<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\LogService;

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    $dotenv->required(['APP_KEY', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']);

} catch (Exception $e) {
    die('Error loading environment variables: ' . $e->getMessage());
}

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function(Throwable $exception) {
    LogService::logException($exception);
    
    http_response_code(500);
    
    if (config('app.debug')) {
        echo '<pre>';
        echo 'Error: ' . $exception->getMessage() . "\n";
        echo 'File: ' . $exception->getFile() . ':' . $exception->getLine() . "\n";
        echo 'Trace: ' . $exception->getTraceAsString();
        echo '</pre>';
    } else {
        echo 'Error interno del servidor';
    }
});

register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        LogService::critical('Fatal error', [
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
        
        http_response_code(500);
        echo 'Error fatal del servidor';
    }
});

Session::start();

Database::getInstance();

$router = new Router();

require_once __DIR__ . '/../routes/web.php';

$request = new Request();

$router->dispatch($request);

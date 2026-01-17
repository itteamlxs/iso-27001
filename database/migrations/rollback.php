<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Services\LogService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "====================================\n";
echo "ISO 27001 - Database Rollback\n";
echo "====================================\n\n";

echo "⚠ ADVERTENCIA: Esta operación eliminará TODAS las tablas y datos\n";
echo "¿Está seguro de continuar? (escriba 'SI' para confirmar): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'SI') {
    echo "\nOperación cancelada\n";
    exit(0);
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "\nEliminando tablas...\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = [
        'audit_logs',
        'requerimientos_controles',
        'empresa_requerimientos',
        'requerimientos_base',
        'evidencias',
        'acciones',
        'gap_items',
        'soa_entries',
        'controles',
        'controles_dominio',
        'usuarios',
        'empresas'
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "✓ Tabla '{$table}' eliminada\n";
        } catch (Exception $e) {
            echo "⚠ No se pudo eliminar '{$table}': " . $e->getMessage() . "\n";
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✓ Rollback completado\n";
    
    LogService::warning('Database rollback executed', [
        'tables_dropped' => count($tables)
    ]);
    
} catch (Exception $e) {
    echo "\n✗ Error en rollback: " . $e->getMessage() . "\n";
    LogService::error('Database rollback failed', [
        'error' => $e->getMessage()
    ]);
    exit(1);
}

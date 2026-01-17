<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Services\LogService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "====================================\n";
echo "ISO 27001 - Database Migration\n";
echo "====================================\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Ejecutar schema
    echo "[1/4] Ejecutando schema principal...\n";
    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    $pdo->exec($schema);
    echo "✓ Schema creado correctamente\n\n";
    
    // Ejecutar seeds
    echo "[2/4] Insertando dominios...\n";
    $dominios = file_get_contents(__DIR__ . '/../seeds/01_dominios.sql');
    $pdo->exec($dominios);
    echo "✓ 4 dominios insertados\n\n";
    
    echo "[3/4] Insertando controles ISO 27001...\n";
    $controles = file_get_contents(__DIR__ . '/../seeds/02_controles.sql');
    $pdo->exec($controles);
    echo "✓ 93 controles insertados\n\n";
    
    echo "[4/4] Insertando requerimientos obligatorios...\n";
    $requerimientos = file_get_contents(__DIR__ . '/../seeds/03_requerimientos.sql');
    $pdo->exec($requerimientos);
    echo "✓ 7 requerimientos insertados\n\n";
    
    // Verificación
    echo "====================================\n";
    echo "Verificación de datos:\n";
    echo "====================================\n";
    
    $stats = [
        'dominios' => $pdo->query("SELECT COUNT(*) FROM controles_dominio")->fetchColumn(),
        'controles' => $pdo->query("SELECT COUNT(*) FROM controles")->fetchColumn(),
        'requerimientos' => $pdo->query("SELECT COUNT(*) FROM requerimientos_base")->fetchColumn(),
        'relaciones' => $pdo->query("SELECT COUNT(*) FROM requerimientos_controles")->fetchColumn(),
    ];
    
    echo "Dominios: {$stats['dominios']}\n";
    echo "Controles: {$stats['controles']}\n";
    echo "Requerimientos: {$stats['requerimientos']}\n";
    echo "Relaciones controles-requerimientos: {$stats['relaciones']}\n\n";
    
    if ($stats['dominios'] == 4 && $stats['controles'] == 93 && $stats['requerimientos'] == 7) {
        echo "✓ Migración completada exitosamente\n";
        LogService::info('Database migration completed', $stats);
    } else {
        echo "⚠ Advertencia: Algunos datos pueden estar incompletos\n";
        LogService::warning('Database migration completed with warnings', $stats);
    }
    
} catch (Exception $e) {
    echo "✗ Error en migración: " . $e->getMessage() . "\n";
    LogService::error('Database migration failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit(1);
}

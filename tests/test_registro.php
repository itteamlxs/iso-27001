<?php

/**
 * Test de Registro de Empresa
 * Simula el proceso completo de registro
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Core\Session;
use App\Core\TenantContext;
use App\Services\AuthService;
use App\Services\LogService;

echo "===========================================\n";
echo "TEST REGISTRO DE EMPRESA\n";
echo "===========================================\n\n";

// Load environment
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    echo "✓ Environment cargado\n";
} catch (Exception $e) {
    die("✗ Error cargando .env: " . $e->getMessage() . "\n");
}

// Iniciar sesión
Session::start();
echo "✓ Sesión iniciada\n";

// Verificar BD
try {
    $db = Database::getInstance();
    echo "✓ Conexión BD establecida\n\n";
} catch (Exception $e) {
    die("✗ Error BD: " . $e->getMessage() . "\n");
}

// Datos de prueba
$empresaData = [
    'nombre' => 'Test Company ' . time(),
    'ruc' => '10000000' . rand(100, 999),
    'sector' => 'Tecnología',
    'telefono' => '+51999888777',
    'email' => 'contacto@test' . time() . '.com',
    'direccion' => 'Av Test 123'
];

$userData = [
    'nombre' => 'Admin Test',
    'email' => 'admin@test' . time() . '.com',
    'password' => 'Test1234!',
    'password_confirmation' => 'Test1234!'
];

echo "DATOS DE PRUEBA:\n";
echo "Empresa: {$empresaData['nombre']}\n";
echo "RUC: {$empresaData['ruc']}\n";
echo "Usuario: {$userData['nombre']}\n";
echo "Email: {$userData['email']}\n\n";

// Ejecutar registro
echo "Ejecutando registro...\n";
echo str_repeat("-", 43) . "\n";

try {
    $authService = new AuthService();
    $result = $authService->register($empresaData, $userData);
    
    if ($result['success']) {
        echo "\n✓ REGISTRO EXITOSO\n\n";
        
        $empresaId = $result['empresa_id'];
        $userId = $result['user_id'];
        
        echo "IDs creados:\n";
        echo "  - empresa_id: $empresaId\n";
        echo "  - user_id: $userId\n\n";
        
        // Verificar datos creados
        echo "Verificando datos...\n";
        echo str_repeat("-", 43) . "\n";
        
        // 1. Empresa
        $empresa = $db->fetch("SELECT * FROM empresas WHERE id = ?", [$empresaId]);
        if ($empresa) {
            echo "✓ Empresa creada: {$empresa['nombre']}\n";
        } else {
            echo "✗ Empresa NO encontrada\n";
        }
        
        // 2. Usuario
        $usuario = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$userId]);
        if ($usuario) {
            echo "✓ Usuario creado: {$usuario['nombre']} ({$usuario['rol']})\n";
            echo "  - Estado: {$usuario['estado']}\n";
        } else {
            echo "✗ Usuario NO encontrado\n";
        }
        
        // 3. SOA Entries
        $soaCount = $db->fetch(
            "SELECT COUNT(*) as total FROM soa_entries WHERE empresa_id = ?", 
            [$empresaId]
        );
        echo "✓ SOA Entries: {$soaCount['total']}/93\n";
        
        if ($soaCount['total'] == 93) {
            echo "  - Todos los controles asignados ✓\n";
            
            // Verificar estados
            $estados = $db->fetch(
                "SELECT 
                    SUM(CASE WHEN aplicable = 1 THEN 1 ELSE 0 END) as aplicables,
                    SUM(CASE WHEN estado = 'no_implementado' THEN 1 ELSE 0 END) as no_implementados
                 FROM soa_entries WHERE empresa_id = ?",
                [$empresaId]
            );
            echo "  - Aplicables: {$estados['aplicables']}\n";
            echo "  - No implementados: {$estados['no_implementados']}\n";
        } else {
            echo "  ✗ Faltan controles\n";
        }
        
        // 4. Requerimientos
        $reqCount = $db->fetch(
            "SELECT COUNT(*) as total FROM empresa_requerimientos WHERE empresa_id = ?",
            [$empresaId]
        );
        echo "✓ Requerimientos: {$reqCount['total']}/7\n";
        
        if ($reqCount['total'] == 7) {
            echo "  - Todos los requerimientos asignados ✓\n";
            
            // Verificar estados
            $reqEstados = $db->fetchAll(
                "SELECT er.estado, COUNT(*) as total 
                 FROM empresa_requerimientos er 
                 WHERE er.empresa_id = ? 
                 GROUP BY er.estado",
                [$empresaId]
            );
            foreach ($reqEstados as $estado) {
                echo "  - {$estado['estado']}: {$estado['total']}\n";
            }
        } else {
            echo "  ✗ Faltan requerimientos\n";
        }
        
        // 5. Sesión
        echo "\nEstado de sesión:\n";
        echo "  - user_id: " . Session::get('user_id') . "\n";
        echo "  - empresa_id: " . Session::get('empresa_id') . "\n";
        
        // Cleanup (opcional)
        echo "\n¿Eliminar datos de prueba? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $cleanup = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($cleanup) === 'y') {
            echo "\nEliminando datos de prueba...\n";
            
            $db->beginTransaction();
            try {
                // Orden inverso por foreign keys
                $db->delete('empresa_requerimientos', 'empresa_id = ?', [$empresaId]);
                $db->delete('soa_entries', 'empresa_id = ?', [$empresaId]);
                $db->delete('usuarios', 'empresa_id = ?', [$empresaId]);
                $db->delete('empresas', 'id = ?', [$empresaId]);
                $db->commit();
                echo "✓ Datos eliminados\n";
            } catch (Exception $e) {
                $db->rollback();
                echo "✗ Error al eliminar: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n===========================================\n";
        echo "TEST COMPLETADO EXITOSAMENTE\n";
        echo "===========================================\n";
        exit(0);
        
    } else {
        echo "\n✗ REGISTRO FALLÓ\n\n";
        
        if (isset($result['errors'])) {
            echo "Errores encontrados:\n";
            foreach ($result['errors'] as $field => $errors) {
                echo "  - $field:\n";
                foreach ($errors as $error) {
                    echo "    * $error\n";
                }
            }
        }
        
        echo "\n===========================================\n";
        echo "TEST FALLÓ\n";
        echo "===========================================\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n✗ EXCEPCIÓN CAPTURADA\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    
    echo "\n===========================================\n";
    echo "TEST FALLÓ CON EXCEPCIÓN\n";
    echo "===========================================\n";
    exit(1);
}

<?php

/**
 * Multi-Tenancy Test
 * Verifica aislamiento de datos entre empresas
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Core\Database;
use App\Core\Session;
use App\Core\TenantContext;
use App\Models\Usuario;
use App\Models\Control;

echo "===========================================\n";
echo "MULTI-TENANCY TEST\n";
echo "===========================================\n\n";

// Setup
Session::start();
$db = Database::getInstance();
$errors = [];
$warnings = [];

// TEST 1: Crear segunda empresa y usuarios
echo "[1/6] Preparando datos de prueba...\n";
try {
    // Empresa 2
    $empresa2Id = $db->insert('empresas', [
        'nombre' => 'Empresa Test 2',
        'ruc' => '98765432101',
        'sector' => 'Retail',
        'email' => 'test2@demo.cl'
    ]);
    
    // Usuario empresa 2
    $hash = password_hash('password', PASSWORD_BCRYPT);
    $db->insert('usuarios', [
        'empresa_id' => $empresa2Id,
        'nombre' => 'User Empresa 2',
        'email' => 'user2@test.com',
        'password_hash' => $hash,
        'rol' => 'admin_empresa',
        'estado' => 'activo'
    ]);
    
    echo "✓ Empresa 2 (ID: $empresa2Id) y usuario creados\n\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "⚠ Datos ya existen, continuando...\n\n";
        $empresa2 = $db->fetch("SELECT id FROM empresas WHERE ruc = '98765432101'");
        $empresa2Id = $empresa2['id'];
    } else {
        $errors[] = "Setup: " . $e->getMessage();
        echo "✗ Error en setup\n\n";
    }
}

// TEST 2: Aislamiento en consultas
echo "[2/6] Test aislamiento de consultas...\n";
try {
    // Simular sesión empresa 1
    Session::put('empresa_id', 1);
    TenantContext::getInstance()->setTenant(1);
    
    $usuarioModel = new Usuario();
    $usuarios1 = $usuarioModel->findAll();
    
    // Simular sesión empresa 2
    Session::put('empresa_id', $empresa2Id);
    TenantContext::getInstance()->setTenant($empresa2Id);
    
    $usuarios2 = $usuarioModel->findAll();
    
    $isolated = true;
    foreach ($usuarios1 as $u1) {
        foreach ($usuarios2 as $u2) {
            if ($u1['id'] === $u2['id']) {
                $isolated = false;
                break 2;
            }
        }
    }
    
    if ($isolated) {
        echo "✓ Consultas aisladas por empresa\n";
        echo "  - Empresa 1: " . count($usuarios1) . " usuarios\n";
        echo "  - Empresa 2: " . count($usuarios2) . " usuarios\n\n";
    } else {
        $errors[] = "Usuarios compartidos entre empresas";
        echo "✗ FALLO: Datos no aislados\n\n";
    }
} catch (Exception $e) {
    $errors[] = "Aislamiento: " . $e->getMessage();
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// TEST 3: Create con auto-asignación de tenant
echo "[3/6] Test CREATE con tenant automático...\n";
try {
    Session::put('empresa_id', 1);
    TenantContext::getInstance()->setTenant(1);
    
    $hash = password_hash('test123', PASSWORD_BCRYPT);
    $newUserId = $usuarioModel->create([
        'nombre' => 'Test Auto Tenant',
        'email' => 'autotest@empresa1.com',
        'password_hash' => $hash,
        'rol' => 'consultor',
        'estado' => 'activo'
    ]);
    
    $newUser = $db->fetch("SELECT empresa_id FROM usuarios WHERE id = ?", [$newUserId]);
    
    if ($newUser && $newUser['empresa_id'] == 1) {
        echo "✓ Tenant asignado automáticamente\n";
        echo "  - Usuario ID: $newUserId → empresa_id: 1\n\n";
        
        // Cleanup
        $db->delete('usuarios', 'id = ?', [$newUserId]);
    } else {
        $errors[] = "Tenant no asignado correctamente";
        echo "✗ FALLO: empresa_id = " . ($newUser['empresa_id'] ?? 'NULL') . "\n\n";
    }
} catch (Exception $e) {
    $errors[] = "CREATE: " . $e->getMessage();
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// TEST 4: Update respeta tenant
echo "[4/6] Test UPDATE respeta tenant...\n";
try {
    // Intentar actualizar usuario de empresa 2 desde contexto empresa 1
    Session::put('empresa_id', 1);
    TenantContext::getInstance()->setTenant(1);
    
    $user2 = $db->fetch("SELECT id FROM usuarios WHERE empresa_id = ? LIMIT 1", [$empresa2Id]);
    
    if ($user2) {
        $updated = $usuarioModel->update($user2['id'], ['nombre' => 'HACKED']);
        
        $check = $db->fetch("SELECT nombre FROM usuarios WHERE id = ?", [$user2['id']]);
        
        if ($check['nombre'] === 'HACKED') {
            $errors[] = "UPDATE cruzó tenant boundaries";
            echo "✗ FALLO: Actualizó usuario de otra empresa\n\n";
        } else {
            echo "✓ UPDATE bloqueado correctamente\n";
            echo "  - No se pudo modificar usuario de otra empresa\n\n";
        }
    } else {
        $warnings[] = "No hay usuario en empresa 2 para probar";
        echo "⚠ Sin datos para test\n\n";
    }
} catch (Exception $e) {
    $errors[] = "UPDATE: " . $e->getMessage();
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// TEST 5: Find by ID respeta tenant
echo "[5/6] Test FIND respeta tenant...\n";
try {
    Session::put('empresa_id', 1);
    TenantContext::getInstance()->setTenant(1);
    
    $user2 = $db->fetch("SELECT id FROM usuarios WHERE empresa_id = ? LIMIT 1", [$empresa2Id]);
    
    if ($user2) {
        $found = $usuarioModel->find($user2['id']);
        
        if ($found === null) {
            echo "✓ FIND respeta tenant\n";
            echo "  - Usuario de empresa 2 no visible desde empresa 1\n\n";
        } else {
            $errors[] = "FIND devolvió usuario de otra empresa";
            echo "✗ FALLO: Find cruzó tenant\n\n";
        }
    } else {
        $warnings[] = "No hay usuario en empresa 2 para probar";
        echo "⚠ Sin datos para test\n\n";
    }
} catch (Exception $e) {
    $errors[] = "FIND: " . $e->getMessage();
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// TEST 6: TenantContext singleton
echo "[6/6] Test TenantContext singleton...\n";
try {
    $tenant1 = TenantContext::getInstance();
    $tenant2 = TenantContext::getInstance();
    
    if ($tenant1 === $tenant2) {
        echo "✓ TenantContext es singleton\n\n";
    } else {
        $errors[] = "TenantContext no es singleton";
        echo "✗ FALLO: Múltiples instancias\n\n";
    }
} catch (Exception $e) {
    $errors[] = "Singleton: " . $e->getMessage();
    echo "✗ Error\n\n";
}

// RESUMEN
echo "===========================================\n";
echo "RESUMEN\n";
echo "===========================================\n";

if (empty($errors) && empty($warnings)) {
    echo "✓ TODOS LOS TESTS PASARON\n";
    echo "Multi-tenancy funcionando correctamente\n";
    exit(0);
}

if (!empty($warnings)) {
    echo "\n⚠ ADVERTENCIAS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $w) {
        echo "  " . ($i + 1) . ". $w\n";
    }
}

if (!empty($errors)) {
    echo "\n✗ ERRORES CRÍTICOS (" . count($errors) . "):\n";
    foreach ($errors as $i => $e) {
        echo "  " . ($i + 1) . ". $e\n";
    }
    echo "\nMulti-tenancy tiene problemas\n";
    exit(1);
}

echo "\nMulti-tenancy funcional con advertencias\n";
exit(0);

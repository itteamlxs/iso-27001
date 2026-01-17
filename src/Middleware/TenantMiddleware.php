<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Services\LogService;

class TenantMiddleware
{
    public function handle(Request $request, callable $next)
    {
        if (!Session::has('empresa_id')) {
            LogService::critical('Missing empresa_id in session', [
                'user_id' => Session::get('user_id'),
                'ip' => $request->ip(),
                'url' => $request->url(),
            ]);
            
            Session::invalidate();
            redirect('/login');
        }

        $empresaId = Session::get('empresa_id');
        
        $this->validateTenantAccess($request, $empresaId);
        
        define('TENANT_ID', $empresaId);
        
        return $next();
    }

    private function validateTenantAccess(Request $request, int $empresaId): void
    {
        $resourceId = $this->extractResourceId($request);
        
        if (!$resourceId) {
            return;
        }

        $resourceType = $this->detectResourceType($request);
        
        if (!$resourceType) {
            return;
        }

        if (!$this->belongsToTenant($resourceType, $resourceId, $empresaId)) {
            LogService::warning('IDOR attempt detected', [
                'user_id' => Session::get('user_id'),
                'empresa_id' => $empresaId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'ip' => $request->ip(),
                'url' => $request->url(),
                'method' => $request->method(),
            ]);

            $this->incrementIdorAttempts($request);
            
            abort(403, 'Acceso denegado');
        }
    }

    private function extractResourceId(Request $request): ?int
    {
        $path = $request->path();
        
        if (preg_match('#/(\d+)(?:/|$)#', $path, $matches)) {
            return (int) $matches[1];
        }

        $id = $request->input('id') ?? $request->param('id');
        return $id ? (int) $id : null;
    }

    private function detectResourceType(Request $request): ?string
    {
        $path = $request->path();
        
        $resources = [
            'controles' => 'soa_entries',
            'gap' => 'gap_items',
            'evidencias' => 'evidencias',
            'acciones' => 'acciones',
            'requerimientos' => 'empresa_requerimientos',
        ];

        foreach ($resources as $segment => $table) {
            if (strpos($path, '/' . $segment) !== false) {
                return $table;
            }
        }

        return null;
    }

    private function belongsToTenant(string $table, int $resourceId, int $empresaId): bool
    {
        $db = \App\Core\Database::getInstance();
        
        $tablesWithTenant = [
            'soa_entries',
            'gap_items',
            'evidencias',
            'empresa_requerimientos',
        ];

        if (!in_array($table, $tablesWithTenant)) {
            return true;
        }

        if ($table === 'gap_items') {
            $result = $db->fetch(
                "SELECT g.id 
                 FROM gap_items g
                 INNER JOIN soa_entries s ON g.soa_id = s.id
                 WHERE g.id = ? AND s.empresa_id = ? AND g.estado_gap = 'activo'
                 LIMIT 1",
                [$resourceId, $empresaId]
            );
        } else {
            $result = $db->fetch(
                "SELECT id FROM {$table} WHERE id = ? AND empresa_id = ? LIMIT 1",
                [$resourceId, $empresaId]
            );
        }

        return $result !== null;
    }

    private function incrementIdorAttempts(Request $request): void
    {
        $config = config('security.idor_protection');
        
        if (!$config['enabled'] || !$config['log_attempts']) {
            return;
        }

        $key = 'idor_attempts:' . Session::get('user_id');
        $attempts = Session::get($key, 0) + 1;
        
        Session::put($key, $attempts);

        if ($attempts >= $config['block_after_attempts']) {
            Session::put('idor_blocked_until', time() + $config['block_duration']);
            
            LogService::critical('User blocked for repeated IDOR attempts', [
                'user_id' => Session::get('user_id'),
                'empresa_id' => Session::get('empresa_id'),
                'attempts' => $attempts,
                'ip' => $request->ip(),
            ]);

            Session::invalidate();
            abort(403, 'Cuenta bloqueada por actividad sospechosa');
        }
    }
}

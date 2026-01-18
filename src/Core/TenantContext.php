<?php

namespace App\Core;

use App\Services\LogService;

class TenantContext
{
    private static ?TenantContext $instance = null;
    private ?int $tenantId = null;
    private bool $enabled = true;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setTenant(int $tenantId): void
    {
        if ($tenantId <= 0) {
            throw new \InvalidArgumentException('Invalid tenant ID');
        }

        $this->tenantId = $tenantId;

        LogService::debug('Tenant context set', [
            'tenant_id' => $tenantId
        ]);
    }

    public function getTenant(): ?int
    {
        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function requireTenant(): int
    {
        if (!$this->hasTenant()) {
            LogService::critical('Tenant context required but not set');
            throw new \RuntimeException('Tenant context not set');
        }

        return $this->tenantId;
    }

    public function clearTenant(): void
    {
        $oldTenant = $this->tenantId;
        $this->tenantId = null;

        LogService::debug('Tenant context cleared', [
            'previous_tenant_id' => $oldTenant
        ]);
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
        LogService::warning('Tenant context disabled - USE WITH CAUTION');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function withoutTenant(callable $callback)
    {
        $wasEnabled = $this->enabled;
        $this->disable();

        try {
            return $callback();
        } finally {
            if ($wasEnabled) {
                $this->enable();
            }
        }
    }

    public function validateAccess(string $table, int $resourceId): bool
    {
        if (!$this->enabled || !$this->hasTenant()) {
            return true;
        }

        $tenantTables = [
            'usuarios',
            'soa_entries',
            'gap_items',
            'evidencias',
            'empresa_requerimientos',
        ];

        if (!in_array($table, $tenantTables)) {
            return true;
        }

        $db = Database::getInstance();

        if ($table === 'gap_items') {
            $result = $db->fetch(
                "SELECT g.id 
                 FROM gap_items g
                 INNER JOIN soa_entries s ON g.soa_id = s.id
                 WHERE g.id = ? AND s.empresa_id = ? AND g.estado_gap = 'activo'
                 LIMIT 1",
                [$resourceId, $this->tenantId]
            );
        } else {
            $result = $db->fetch(
                "SELECT id FROM {$table} WHERE id = ? AND empresa_id = ? LIMIT 1",
                [$resourceId, $this->tenantId]
            );
        }

        if (!$result) {
            LogService::warning('IDOR attempt detected in TenantContext', [
                'table' => $table,
                'resource_id' => $resourceId,
                'tenant_id' => $this->tenantId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }

        return true;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

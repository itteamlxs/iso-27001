<?php

namespace App\Repositories\Base;

use App\Core\Database;
use App\Core\TenantContext;
use App\Services\CacheService;
use App\Services\LogService;

abstract class Repository
{
    protected Database $db;
    protected TenantContext $tenant;
    protected CacheService $cache;
    
    protected string $model;
    protected ?object $modelInstance = null;
    
    protected bool $useCache = true;
    protected int $cacheTtl = 300;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenant = TenantContext::getInstance();
        $this->cache = new CacheService();
        
        if ($this->model) {
            $this->modelInstance = new $this->model();
        }
    }

    public function find(int $id): ?array
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        if ($this->useCache) {
            $cacheKey = $this->getCacheKey('find', $id);
            
            return $this->cache->remember($cacheKey, function() use ($id) {
                return $this->modelInstance->find($id);
            }, $this->cacheTtl);
        }

        return $this->modelInstance->find($id);
    }

    public function findAll(array $conditions = [], int $limit = 100, int $offset = 0): array
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        return $this->modelInstance->findAll($conditions, $limit, $offset);
    }

    public function create(array $data): int
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        $id = $this->modelInstance->create($data);
        
        $this->clearCache();
        
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        $result = $this->modelInstance->update($id, $data);
        
        if ($result) {
            $this->clearCache($id);
        }
        
        return $result;
    }

    public function delete(int $id): bool
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        $result = $this->modelInstance->delete($id);
        
        if ($result) {
            $this->clearCache($id);
        }
        
        return $result;
    }

    public function count(array $conditions = []): int
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        return $this->modelInstance->count($conditions);
    }

    public function exists(int $id): bool
    {
        if (!$this->modelInstance) {
            throw new \RuntimeException('Model not defined in repository');
        }

        return $this->modelInstance->exists($id);
    }

    public function transaction(callable $callback)
    {
        try {
            $this->db->beginTransaction();
            
            $result = $callback($this);
            
            $this->db->commit();
            
            LogService::debug('Transaction committed', [
                'repository' => get_class($this)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            
            LogService::error('Transaction rolled back', [
                'repository' => get_class($this),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function paginate(array $conditions = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $items = $this->findAll($conditions, $perPage, $offset);
        $total = $this->count($conditions);
        
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    protected function getCacheKey(string $operation, ...$params): string
    {
        $tenantId = $this->tenant->hasTenant() ? $this->tenant->getTenant() : 'global';
        $modelName = $this->model ? class_basename($this->model) : 'repository';
        
        return sprintf(
            '%s:%s:%s:%s',
            $modelName,
            $tenantId,
            $operation,
            md5(serialize($params))
        );
    }

    protected function clearCache(?int $id = null): void
    {
        if (!$this->useCache) {
            return;
        }

        if ($id !== null) {
            $cacheKey = $this->getCacheKey('find', $id);
            $this->cache->delete($cacheKey);
        }

        $tenantId = $this->tenant->hasTenant() ? $this->tenant->getTenant() : 'global';
        $modelName = $this->model ? class_basename($this->model) : 'repository';
        
        $pattern = "{$modelName}:{$tenantId}:";
        
        LogService::debug('Cache cleared', [
            'pattern' => $pattern,
            'id' => $id
        ]);
    }

    public function disableCache(): self
    {
        $this->useCache = false;
        return $this;
    }

    public function enableCache(): self
    {
        $this->useCache = true;
        return $this;
    }
}

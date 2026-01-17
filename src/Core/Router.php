<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $groupMiddlewares = [];

    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => array_merge($this->groupMiddlewares, $middlewares),
        ];
    }

    public function group(array $middlewares, callable $callback): void
    {
        $previousMiddlewares = $this->groupMiddlewares;
        $this->groupMiddlewares = array_merge($this->groupMiddlewares, $middlewares);
        
        $callback($this);
        
        $this->groupMiddlewares = $previousMiddlewares;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                
                $params = $this->extractParams($route['path'], $matches);
                $request->setParams($params);

                $this->runMiddlewares($route['middlewares'], $request, function() use ($route, $request) {
                    $this->callHandler($route['handler'], $request);
                });
                
                return;
            }
        }

        abort(404, 'Route not found');
    }

    private function convertToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function extractParams(string $path, array $matches): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $paramNames);
        
        $params = [];
        foreach ($paramNames[1] as $index => $name) {
            $params[$name] = $matches[$index] ?? null;
        }
        
        return $params;
    }

    private function runMiddlewares(array $middlewares, Request $request, callable $final): void
    {
        $next = $final;
        
        foreach (array_reverse($middlewares) as $middleware) {
            $next = function() use ($middleware, $request, $next) {
                $middlewareInstance = new $middleware();
                return $middlewareInstance->handle($request, $next);
            };
        }
        
        $next();
    }

    private function callHandler($handler, Request $request): void
    {
        if (is_callable($handler)) {
            echo $handler($request);
            return;
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (!class_exists($controllerClass)) {
                abort(500, "Controller {$controllerClass} not found");
            }
            
            $controllerInstance = new $controllerClass();
            
            if (!method_exists($controllerInstance, $method)) {
                abort(500, "Method {$method} not found in {$controllerClass}");
            }
            
            echo $controllerInstance->$method($request);
            return;
        }

        abort(500, 'Invalid route handler');
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

/** Mini router: GET/POST + group prefix + param {id}. */
final class Router {
    /** @var array<int,array{method:string,path:string,handler:mixed}> */
    private array $routes = [];

    public function get(string $path, $handler): void { $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler]; }
    public function post(string $path, $handler): void { $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler]; }
    public function any(string $path, $handler): void { $this->routes[] = ['method' => '*', 'path' => $path, 'handler' => $handler]; }

    /** @return array{handler:mixed,params:array}|null */
    public function match(string $method, string $path): ?array {
        foreach ($this->routes as $r) {
            if ($r['method'] !== '*' && $r['method'] !== $method) continue;
            $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $r['path']);
            if (preg_match('#^' . $pattern . '$#', $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['handler' => $r['handler'], 'params' => $params];
            }
        }
        return null;
    }
}

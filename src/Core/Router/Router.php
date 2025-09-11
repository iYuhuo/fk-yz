<?php

namespace AuthSystem\Core\Router;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Container\Container;
use AuthSystem\Core\Debug\DebugHelper;


class Router
{
    private array $routes = [];
    private array $groups = [];
    private Container $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?? new Container();
    }


    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }


    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }


    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }


    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }


    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $this->groups[] = ['prefix' => $prefix, 'middleware' => $middleware];
        $callback($this);
        array_pop($this->groups);
    }


    private function addRoute(string $method, string $path, $handler): void
    {
        $prefix = '';
        $middleware = [];


        foreach ($this->groups as $group) {
            $prefix .= $group['prefix'];
            $middleware = array_merge($middleware, $group['middleware']);
        }

        $fullPath = $prefix . $path;

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'pattern' => $this->convertToPattern($fullPath),
            'middleware' => $middleware,
        ];
    }


    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();


        $uri = strtok($uri, '?');

        DebugHelper::logRequest($method, $uri, $request->all());

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                DebugHelper::logRoute($method, $uri, $route['handler']);


                if (!empty($route['middleware'])) {
                    $response = $this->executeMiddleware($route['middleware'], $request, function() use ($route, $matches, $request) {
                        return $this->callHandler($route['handler'], $matches, $request);
                    });
                    return $response;
                } else {
                    return $this->callHandler($route['handler'], $matches, $request);
                }
            }
        }

        DebugHelper::log("Route not found", [
            'method' => $method,
            'uri' => $uri,
            'available_routes' => array_map(function($r) {
                return $r['method'] . ' ' . $r['path'];
            }, $this->routes)
        ]);

        return Response::notFound('Route not found');
    }


    private function executeMiddleware(array $middleware, Request $request, callable $next): Response
    {
        if (empty($middleware)) {
            return $next();
        }

        $middlewareClass = array_shift($middleware);
        $remainingMiddleware = $middleware;
        $middlewareInstance = $this->container->make($middlewareClass);

        return $middlewareInstance->handle($request, function() use ($remainingMiddleware, $request, $next) {
            return $this->executeMiddleware($remainingMiddleware, $request, $next);
        });
    }


    private function callHandler($handler, array $matches, Request $request): Response
    {
        if (is_string($handler)) {

            if (strpos($handler, '@') !== false) {
                [$controller, $method] = explode('@', $handler, 2);
                $controller = $this->container->make($controller);
                return $controller->$method($request, $matches);
            }


            if (function_exists($handler)) {
                return $handler($request, $matches);
            }
        }

        if (is_callable($handler)) {
            return $handler($request, $matches);
        }

        return Response::error('Invalid handler');
    }


    private function convertToPattern(string $path): string
    {

        $pattern = str_replace('/', '\/', $path);
        $pattern = preg_replace('/\{[^}]+\}/', '([^\/]+)', $pattern);

        return '/^' . $pattern . '$/';
    }


    public function getRoutes(): array
    {
        return $this->routes;
    }
}
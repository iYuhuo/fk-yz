<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Container\Container;


class MiddlewareStack
{
    private array $middlewares = [];
    private Container $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?? new Container();
    }


    public function add($middleware): void
    {
        $this->middlewares[] = $middleware;
    }


    public function handle(Request $request, callable $next): Response
    {
        return $this->processMiddleware($request, 0, $next);
    }


    private function processMiddleware(Request $request, int $index, callable $next): Response
    {
        if ($index >= count($this->middlewares)) {
            return $next($request);
        }

        $middleware = $this->middlewares[$index];

        if (is_string($middleware)) {
            $middleware = $this->container->make($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->handle($request, function ($req) use ($index, $next) {
                return $this->processMiddleware($req, $index + 1, $next);
            });
        }

        if (is_callable($middleware)) {
            return $middleware($request, function ($req) use ($index, $next) {
                return $this->processMiddleware($req, $index + 1, $next);
            });
        }

        throw new \Exception('Invalid middleware: ' . (is_object($middleware) ? get_class($middleware) : gettype($middleware)));
    }


    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
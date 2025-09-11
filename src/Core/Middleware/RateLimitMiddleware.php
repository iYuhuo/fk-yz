<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Config\Config;


class RateLimitMiddleware implements MiddlewareInterface
{
    private Config $config;
    private array $requests = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function handle(Request $request, callable $next): Response
    {
        $clientIp = $request->getClientIp();
        $path = $request->getUri();


        $limit = $this->getLimitForPath($path);

        if (!$this->checkRateLimit($clientIp, $path, $limit['max'], $limit['per'])) {
            return Response::tooManyRequests('请求过于频繁，请稍后再试');
        }

        return $next($request);
    }


    private function getLimitForPath(string $path): array
    {
        if (strpos($path, '/api/verify') === 0) {
            return [
                'max' => $this->config->get('rate_limit.verify_max', 10),
                'per' => $this->config->get('rate_limit.verify_per', 60),
            ];
        }

        if (strpos($path, '/login') !== false) {
            return [
                'max' => $this->config->get('rate_limit.login_max', 5),
                'per' => $this->config->get('rate_limit.login_per', 60),
            ];
        }

        if (strpos($path, '/api/') === 0) {
            return [
                'max' => $this->config->get('rate_limit.api_max', 100),
                'per' => $this->config->get('rate_limit.api_per', 60),
            ];
        }

        return [
            'max' => 1000,
            'per' => 60,
        ];
    }


    private function checkRateLimit(string $clientIp, string $path, int $maxRequests, int $perSeconds): bool
    {
        $key = $clientIp . ':' . $path;
        $now = time();

        if (!isset($this->requests[$key])) {
            $this->requests[$key] = [];
        }


        $this->requests[$key] = array_filter(
            $this->requests[$key],
            fn($timestamp) => $now - $timestamp < $perSeconds
        );

        if (count($this->requests[$key]) >= $maxRequests) {
            return false;
        }

        $this->requests[$key][] = $now;
        return true;
    }
}
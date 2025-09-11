<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;


class SecurityMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {

        $response = $next($request);

        $this->addSecurityHeaders($response);

        return $response;
    }


    private function addSecurityHeaders(Response $response): void
    {

        $response->setHeader('X-Frame-Options', 'DENY');


        $response->setHeader('X-Content-Type-Options', 'nosniff');


        $response->setHeader('X-XSS-Protection', '1; mode=block');


        if ($this->isHttps()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }


        $response->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' cdn.jsdelivr.net;");


        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');


        $response->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }


    private function isHttps(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
}
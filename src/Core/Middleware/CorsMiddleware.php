<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;


class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', 200);
            $this->addCorsHeaders($response);
            return $response;
        }

        $response = $next($request);
        $this->addCorsHeaders($response);

        return $response;
    }


    private function addCorsHeaders(Response $response): void
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->setHeader('Access-Control-Max-Age', '86400');
    }
}
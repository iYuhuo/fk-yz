<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Session\SessionManager;


class AuthMiddleware implements MiddlewareInterface
{

    private array $protectedPaths = [
        '/',
        '/licenses',
        '/logs',
        '/settings',
    ];

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->getUri();
        $path = strtok($path, '?');


        if ($this->needsAuth($path)) {

            if (SessionManager::isExpired(3600)) {
                SessionManager::clearAdminLogin();
                SessionManager::setFlashMessage('error', '会话已过期，请重新登录');
                return Response::redirect('/login');
            }


            if (!SessionManager::isLoggedIn()) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }


            SessionManager::updateActivity();
        }

        return $next($request);
    }


    private function needsAuth(string $path): bool
    {

        if ($path === '/login' || strpos($path, '/api/') === 0) {
            return false;
        }


        foreach ($this->protectedPaths as $protectedPath) {
            if ($path === $protectedPath || strpos($path, $protectedPath . '/') === 0) {
                return true;
            }
        }


        if ($path === '/logout') {
            return true;
        }

        return false;
    }
}
<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Config\Config;
use AuthSystem\Models\Admin;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Session\SessionManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class AuthController
{
    private Admin $adminModel;
    private AdminLog $adminLogModel;
    private Logger $logger;
    private string $jwtSecret;
    private Config $config;

    public function __construct(Admin $adminModel, AdminLog $adminLogModel, Logger $logger, Config $config)
    {
        $this->adminModel = $adminModel;
        $this->adminLogModel = $adminLogModel;
        $this->logger = $logger;
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret';
        $this->config = $config;
    }


    public function showLogin(Request $request): Response
    {

        if (SessionManager::isLoggedIn()) {
            return Response::redirect('/');
        }

        $error = $request->get('error', '');
        $systemName = $this->config->get('app.name');
        $html = $this->renderLoginPage($error, $systemName);
        return Response::html($html);
    }


    public function login(Request $request): Response
    {
        try {
            $data = $request->all();

            if (!isset($data['username']) || !isset($data['password'])) {
                SessionManager::setFlashMessage('error', '用户名和密码不能为空');
                return Response::redirect('/login');
            }

            $username = $data['username'];
            $password = $data['password'];


            $admin = $this->adminModel->findByUsername($username);

            if (!$admin || !$this->adminModel->verifyPassword($password, $admin['password_hash'])) {

                $this->adminLogModel->logAction(
                    '登录失败',
                    "用户名: {$username}",
                    $request->getClientIp(),
                    $request->getUserAgent()
                );


                SessionManager::setFlashMessage('error', '用户名或密码错误');
                return Response::redirect('/login');
            }


            $this->adminModel->updateLastLogin($admin['id']);


            SessionManager::setAdminLogin($admin['id'], $admin['username']);


            $this->adminLogModel->logAction(
                '登录成功',
                "管理员: {$username}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Web admin login successful', [
                'username' => $username,
                'ip' => $request->getClientIp(),
            ]);

            return Response::redirect('/');

        } catch (\Exception $e) {
            $this->logger->error('Web login error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '登录时发生错误，请稍后再试');
            return Response::redirect('/login');
        }
    }


    public function logout(Request $request): Response
    {
        try {
            $username = SessionManager::getAdminUsername();


            $this->adminLogModel->logAction(
                '登出',
                "管理员 {$username} 登出",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Web admin logout', [
                'username' => $username,
                'ip' => $request->getClientIp(),
            ]);


            SessionManager::clearAdminLogin();

            return Response::redirect('/login');

        } catch (\Exception $e) {
            $this->logger->error('Web logout error', [
                'error' => $e->getMessage(),
            ]);


            SessionManager::clearAdminLogin();
            return Response::redirect('/login');
        }
    }


    private function renderLoginPage(string $error = '', string $systemName = '网络验证系统'): string
    {

        if (empty($error)) {
            $error = SessionManager::getFlashMessage('error');
        }

        $notificationScript = '';
        if (!empty($error)) {
            $escapedError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('{$escapedError}'); });</script>";
        }
        $errorHtml = '';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - {$systemName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--card-border);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        .login-card .form-control {
            background: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        .login-card .form-control:focus {
            background: var(--card-bg);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem var(--primary-color-25);
        }
        .login-card .form-label {
            color: var(--text-primary);
        }
        .login-card h3 {
            color: white;
        }
        .login-card p {
            color: rgba(255, 255, 255, 0.8);
        }
        .login-card .input-group-text {
            background: var(--input-group-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        .login-card .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        .login-card .btn-primary:hover {
            background: var(--primary-color-dark);
            border-color: var(--primary-color-dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <i class="bi bi-shield-check fs-1 mb-3"></i>
                        <h3>{$systemName}</h3>
                        <p class="mb-0">管理员登录</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="/login">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">密码</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> 登录
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme-manager.js"></script>
    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/notifications.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            if (window.ThemeManager) {
                window.themeManager = new ThemeManager();
                window.themeManager.init();
            }
        });
    </script>
    {$notificationScript}
</body>
</html>
HTML;
    }
}
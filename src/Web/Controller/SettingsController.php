<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Config\Config;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Session\SessionManager;
use AuthSystem\Models\AdminLog;
use AuthSystem\Models\Admin;


class SettingsController
{
    private Config $config;
    private Logger $logger;
    private AdminLog $adminLogModel;
    private Admin $adminModel;

    public function __construct(Config $config, Logger $logger, AdminLog $adminLogModel, Admin $adminModel)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->adminLogModel = $adminLogModel;
        $this->adminModel = $adminModel;
    }


    public function index(Request $request): Response
    {
        try {

            if (!SessionManager::isLoggedIn()) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }

            $settings = $this->getCurrentSettings();
            $systemName = $settings['system_name'] ?? '网络验证系统';
            $brandHtml = $this->config->getBrandHtml();
            $html = $this->renderSettingsPage($settings, $systemName, $brandHtml);
            return Response::html($html);

        } catch (\Exception $e) {
            $this->logger->error('Settings page error', [
                'error' => $e->getMessage(),
            ]);

            return Response::html('<h1>错误</h1><p>加载设置页面时发生错误</p>');
        }
    }


    private function getCurrentSettings(): array
    {
        return [
            'system_name' => $_ENV['SYSTEM_NAME'] ?? '网络验证系统',
            'website_url' => $_ENV['WEBSITE_URL'] ?? 'http://localhost',
            'website_logo' => $_ENV['WEBSITE_LOGO'] ?? '',
            'api_secret_key' => $_ENV['API_SECRET_KEY'] ?? '',
            'api_encrypt_method' => $_ENV['API_ENCRYPT_METHOD'] ?? 'AES-256-CBC',
            'client_auth_required' => $_ENV['CLIENT_AUTH_REQUIRED'] ?? 'true',
            'api_key_required' => $_ENV['API_KEY_REQUIRED'] ?? 'false',
            'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
            'db_port' => $_ENV['DB_PORT'] ?? '3306',
            'db_name' => $_ENV['DB_NAME'] ?? 'auth_system',
            'jwt_expiry' => $_ENV['JWT_EXPIRY'] ?? '3600',
            'rate_limit_verify_max' => $_ENV['RATE_LIMIT_VERIFY_MAX'] ?? '10',
            'rate_limit_verify_per' => $_ENV['RATE_LIMIT_VERIFY_PER'] ?? '60',
            'rate_limit_login_max' => $_ENV['RATE_LIMIT_LOGIN_MAX'] ?? '5',
            'rate_limit_login_per' => $_ENV['RATE_LIMIT_LOGIN_PER'] ?? '60',
        ];
    }


    private function renderSettingsPage(array $settings, string $systemName = '网络验证系统', string $brandHtml = ''): string
    {
        if (empty($brandHtml)) {
            $brandHtml = '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($systemName);
        }


        $notificationScript = '';

        $successMessage = SessionManager::getFlashMessage('success');
        $errorMessage = SessionManager::getFlashMessage('error');
        $infoMessage = SessionManager::getFlashMessage('info');

        if ($successMessage) {
            $message = htmlspecialchars($successMessage);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.success('{$message}'); });</script>";
        } elseif ($errorMessage) {
            $message = htmlspecialchars($errorMessage);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('{$message}'); });</script>";
        } elseif ($infoMessage) {
            $message = htmlspecialchars($infoMessage);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.info('{$message}'); });</script>";
        }
        $alertHtml = '';


        $phpVersion = PHP_VERSION;
        $serverTime = date('Y-m-d H:i:s');


        $logoPreview = '';
        if (!empty($settings['website_logo'])) {
            $logoPreview = '<div class="mt-2"><img src="' . htmlspecialchars($settings['website_logo']) . '" alt="当前Logo" style="max-height: 50px;" class="img-thumbnail"></div>';
        }


        $aes256Selected = $settings['api_encrypt_method'] === 'AES-256-CBC' ? 'selected' : '';
        $aes128Selected = $settings['api_encrypt_method'] === 'AES-128-CBC' ? 'selected' : '';
        $desSelected = $settings['api_encrypt_method'] === 'DES-EDE3-CBC' ? 'selected' : '';


        $authRequiredChecked = $settings['client_auth_required'] === 'true' ? 'checked' : '';
        $apiKeyRequiredChecked = $settings['api_key_required'] === 'true' ? 'checked' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - {$systemName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
{$brandHtml}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="/">
                        <i class="bi bi-house"></i> 首页
                    </a>
                    <a class="nav-link" href="/licenses">
                        <i class="bi bi-key"></i> 许可证管理
                    </a>
                    <a class="nav-link" href="/logs">
                        <i class="bi bi-list-ul"></i> 日志查看
                    </a>
                    <a class="nav-link active" href="/settings">
                        <i class="bi bi-gear"></i> 设置
                    </a>
                    <a class="nav-link" href="/logout">
                        <i class="bi bi-box-arrow-right"></i> 退出
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 page-content">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">
                    <i class="bi bi-gear me-2"></i>系统设置
                </h1>
            </div>
        </div>

        <div class="row card-stack">
            <div class="col-md-8">
                <!-- 基本设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear"></i> 基本设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/save" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="system_name" class="form-label">系统名称</label>
                                <input type="text" class="form-control" id="system_name" name="SYSTEM_NAME" value="{$settings['system_name']}" required>
                                <div class="form-text">当前系统的显示名称</div>
                            </div>
                            <div class="mb-3">
                                <label for="website_url" class="form-label">网站URL</label>
                                <input type="url" class="form-control" id="website_url" name="WEBSITE_URL" value="{$settings['website_url']}" required>
                                <div class="form-text">网站的完整URL地址，例如http:
                            </div>
                            <div class="mb-3">
                                <label for="website_logo" class="form-label">网站Logo</label>
                                <div class="mb-2">
                                    <input type="text" class="form-control" id="website_logo_url" name="WEBSITE_LOGO" value="{$settings['website_logo']}" placeholder="https://example.com/logo.png">
                                    <div class="form-text">输入Logo图片的URL地址</div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">或上传本地Logo文件</label>
                                    <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*">
                                    <div class="form-text">支持 JPG, PNG, GIF, SVG 格式，建议尺寸 200x50px</div>
                                </div>
                                {$logoPreview}
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const logoUrlInput = document.getElementById('website_logo_url');
                                    const logoFileInput = document.getElementById('logo_file');
                                    const form = document.querySelector('form[method="post"]');

                                    if (!logoUrlInput || !logoFileInput || !form) return;

                                    const originalLogoValue = logoUrlInput.value;


                                    logoFileInput.addEventListener('change', function() {
                                        if (this.files.length > 0) {
                                            logoUrlInput.value = '';
                                        }
                                    });


                                    logoUrlInput.addEventListener('input', function() {
                                        if (this.value.trim() !== '' && this.value !== originalLogoValue) {
                                            logoFileInput.value = '';
                                        }
                                    });


                                    form.addEventListener('submit', function(e) {

                                        if (logoFileInput.files.length === 0 && logoUrlInput.value.trim() === '') {
                                            logoUrlInput.value = originalLogoValue;
                                        }


                                        console.log('Logo URL value:', logoUrlInput.value);
                                        console.log('Logo file selected:', logoFileInput.files.length > 0);
                                    });
                                });
                                </script>
                            </div>
                            <div class="mb-3">
                                <label for="jwt_expiry" class="form-label">JWT令牌有效期 (秒)</label>
                                <input type="number" class="form-control" id="jwt_expiry" name="JWT_EXPIRY" value="{$settings['jwt_expiry']}" min="300" max="86400">
                                <div class="form-text">管理员登录令牌的有效期（300-86400秒）</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> 保存基本设置
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 主题设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-palette"></i> 主题设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">选择主题</label>
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-preview" data-theme="light">
                                            <div class="theme-preview-card">
                                                <div class="theme-preview-header" style="background: #0d6efd;"></div>
                                                <div class="theme-preview-body" style="background: #ffffff; color: #212529;">
                                                    <div class="theme-preview-text"></div>
                                                    <div class="theme-preview-text short"></div>
                                                </div>
                                            </div>
                                            <div class="theme-preview-name">
                                                <input type="radio" class="form-check-input" name="theme" value="light" id="theme_light">
                                                <label class="form-check-label ms-2" for="theme_light">浅色主题</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-preview" data-theme="dark">
                                            <div class="theme-preview-card">
                                                <div class="theme-preview-header" style="background: #1a202c;"></div>
                                                <div class="theme-preview-body" style="background: #2d3748; color: #f7fafc;">
                                                    <div class="theme-preview-text"></div>
                                                    <div class="theme-preview-text short"></div>
                                                </div>
                                            </div>
                                            <div class="theme-preview-name">
                                                <input type="radio" class="form-check-input" name="theme" value="dark" id="theme_dark">
                                                <label class="form-check-label ms-2" for="theme_dark">深色主题</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-preview" data-theme="blue">
                                            <div class="theme-preview-card">
                                                <div class="theme-preview-header" style="background: #1e40af;"></div>
                                                <div class="theme-preview-body" style="background: #eff6ff; color: #1e293b;">
                                                    <div class="theme-preview-text"></div>
                                                    <div class="theme-preview-text short"></div>
                                                </div>
                                            </div>
                                            <div class="theme-preview-name">
                                                <input type="radio" class="form-check-input" name="theme" value="blue" id="theme_blue">
                                                <label class="form-check-label ms-2" for="theme_blue">海洋蓝</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-preview" data-theme="green">
                                            <div class="theme-preview-card">
                                                <div class="theme-preview-header" style="background: #064e3b;"></div>
                                                <div class="theme-preview-body" style="background: #ecfdf5; color: #111827;">
                                                    <div class="theme-preview-text"></div>
                                                    <div class="theme-preview-text short"></div>
                                                </div>
                                            </div>
                                            <div class="theme-preview-name">
                                                <input type="radio" class="form-check-input" name="theme" value="green" id="theme_green">
                                                <label class="form-check-label ms-2" for="theme_green">森林绿</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-preview" data-theme="purple">
                                            <div class="theme-preview-card">
                                                <div class="theme-preview-header" style="background: #581c87;"></div>
                                                <div class="theme-preview-body" style="background: #faf5ff; color: #111827;">
                                                    <div class="theme-preview-text"></div>
                                                    <div class="theme-preview-text short"></div>
                                                </div>
                                            </div>
                                            <div class="theme-preview-name">
                                                <input type="radio" class="form-check-input" name="theme" value="purple" id="theme_purple">
                                                <label class="form-check-label ms-2" for="theme_purple">典雅紫</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-preview" data-theme="orange">
                                            <div class="theme-preview-card">
                                                <div class="theme-preview-header" style="background: #9a3412;"></div>
                                                <div class="theme-preview-body" style="background: #fff7ed; color: #111827;">
                                                    <div class="theme-preview-text"></div>
                                                    <div class="theme-preview-text short"></div>
                                                </div>
                                            </div>
                                            <div class="theme-preview-name">
                                                <input type="radio" class="form-check-input" name="theme" value="orange" id="theme_orange">
                                                <label class="form-check-label ms-2" for="theme_orange">活力橙</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> 主题会即时应用，也可以使用导航栏的调色板按钮快速切换
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="followSystemTheme" name="follow_system_theme">
                                    <label class="form-check-label" for="followSystemTheme">
                                        跟随系统主题
                                    </label>
                                    <div class="form-text">自动根据系统的明暗模式切换主题</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableAnimations" name="enable_animations" checked>
                                    <label class="form-check-label" for="enableAnimations">
                                        启用动画效果
                                    </label>
                                    <div class="form-text">开启页面过渡动画和交互效果</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 限流设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-speedometer2"></i> 限流设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/save">
                            <input type="hidden" name="section" value="rate_limit">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_verify_max" class="form-label">验证接口最大请求数</label>
                                        <input type="number" class="form-control" id="rate_limit_verify_max" name="RATE_LIMIT_VERIFY_MAX" value="{$settings['rate_limit_verify_max']}" min="1" max="1000">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_verify_per" class="form-label">时间窗口 (秒)</label>
                                        <input type="number" class="form-control" id="rate_limit_verify_per" name="RATE_LIMIT_VERIFY_PER" value="{$settings['rate_limit_verify_per']}" min="10" max="3600">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_login_max" class="form-label">登录接口最大请求数</label>
                                        <input type="number" class="form-control" id="rate_limit_login_max" name="RATE_LIMIT_LOGIN_MAX" value="{$settings['rate_limit_login_max']}" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rate_limit_login_per" class="form-label">时间窗口 (秒)</label>
                                        <input type="number" class="form-control" id="rate_limit_login_per" name="RATE_LIMIT_LOGIN_PER" value="{$settings['rate_limit_login_per']}" min="10" max="3600">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-speedometer2"></i> 保存限流设置
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 管理员账号设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-gear"></i> 管理员账号管理
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/change-password">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">当前密码</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <div class="form-text">请输入当前管理员密码以验证身份</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_username" class="form-label">新用户名</label>
                                        <input type="text" class="form-control" id="new_username" name="new_username" placeholder="留空则不修改">
                                        <div class="form-text">修改管理员用户名（可选）</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">新密码</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                        <div class="form-text">至少6位字符，留空则不修改</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">确认新密码</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                        <div class="form-text">请再次输入新密码确认</div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-key"></i> 修改管理员账号
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 安全设置 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock"></i> 安全设置
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/settings/save">
                            <input type="hidden" name="section" value="security">
                            <div class="mb-3">
                                <label for="api_secret_key" class="form-label">API通信密钥</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_secret_key" name="API_SECRET_KEY" value="{$settings['api_secret_key']}" minlength="16" maxlength="64">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateApiKey()">
                                        <i class="bi bi-arrow-clockwise"></i> 生成
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('api_secret_key')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">用于客户端与服务器通信加密的密钥（16-64位字符）</div>
                            </div>
                            <div class="mb-3">
                                <label for="api_encrypt_method" class="form-label">加密算法</label>
                                <select class="form-select" id="api_encrypt_method" name="API_ENCRYPT_METHOD">
                                    <option value="AES-256-CBC" {$aes256Selected}>AES-256-CBC (推荐)</option>
                                    <option value="AES-128-CBC" {$aes128Selected}>AES-128-CBC</option>
                                    <option value="DES-EDE3-CBC" {$desSelected}>3DES-CBC</option>
                                </select>
                                <div class="form-text">选择客户端通信使用的加密算法</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="CLIENT_AUTH_REQUIRED" value="false">
                                    <input class="form-check-input" type="checkbox" id="client_auth_required" name="CLIENT_AUTH_REQUIRED" value="true" {$authRequiredChecked}>
                                    <label class="form-check-label" for="client_auth_required">
                                        强制客户端身份验证
                                    </label>
                                </div>
                                <div class="form-text">启用后，所有API请求都必须包含有效的身份验证信息</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="API_KEY_REQUIRED" value="false">
                                    <input class="form-check-input" type="checkbox" id="api_key_required" name="API_KEY_REQUIRED" value="true" {$apiKeyRequiredChecked}>
                                    <label class="form-check-label" for="api_key_required">
                                        启用API密钥验证
                                    </label>
                                </div>
                                <div class="form-text">启用后，所有API请求都必须包含正确的API密钥</div>
                            </div>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>重要提醒：</strong>修改安全设置后，现有客户端可能需要更新配置才能继续正常工作。
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-shield-lock"></i> 保存安全设置
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- 系统信息 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle"></i> 系统信息
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-6">PHP版本:</dt>
                            <dd class="col-sm-6">{$phpVersion}</dd>

                            <dt class="col-sm-6">服务器时间:</dt>
                            <dd class="col-sm-6" id="server-time">{$serverTime}</dd>

                            <dt class="col-sm-6">系统负载:</dt>
                            <dd class="col-sm-6">
                                <span class="badge bg-success">正常</span>
                            </dd>
                        </dl>
                    </div>
                </div>

                <!-- 操作面板 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-tools"></i> 操作面板
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="clearCache()">
                                <i class="bi bi-arrow-clockwise"></i> 清理缓存
                            </button>
                            <button class="btn btn-outline-warning" onclick="exportData()">
                                <i class="bi bi-download"></i> 导出数据
                            </button>
                            <button class="btn btn-outline-info" onclick="systemDiagnosis()">
                                <i class="bi bi-shield-check"></i> 系统诊断
                            </button>
                        </div>
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

            initThemeSelection();


            document.addEventListener('themeChange', function(e) {
                updateThemeSelection(e.detail.theme);
            });
        });

        function initThemeSelection() {

            let currentTheme = 'light';


            if (window.themeManager && typeof window.themeManager.getCurrentTheme === 'function') {
                currentTheme = window.themeManager.getCurrentTheme();
            }

            else if (localStorage.getItem('theme')) {
                currentTheme = localStorage.getItem('theme');
            }

            else if (document.documentElement.getAttribute('data-theme')) {
                currentTheme = document.documentElement.getAttribute('data-theme');
            }

            else {
                const bodyClasses = document.body.className;
                if (bodyClasses.includes('theme-dark')) currentTheme = 'dark';
                else if (bodyClasses.includes('theme-blue')) currentTheme = 'blue';
                else if (bodyClasses.includes('theme-green')) currentTheme = 'green';
                else if (bodyClasses.includes('theme-purple')) currentTheme = 'purple';
                else if (bodyClasses.includes('theme-orange')) currentTheme = 'orange';
            }

            console.log('检测到当前主题:', currentTheme);
            updateThemeSelection(currentTheme);


            document.querySelectorAll('.theme-preview').forEach(preview => {
                preview.addEventListener('click', function() {
                    const theme = this.dataset.theme;
                    const radio = this.querySelector('input[type="radio"]');


                    document.querySelectorAll('.theme-preview').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    radio.checked = true;


                    if (window.themeManager) {
                        window.themeManager.changeTheme(theme);
                    }
                });
            });


            const followSystemToggle = document.getElementById('followSystemTheme');
            if (followSystemToggle) {
                followSystemToggle.addEventListener('change', function() {
                    if (this.checked) {

                        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                        const systemTheme = isDarkMode ? 'dark' : 'light';
                        if (window.themeManager) {
                            window.themeManager.changeTheme(systemTheme);
                        }
                    }
                });
            }


            const animationsToggle = document.getElementById('enableAnimations');
            if (animationsToggle) {
                const isEnabled = localStorage.getItem('animations_enabled') !== 'false';
                animationsToggle.checked = isEnabled;
                document.body.style.setProperty('--enable-animations', isEnabled ? '1' : '0');

                animationsToggle.addEventListener('change', function() {
                    localStorage.setItem('animations_enabled', this.checked);
                    document.body.style.setProperty('--enable-animations', this.checked ? '1' : '0');

                    if (window.notify) {
                        window.notify.success(this.checked ? '已启用动画效果' : '已禁用动画效果');
                    }
                });
            }
        }

        function updateThemeSelection(theme) {
            console.log('更新主题选择状态:', theme);


            document.querySelectorAll('.theme-preview').forEach(preview => {
                preview.classList.remove('active');
                const radio = preview.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            });


            const selectedPreview = document.querySelector('.theme-preview[data-theme="' + theme + '"]');
            if (selectedPreview) {
                selectedPreview.classList.add('active');
                const radio = selectedPreview.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    console.log('已选中主题:', theme);
                } else {
                    console.warn('未找到主题单选按钮:', theme);
                }
            } else {
                console.warn('未找到主题预览元素:', theme);

                const radioByValue = document.querySelector('input[type="radio"][name="theme"][value="' + theme + '"]');
                if (radioByValue) {
                    radioByValue.checked = true;
                    const preview = radioByValue.closest('.theme-preview');
                    if (preview) preview.classList.add('active');
                    console.log('通过radio按钮选中主题:', theme);
                }
            }


            setTimeout(() => {
                document.querySelectorAll('.theme-preview.active').forEach(activePreview => {
                    activePreview.style.transform = 'scale(1.02)';
                    activePreview.style.borderColor = 'var(--primary-color)';
                });
            }, 100);
        }

        async function clearCache() {
            const confirmed = await modernModal.confirm('确定要清理系统缓存吗？', '清理缓存');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/settings/clear-cache';
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function exportData() {
            const confirmed = await modernModal.confirm('确定要导出系统数据吗？这可能需要一些时间。', '导出数据');
            if (confirmed) {

                window.location.href = '/settings/export-data';
            }
        }

        async function systemDiagnosis() {
            const confirmed = await modernModal.confirm('确定要进行系统诊断吗？这将检查系统健康状态和配置完整性。', '系统诊断');
            if (confirmed) {

                const loadingBtn = document.querySelector('button[onclick="systemDiagnosis()"]');
                const originalText = loadingBtn.innerHTML;
                loadingBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 诊断中...';
                loadingBtn.disabled = true;

                try {
                    const response = await fetch('/settings/system-diagnosis', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        }
                    });

                    const result = await response.json();


                    loadingBtn.innerHTML = originalText;
                    loadingBtn.disabled = false;


                    showDiagnosisResult(result);

                } catch (error) {
                    loadingBtn.innerHTML = originalText;
                    loadingBtn.disabled = false;
                    await modernModal.alert('系统诊断失败: ' + error.message, '错误', 'error');
                }
            }
        }

        function showDiagnosisResult(result) {
            let accordionHtml = '';
            result.details.forEach((section, index) => {
                let itemsHtml = '';
                section.items.forEach(item => {
                    itemsHtml += `
                        <div class="d-flex align-items-center mb-2">
                            <span class="me-2">\${item.icon}</span>
                            <span>\${item.text}</span>
                        </div>
                    `;
                });

                accordionHtml += `
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button \${index === 0 ? '' : 'collapsed'}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse\${index}">
                                \${section.icon} \${section.title}
                                <span class="badge bg-\${section.status === 'success' ? 'success' : 'warning'} ms-2">
                                    \${section.status === 'success' ? '正常' : '有问题'}
                                </span>
                            </button>
                        </h2>
                        <div id="collapse\${index}" class="accordion-collapse collapse \${index === 0 ? 'show' : ''}"
                             data-bs-parent="#diagnosisAccordion">
                            <div class="accordion-body">
                                \${itemsHtml}
                            </div>
                        </div>
                    </div>
                `;
            });

            const modalHtml = `
                <div class="modal fade" id="diagnosisModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header \${result.status === 'success' ? 'bg-success' : 'bg-warning'} text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-\${result.status === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                                    系统诊断报告
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-\${result.status === 'success' ? 'success' : 'warning'} mb-3">
                                    <strong>\${result.message}</strong>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>当前版本:</strong> \${result.version}
                                    </div>
                                    <div class="col-6">
                                        <strong>检查时间:</strong> \${result.checkTime}
                                    </div>
                                </div>

                                <div class="accordion" id="diagnosisAccordion">
                                    \${accordionHtml}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i>关闭
                                </button>
                                <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>刷新页面
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;


            const existingModal = document.getElementById('diagnosisModal');
            if (existingModal) {
                existingModal.remove();
            }


            document.body.insertAdjacentHTML('beforeend', modalHtml);


            const modal = new bootstrap.Modal(document.getElementById('diagnosisModal'));
            modal.show();
        }


        function updateServerTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            const timeString = `\${year}-\${month}-\${day} \${hours}:\${minutes}:\${seconds}`;
            const timeElement = document.getElementById('server-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }


        function generateApiKey() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let result = '';
            for (let i = 0; i < 32; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('api_secret_key').value = result;
        }


        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling.nextElementSibling;
            const icon = button.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            updateServerTime();
            setInterval(updateServerTime, 1000);
        });
    </script>
    {$notificationScript}
</body>
</html>
HTML;
    }


    public function save(Request $request): Response
    {
        try {

            if (!SessionManager::isLoggedIn()) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }


            $data = $_POST;


            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $logoPath = $this->handleLogoUpload($_FILES['logo_file']);
                if ($logoPath) {
                    $data['WEBSITE_LOGO'] = $logoPath;

                    $data['_logo_uploaded'] = true;
                } else {

                    global $logoUploadError;
                    if ($logoUploadError) {
                        SessionManager::setFlashMessage('error', 'Logo上传失败: ' . $logoUploadError['error']);
                    } else {
                        SessionManager::setFlashMessage('error', 'Logo上传失败，未知错误');
                    }
                    return Response::redirect('/settings');
                }
            } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] !== UPLOAD_ERR_NO_FILE) {

                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
                    UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                    UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                    UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹',
                    UPLOAD_ERR_CANT_WRITE => '文件写入失败',
                    UPLOAD_ERR_EXTENSION => '文件上传被扩展程序阻止'
                ];
                $errorMsg = $uploadErrors[$_FILES['logo_file']['error']] ?? '未知上传错误';
                SessionManager::setFlashMessage('error', 'Logo上传失败: ' . $errorMsg);
                return Response::redirect('/settings');
            }
            $envPath = PROJECT_ROOT . '/.env';



            $data['CLIENT_AUTH_REQUIRED'] = (isset($data['CLIENT_AUTH_REQUIRED']) && $data['CLIENT_AUTH_REQUIRED'] === 'true') ? 'true' : 'false';
            $data['API_KEY_REQUIRED'] = (isset($data['API_KEY_REQUIRED']) && $data['API_KEY_REQUIRED'] === 'true') ? 'true' : 'false';


            $validationErrors = $this->validateSettings($data);
            if (!empty($validationErrors)) {
                SessionManager::setFlashMessage('error', implode(', ', $validationErrors));
                return Response::redirect('/settings');
            }


            $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
            $envVars = $this->parseEnvFile($envContent);


            $allowedSettings = [
                'SYSTEM_NAME' => '系统名称',
                'WEBSITE_URL' => '网站URL',
                'WEBSITE_LOGO' => '网站Logo',
                'API_SECRET_KEY' => 'API通信密钥',
                'API_ENCRYPT_METHOD' => '加密算法',
                'CLIENT_AUTH_REQUIRED' => '客户端身份验证',
                'API_KEY_REQUIRED' => 'API密钥验证',
                'JWT_EXPIRY' => 'JWT有效期',
                'RATE_LIMIT_VERIFY_MAX' => '验证接口限流',
                'RATE_LIMIT_VERIFY_PER' => '验证接口时间窗口',
                'RATE_LIMIT_LOGIN_MAX' => '登录接口限流',
                'RATE_LIMIT_LOGIN_PER' => '登录接口时间窗口',
            ];

            $updated = [];
            foreach ($allowedSettings as $key => $name) {
                if (isset($data[$key]) && $data[$key] !== ($envVars[$key] ?? '')) {
                    $envVars[$key] = $data[$key];
                    $updated[] = $name;
                }
            }

            if (empty($updated)) {
                SessionManager::setFlashMessage('info', '没有配置项被修改');
                return Response::redirect('/settings');
            }


            $newEnvContent = $this->buildEnvContent($envVars);


            if (file_exists($envPath)) {
                $this->backupEnvFile($envPath);
            }


            if (file_put_contents($envPath, $newEnvContent) === false) {
                SessionManager::setFlashMessage('error', '保存配置失败，请检查文件权限');
                return Response::redirect('/settings');
            }


            $this->adminLogModel->logAction(
                '修改系统设置',
                '修改了以下配置: ' . implode(', ', $updated),
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Settings updated', [
                'updated_settings' => $updated,
                'ip' => $request->getClientIp(),
            ]);

            SessionManager::setFlashMessage('success', '设置保存成功，部分配置需要重启服务器生效');
            return Response::redirect('/settings');

        } catch (\Exception $e) {

            error_log('Settings save error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            try {
                $this->logger->error('Save settings error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } catch (\Exception $logError) {
                error_log('Logger error: ' . $logError->getMessage());
            }

            SessionManager::setFlashMessage('error', '保存设置时发生错误: ' . $e->getMessage());
            return Response::redirect('/settings');
        } catch (\Throwable $e) {

            error_log('Settings save fatal error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            SessionManager::setFlashMessage('error', '保存设置时发生严重错误: ' . $e->getMessage());
            return Response::redirect('/settings');
        }
    }


    private function validateSettings(array $data): array
    {
        $errors = [];


        if (isset($data['SYSTEM_NAME'])) {
            if (empty($data['SYSTEM_NAME']) || strlen($data['SYSTEM_NAME']) > 100) {
                $errors[] = '系统名称不能为空且长度不能超过100字符';
            }
        }


        if (isset($data['WEBSITE_URL'])) {
            if (empty($data['WEBSITE_URL']) || !filter_var($data['WEBSITE_URL'], FILTER_VALIDATE_URL)) {
                $errors[] = '网站URL格式不正确';
            }
        }


        if (isset($data['WEBSITE_LOGO']) && !empty(trim($data['WEBSITE_LOGO']))) {
            $logo = trim($data['WEBSITE_LOGO']);


            $isUploadedFile = strpos($logo, '/storage/uploads/logos/') === 0;


            if (isset($data['_logo_uploaded']) && $data['_logo_uploaded'] === true) {

                if (!$isUploadedFile) {
                    $errors[] = '上传的Logo文件路径格式不正确';
                }
            } elseif ($isUploadedFile) {


            } else {

                $isValidUrl = filter_var($logo, FILTER_VALIDATE_URL);
                $isValidLocalPath = preg_match('/^\/[a-zA-Z0-9\/_.-]+\.(jpg|jpeg|png|gif|svg)$/i', $logo);

                if (!$isValidUrl && !$isValidLocalPath) {
                    $errors[] = '网站Logo必须是有效的URL或本地图片路径';
                }
            }
        }


        if (isset($data['API_SECRET_KEY'])) {
            if (!empty($data['API_SECRET_KEY'])) {
                if (strlen($data['API_SECRET_KEY']) < 16 || strlen($data['API_SECRET_KEY']) > 64) {
                    $errors[] = 'API通信密钥长度必须在16-64位字符之间';
                }
                if (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{}|;:,.<>?]+$/', $data['API_SECRET_KEY'])) {
                    $errors[] = 'API通信密钥只能包含字母、数字和常见符号';
                }
            }
        }


        if (isset($data['API_ENCRYPT_METHOD'])) {
            $allowedMethods = ['AES-256-CBC', 'AES-128-CBC', 'DES-EDE3-CBC'];
            if (!in_array($data['API_ENCRYPT_METHOD'], $allowedMethods)) {
                $errors[] = '不支持的加密算法';
            }
        }


        if (isset($data['JWT_EXPIRY'])) {
            $expiry = (int)$data['JWT_EXPIRY'];
            if ($expiry < 300 || $expiry > 86400) {
                $errors[] = 'JWT有效期必须在300-86400秒之间';
            }
        }


        $rateLimitFields = [
            'RATE_LIMIT_VERIFY_MAX' => [1, 1000, '验证接口最大请求数'],
            'RATE_LIMIT_VERIFY_PER' => [10, 3600, '验证接口时间窗口'],
            'RATE_LIMIT_LOGIN_MAX' => [1, 100, '登录接口最大请求数'],
            'RATE_LIMIT_LOGIN_PER' => [10, 3600, '登录接口时间窗口'],
        ];

        foreach ($rateLimitFields as $field => [$min, $max, $name]) {
            if (isset($data[$field])) {
                $value = (int)$data[$field];
                if ($value < $min || $value > $max) {
                    $errors[] = "{$name}必须在{$min}-{$max}之间";
                }
            }
        }

        return $errors;
    }


    private function parseEnvFile(string $content): array
    {
        $envVars = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $envVars[trim($key)] = trim($value, '"\'');
            }
        }

        return $envVars;
    }


    private function buildEnvContent(array $envVars): string
    {
        $content = "# 网络验证系统配置文件\n";
        $content .= "# 生成时间: " . date('Y-m-d H:i:s') . "\n\n";


        $groups = [
            '# 应用配置' => ['SYSTEM_NAME', 'WEBSITE_URL', 'WEBSITE_LOGO', 'JWT_SECRET', 'JWT_EXPIRY', 'JWT_ALGORITHM'],
            '# 数据库配置' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'],
            '# 安全配置' => ['API_SECRET_KEY', 'API_ENCRYPT_METHOD', 'CLIENT_AUTH_REQUIRED', 'API_KEY_REQUIRED'],
            '# 限流配置' => ['RATE_LIMIT_VERIFY_MAX', 'RATE_LIMIT_VERIFY_PER', 'RATE_LIMIT_LOGIN_MAX', 'RATE_LIMIT_LOGIN_PER'],
        ];

        foreach ($groups as $groupTitle => $keys) {
            $content .= $groupTitle . "\n";
            foreach ($keys as $key) {
                if (isset($envVars[$key])) {
                    $value = $envVars[$key];

                    if (preg_match('/[\s#]/', $value)) {
                        $value = '"' . $value . '"';
                    }
                    $content .= "{$key}={$value}\n";
                }
            }
            $content .= "\n";
        }


        foreach ($envVars as $key => $value) {
            $found = false;
            foreach ($groups as $keys) {
                if (in_array($key, $keys)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                if (preg_match('/[\s#]/', $value)) {
                    $value = '"' . $value . '"';
                }
                $content .= "{$key}={$value}\n";
            }
        }

        return $content;
    }


    public function changePassword(Request $request): Response
    {
        try {
            $data = $request->all();


            if (empty($data['current_password'])) {
                SessionManager::setFlashMessage('error', '请输入当前密码');
                return Response::redirect('/settings');
            }


            if (!empty($data['new_password'])) {
                if (strlen($data['new_password']) < 6) {
                    SessionManager::setFlashMessage('error', '新密码至少需要6位字符');
                    return Response::redirect('/settings');
                }

                if ($data['new_password'] !== $data['confirm_password']) {
                    SessionManager::setFlashMessage('error', '两次输入的新密码不一致');
                    return Response::redirect('/settings');
                }
            }


            $currentAdminId = SessionManager::getAdminId();
            if (!$currentAdminId) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }


            $currentAdmin = $this->adminModel->find($currentAdminId);
            if (!$currentAdmin) {
                SessionManager::setFlashMessage('error', '管理员信息不存在');
                return Response::redirect('/settings');
            }

            $changes = [];


            $currentPasswordValid = $this->adminModel->verifyPassword(
                $data['current_password'],
                $currentAdmin['password_hash']
            );

            if (!$currentPasswordValid) {
                SessionManager::setFlashMessage('error', '当前密码错误');
                return Response::redirect('/settings');
            }


            if (!empty($data['new_username']) && $data['new_username'] !== $currentAdmin['username']) {
                $updateData = ['username' => $data['new_username']];
                $this->adminModel->update($currentAdminId, $updateData);
                $changes[] = '用户名';
            }


            if (!empty($data['new_password'])) {
                $this->adminModel->updatePassword($currentAdminId, $data['new_password']);
                $changes[] = '密码';
            }

            if (empty($changes)) {
                SessionManager::setFlashMessage('info', '没有任何修改');
                return Response::redirect('/settings');
            }


            $this->adminLogModel->logAction(
                '修改管理员账号',
                '修改了: ' . implode(', ', $changes),
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', '管理员账号修改成功: ' . implode(', ', $changes));
            return Response::redirect('/settings');

        } catch (\Exception $e) {
            $this->logger->error('Change password error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '修改密码时发生错误: ' . $e->getMessage());
            return Response::redirect('/settings');
        }
    }


    public function clearCache(Request $request): Response
    {
        try {
            $clearedItems = [];


            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
                $clearedItems[] = '用户会话';
            }


            $tempDir = PROJECT_ROOT . '/storage/temp';
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*');
                $deletedFiles = 0;
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < time() - 3600) {
                        unlink($file);
                        $deletedFiles++;
                    }
                }
                if ($deletedFiles > 0) {
                    $clearedItems[] = "临时文件({$deletedFiles}个)";
                }
            }


            $logDir = PROJECT_ROOT . '/storage/logs';
            if (is_dir($logDir)) {
                $logFiles = glob($logDir . '/*.log');
                $deletedLogs = 0;
                foreach ($logFiles as $logFile) {
                    if (filemtime($logFile) < time() - 7 * 24 * 3600) {
                        unlink($logFile);
                        $deletedLogs++;
                    }
                }
                if ($deletedLogs > 0) {
                    $clearedItems[] = "旧日志文件({$deletedLogs}个)";
                }
            }


            $this->adminLogModel->logAction(
                '清理系统缓存',
                '清理了: ' . implode(', ', $clearedItems),
                $request->getClientIp(),
                $request->getUserAgent()
            );

            if (empty($clearedItems)) {
                SessionManager::setFlashMessage('info', '没有需要清理的缓存项');
                return Response::redirect('/settings');
            }

            SessionManager::setFlashMessage('success', '缓存清理成功: ' . implode(', ', $clearedItems));
            return Response::redirect('/settings');

        } catch (\Exception $e) {
            $this->logger->error('Clear cache error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '清理缓存失败: ' . $e->getMessage());
            return Response::redirect('/settings');
        }
    }


    public function exportData(Request $request): Response
    {
        try {

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $_ENV['DB_CONNECTION'] ?? 'mysql',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'auth_system',
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );

            $pdo = new \PDO(
                $dsn,
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );


            $tables = ['licenses', 'usage_logs', 'admin_logs', 'admin_settings'];
            $exportData = [];

            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT * FROM {$table}");
                    $exportData[$table] = $stmt->fetchAll();
                } catch (\Exception $e) {
                    $exportData[$table] = ['error' => $e->getMessage()];
                }
            }


            $jsonData = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


            $this->adminLogModel->logAction(
                '导出系统数据',
                '导出了所有系统数据',
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $filename = 'system_export_' . date('Y-m-d_H-i-s') . '.json';

            return new Response($jsonData, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($jsonData)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Export data error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '数据导出失败: ' . $e->getMessage());
            return Response::redirect('/settings');
        }
    }


    public function systemDiagnosis(Request $request): Response
    {
        try {

            $currentVersion = $_ENV['APP_VERSION'] ?? '2.0.0';
            $updateInfo = [];


            $coreFiles = [
                'public/index.php',
                'src/Core/Application.php',
                'src/Core/Router/Router.php',
                'src/Core/Container/Container.php',
                'src/Web/Controller/LicenseController.php',
                'src/Api/Controller/VerificationController.php',
            ];

            $missingFiles = [];
            foreach ($coreFiles as $file) {
                if (!file_exists(PROJECT_ROOT . '/' . $file)) {
                    $missingFiles[] = $file;
                }
            }


            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $_ENV['DB_CONNECTION'] ?? 'mysql',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'auth_system',
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );

            $pdo = new \PDO(
                $dsn,
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );


            $requiredTables = ['licenses', 'usage_logs', 'admin_logs'];
            $missingTables = [];

            foreach ($requiredTables as $table) {
                try {
                    $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
                } catch (\Exception $e) {
                    $missingTables[] = $table;
                }
            }


            $configFiles = [
                '.env' => '环境配置文件',
                'composer.json' => 'Composer配置文件',
                'vendor/autoload.php' => '依赖自动加载文件'
            ];

            $missingConfigs = [];
            foreach ($configFiles as $file => $name) {
                if (!file_exists(PROJECT_ROOT . '/' . $file)) {
                    $missingConfigs[] = $name;
                }
            }


            $writeableDirs = [
                'storage/logs',
                'storage/sessions',
                'storage/cache',
                'public/storage/uploads'
            ];

            $permissionIssues = [];
            foreach ($writeableDirs as $dir) {
                $fullPath = PROJECT_ROOT . '/' . $dir;
                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
                if (!is_writable($fullPath)) {
                    $permissionIssues[] = $dir;
                }
            }


            $updateInfo[] = "当前版本: {$currentVersion}";
            $updateInfo[] = "检查时间: " . date('Y-m-d H:i:s');
            $updateInfo[] = "PHP版本: " . PHP_VERSION;
            $updateInfo[] = "系统环境: " . PHP_OS;

            $hasIssues = false;

            if (empty($missingFiles)) {
                $updateInfo[] = "✅ 核心文件完整 (" . count($coreFiles) . "个)";
            } else {
                $updateInfo[] = "❌ 缺失核心文件: " . implode(', ', $missingFiles);
                $hasIssues = true;
            }

            if (empty($missingTables)) {
                $updateInfo[] = "✅ 数据库结构正常 (" . count($requiredTables) . "个表)";
            } else {
                $updateInfo[] = "❌ 缺失数据表: " . implode(', ', $missingTables);
                $hasIssues = true;
            }

            if (empty($missingConfigs)) {
                $updateInfo[] = "✅ 配置文件完整";
            } else {
                $updateInfo[] = "❌ 缺失配置文件: " . implode(', ', $missingConfigs);
                $hasIssues = true;
            }

            if (empty($permissionIssues)) {
                $updateInfo[] = "✅ 目录权限正常";
            } else {
                $updateInfo[] = "⚠️ 目录权限问题: " . implode(', ', $permissionIssues);
                $updateInfo[] = "建议执行: chmod 755 " . implode(' ', $permissionIssues);
            }


            $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
            $missingExtensions = [];
            foreach ($requiredExtensions as $ext) {
                if (!extension_loaded($ext)) {
                    $missingExtensions[] = $ext;
                }
            }

            if (empty($missingExtensions)) {
                $updateInfo[] = "✅ PHP扩展完整";
            } else {
                $updateInfo[] = "❌ 缺失PHP扩展: " . implode(', ', $missingExtensions);
                $hasIssues = true;
            }


            $memoryLimit = ini_get('memory_limit');
            $updateInfo[] = "📊 内存限制: {$memoryLimit}";


            $maxFileSize = ini_get('upload_max_filesize');
            $updateInfo[] = "📊 上传限制: {$maxFileSize}";


            $diagnosisResult = [
                'status' => $hasIssues ? 'warning' : 'success',
                'message' => $hasIssues ?
                    '⚠️ 系统检查发现问题，请根据详细信息进行修复。' :
                    '🎉 系统检查完成，一切正常！当前系统运行状态良好。',
                'version' => $currentVersion,
                'checkTime' => date('Y-m-d H:i:s'),
                'details' => $this->formatDiagnosisDetails($missingFiles, $missingTables, $missingConfigs, $permissionIssues, $missingExtensions, $memoryLimit, $maxFileSize)
            ];


            $this->adminLogModel->logAction(
                '系统诊断检查',
                implode('; ', $updateInfo),
                $request->getClientIp(),
                $request->getUserAgent()
            );


            return new Response(json_encode($diagnosisResult), 200, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('System diagnosis error', [
                'error' => $e->getMessage(),
            ]);

            return new Response(json_encode([
                'status' => 'error',
                'message' => '系统诊断失败: ' . $e->getMessage(),
                'version' => $_ENV['APP_VERSION'] ?? '2.0.0',
                'checkTime' => date('Y-m-d H:i:s'),
                'details' => []
            ]), 500, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        }
    }


    private function formatDiagnosisDetails(array $missingFiles, array $missingTables, array $missingConfigs, array $permissionIssues, array $missingExtensions, string $memoryLimit, string $maxFileSize): array
    {
        $details = [];


        $details[] = [
            'title' => '核心文件完整性',
            'icon' => '📁',
            'status' => empty($missingFiles) ? 'success' : 'warning',
            'items' => empty($missingFiles) ? [
                ['icon' => '✅', 'text' => '所有核心文件完整 (6个文件)']
            ] : array_map(fn($file) => ['icon' => '❌', 'text' => "缺失文件: {$file}"], $missingFiles)
        ];


        $details[] = [
            'title' => '数据库结构',
            'icon' => '🗄️',
            'status' => empty($missingTables) ? 'success' : 'warning',
            'items' => empty($missingTables) ? [
                ['icon' => '✅', 'text' => '数据库结构正常 (3个表)']
            ] : array_map(fn($table) => ['icon' => '❌', 'text' => "缺失数据表: {$table}"], $missingTables)
        ];


        $details[] = [
            'title' => '配置文件',
            'icon' => '⚙️',
            'status' => empty($missingConfigs) ? 'success' : 'warning',
            'items' => empty($missingConfigs) ? [
                ['icon' => '✅', 'text' => '配置文件完整']
            ] : array_map(fn($config) => ['icon' => '❌', 'text' => "缺失配置: {$config}"], $missingConfigs)
        ];


        $details[] = [
            'title' => '目录权限',
            'icon' => '🔐',
            'status' => empty($permissionIssues) ? 'success' : 'warning',
            'items' => empty($permissionIssues) ? [
                ['icon' => '✅', 'text' => '目录权限正常']
            ] : array_merge(
                [['icon' => '⚠️', 'text' => '以下目录权限有问题:']],
                array_map(fn($dir) => ['icon' => '📁', 'text' => $dir], $permissionIssues),
                [['icon' => '💡', 'text' => '建议执行: chmod 755 ' . implode(' ', $permissionIssues)]]
            )
        ];


        $details[] = [
            'title' => 'PHP扩展',
            'icon' => '🔌',
            'status' => empty($missingExtensions) ? 'success' : 'warning',
            'items' => empty($missingExtensions) ? [
                ['icon' => '✅', 'text' => 'PHP扩展完整']
            ] : array_map(fn($ext) => ['icon' => '❌', 'text' => "缺失扩展: {$ext}"], $missingExtensions)
        ];


        $details[] = [
            'title' => '系统信息',
            'icon' => '📊',
            'status' => 'success',
            'items' => [
                ['icon' => '🖥️', 'text' => 'PHP版本: ' . PHP_VERSION],
                ['icon' => '💾', 'text' => '内存限制: ' . $memoryLimit],
                ['icon' => '📁', 'text' => '上传限制: ' . $maxFileSize],
                ['icon' => '🌐', 'text' => '系统环境: ' . PHP_OS]
            ]
        ];

        return $details;
    }


    private function handleLogoUpload(array $file): ?string
    {
        try {
            \error_log('Logo upload started: ' . \json_encode($file));


            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('文件上传失败，错误代码: ' . $file['error']);
            }
            \error_log('Step 1: Upload status OK');


            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];


            $extension = \strtolower(\pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                throw new \Exception('只支持 JPG, PNG, GIF, SVG 格式的图片文件，当前文件扩展名: ' . $extension);
            }
            \error_log('Step 2: File extension validation OK: ' . $extension);


            if (\extension_loaded('fileinfo')) {
                \error_log('Step 3: Using fileinfo extension for MIME type validation');
                $finfo = \finfo_open(\FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = \finfo_file($finfo, $file['tmp_name']);
                    \finfo_close($finfo);
                    \error_log('Step 4: Detected MIME type: ' . $mimeType);

                    if (!in_array($mimeType, $allowedMimeTypes)) {
                        throw new \Exception('文件类型验证失败，检测到的MIME类型: ' . $mimeType);
                    }
                    \error_log('Step 5: MIME type validation OK');
                } else {
                    \error_log('Step 3: Failed to open fileinfo resource, using fallback validation');
                }
            } else {
                \error_log('Step 3: Fileinfo extension not available, using fallback validation');

                $fileHandle = \fopen($file['tmp_name'], 'rb');
                if ($fileHandle) {
                    $header = \fread($fileHandle, 8);
                    \fclose($fileHandle);


                    $isValidImage = false;
                    if (\substr($header, 0, 2) === "\xFF\xD8") {
                        $isValidImage = true;
                    } elseif (\substr($header, 0, 8) === "\x89PNG\r\n\x1a\n") {
                        $isValidImage = true;
                    } elseif (\substr($header, 0, 6) === 'GIF87a' || \substr($header, 0, 6) === 'GIF89a') {
                        $isValidImage = true;
                    } elseif (\strpos($header, '<svg') !== false) {
                        $isValidImage = true;
                    }

                    if (!$isValidImage) {
                        throw new \Exception('文件头验证失败，文件可能不是有效的图片格式');
                    }
                    \error_log('Step 4: File header validation OK');
                }
            }


            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new \Exception('文件大小不能超过 2MB');
            }
            \error_log('Step 6: File size validation OK (' . $file['size'] . ' bytes)');


            if (empty($file['name'])) {
                throw new \Exception('文件名不能为空');
            }
            \error_log('Step 7: Filename validation OK');


            $extension = \strtolower(\pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'logo_' . \date('Ymd_His') . '_' . \uniqid() . '.' . $extension;
            \error_log('Step 8: Generated filename: ' . $filename);


            $uploadDir = PROJECT_ROOT . '/public/storage/uploads/logos';
            \error_log('Step 9: Upload directory: ' . $uploadDir);

            if (!\is_dir($uploadDir)) {
                \error_log('Step 10: Directory does not exist, creating...');
                if (!\mkdir($uploadDir, 0755, true)) {
                    throw new \Exception('无法创建上传目录: ' . $uploadDir);
                }
                \error_log('Step 11: Directory created successfully');
            } else {
                \error_log('Step 10: Directory already exists');
            }


            if (!\is_writable($uploadDir)) {
                throw new \Exception('上传目录不可写: ' . $uploadDir);
            }
            \error_log('Step 12: Directory is writable');


            $targetPath = $uploadDir . '/' . $filename;
            \error_log('Step 13: Target path: ' . $targetPath);

            if (\move_uploaded_file($file['tmp_name'], $targetPath)) {
                \error_log('Step 14: File moved successfully');

                $this->cleanupOldLogo();


                $relativePath = '/storage/uploads/logos/' . $filename;
                \error_log('Step 15: Returning path: ' . $relativePath);
                return $relativePath;
            } else {
                throw new \Exception('文件移动失败，请检查目录权限。源文件: ' . $file['tmp_name'] . ', 目标: ' . $targetPath);
            }

        } catch (\Exception $e) {

            global $logoUploadError;
            $logoUploadError = [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'upload_dir' => PROJECT_ROOT . '/public/storage/uploads/logos',
                'tmp_file_exists' => \file_exists($file['tmp_name']),
                'upload_dir_exists' => \is_dir(PROJECT_ROOT . '/public/storage/uploads/logos'),
                'upload_dir_writable' => \is_writable(PROJECT_ROOT . '/public/storage/uploads/logos')
            ];

            \error_log('Logo upload error: ' . $e->getMessage());
            \error_log('File info: ' . \json_encode($file));

            try {
                $this->logger->error('Logo upload error', [
                    'error' => $e->getMessage(),
                    'file' => $file['name'] ?? 'unknown',
                    'size' => $file['size'] ?? 0,
                    'type' => $file['type'] ?? 'unknown'
                ]);
            } catch (\Exception $logError) {

            }

            return null;
        }
    }


    private function cleanupOldLogo(): void
    {
        try {
            $currentLogo = $_ENV['WEBSITE_LOGO'] ?? '';
            if (!empty($currentLogo) && \strpos($currentLogo, '/storage/uploads/logos/') === 0) {
                $oldFile = PROJECT_ROOT . '/public' . $currentLogo;
                if (\file_exists($oldFile)) {
                    \unlink($oldFile);
                }
            }
        } catch (\Exception $e) {

            $this->logger->warning('Failed to cleanup old logo', ['error' => $e->getMessage()]);
        }
    }


    private function backupEnvFile(string $envPath): void
    {
        try {

            $storageDir = PROJECT_ROOT . '/storage';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }


            $backupDir = $storageDir . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }


            $backupPath = $backupDir . '/.env.backup';


            if (file_exists($backupPath)) {
                unlink($backupPath);
            }


            if (copy($envPath, $backupPath)) {
                $this->logger->info('Environment file backed up successfully', [
                    'backup_path' => $backupPath
                ]);
            } else {
                $this->logger->warning('Failed to backup environment file', [
                    'source' => $envPath,
                    'backup_path' => $backupPath
                ]);
            }


            $this->cleanupOldEnvBackups();

        } catch (\Exception $e) {
            $this->logger->error('Error during environment file backup', [
                'error' => $e->getMessage(),
                'env_path' => $envPath
            ]);
        }
    }


    private function cleanupOldEnvBackups(): void
    {
        try {
            $rootDir = PROJECT_ROOT;
            $pattern = $rootDir . '/.env.backup.*';


            $backupFiles = glob($pattern);

            if ($backupFiles) {
                foreach ($backupFiles as $backupFile) {
                    if (file_exists($backupFile) && is_file($backupFile)) {
                        unlink($backupFile);
                        $this->logger->info('Removed old env backup file', [
                            'file' => basename($backupFile)
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to cleanup old env backup files', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
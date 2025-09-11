<?php

namespace AuthSystem\Core\Config;


class Config
{
    private array $config = [];

    public function __construct()
    {
        $this->loadConfig();
    }


    private function loadConfig(): void
    {

        $this->config = [
            'app' => [
                'name' => $_ENV['SYSTEM_NAME'] ?? $_ENV['APP_NAME'] ?? '网络验证系统',
                'version' => $_ENV['APP_VERSION'] ?? '2.0.0',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['WEBSITE_URL'] ?? $_ENV['APP_URL'] ?? 'http://localhost',
                'logo' => $_ENV['WEBSITE_LOGO'] ?? '',
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Shanghai',
            ],
            'database' => [
                'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['DB_PORT'] ?? 3306),
                'database' => $_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? 'auth_system',
                'username' => $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ],
            'security' => [
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
                'jwt_algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
                'jwt_expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 3600),
                'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 3600),
                'csrf_token_lifetime' => (int)($_ENV['CSRF_TOKEN_LIFETIME'] ?? 1800),
                'api_secret_key' => $_ENV['API_SECRET_KEY'] ?? '',
                'api_encrypt_method' => $_ENV['API_ENCRYPT_METHOD'] ?? 'AES-256-CBC',
                'client_auth_required' => filter_var($_ENV['CLIENT_AUTH_REQUIRED'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'api_key_required' => filter_var($_ENV['API_KEY_REQUIRED'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            'rate_limit' => [
                'verify_max' => (int)($_ENV['RATE_LIMIT_VERIFY_MAX'] ?? 10),
                'verify_per' => (int)($_ENV['RATE_LIMIT_VERIFY_PER'] ?? 60),
                'login_max' => (int)($_ENV['RATE_LIMIT_LOGIN_MAX'] ?? 5),
                'login_per' => (int)($_ENV['RATE_LIMIT_LOGIN_PER'] ?? 60),
                'api_max' => (int)($_ENV['RATE_LIMIT_API_MAX'] ?? 100),
                'api_per' => (int)($_ENV['RATE_LIMIT_API_PER'] ?? 60),
            ],
            'log' => [
                'level' => $_ENV['LOG_LEVEL'] ?? 'info',
                'file' => $_ENV['LOG_FILE'] ?? 'storage/logs/app.log',
                'max_files' => (int)($_ENV['LOG_MAX_FILES'] ?? 30),
            ],
            'cache' => [
                'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
                'ttl' => (int)($_ENV['CACHE_TTL'] ?? 3600),
            ],
            'mail' => [
                'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? '',
                'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? '',
            ],
            'storage' => [
                'path' => $_ENV['STORAGE_PATH'] ?? 'storage',
                'upload_max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
            ],
        ];
    }


    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }


    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }


    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }


    public function all(): array
    {
        return $this->config;
    }


    public function getBrandHtml(): string
    {
        $name = $this->get('app.name');
        $logo = $this->get('app.logo');

        if (!empty($logo)) {
            return '<img src="' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($name) . '" style="height: 30px; margin-right: 8px;"> ' . htmlspecialchars($name);
        } else {
            return '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($name);
        }
    }
}
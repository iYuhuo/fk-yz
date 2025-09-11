<?php

namespace AuthSystem\Core;

use AuthSystem\Core\Container\Container;
use AuthSystem\Core\Router\Router;
use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Exception\Handler;
use AuthSystem\Core\Middleware\MiddlewareStack;
use AuthSystem\Core\Config\Config;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Debug\DebugHelper;


class Application
{
    private Container $container;
    private Router $router;
    private MiddlewareStack $middleware;
    private Config $config;
    private Logger $logger;

    public function __construct()
    {
        $this->container = new Container();
        $this->config = new Config();
        $this->logger = new Logger($this->config);
        $this->router = new Router($this->container);
        $this->middleware = new MiddlewareStack($this->container);


        DebugHelper::setLogger($this->logger);

        $this->registerServices();
        $this->registerRoutes();
        $this->registerMiddleware();

        DebugHelper::log("Application initialized successfully");
    }


    public function run(): void
    {
        try {
            $request = Request::createFromGlobals();
            $response = $this->handleRequest($request);
            $response->send();
        } catch (\Throwable $e) {
            $handler = new Handler($this->logger);
            $response = $handler->handle($e);
            $response->send();
        }
    }


    private function handleRequest(Request $request): Response
    {

        $response = $this->middleware->handle($request, function ($req) {
            return $this->router->dispatch($req);
        });

        return $response;
    }


    private function registerServices(): void
    {
        $this->container->singleton(Config::class, function () {
            return $this->config;
        });

        $this->container->singleton(Logger::class, function () {
            return $this->logger;
        });

        $this->container->singleton(\PDO::class, function () {
            return $this->createDatabaseConnection();
        });


        $this->container->singleton(\AuthSystem\Models\License::class, function ($container) {
            return new \AuthSystem\Models\License($container->make(\PDO::class));
        });

        $this->container->singleton(\AuthSystem\Models\UsageLog::class, function ($container) {
            return new \AuthSystem\Models\UsageLog($container->make(\PDO::class));
        });

        $this->container->singleton(\AuthSystem\Models\AdminLog::class, function ($container) {
            return new \AuthSystem\Models\AdminLog($container->make(\PDO::class));
        });

        $this->container->singleton(\AuthSystem\Models\Admin::class, function ($container) {
            return new \AuthSystem\Models\Admin($container->make(\PDO::class));
        });


        $this->container->singleton(\AuthSystem\Core\Middleware\ApiSecurityMiddleware::class, function ($container) {
            return new \AuthSystem\Core\Middleware\ApiSecurityMiddleware(
                $container->make(Config::class),
                $container->make(Logger::class)
            );
        });

    }


    private function registerRoutes(): void
    {

        $this->registerApiRoutes();


        $this->registerWebRoutes();
    }


    private function registerApiRoutes(): void
    {

        $this->router->group('/api', function ($router) {

            $router->post('/verify', 'AuthSystem\\Api\\Controller\\VerificationController@verify');


            $router->post('/auth/login', 'AuthSystem\\Api\\Controller\\AuthController@login');
            $router->post('/auth/logout', 'AuthSystem\\Api\\Controller\\AuthController@logout');
            $router->get('/licenses', 'AuthSystem\\Api\\Controller\\LicenseController@index');
            $router->post('/licenses', 'AuthSystem\\Api\\Controller\\LicenseController@store');
            $router->put('/licenses/{id}', 'AuthSystem\\Api\\Controller\\LicenseController@update');
            $router->delete('/licenses/{id}', 'AuthSystem\\Api\\Controller\\LicenseController@destroy');
            $router->get('/logs', 'AuthSystem\\Api\\Controller\\LogController@index');
        }, [\AuthSystem\Core\Middleware\ApiSecurityMiddleware::class]);
    }


    private function registerWebRoutes(): void
    {

        $this->router->get('/login', 'AuthSystem\\Web\\Controller\\AuthController@showLogin');
        $this->router->post('/login', 'AuthSystem\\Web\\Controller\\AuthController@login');
        $this->router->get('/logout', 'AuthSystem\\Web\\Controller\\AuthController@logout');


        $this->router->get('/', 'AuthSystem\\Web\\Controller\\DashboardController@index');


        $this->registerLicenseRoutes();


        $this->router->get('/logs', 'AuthSystem\\Web\\Controller\\LogController@index');
        $this->router->get('/logs/export', 'AuthSystem\\Web\\Controller\\LogController@export');
        $this->router->post('/logs/cleanup', 'AuthSystem\\Web\\Controller\\LogController@cleanup');


        $this->router->get('/settings', 'AuthSystem\\Web\\Controller\\SettingsController@index');
        $this->router->post('/settings/save', 'AuthSystem\\Web\\Controller\\SettingsController@save');
        $this->router->post('/settings/change-password', 'AuthSystem\\Web\\Controller\\SettingsController@changePassword');
        $this->router->post('/settings/clear-cache', 'AuthSystem\\Web\\Controller\\SettingsController@clearCache');
        $this->router->get('/settings/export-data', 'AuthSystem\\Web\\Controller\\SettingsController@exportData');
        $this->router->post('/settings/system-diagnosis', 'AuthSystem\\Web\\Controller\\SettingsController@systemDiagnosis');
    }


    private function registerLicenseRoutes(): void
    {
        $this->router->get('/licenses', 'AuthSystem\\Web\\Controller\\LicenseController@index');
        $this->router->post('/licenses/create', 'AuthSystem\\Web\\Controller\\LicenseController@create');
        $this->router->post('/licenses/{id}/edit', 'AuthSystem\\Web\\Controller\\LicenseController@edit');
        $this->router->post('/licenses/{id}/delete', 'AuthSystem\\Web\\Controller\\LicenseController@delete');
        $this->router->post('/licenses/{id}/disable', 'AuthSystem\\Web\\Controller\\LicenseController@disable');
        $this->router->post('/licenses/{id}/enable', 'AuthSystem\\Web\\Controller\\LicenseController@enable');
        $this->router->post('/licenses/{id}/unbind', 'AuthSystem\\Web\\Controller\\LicenseController@unbind');
        $this->router->post('/licenses/{id}/extend', 'AuthSystem\\Web\\Controller\\LicenseController@extend');
        $this->router->post('/licenses/reorder-ids', 'AuthSystem\\Web\\Controller\\LicenseController@reorderIds');
    }


    private function registerMiddleware(): void
    {

        $this->middleware->add(\AuthSystem\Core\Middleware\AuthMiddleware::class);
        $this->middleware->add(\AuthSystem\Core\Middleware\CorsMiddleware::class);
        $this->middleware->add(\AuthSystem\Core\Middleware\RateLimitMiddleware::class);
        $this->middleware->add(\AuthSystem\Core\Middleware\SecurityMiddleware::class);
    }


    private function createDatabaseConnection(): \PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_CONNECTION'] ?? 'mysql',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'auth_system',
            $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        return new \PDO(
            $dsn,
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? '',
            $options
        );
    }
}
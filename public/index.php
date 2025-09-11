<?php


declare(strict_types=1);


error_reporting(E_ALL);
ini_set('display_errors', '0');


ini_set('memory_limit', '256M');


date_default_timezone_set('Asia/Shanghai');


define('PROJECT_ROOT', dirname(__DIR__));


if (!file_exists(PROJECT_ROOT . '/config/installed.lock')) {

    $installUrl = '../install.php';
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $installUrl = $protocol . '://' . $host . '/install.php';
    }
    header('Location: ' . $installUrl);
    exit;
}


if (!file_exists(PROJECT_ROOT . '/vendor/autoload.php')) {
    die('
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #d32f2f;">❌ 缺少依赖包</h2>
        <p>系统检测到缺少Composer依赖包，请先完成安装：</p>
        <div style="text-align: center; margin: 20px 0;">
            <a href="../install.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                进入安装页面
            </a>
        </div>
    </div>
    ');
}


require_once PROJECT_ROOT . '/vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
$dotenv->safeLoad();


try {
    $app = new AuthSystem\Core\Application();
    $app->run();
} catch (Throwable $e) {

    error_log("Application Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());


    if (defined('DEBUG') && constant('DEBUG')) {
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>系统维护中</h1><p>系统暂时无法访问，请稍后再试。</p>";
    }


    http_response_code(500);
}
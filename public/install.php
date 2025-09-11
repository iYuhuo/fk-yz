<?php



define('PROJECT_ROOT', dirname(__DIR__));


if (file_exists(PROJECT_ROOT . '/config/installed.lock')) {
    die('系统已安装，如需重新安装请删除 config/installed.lock 文件');
}


error_reporting(E_ALL);
ini_set('display_errors', 1);


date_default_timezone_set('Asia/Shanghai');


session_start();


function loadEnv($file) {
    if (!file_exists($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);


            if (($value[0] ?? '') === '"' && ($value[-1] ?? '') === '"') {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}


loadEnv(PROJECT_ROOT . '/.env');


$step = $_GET['step'] ?? 'welcome';
$allowedSteps = ['welcome', 'environment', 'database', 'dependencies', 'admin', 'install', 'complete'];

if (!in_array($step, $allowedSteps)) {
    $step = 'welcome';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 'database':

            if (!isset($_GET['test_db'])) {

                if (isset($_SESSION['db_connected']) && $_SESSION['db_connected'] === true) {
                    $_SESSION['success'] = '数据库已配置，正在跳转到依赖安装...';
                    header('Location: ?step=dependencies');
                    exit;
                }
                handleDatabaseConfig();
            }
            break;
        case 'dependencies':

            break;
        case 'admin':
            handleAdminSetup();
            break;
        case 'install':
            handleInstallation();
            break;
    }
}

function handleDatabaseConfig() {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbName = $_POST['db_name'] ?? 'auth_system';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';

    try {

        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);


        $pdo->exec("USE `{$dbName}`");


        $_SESSION['db_config'] = [
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass
        ];

        $_SESSION['db_connected'] = true;
        $_SESSION['success'] = '数据库连接成功！';
        header('Location: ?step=dependencies');
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = '数据库连接失败: ' . $e->getMessage();
    }
}



function handleAdminSetup() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($username) || empty($password) || empty($email)) {
        $_SESSION['error'] = '请填写完整的管理员信息';
        return;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = '密码长度至少6位';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = '请输入有效的邮箱地址';
        return;
    }


    $_SESSION['admin_config'] = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email
    ];

    $_SESSION['success'] = '管理员信息配置成功！';
    header('Location: ?step=install');
    exit;
}

function handleInstallation() {
    if (performInstallation()) {
        $_SESSION['success'] = '系统安装完成！';

        header('Location: /');
        exit;
    } else {

        header('Location: ?step=install');
        exit;
    }
}

function performInstallation() {
    try {

        error_log("开始创建 .env 文件");
        createEnvFile();
        error_log(".env 文件创建完成");


        error_log("开始运行数据库迁移");
        runDatabaseMigrations();
        error_log("数据库迁移完成");


        error_log("开始创建管理员账号");
        createAdminAccount();
        error_log("管理员账号创建完成");


        error_log("开始创建安装锁定文件");
        createInstallLock();
        error_log("安装锁定文件创建完成");

        error_log("系统安装完全成功");
        return true;
    } catch (Exception $e) {
        $errorMsg = '安装失败: ' . $e->getMessage();
        error_log("安装失败: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        $_SESSION['error'] = $errorMsg;
        return false;
    }
}

function createEnvFile() {
    $dbConfig = $_SESSION['db_config'];


    $jwtSecret = bin2hex(random_bytes(32));

    $envContent = "# 网络验证系统配置文件
# 生成时间: " . date('Y-m-d H:i:s') . "

# 应用配置
SYSTEM_NAME=网络验证系统
JWT_SECRET={$jwtSecret}
JWT_EXPIRY=3600
JWT_ALGORITHM=HS256

# 数据库配置
DB_CONNECTION=mysql
DB_HOST={$dbConfig['host']}
DB_PORT={$dbConfig['port']}
DB_NAME={$dbConfig['name']}
DB_USER={$dbConfig['user']}
DB_PASS={$dbConfig['pass']}
DB_CHARSET=utf8mb4

# 限流配置
RATE_LIMIT_VERIFY_MAX=10
RATE_LIMIT_VERIFY_PER=60
RATE_LIMIT_LOGIN_MAX=5
RATE_LIMIT_LOGIN_PER=60
";

    $envPath = PROJECT_ROOT . '/.env';
    if (!file_put_contents($envPath, $envContent)) {
        throw new Exception('无法创建.env文件，请检查目录权限。路径：' . $envPath);
    }
}

function runDatabaseMigrations() {
    $dbConfig = $_SESSION['db_config'];

    try {

        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);


        $dbName = $dbConfig['name'];
        $pdo->exec("USE `{$dbName}`");


        $sqlFile = PROJECT_ROOT . '/init_database_complete.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception('数据库初始化文件不存在');
        }

        $sqlContent = file_get_contents($sqlFile);


        $lines = explode("\n", $sqlContent);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim($line);

            if (!empty($line) && !preg_match('/^--/', $line)) {
                $cleanLines[] = $line;
            }
        }
        $cleanSql = implode("\n", $cleanLines);


        $sqlStatements = array_filter(
            array_map('trim', explode(';', $cleanSql)),
            function($sql) {
                $sql = trim($sql);
                return !empty($sql) &&
                       !preg_match('/^USE\s+/i', $sql) &&
                       !preg_match('/^DROP\s+DATABASE/i', $sql) &&
                       !preg_match('/^CREATE\s+DATABASE/i', $sql) &&
                       !preg_match('/^SELECT.*as\s+(message|.*_count)/i', $sql);
            }
        );


        $executedCount = 0;
        foreach ($sqlStatements as $index => $sql) {
            if (trim($sql)) {
                try {
                    error_log("执行SQL #{$index}: " . substr($sql, 0, 100) . "...");
                    $pdo->exec($sql);
                    $executedCount++;
                    error_log("SQL #{$index} 执行成功");
                } catch (PDOException $e) {

                    error_log("SQL执行失败 #{$index}: " . $sql . " - 错误: " . $e->getMessage());


                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw new Exception("SQL执行失败 #{$index}: " . $e->getMessage() . "\nSQL: " . $sql);
                    } else {
                        error_log("SQL #{$index} 表已存在，跳过");
                    }
                }
            }
        }
        error_log("数据库迁移完成，共执行 {$executedCount} 条SQL语句");

    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();


        if (strpos($errorMsg, 'Access denied') !== false) {
            throw new Exception('数据库访问被拒绝。请检查：1) 数据库用户名和密码是否正确 2) 用户是否有CREATE、ALTER、INSERT、UPDATE、DELETE、SELECT权限 3) 在堡塔面板数据库管理中确认用户权限设置');
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            throw new Exception('无法连接到数据库服务器，请检查MySQL服务是否已启动');
        } elseif (strpos($errorMsg, 'Unknown database') !== false) {
            throw new Exception('指定的数据库不存在且无法创建，请检查用户是否有CREATE权限');
        } else {
            throw new Exception('数据库迁移失败: ' . $errorMsg);
        }
    }
}

function createAdminAccount() {
    $dbConfig = $_SESSION['db_config'];
    $adminConfig = $_SESSION['admin_config'];

    try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);


        $stmt = $pdo->prepare("
            INSERT INTO admin_settings (id, username, password_hash, email)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            username = VALUES(username),
            password_hash = VALUES(password_hash),
            email = VALUES(email)
        ");

        $stmt->execute([
            $adminConfig['username'],
            $adminConfig['password'],
            $adminConfig['email']
        ]);

    } catch (PDOException $e) {
        throw new Exception('创建管理员账号失败: ' . $e->getMessage());
    }
}

function createInstallLock() {
    $lockDir = PROJECT_ROOT . '/config';
    if (!is_dir($lockDir)) {
        if (!mkdir($lockDir, 0755, true)) {
            throw new Exception('无法创建config目录');
        }
    }

    $lockContent = json_encode([
        'installed_at' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
        'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], JSON_PRETTY_PRINT);

    $lockFile = $lockDir . '/installed.lock';
    if (!file_put_contents($lockFile, $lockContent)) {
        throw new Exception('无法创建安装锁定文件，请检查目录权限。路径：' . $lockFile);
    }
}

function checkEnvironment() {
    $checks = [];


    $checks[] = [
        'name' => 'PHP版本',
        'required' => 'PHP 8.1+',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'success' : 'error'
    ];


    $requiredExtensions = [
        'pdo' => 'PDO扩展 (数据库连接)',
        'pdo_mysql' => 'PDO MySQL (MySQL数据库)',
        'json' => 'JSON扩展 (数据序列化)',
        'session' => 'Session扩展 (会话管理)',
        'mbstring' => 'Mbstring扩展 (多字节字符串)',
        'curl' => 'cURL扩展 (HTTP请求)',
        'openssl' => 'OpenSSL扩展 (加密功能)',
    ];

    foreach ($requiredExtensions as $ext => $description) {
        $checks[] = [
            'name' => $description,
            'required' => '必需',
            'current' => extension_loaded($ext) ? '已安装' : '未安装',
            'status' => extension_loaded($ext) ? 'success' : 'error'
        ];
    }


    $recommendedExtensions = [
        'fileinfo' => 'Fileinfo扩展 (文件类型检测)',
        'zip' => 'Zip扩展 (压缩文件处理)',
        'gd' => 'GD扩展 (图像处理)',
        'opcache' => 'OPcache扩展 (代码缓存)',
    ];

    foreach ($recommendedExtensions as $ext => $description) {

        $isLoaded = false;
        if ($ext === 'opcache') {
            $isLoaded = extension_loaded('opcache') || extension_loaded('Zend OPcache');
        } else {
            $isLoaded = extension_loaded($ext);
        }

        $checks[] = [
            'name' => $description,
            'required' => '推荐',
            'current' => $isLoaded ? '已安装' : '未安装',
            'status' => $isLoaded ? 'success' : 'warning'
        ];
    }


    $checks[] = [
        'name' => '文件上传',
        'required' => '启用',
        'current' => ini_get('file_uploads') ? '已启用' : '已禁用',
        'status' => ini_get('file_uploads') ? 'success' : 'warning'
    ];

    $uploadMaxSize = ini_get('upload_max_filesize');
    $checks[] = [
        'name' => '上传文件大小限制',
        'required' => '2M+',
        'current' => $uploadMaxSize,
        'status' => (int)$uploadMaxSize >= 2 ? 'success' : 'warning'
    ];

    $postMaxSize = ini_get('post_max_size');
    $checks[] = [
        'name' => 'POST数据大小限制',
        'required' => '8M+',
        'current' => $postMaxSize,
        'status' => (int)$postMaxSize >= 8 ? 'success' : 'warning'
    ];

    $memoryLimit = ini_get('memory_limit');
    $checks[] = [
        'name' => '内存限制',
        'required' => '128M+',
        'current' => $memoryLimit,
        'status' => (int)$memoryLimit >= 128 ? 'success' : 'warning'
    ];


    $checks[] = [
        'name' => '项目根目录写权限',
        'required' => '可写',
        'current' => is_writable(PROJECT_ROOT) ? '可写' : '不可写',
        'status' => is_writable(PROJECT_ROOT) ? 'success' : 'error'
    ];

    $storageDir = PROJECT_ROOT . '/storage';
    $checks[] = [
        'name' => 'storage目录写权限',
        'required' => '可写',
        'current' => is_writable($storageDir) ? '可写' : '不可写',
        'status' => is_writable($storageDir) ? 'success' : 'error'
    ];

    $publicDir = PROJECT_ROOT . '/public';
    $checks[] = [
        'name' => 'public目录写权限',
        'required' => '可写',
        'current' => is_writable($publicDir) ? '可写' : '不可写',
        'status' => is_writable($publicDir) ? 'success' : 'warning'
    ];


    $requiredDirs = [
        '/storage/logs',
        '/storage/cache',
        '/storage/sessions',
        '/public/storage/uploads/logos'
    ];

    foreach ($requiredDirs as $dir) {
        $fullPath = PROJECT_ROOT . $dir;
        $dirExists = is_dir($fullPath);

        if (!$dirExists) {
            @mkdir($fullPath, 0755, true);
            $dirExists = is_dir($fullPath);
        }

        $checks[] = [
            'name' => "目录: {$dir}",
            'required' => '存在且可写',
            'current' => $dirExists ? (is_writable($fullPath) ? '存在且可写' : '存在但不可写') : '不存在',
            'status' => ($dirExists && is_writable($fullPath)) ? 'success' : 'warning'
        ];
    }

    return $checks;
}

function checkDependencies() {
    $dependencies = [];


    $vendorDir = PROJECT_ROOT . '/vendor';
    $autoloadFile = $vendorDir . '/autoload.php';

    if (!is_dir($vendorDir) || !file_exists($autoloadFile)) {
        $dependencies[] = [
            'name' => 'Composer依赖',
            'status' => 'missing',
            'command' => 'composer install --no-dev --optimize-autoloader',
            'description' => '安装项目依赖包'
        ];
    } else {

        $keyDependencies = [
            'firebase/php-jwt',
            'monolog/monolog',
            'vlucas/phpdotenv'
        ];

        $missingDeps = [];
        foreach ($keyDependencies as $dep) {
            $depPath = $vendorDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $dep);
            if (!is_dir($depPath)) {
                $missingDeps[] = $dep;
            }
        }

        if (empty($missingDeps)) {
            $dependencies[] = [
                'name' => 'Composer依赖',
                'status' => 'success',
                'description' => '依赖包已安装'
            ];
        } else {
            $dependencies[] = [
                'name' => 'Composer依赖',
                'status' => 'missing',
                'command' => 'composer install --no-dev --optimize-autoloader',
                'description' => '缺少关键依赖: ' . implode(', ', $missingDeps)
            ];
        }
    }

    return $dependencies;
}



function getStepProgress($currentStep) {
    $steps = ['welcome', 'environment', 'database', 'dependencies', 'admin', 'install', 'complete'];
    $currentIndex = array_search($currentStep, $steps);
    return [
        'current' => $currentIndex + 1,
        'total' => count($steps),
        'percentage' => round(($currentIndex + 1) / count($steps) * 100)
    ];
}

$progress = getStepProgress($step);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网络验证系统 - 安装引导</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .install-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .install-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }

        .install-header {
            background: var(--success-gradient);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .install-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .install-header h1 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-weight: 300;
            font-size: 2.5rem;
        }

        .install-header .subtitle {
            position: relative;
            z-index: 1;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .progress-section {
            padding: 1.5rem 2rem 0;
            background: #f8f9fa;
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-gradient);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .step-item {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-weight: 500;
        }

        .step-item.active {
            color: #0066cc;
            font-weight: 600;
        }

        .step-item.completed {
            color: #28a745;
        }

        .step-item i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        .install-content {
            padding: 2.5rem;
        }

        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            transition: transform 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
        }

        .btn-modern {
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary.btn-modern {
            background: var(--primary-gradient);
        }

        .btn-success.btn-modern {
            background: var(--success-gradient);
        }

        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .alert-modern {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }

        .form-control-modern {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .check-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .check-table th {
            background: #f8f9fa;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
        }

        .check-table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="container">
            <div class="install-card fade-in">
                <div class="install-header">
                    <i class="bi bi-shield-check mb-3" style="font-size: 3rem;"></i>
                    <h1>网络验证系统</h1>
                    <p class="subtitle mb-0">安装引导</p>
                            </div>

                <div class="progress-section">
                    <div class="step-indicator">
                        <div class="step-item <?= $step === 'welcome' ? 'active' : ($progress['current'] > 1 ? 'completed' : '') ?>">
                            <i class="bi bi-house-door"></i> 欢迎
                                </div>
                        <div class="step-item <?= $step === 'environment' ? 'active' : ($progress['current'] > 2 ? 'completed' : '') ?>">
                            <i class="bi bi-gear"></i> 环境
                                </div>
                        <div class="step-item <?= $step === 'database' ? 'active' : ($progress['current'] > 3 ? 'completed' : '') ?>">
                            <i class="bi bi-database"></i> 数据库
                                </div>
                        <div class="step-item <?= $step === 'dependencies' ? 'active' : ($progress['current'] > 4 ? 'completed' : '') ?>">
                            <i class="bi bi-box"></i> 依赖
                                </div>
                        <div class="step-item <?= $step === 'admin' ? 'active' : ($progress['current'] > 5 ? 'completed' : '') ?>">
                            <i class="bi bi-person-gear"></i> 管理员
                                        </div>
                        <div class="step-item <?= $step === 'install' ? 'active' : ($progress['current'] > 6 ? 'completed' : '') ?>">
                            <i class="bi bi-download"></i> 安装
                                    </div>
                        <div class="step-item <?= $step === 'complete' ? 'active' : '' ?>">
                            <i class="bi bi-check-circle"></i> 完成
                                    </div>
                                </div>

                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width: <?= $progress['percentage'] ?>%"></div>
                                </div>

                    <div class="text-center text-muted">
                        步骤 <?= $progress['current'] ?> / <?= $progress['total'] ?> (<?= $progress['percentage'] ?>%)
                                    </div>
                                </div>

                <div class="install-content">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-modern">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($_SESSION['error']) ?>
                                </div>
                        <?php unset($_SESSION['error']); ?>
                                            <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-modern">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['success']) ?>
                                </div>
                        <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>

                    <?php
                    switch ($step) {
                        case 'welcome':
                            include 'install_steps/welcome.php';
                            break;
                        case 'environment':
                            include 'install_steps/environment.php';
                            break;
                        case 'database':
                            include 'install_steps/database.php';
                            break;
                        case 'dependencies':
                            include 'install_steps/dependencies.php';
                            break;
                        case 'admin':
                            include 'install_steps/admin.php';
                            break;
                        case 'install':
                            include 'install_steps/install.php';
                            break;
                        case 'complete':
                            include 'install_steps/complete.php';
                            break;
                        default:
                            echo '<div class="text-center"><h3>未知步骤</h3></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {

            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>处理中...';
                    }
                });
            });


            const inputs = document.querySelectorAll('.form-control-modern');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.2s ease';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });


        function testConnection() {
            const form = document.querySelector('#database-form');
            const formData = new FormData(form);

            fetch('?step=database&test=1', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alert = document.createElement('div');
                alert.className = `alert alert-${data.success ? 'success' : 'danger'} alert-modern`;
                alert.innerHTML = `<i class="bi bi-${data.success ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${data.message}`;

                const existingAlert = document.querySelector('.test-result');
                if (existingAlert) {
                    existingAlert.remove();
                }

                alert.classList.add('test-result');
                form.insertBefore(alert, form.firstChild);
            });
        }
    </script>
</body>
</html>
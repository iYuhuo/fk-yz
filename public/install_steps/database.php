<?php

$test_success = '';
$test_error = '';


if (isset($_POST['test_connection']) && $_POST['test_connection'] === '1' && isset($_GET['test_db'])) {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbName = $_POST['db_name'] ?? 'auth_system';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';

    try {

        if (!extension_loaded('pdo_mysql')) {
            $test_error = '错误：PDO MySQL扩展未安装。请安装php-mysql扩展。';
        } else {
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);


            $result = $pdo->query("SELECT VERSION() as version");
            $info = $result->fetch(PDO::FETCH_ASSOC);



            try {
                $pdo->exec("USE `{$dbName}`");
                $dbConnected = true;
            } catch (PDOException $useError) {
                throw new PDOException("无法连接到数据库 '{$dbName}'，请确认该数据库已在堡塔面板中创建: " . $useError->getMessage());
            }


            $testTableName = "test_table_" . time();
            try {

                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$testTableName}` (id INT PRIMARY KEY, name VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");


                $pdo->exec("INSERT INTO `{$testTableName}` (id, name) VALUES (1, 'connection_test')");


                $result = $pdo->query("SELECT * FROM `{$testTableName}` WHERE id = 1");
                $testData = $result->fetch(PDO::FETCH_ASSOC);


                $pdo->exec("UPDATE `{$testTableName}` SET name = 'updated_test' WHERE id = 1");


                $pdo->exec("DELETE FROM `{$testTableName}` WHERE id = 1");


                $pdo->exec("DROP TABLE IF EXISTS `{$testTableName}`");


                $_SESSION['db_config'] = [
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass
                ];
                $_SESSION['db_connected'] = true;

                $test_success = "✅ 数据库连接成功！<br>" .
                    "<strong>MySQL版本:</strong> " . $info['version'] . "<br>" .
                    "<strong>数据库:</strong> {$dbName}<br>" .
                    "<strong>权限测试:</strong> 用户 '{$dbUser}' 对数据库 '{$dbName}' 具有完整的表操作权限（CREATE、SELECT、INSERT、UPDATE、DELETE）。<br>" .
                    "<strong>✨ 配置已保存，您现在可以点击【下一步】继续安装。</strong>";

            } catch (PDOException $e) {

                try {
                    $pdo->exec("DROP TABLE IF EXISTS `{$testTableName}`");
                } catch (PDOException $cleanupError) {

                }

                $grantsInfo = "";

                if (strpos($e->getMessage(), 'Access denied') !== false) {
                    $test_error = '⚠️ 基本连接成功，但某些操作权限不足。<br>' .
                        "<strong>用户名:</strong> '{$dbUser}'<br>" .
                        "<strong>MySQL版本:</strong> " . $info['version'] . "<br>" .
                        "<strong>建议:</strong> 检查用户是否对数据库 '{$dbName}' 有完整权限";
                } else {
                    $test_error = '❌ 权限测试失败<br>' .
                        "<strong>错误信息:</strong> " . htmlspecialchars($e->getMessage()) . "<br>" .
                        "<strong>用户名:</strong> '{$dbUser}'";
                }
            }
        }

    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'Access denied') !== false) {
            $test_error = '数据库认证失败：用户名或密码错误，或者用户不存在。请检查堡塔面板数据库设置。';
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            $test_error = '连接被拒绝：MySQL服务未启动或端口配置错误。';
        } elseif (strpos($errorMsg, 'Unknown MySQL server host') !== false) {
            $test_error = '未知的MySQL服务器主机，请检查主机地址配置。';
        } else {
            $test_error = '连接失败: ' . htmlspecialchars($errorMsg);
        }
    }
}


if (isset($_GET['test']) && $_SERVER['REQUEST_METHOD'] === 'POST') {



    header('Content-Type: text/plain; charset=utf-8');

    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbName = $_POST['db_name'] ?? 'auth_system';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';

    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);


        $pdo->query("SELECT 1");


        $testDbName = "test_conn_" . time();
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDbName}`");
            $pdo->exec("USE `{$testDbName}`");


            $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(50))");


            $pdo->exec("INSERT INTO test_table (id, name) VALUES (1, 'test')");


            $result = $pdo->query("SELECT * FROM test_table WHERE id = 1");


            $pdo->exec("DELETE FROM test_table WHERE id = 1");


            $pdo->exec("DROP DATABASE `{$testDbName}`");

            echo '数据库连接成功！用户具有完整的权限（CREATE、SELECT、INSERT、UPDATE、DELETE）。';

        } catch (PDOException $e) {

            try {
                $pdo->exec("DROP DATABASE IF EXISTS `{$testDbName}`");
            } catch (PDOException $cleanupError) {

            }

            if (strpos($e->getMessage(), 'Access denied') !== false) {
                echo '连接成功，但权限不足。请在堡塔面板数据库管理中为用户 "' . htmlspecialchars($dbUser) . '" 添加以下权限：CREATE、ALTER、INSERT、UPDATE、DELETE、SELECT、DROP';
            } else {
                throw $e;
            }
        }

    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'Access denied') !== false) {
            echo '数据库认证失败：用户名或密码错误，或者用户不存在。请检查堡塔面板数据库设置。';
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            echo '连接被拒绝：MySQL服务未启动或端口配置错误。';
        } elseif (strpos($errorMsg, 'Unknown MySQL server host') !== false) {
            echo '未知的MySQL服务器主机，请检查主机地址配置。';
        } else {
            echo '连接失败: ' . htmlspecialchars($errorMsg);
        }
    }
    exit;
}
?>

<div class="text-center mb-4">
    <i class="bi bi-database mb-3" style="font-size: 3rem; color: #667eea;"></i>
    <h2>数据库配置</h2>
    <p class="text-muted">配置数据库连接信息</p>
</div>

<?php if (!empty($test_success)): ?>
    <div class="alert alert-success alert-modern">
        <i class="bi bi-check-circle me-2"></i>
        <?= $test_success ?>
    </div>
<?php endif; ?>

<?php if (!empty($test_error)): ?>
    <div class="alert alert-danger alert-modern">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= $test_error ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['test_success'])): ?>
    <div class="alert alert-success alert-modern">
        <i class="bi bi-check-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['test_success']) ?>
    </div>
    <?php unset($_SESSION['test_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['test_error'])): ?>
    <div class="alert alert-danger alert-modern">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($_SESSION['test_error']) ?>
    </div>
    <?php unset($_SESSION['test_error']); ?>
<?php endif; ?>

<form method="POST" action="?step=database" id="database-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="db_host" class="form-label">数据库主机</label>
            <input type="text" class="form-control form-control-modern" id="db_host" name="db_host"
                   value="<?= htmlspecialchars($_POST['db_host'] ?? $_SESSION['db_config']['host'] ?? 'localhost') ?>" required>
            <div class="form-text">通常为 localhost 或 127.0.0.1</div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="db_port" class="form-label">端口</label>
            <input type="number" class="form-control form-control-modern" id="db_port" name="db_port"
                   value="<?= htmlspecialchars($_POST['db_port'] ?? $_SESSION['db_config']['port'] ?? '3306') ?>" min="1" max="65535" required>
            <div class="form-text">MySQL 默认端口为 3306</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="db_name" class="form-label">数据库名称</label>
        <input type="text" class="form-control form-control-modern" id="db_name" name="db_name"
               value="<?= htmlspecialchars($_POST['db_name'] ?? $_SESSION['db_config']['name'] ?? 'auth_system') ?>" required>
        <div class="form-text">如果数据库不存在，将自动创建</div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="db_user" class="form-label">用户名</label>
            <input type="text" class="form-control form-control-modern" id="db_user" name="db_user"
                   value="<?= htmlspecialchars($_POST['db_user'] ?? $_SESSION['db_config']['user'] ?? 'root') ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label for="db_pass" class="form-label">密码</label>
            <input type="password" class="form-control form-control-modern" id="db_pass" name="db_pass"
                   value="<?= htmlspecialchars($_POST['db_pass'] ?? $_SESSION['db_config']['pass'] ?? '') ?>">
            <div class="form-text">如果没有密码请留空</div>
        </div>
    </div>

    <div class="alert alert-info alert-modern">
        <i class="bi bi-info-circle me-2"></i>
        <strong>权限要求：</strong>
        数据库用户需要具有 CREATE、ALTER、INSERT、UPDATE、DELETE、SELECT 权限。
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
        <button type="button" class="btn btn-outline-primary btn-modern" onclick="testDatabaseConnection()">
            <i class="bi bi-database-check me-2"></i>测试数据库连接
        </button>
    </div>

    <div class="d-flex justify-content-between">
        <a href="?step=environment" class="btn btn-outline-secondary btn-modern">
            <i class="bi bi-arrow-left me-2"></i>上一步
        </a>

        <?php if (isset($_SESSION['db_connected']) && $_SESSION['db_connected'] === true): ?>
            <a href="?step=dependencies" class="btn btn-success btn-modern">
                数据库已配置，继续安装<i class="bi bi-arrow-right ms-2"></i>
            </a>
        <?php else: ?>
            <button type="submit" class="btn btn-primary btn-modern">
                下一步<i class="bi bi-arrow-right ms-2"></i>
            </button>
        <?php endif; ?>
    </div>
</form>

<!-- 隐藏的测试表单 -->
<form id="test-form" method="POST" action="?step=database&test_db=1" style="display: none;">
    <input type="hidden" name="test_connection" value="1">
    <input type="hidden" name="db_host" id="test_db_host">
    <input type="hidden" name="db_port" id="test_db_port">
    <input type="hidden" name="db_name" id="test_db_name">
    <input type="hidden" name="db_user" id="test_db_user">
    <input type="hidden" name="db_pass" id="test_db_pass">
</form>

<div class="row mt-5">
    <div class="col-md-6">
        <div class="feature-card">
            <h6><i class="bi bi-lightbulb text-warning me-2"></i>配置提示</h6>
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-dot"></i> 确保 MySQL 服务已启动</li>
                <li><i class="bi bi-dot"></i> 建议使用专门的数据库用户</li>
                <li><i class="bi bi-dot"></i> 密码应包含字母和数字</li>
                <li><i class="bi bi-dot"></i> 避免使用 root 用户（生产环境）</li>
            </ul>
        </div>
    </div>

    <div class="col-md-6">
        <div class="feature-card">
            <h6><i class="bi bi-database-gear text-info me-2"></i>数据库信息</h6>
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-dot"></i> 字符集: UTF8MB4</li>
                <li><i class="bi bi-dot"></i> 排序规则: utf8mb4_unicode_ci</li>
                <li><i class="bi bi-dot"></i> 存储引擎: InnoDB</li>
                <li><i class="bi bi-dot"></i> 预计占用: ~10MB</li>
            </ul>
        </div>
    </div>
</div>

<script>
function testDatabaseConnection() {
    console.log('testDatabaseConnection called');

    const mainForm = document.getElementById('database-form');
    const testForm = document.getElementById('test-form');

    if (!mainForm || !testForm) {
        alert('表单未找到，请刷新页面重试');
        return;
    }


    document.getElementById('test_db_host').value = document.getElementById('db_host').value;
    document.getElementById('test_db_port').value = document.getElementById('db_port').value;
    document.getElementById('test_db_name').value = document.getElementById('db_name').value;
    document.getElementById('test_db_user').value = document.getElementById('db_user').value;
    document.getElementById('test_db_pass').value = document.getElementById('db_pass').value;


    testForm.submit();
}
</script>
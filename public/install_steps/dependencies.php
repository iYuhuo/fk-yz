<?php $dependencies = checkDependencies(); ?>

<div class="text-center mb-4">
    <i class="bi bi-box-seam mb-3" style="font-size: 3rem; color: #667eea;"></i>
    <h2>依赖检查</h2>
    <p class="text-muted">检查并安装项目依赖</p>
</div>

<div class="check-table">
    <table class="table mb-0">
        <thead>
            <tr>
                <th style="width: 40%;">依赖项目</th>
                <th style="width: 40%;">描述</th>
                <th style="width: 20%;">状态</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dependencies as $dep): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($dep['name']) ?></strong>
                    <?php if (isset($dep['command'])): ?>
                    <br><code class="small text-muted"><?= htmlspecialchars($dep['command']) ?></code>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="text-muted"><?= htmlspecialchars($dep['description']) ?></span>
                </td>
                <td>
                    <?php if ($dep['status'] === 'success'): ?>
                        <span class="status-badge status-success">
                            <i class="bi bi-check-circle"></i> 已安装
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-error">
                            <i class="bi bi-x-circle"></i> 未安装
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (isset($_SESSION['install_results'])): ?>
<div class="mt-4">
    <h5><i class="bi bi-terminal me-2"></i>安装结果</h5>
    <?php foreach ($_SESSION['install_results'] as $result): ?>
    <div class="alert alert-<?= $result['status'] === 'success' ? 'success' : 'danger' ?> alert-modern">
        <strong><?= htmlspecialchars($result['name']) ?>:</strong>
        <?= htmlspecialchars($result['message']) ?>
    </div>
    <?php endforeach; ?>
    <?php unset($_SESSION['install_results']); ?>
</div>
<?php endif; ?>

<?php
$needsInstall = false;
foreach ($dependencies as $dep) {
    if ($dep['status'] !== 'success') {
        $needsInstall = true;
        break;
    }
}
?>

<?php if ($needsInstall): ?>
<div class="alert alert-warning alert-modern mt-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>发现缺失的依赖！</strong>
    请按照下方步骤手动安装依赖后刷新页面。
</div>

<div class="alert alert-info alert-modern">
    <h6><i class="bi bi-terminal me-2"></i>安装步骤</h6>
    <ol class="mb-2">
        <li>使用SSH或服务器面板的终端功能</li>
        <li>进入项目根目录</li>
        <li>执行安装命令</li>
        <li>刷新此页面检查结果</li>
    </ol>

    <div class="bg-dark text-light p-3 rounded mt-3" style="font-family: 'Courier New', monospace;">
        <div class="text-warning"># 宝塔面板用户</div>
        <div>cd /www/wwwroot/your-site</div>
        <div>composer install --no-dev --optimize-autoloader</div>

        <div class="text-warning mt-3"># PHPStudy用户</div>
        <div>cd F:\phpstudy_pro\WWW\your-site</div>
        <div>composer install --no-dev --optimize-autoloader</div>

        <div class="text-warning mt-3"># 如果提示composer命令不存在</div>
        <div>curl -sS https:
        <div>php composer.phar install --no-dev --optimize-autoloader</div>

        <div class="text-success mt-3"># 安装完成后检查vendor目录</div>
        <div>ls -la vendor/</div>
    </div>

    <div class="text-center mt-3">
        <button onclick="window.location.reload()" class="btn btn-primary btn-modern">
            <i class="bi bi-arrow-clockwise me-2"></i>刷新检查结果
        </button>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success alert-modern mt-4">
    <i class="bi bi-check-circle me-2"></i>
    <strong>所有依赖已安装！</strong>
    您可以继续下一步配置。
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="feature-card">
            <h6><i class="bi bi-info-circle text-info me-2"></i>关于 Composer</h6>
            <p class="small text-muted">
                Composer 是 PHP 的依赖管理工具，用于管理项目所需的第三方库。
                如果您的系统中没有安装 Composer，请先访问
                <a href="https://getcomposer.org/" target="_blank">getcomposer.org</a>
                进行安装。
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="feature-card">
            <h6><i class="bi bi-list-ul text-primary me-2"></i>主要依赖库</h6>
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-dot"></i> Firebase JWT - JWT 认证</li>
                <li><i class="bi bi-dot"></i> Monolog - 日志系统</li>
                <li><i class="bi bi-dot"></i> vlucas/phpdotenv - 环境变量</li>
                <li><i class="bi bi-dot"></i> PHPUnit - 单元测试</li>
            </ul>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="feature-card">
            <h6><i class="bi bi-terminal text-success me-2"></i>手动安装指令</h6>
            <p class="small text-muted mb-2">如果自动安装失败，您可以在项目根目录执行以下命令：</p>
            <div class="bg-dark text-light p-3 rounded" style="font-family: 'Courier New', monospace;">
                <div># 生产环境安装</div>
                <div>composer install --no-dev --optimize-autoloader</div>
                <div class="mt-2"># 开发环境安装（包含开发工具）</div>
                <div>composer install</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-5">
    <a href="?step=database" class="btn btn-outline-secondary btn-modern">
        <i class="bi bi-arrow-left me-2"></i>上一步
    </a>

    <?php if (!$needsInstall): ?>
    <a href="?step=admin" class="btn btn-primary btn-modern">
        下一步<i class="bi bi-arrow-right ms-2"></i>
    </a>
    <?php else: ?>
    <button class="btn btn-secondary btn-modern" disabled>
        请先安装依赖
    </button>
    <?php endif; ?>
</div>
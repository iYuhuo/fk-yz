<?php $envChecks = checkEnvironment(); ?>

<div class="text-center mb-4">
    <i class="bi bi-pc-display-horizontal mb-3" style="font-size: 3rem; color: #667eea;"></i>
    <h2>环境检测</h2>
    <p class="text-muted">检查服务器环境是否满足安装要求</p>
</div>

<div class="check-table">
    <table class="table mb-0">
        <thead>
            <tr>
                <th style="width: 30%;">检查项目</th>
                <th style="width: 25%;">要求</th>
                <th style="width: 25%;">当前状态</th>
                <th style="width: 20%;">结果</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($envChecks as $check): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($check['name']) ?></strong>
                </td>
                <td>
                    <span class="text-muted"><?= htmlspecialchars($check['required']) ?></span>
                </td>
                <td>
                    <?= htmlspecialchars($check['current']) ?>
                </td>
                <td>
                    <?php if ($check['status'] === 'success'): ?>
                        <span class="status-badge status-success">
                            <i class="bi bi-check-circle"></i> 正常
                        </span>
                    <?php elseif ($check['status'] === 'warning'): ?>
                        <span class="status-badge status-warning">
                            <i class="bi bi-exclamation-triangle"></i> 警告
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-error">
                            <i class="bi bi-x-circle"></i> 错误
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$hasErrors = false;
$hasWarnings = false;
foreach ($envChecks as $check) {
    if ($check['status'] === 'error') {
        $hasErrors = true;
    } elseif ($check['status'] === 'warning') {
        $hasWarnings = true;
    }
}
?>

<?php if ($hasErrors): ?>
<div class="alert alert-danger alert-modern mt-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>发现严重问题！</strong>
    请先解决上述错误项目后再继续安装。这些问题可能导致系统无法正常运行。
</div>
<?php elseif ($hasWarnings): ?>
<div class="alert alert-warning alert-modern mt-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>发现警告！</strong>
    建议先解决这些问题，但不会阻止安装进程。这些问题可能影响部分功能。
</div>
<?php else: ?>
<div class="alert alert-success alert-modern mt-4">
    <i class="bi bi-check-circle me-2"></i>
    <strong>环境检测通过！</strong>
    您的服务器环境完全满足安装要求。
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="feature-card">
            <h6><i class="bi bi-list-check text-success me-2"></i>系统要求</h6>
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-dot"></i> PHP 8.1+ (当前: <?= PHP_VERSION ?>)</li>
                <li><i class="bi bi-dot"></i> MySQL 5.7+ 或 MariaDB 10.3+</li>
                <li><i class="bi bi-dot"></i> 内存: 128MB+</li>
                <li><i class="bi bi-dot"></i> 磁盘空间: 200MB+</li>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="feature-card">
            <h6><i class="bi bi-puzzle text-warning me-2"></i>PHP扩展安装</h6>
            <div class="small text-muted">
                <strong>Ubuntu/Debian:</strong><br>
                <code class="small">sudo apt-get install php-pdo php-mysql php-json php-mbstring php-curl php-openssl php-fileinfo</code><br><br>
                <strong>宝塔面板:</strong><br>
                软件商店 → PHP设置 → 安装扩展
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="feature-card">
            <h6><i class="bi bi-shield-check text-primary me-2"></i>安全建议</h6>
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-dot"></i> 禁用 debug 模式</li>
                <li><i class="bi bi-dot"></i> 使用 HTTPS 协议</li>
                <li><i class="bi bi-dot"></i> 定期更新 PHP 版本</li>
                <li><i class="bi bi-dot"></i> 设置防火墙规则</li>
            </ul>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-5">
    <a href="?step=welcome" class="btn btn-outline-secondary btn-modern">
        <i class="bi bi-arrow-left me-2"></i>上一步
    </a>

    <?php if (!$hasErrors): ?>
    <a href="?step=database" class="btn btn-primary btn-modern">
        下一步<i class="bi bi-arrow-right ms-2"></i>
    </a>
    <?php else: ?>
    <button class="btn btn-secondary btn-modern" disabled>
        请先解决环境问题
    </button>
    <?php endif; ?>
</div>
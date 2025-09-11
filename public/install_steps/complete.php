<div class="text-center mb-5">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
    </div>
    <h1 class="text-success mb-3">🎉 安装完成！</h1>
    <p class="lead text-muted">网络验证系统已成功安装并配置完毕</p>
</div>

<div class="row mb-5">
    <div class="col-md-4 mb-3">
        <div class="feature-card text-center">
            <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="bi bi-speedometer2"></i>
            </div>
            <h6>系统性能</h6>
            <p class="small text-muted">高效的许可证验证，毫秒级响应</p>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="feature-card text-center">
            <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="bi bi-shield-check"></i>
            </div>
            <h6>安全保障</h6>
            <p class="small text-muted">JWT加密，多重安全防护机制</p>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="feature-card text-center">
            <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="bi bi-graph-up"></i>
            </div>
            <h6>数据统计</h6>
            <p class="small text-muted">详细的使用日志和统计分析</p>
        </div>
    </div>
</div>

<div class="feature-card mb-4">
    <h5><i class="bi bi-info-circle text-primary me-2"></i>系统信息</h5>
    <div class="row">
        <div class="col-md-6">
            <ul class="list-unstyled">
                <li><strong>系统版本:</strong> 2.0.0</li>
                <li><strong>安装时间:</strong> <?= date('Y-m-d H:i:s') ?></li>
                <li><strong>PHP版本:</strong> <?= PHP_VERSION ?></li>
                <li><strong>数据库:</strong> <?= htmlspecialchars($_SESSION['db_config']['name'] ?? 'N/A') ?></li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul class="list-unstyled">
                <li><strong>管理员:</strong> <?= htmlspecialchars($_SESSION['admin_config']['username'] ?? 'N/A') ?></li>
                <li><strong>服务器时间:</strong> <?= date('Y-m-d H:i:s') ?></li>
                <li><strong>时区:</strong> <?= date_default_timezone_get() ?></li>
                <li><strong>字符编码:</strong> UTF-8</li>
            </ul>
        </div>
    </div>
</div>

<div class="feature-card mb-4">
    <h5><i class="bi bi-bookmark-check text-success me-2"></i>接下来该做什么？</h5>
    <div class="row">
        <div class="col-md-6">
            <h6>👨‍💼 管理员任务</h6>
            <ul class="small text-muted">
                <li>登录管理后台，熟悉界面操作</li>
                <li>创建第一批许可证进行测试</li>
                <li>配置系统设置和安全选项</li>
                <li>下载客户端SDK进行集成</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6>🔧 系统优化</h6>
            <ul class="small text-muted">
                <li>配置Web服务器（Nginx/Apache）</li>
                <li>设置SSL证书启用HTTPS</li>
                <li>配置定时任务清理日志</li>
                <li>备份数据库和配置文件</li>
            </ul>
        </div>
    </div>
</div>

<div class="alert alert-success alert-modern mb-4">
    <i class="bi bi-lightbulb me-2"></i>
    <strong>温馨提示：</strong>
    为了系统安全，建议删除本安装文件 (install.php) 或将其移动到安全位置。
    如需重新安装，请删除 config/installed.lock 文件。
</div>

<div class="feature-card mb-4">
    <h5><i class="bi bi-link-45deg text-info me-2"></i>有用的链接</h5>
    <div class="row">
        <div class="col-md-6">
            <ul class="list-unstyled">
                <li><a href="/"><i class="bi bi-house me-1"></i>系统首页</a></li>
                <li><a href="/licenses"><i class="bi bi-key me-1"></i>许可证管理</a></li>
                <li><a href="/logs"><i class="bi bi-list-ul me-1"></i>系统日志</a></li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul class="list-unstyled">
                <li><a href="/settings"><i class="bi bi-gear me-1"></i>系统设置</a></li>
                <li><a href="/client/php/AuthClient.php" target="_blank"><i class="bi bi-file-earmark-code me-1"></i>PHP客户端</a></li>
                <li><a href="/client/python/auth_client.py" target="_blank"><i class="bi bi-file-earmark-code me-1"></i>Python客户端</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="text-center">
    <a href="/" class="btn btn-success btn-modern btn-lg me-3">
        <i class="bi bi-house me-2"></i>进入系统
    </a>
    <a href="/licenses" class="btn btn-primary btn-modern btn-lg">
        <i class="bi bi-key me-2"></i>管理许可证
    </a>
</div>

<div class="text-center mt-5">
    <div class="feature-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h6>感谢您选择网络验证系统！</h6>
        <p class="small mb-0 opacity-75">
            如果您在使用过程中遇到任何问题，请参考文档或联系技术支持。<br>
            祝您使用愉快！
        </p>
    </div>
</div>

<script>

<?php

unset($_SESSION['db_config']);
unset($_SESSION['admin_config']);
unset($_SESSION['db_connected']);
?>


document.addEventListener('DOMContentLoaded', function() {

    const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7'];

    function createConfetti() {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            top: -10px;
            left: ${Math.random() * 100}%;
            opacity: 0.8;
            pointer-events: none;
            z-index: 1000;
            border-radius: 50%;
        `;

        document.body.appendChild(confetti);


        const fallDuration = Math.random() * 3000 + 2000;
        const rotation = Math.random() * 360;

        confetti.animate([
            { transform: `translateY(-10px) rotate(0deg)`, opacity: 0.8 },
            { transform: `translateY(${window.innerHeight + 10}px) rotate(${rotation}deg)`, opacity: 0 }
        ], {
            duration: fallDuration,
            easing: 'linear'
        }).onfinish = () => confetti.remove();
    }


    const confettiInterval = setInterval(createConfetti, 150);


    setTimeout(() => {
        clearInterval(confettiInterval);
    }, 3000);


    setTimeout(() => {
        window.location.href = '/';
    }, 5000);


    let countdown = 5;
    const countdownElement = document.createElement('div');
    countdownElement.className = 'alert alert-info text-center mt-4';
    countdownElement.innerHTML = `<i class="bi bi-clock me-2"></i>页面将在 <span id="countdown">${countdown}</span> 秒后自动跳转到系统首页...`;

    document.querySelector('.text-center:last-of-type').before(countdownElement);

    const countdownInterval = setInterval(() => {
        countdown--;
        document.getElementById('countdown').textContent = countdown;
        if (countdown <= 0) {
            clearInterval(countdownInterval);
        }
    }, 1000);
});
</script>
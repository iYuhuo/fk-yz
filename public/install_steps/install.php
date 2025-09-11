<div class="text-center mb-4">
    <i class="bi bi-download mb-3" style="font-size: 3rem; color: #667eea;"></i>
    <h2>系统安装</h2>
    <p class="text-muted">即将开始安装系统，请确认配置信息</p>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="feature-card">
            <h6><i class="bi bi-database text-primary me-2"></i>数据库配置</h6>
            <ul class="list-unstyled small">
                <li><strong>主机:</strong> <?= htmlspecialchars($_SESSION['db_config']['host'] ?? 'N/A') ?></li>
                <li><strong>端口:</strong> <?= htmlspecialchars($_SESSION['db_config']['port'] ?? 'N/A') ?></li>
                <li><strong>数据库:</strong> <?= htmlspecialchars($_SESSION['db_config']['name'] ?? 'N/A') ?></li>
                <li><strong>用户:</strong> <?= htmlspecialchars($_SESSION['db_config']['user'] ?? 'N/A') ?></li>
            </ul>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="feature-card">
            <h6><i class="bi bi-person-gear text-success me-2"></i>管理员配置</h6>
            <ul class="list-unstyled small">
                <li><strong>用户名:</strong> <?= htmlspecialchars($_SESSION['admin_config']['username'] ?? 'N/A') ?></li>
                <li><strong>邮箱:</strong> <?= htmlspecialchars($_SESSION['admin_config']['email'] ?? 'N/A') ?></li>
                <li><strong>密码:</strong> ********</li>
            </ul>
        </div>
    </div>
</div>

<div class="feature-card mb-4">
    <h6><i class="bi bi-list-check text-info me-2"></i>安装内容</h6>
    <div class="row">
        <div class="col-md-6">
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-check-circle text-success"></i> 创建环境配置文件 (.env)</li>
                <li><i class="bi bi-check-circle text-success"></i> 初始化数据库结构</li>
                <li><i class="bi bi-check-circle text-success"></i> 创建管理员账号</li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul class="list-unstyled small text-muted">
                <li><i class="bi bi-check-circle text-success"></i> 设置系统权限</li>
                <li><i class="bi bi-check-circle text-success"></i> 生成JWT密钥</li>
                <li><i class="bi bi-check-circle text-success"></i> 创建安装锁定文件</li>
            </ul>
        </div>
    </div>
</div>

<div class="alert alert-warning alert-modern">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>注意：</strong>
    安装过程中请不要关闭浏览器或刷新页面。安装完成后将自动跳转到完成页面。
</div>

<form method="POST" id="install-form">
    <div class="text-center">
        <button type="submit" class="btn btn-success btn-modern btn-lg" id="install-btn">
            <i class="bi bi-play-circle me-2"></i>开始安装
        </button>
    </div>
</form>

<div class="d-flex justify-content-between mt-5">
    <a href="?step=admin" class="btn btn-outline-secondary btn-modern">
        <i class="bi bi-arrow-left me-2"></i>上一步
    </a>

    <div></div> <!-- 占位符 -->
</div>

<script>
document.getElementById('install-form').addEventListener('submit', function(e) {
    const btn = document.getElementById('install-btn');


    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner me-2"></span>正在安装...';


    const progressArea = document.createElement('div');
    progressArea.className = 'mt-4';
    progressArea.innerHTML = `
        <div class="text-center mb-3">
            <h5>安装进度</h5>
        </div>
        <div class="progress mb-3" style="height: 20px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                 id="progress-bar" style="width: 0%;">0%</div>
        </div>
        <div id="progress-text" class="text-center text-muted">
            正在准备安装...
        </div>
    `;

    this.appendChild(progressArea);


    let progress = 0;
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');

    const steps = [
        '正在创建环境配置文件...',
        '正在初始化数据库...',
        '正在创建数据表...',
        '正在创建管理员账号...',
        '正在生成系统密钥...',
        '正在完成最后设置...'
    ];

    let stepIndex = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15 + 5;
        if (progress > 95) progress = 95;

        progressBar.style.width = progress + '%';
        progressBar.textContent = Math.round(progress) + '%';

        if (stepIndex < steps.length && progress > (stepIndex + 1) * 15) {
            progressText.textContent = steps[stepIndex];
            stepIndex++;
        }

        if (progress >= 95) {
            clearInterval(interval);
            progressText.textContent = '安装即将完成...';
        }
    }, 500);
});
</script>
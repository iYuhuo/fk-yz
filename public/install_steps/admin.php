<div class="text-center mb-4">
    <i class="bi bi-person-gear mb-3" style="font-size: 3rem; color: #667eea;"></i>
    <h2>管理员设置</h2>
    <p class="text-muted">创建系统管理员账号</p>
</div>

<form method="POST" id="admin-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="username" class="form-label">用户名</label>
            <input type="text" class="form-control form-control-modern" id="username" name="username"
                   value="<?= htmlspecialchars($_SESSION['admin_config']['username'] ?? 'admin') ?>"
                   required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+"
                   title="用户名只能包含字母、数字和下划线">
            <div class="form-text">3-50个字符，仅支持字母、数字和下划线</div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">邮箱地址</label>
            <input type="email" class="form-control form-control-modern" id="email" name="email"
                   value="<?= htmlspecialchars($_SESSION['admin_config']['email'] ?? '') ?>"
                   required maxlength="100">
            <div class="form-text">用于找回密码和系统通知</div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="password" class="form-label">密码</label>
            <div class="input-group">
                <input type="password" class="form-control form-control-modern" id="password" name="password"
                       required minlength="6" maxlength="100">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                    <i class="bi bi-eye" id="password-icon"></i>
                </button>
            </div>
            <div class="form-text">至少6个字符，建议包含字母、数字和特殊字符</div>
            <div class="password-strength mt-1">
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar" id="password-strength-bar" style="width: 0%; transition: width 0.3s;"></div>
                </div>
                <small id="password-strength-text" class="text-muted"></small>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="password_confirm" class="form-label">确认密码</label>
            <div class="input-group">
                <input type="password" class="form-control form-control-modern" id="password_confirm" name="password_confirm"
                       required minlength="6" maxlength="100">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirm')">
                    <i class="bi bi-eye" id="password_confirm-icon"></i>
                </button>
            </div>
            <div id="password-match" class="form-text"></div>
        </div>
    </div>

    <div class="alert alert-info alert-modern">
        <i class="bi bi-shield-check me-2"></i>
        <strong>安全提示：</strong>
        管理员拥有系统的完全控制权，请使用强密码并妥善保管登录信息。
    </div>

    <div class="feature-card mt-4">
        <h6><i class="bi bi-key text-warning me-2"></i>密码安全建议</h6>
        <div class="row">
            <div class="col-md-6">
                <ul class="list-unstyled small text-muted">
                    <li><i class="bi bi-check-circle text-success"></i> 至少8个字符</li>
                    <li><i class="bi bi-check-circle text-success"></i> 包含大小写字母</li>
                    <li><i class="bi bi-check-circle text-success"></i> 包含数字</li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-unstyled small text-muted">
                    <li><i class="bi bi-check-circle text-success"></i> 包含特殊字符</li>
                    <li><i class="bi bi-check-circle text-success"></i> 避免常用词汇</li>
                    <li><i class="bi bi-check-circle text-success"></i> 定期更换密码</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-5">
        <a href="?step=dependencies" class="btn btn-outline-secondary btn-modern">
            <i class="bi bi-arrow-left me-2"></i>上一步
        </a>

        <button type="submit" class="btn btn-primary btn-modern" id="submit-btn">
            下一步<i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</form>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');

    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];

    if (password.length >= 8) {
        strength += 20;
    } else {
        feedback.push('至少8个字符');
    }

    if (/[a-z]/.test(password)) {
        strength += 20;
    } else {
        feedback.push('包含小写字母');
    }

    if (/[A-Z]/.test(password)) {
        strength += 20;
    } else {
        feedback.push('包含大写字母');
    }

    if (/[0-9]/.test(password)) {
        strength += 20;
    } else {
        feedback.push('包含数字');
    }

    if (/[^a-zA-Z0-9]/.test(password)) {
        strength += 20;
    } else {
        feedback.push('包含特殊字符');
    }

    return { strength, feedback };
}

document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const result = checkPasswordStrength(password);
    const bar = document.getElementById('password-strength-bar');
    const text = document.getElementById('password-strength-text');

    bar.style.width = result.strength + '%';

    if (result.strength < 40) {
        bar.className = 'progress-bar bg-danger';
        text.textContent = '弱 - ' + result.feedback.join(', ');
        text.className = 'text-danger small';
    } else if (result.strength < 80) {
        bar.className = 'progress-bar bg-warning';
        text.textContent = '中等 - ' + result.feedback.join(', ');
        text.className = 'text-warning small';
    } else {
        bar.className = 'progress-bar bg-success';
        text.textContent = '强';
        text.className = 'text-success small';
    }

    checkPasswordMatch();
});

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;
    const matchDiv = document.getElementById('password-match');
    const submitBtn = document.getElementById('submit-btn');

    if (confirm === '') {
        matchDiv.textContent = '';
        matchDiv.className = 'form-text';
        return;
    }

    if (password === confirm) {
        matchDiv.textContent = '密码匹配';
        matchDiv.className = 'form-text text-success';
        submitBtn.disabled = false;
    } else {
        matchDiv.textContent = '密码不匹配';
        matchDiv.className = 'form-text text-danger';
        submitBtn.disabled = true;
    }
}

document.getElementById('password_confirm').addEventListener('input', checkPasswordMatch);


document.getElementById('admin-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;

    if (password !== confirm) {
        e.preventDefault();
        alert('密码确认不匹配！');
        return false;
    }

    if (password.length < 6) {
        e.preventDefault();
        alert('密码长度至少6个字符！');
        return false;
    }
});
</script>
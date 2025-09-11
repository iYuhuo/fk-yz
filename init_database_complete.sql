-- 网络验证系统 - 完整数据库初始化脚本

-- 1. 创建许可证表
CREATE TABLE IF NOT EXISTS licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(255) NOT NULL UNIQUE COMMENT '许可证密钥',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0=未使用, 1=已使用, 2=禁用',
    machine_code VARCHAR(255) NULL COMMENT '绑定的机器码',
    duration_days INT NOT NULL COMMENT '有效期天数',
    expires_at TIMESTAMP NULL COMMENT '过期时间',
    last_used_at TIMESTAMP NULL COMMENT '最后使用时间',
    machine_note TEXT NULL COMMENT '机器备注',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_license_key (license_key),
    INDEX idx_status (status),
    INDEX idx_machine_code (machine_code),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='许可证表';

-- 2. 创建使用日志表
CREATE TABLE IF NOT EXISTS usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(255) NOT NULL COMMENT '许可证密钥',
    machine_code VARCHAR(255) NOT NULL COMMENT '机器码',
    status VARCHAR(50) NOT NULL COMMENT '验证状态',
    ip_address VARCHAR(45) NULL COMMENT 'IP地址',
    user_agent TEXT NULL COMMENT '用户代理',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    
    INDEX idx_license_key (license_key),
    INDEX idx_machine_code (machine_code),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='使用日志表';

-- 3. 创建管理员设置表
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT PRIMARY KEY DEFAULT 1,
    username VARCHAR(100) NOT NULL UNIQUE COMMENT '用户名',
    password_hash VARCHAR(255) NOT NULL COMMENT '密码哈希',
    email VARCHAR(255) NULL COMMENT '邮箱',
    last_login_at TIMESTAMP NULL COMMENT '最后登录时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员设置表';

-- 4. 创建管理员操作日志表
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL COMMENT '操作类型',
    detail TEXT NULL COMMENT '操作详情',
    ip_address VARCHAR(45) NULL COMMENT 'IP地址',
    user_agent TEXT NULL COMMENT '用户代理',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- 5. 插入默认管理员账号
-- 默认管理员账号由安装程序创建，此处不再插入
-- 安装程序会在 createAdminAccount() 函数中插入用户填写的管理员信息

-- 6. 清除所有旧数据并重新初始化
-- 清空所有表数据（按外键依赖顺序）
TRUNCATE TABLE usage_logs;
TRUNCATE TABLE admin_logs;
TRUNCATE TABLE admin_settings;
TRUNCATE TABLE licenses;

-- 插入新的测试数据
INSERT INTO licenses (license_key, status, duration_days, expires_at) VALUES
('LIC-TEST001', 0, 30, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('LIC-TEST002', 0, 90, DATE_ADD(NOW(), INTERVAL 90 DAY)),
('LIC-TEST003', 1, 365, DATE_ADD(NOW(), INTERVAL 365 DAY));

INSERT INTO admin_logs (action, detail, ip_address) VALUES
('系统初始化', '数据库初始化完成', '127.0.0.1');

-- 显示创建结果
SELECT 'Database initialization completed successfully!' as message;
SELECT COUNT(*) as license_count FROM licenses;
SELECT COUNT(*) as admin_count FROM admin_settings;
SELECT COUNT(*) as log_count FROM admin_logs;

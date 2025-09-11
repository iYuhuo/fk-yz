# API 接口文档

**接口地址**
```http
POST /api/verify
```

**请求参数**
```json
{
    "license_key": "LIC-ABCD1234EFGH5678",
    "machine_code": "MACHINE-XXXXXXXXXXXX",
    "app_version": "1.0.0",
    "extra_data": {}
}
```

**参数说明**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| license_key | string | 是 | 许可证密钥 |
| machine_code | string | 是 | 机器码/设备唯一标识 |
| app_version | string | 否 | 应用程序版本号 |
| extra_data | object | 否 | 额外的自定义数据 |

**成功响应**
```json
{
    "success": true,
    "message": "验证成功",
    "data": {
        "license_key": "LIC-ABCD1234EFGH5678",
        "status": "active",
        "expires_at": "2024-12-31 23:59:59",
        "remaining_days": 365,
        "machine_code": "MACHINE-XXXXXXXXXXXX",
        "last_used_at": "2024-01-01 12:00:00",
        "created_at": "2024-01-01 00:00:00"
    }
}
```

**失败响应**
```json
{
    "success": false,
    "message": "许可证无效或已过期",
    "error_code": "INVALID_LICENSE"
}
```

## 🔐 认证接口

### 获取访问令牌
管理接口需要先获取访问令牌。

**接口地址**
```http
POST /api/auth/login
```

**请求参数**
```json
{
    "username": "admin",
    "password": "your_password"
}
```

**成功响应**
```json
{
    "success": true,
    "message": "登录成功",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "username": "admin",
            "created_at": "2024-01-01 00:00:00"
        }
    }
}
```

### 退出登录
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
```

## 📊 许可证管理接口

> 以下接口需要在请求头中携带访问令牌：`Authorization: Bearer {access_token}`

### 获取许可证列表
```http
GET /api/licenses
Authorization: Bearer {access_token}
```

**查询参数**
| 参数名 | 类型 | 说明 |
|--------|------|------|
| page | int | 页码，默认1 |
| per_page | int | 每页数量，默认20 |
| status | int | 状态筛选：0=未使用，1=已使用，2=已禁用 |
| search | string | 搜索关键词（许可证密钥或备注） |

**响应示例**
```json
{
    "success": true,
    "data": {
        "licenses": [
            {
                "id": 1,
                "license_key": "LIC-ABCD1234EFGH5678",
                "status": 1,
                "status_text": "已使用",
                "machine_code": "MACHINE-XXXXXXXXXXXX",
                "machine_note": "办公电脑",
                "duration_days": 365,
                "created_at": "2024-01-01 00:00:00",
                "expires_at": "2024-12-31 23:59:59",
                "last_used_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100,
            "last_page": 5
        }
    }
}
```

### 创建许可证
```http
POST /api/licenses
Authorization: Bearer {access_token}
```

**请求参数**
```json
{
    "count": 10,
    "duration_days": 365,
    "note": "许可证批次"
}
```

**参数说明**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| count | int | 是 | 创建数量（1-100） |
| duration_days | int | 是 | 有效天数 |
| note | string | 否 | 批次备注说明 |

**响应示例**
```json
{
    "success": true,
    "message": "许可证创建成功",
    "data": {
        "license_keys": [
            "LIC-ABCD1234EFGH5678",
            "LIC-EFGH5678IJKL9012",
            "LIC-IJKL9012MNOP3456"
        ],
        "count": 3,
        "duration_days": 365,
        "expires_at": "2024-12-31 23:59:59"
    }
}
```

### 更新许可证
```http
PUT /api/licenses/{id}
Authorization: Bearer {access_token}
```

**请求参数**
```json
{
    "machine_note": "新的设备备注",
    "status": 1
}
```

### 删除许可证
```http
DELETE /api/licenses/{id}
Authorization: Bearer {access_token}
```

### 延长许可证有效期
```http
POST /api/licenses/{id}/extend
Authorization: Bearer {access_token}
```

**请求参数**
```json
{
    "days": 30
}
```

### 禁用/启用许可证
```http
POST /api/licenses/{id}/disable
POST /api/licenses/{id}/enable
Authorization: Bearer {access_token}
```

### 解绑设备
```http
POST /api/licenses/{id}/unbind
Authorization: Bearer {access_token}
```

## 📈 统计和日志接口

### 获取系统统计
```http
GET /api/stats
Authorization: Bearer {access_token}
```

**响应示例**
```json
{
    "success": true,
    "data": {
        "total_licenses": 1000,
        "active_licenses": 800,
        "expired_licenses": 150,
        "disabled_licenses": 50,
        "unused_licenses": 200,
        "today_verifications": 5000,
        "total_verifications": 500000,
        "recent_activity": [
            {
                "date": "2024-01-07",
                "verifications": 1200
            },
            {
                "date": "2024-01-06",
                "verifications": 1100
            }
        ]
    }
}
```

### 获取使用日志
```http
GET /api/logs
Authorization: Bearer {access_token}
```

**查询参数**
| 参数名 | 类型 | 说明 |
|--------|------|------|
| page | int | 页码，默认1 |
| per_page | int | 每页数量，默认50 |
| license_key | string | 按许可证密钥筛选 |
| start_date | string | 开始日期 (YYYY-MM-DD) |
| end_date | string | 结束日期 (YYYY-MM-DD) |
| ip_address | string | 按IP地址筛选 |

**响应示例**
```json
{
    "success": true,
    "data": {
        "logs": [
            {
                "id": 1,
                "license_key": "LIC-ABCD1234EFGH5678",
                "machine_code": "MACHINE-XXXXXXXXXXXX",
                "ip_address": "192.168.1.100",
                "user_agent": "MyApp/1.0.0",
                "result": "验证成功",
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 50,
            "total": 10000,
            "last_page": 200
        }
    }
}
```

## 🚨 错误码说明

### HTTP状态码
| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误 |
| 401 | 未授权访问 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 422 | 数据验证失败 |
| 429 | 请求频率超限 |
| 500 | 服务器内部错误 |

### 业务错误码
| 错误码 | 说明 |
|--------|------|
| INVALID_LICENSE | 许可证无效 |
| EXPIRED_LICENSE | 许可证已过期 |
| DISABLED_LICENSE | 许可证已禁用 |
| MACHINE_MISMATCH | 设备码不匹配 |
| MACHINE_ALREADY_BOUND | 设备已绑定其他许可证 |
| LICENSE_NOT_FOUND | 许可证不存在 |
| RATE_LIMIT_EXCEEDED | 请求频率超限 |
| INVALID_CREDENTIALS | 用户名或密码错误 |
| TOKEN_EXPIRED | 访问令牌已过期 |
| INSUFFICIENT_PERMISSIONS | 权限不足 |

### 错误响应格式
```json
{
    "success": false,
    "message": "错误描述信息",
    "error_code": "ERROR_CODE",
    "errors": {
        "field_name": ["具体的字段错误信息"]
    }
}
```

## 🔄 请求限制

### 频率限制
- **验证接口** (`/api/verify`): 每分钟最多 100 次请求
- **管理接口**: 每分钟最多 1000 次请求
- **认证接口**: 每分钟最多 10 次请求

### 并发限制
- 每IP同时最多 20 个连接
- 单个令牌同时最多 10 个请求

### 超出限制响应
```json
{
    "success": false,
    "message": "请求频率超限，请稍后再试",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60
}
```



## 📚 更多信息

### 版本信息
- **当前版本**: v2.0.0

### 技术支持
如果您在使用API过程中遇到问题，请参考以下资源：
- 检查请求格式是否正确
- 确认访问令牌是否有效
- 查看错误响应中的详细信息
- 检查网络连接和服务器状态



<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Session\SessionManager;
use AuthSystem\Core\Config\Config;
use AuthSystem\Models\License;
use AuthSystem\Models\UsageLog;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;


class DashboardController
{
    private License $licenseModel;
    private UsageLog $usageLogModel;
    private AdminLog $adminLogModel;
    private Logger $logger;
    private Config $config;

    public function __construct(License $licenseModel, UsageLog $usageLogModel, AdminLog $adminLogModel, Logger $logger, Config $config)
    {
        $this->licenseModel = $licenseModel;
        $this->usageLogModel = $usageLogModel;
        $this->adminLogModel = $adminLogModel;
        $this->logger = $logger;
        $this->config = $config;
    }


    public function index(Request $request): Response
    {
        try {

            if (!SessionManager::isLoggedIn()) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }

            $licenseStats = $this->licenseModel->getStats();


            $usageStats = $this->usageLogModel->getStats(7);


            $recentActions = $this->adminLogModel->getActionHistory(10);


            $recentUsage = $this->usageLogModel->query()
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->get();


            $activeLicenses = $this->getActiveLicensesStats(7);



            $recentFailures = $this->usageLogModel->query()
                ->where('status', '!=', '验证成功')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get();

            $data = [
                'license_stats' => $licenseStats,
                'usage_stats' => $usageStats,
                'recent_actions' => $recentActions,
                'recent_usage' => $recentUsage,
                'active_licenses' => $activeLicenses,
                'recent_failures' => $recentFailures,
            ];

            $systemName = $this->config->get('app.name');
            $brandHtml = $this->config->getBrandHtml();

            $html = $this->renderDashboard($data, $systemName, $brandHtml);

            return Response::html($html);

        } catch (\Exception $e) {
            $this->logger->error('Dashboard error', [
                'error' => $e->getMessage(),
            ]);

            return Response::html('<h1>错误</h1><p>加载仪表板时发生错误</p>');
        }
    }


    private function renderDashboard(array $data, string $systemName = '网络验证系统', string $brandHtml = ''): string
    {

        $licenseStats = $data['license_stats'];
        $usageStats = $data['usage_stats'];
        $recentActions = $data['recent_actions'];
        $recentUsage = $data['recent_usage'];
        $activeLicenses = $data['active_licenses'];
        $recentFailures = $data['recent_failures'];

        if (empty($brandHtml)) {
            $brandHtml = '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($systemName);
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - {$systemName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--card-border);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color) 0%, var(--info-color) 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, var(--success-color) 100%);
        }
        .stat-card.danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, var(--warning-color) 100%);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }


        .list-group-item {
            background-color: var(--card-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                {$brandHtml}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link active" href="/">
                        <i class="bi bi-house"></i> 首页
                    </a>
                    <a class="nav-link" href="/licenses">
                        <i class="bi bi-key"></i> 许可证管理
                    </a>
                    <a class="nav-link" href="/logs">
                        <i class="bi bi-list-ul"></i> 日志查看
                    </a>
                    <a class="nav-link" href="/settings">
                        <i class="bi bi-gear"></i> 设置
                    </a>
                    <a class="nav-link" href="/logout">
                        <i class="bi bi-box-arrow-right"></i> 退出
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 page-content">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">
                    <i class="bi bi-speedometer2 me-2"></i>仪表板
                </h1>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="row mb-4 card-stack">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">{$licenseStats['total']}</div>
                    <div class="stat-label">许可证总数</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-number">{$licenseStats['status_1']}</div>
                    <div class="stat-label">已使用</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-number">{$licenseStats['status_0']}</div>
                    <div class="stat-label">未使用</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-number">{$licenseStats['status_2']}</div>
                    <div class="stat-label">已禁用</div>
                </div>
            </div>
        </div>

        <div class="row card-stack">
            <!-- 最近操作 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-fire"></i> 许可证动态 (最近7天)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
HTML;


        if (empty($activeLicenses)) {
            $html .= <<<HTML
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="bi bi-activity display-4 mb-3"></i>
                                <p>暂无许可证动态</p>
                                <small>最近7天内没有许可证活动记录<br>创建一些许可证来查看动态</small>
                            </div>
HTML;
        } else {
            foreach ($activeLicenses as $license) {
                $time = date('m-d H:i', strtotime($license['last_used']));
                $usageCount = $license['usage_count'];
                $machineCode = strlen($license['machine_code']) > 16 ? substr($license['machine_code'], 0, 16) . '...' : $license['machine_code'];
                $licenseKey = strlen($license['license_key']) > 12 ? substr($license['license_key'], 0, 12) . '...' : $license['license_key'];
                $type = $license['type'] ?? 'active';


                if ($type === 'recent') {
                    $status = $license['status'] ?? '未知';
                    switch($status) {
                        case '未使用':
                            $statusColor = 'bg-primary';
                            break;
                        case '已使用':
                            $statusColor = 'bg-success';
                            break;
                        case '已禁用':
                            $statusColor = 'bg-danger';
                            break;
                        default:
                            $statusColor = 'bg-secondary';
                            break;
                    }
                    $badge = "<span class=\"badge {$statusColor}\">{$status}</span>";
                    $detail = "<i class=\"bi bi-calendar-plus\"></i> 新创建 | <i class=\"bi bi-laptop\"></i> {$machineCode}";
                } else {

                    if ($usageCount >= 20) {
                        $badge = '<span class="badge bg-danger">高频</span>';
                    } elseif ($usageCount >= 10) {
                        $badge = '<span class="badge bg-warning">中频</span>';
                    } elseif ($usageCount > 0) {
                        $badge = '<span class="badge bg-success">活跃</span>';
                    } else {
                        $badge = '<span class="badge bg-secondary">未使用</span>';
                    }
                    $detail = "<i class=\"bi bi-laptop\"></i> {$machineCode} | <i class=\"bi bi-arrow-repeat\"></i> {$usageCount}次验证";
                }

                $html .= <<<HTML
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center mb-1">
                                        <code class="me-2">{$licenseKey}</code>
                                        {$badge}
                                    </div>
                                    <small class="text-muted">
                                        {$detail}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">{$time}</small>
                                </div>
                            </div>
HTML;
            }
        }

        $html .= <<<HTML
                        </div>
                    </div>
                </div>
            </div>

            <!-- 验证失败记录 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle"></i> 异常验证记录
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
HTML;

        if (empty($recentFailures)) {
            $html .= <<<HTML
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="bi bi-shield-check display-4 mb-3 text-success"></i>
                                <p>验证状态良好</p>
                                <small>暂无异常验证记录</small>
                            </div>
HTML;
        } else {
            foreach ($recentFailures as $failure) {
                $time = date('m-d H:i', strtotime($failure['created_at']));
                $licenseKey = substr($failure['license_key'], 0, 8) . '...';
                $machineCode = substr($failure['machine_code'], 0, 16) . '...';


                $statusClass = 'danger';
                $icon = 'bi-x-circle';
                if (strpos($failure['status'], '过期') !== false) {
                    $statusClass = 'warning';
                    $icon = 'bi-clock';
                } elseif (strpos($failure['status'], '不存在') !== false) {
                    $statusClass = 'secondary';
                    $icon = 'bi-question-circle';
                }

                $html .= <<<HTML
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi {$icon} text-{$statusClass} me-2"></i>
                                        <code>{$licenseKey}</code>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-laptop"></i> {$machineCode}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{$statusClass} mb-1">{$failure['status']}</span>
                                    <br>
                                    <small class="text-muted">{$time}</small>
                                </div>
                            </div>
HTML;
            }
        }

        $html .= <<<HTML
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme-manager.js"></script>
    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/notifications.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            if (window.ThemeManager) {
                window.themeManager = new ThemeManager();
                window.themeManager.init();
            }
        });
    </script>
</body>
</html>
HTML;

        return $html;
    }


    private function getActiveLicensesStats(int $days): array
    {
        try {

            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            error_log("getActiveLicensesStats: cutoffDate = {$cutoffDate}");

            $results = $this->usageLogModel->query()
                ->where('status', 'LIKE', '%验证成功%')
                ->where('created_at', '>=', $cutoffDate)
                ->orderBy('created_at', 'DESC')
                ->limit(100)
                ->get();

            error_log("getActiveLicensesStats: found " . count($results) . " usage records");


            if (empty($results)) {
                return $this->getRecentLicensesAsActive($days);
            }


            $grouped = [];
            foreach ($results as $record) {
                $key = $record['license_key'] . '|' . ($record['machine_code'] ?? 'unbound');
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'license_key' => $record['license_key'],
                        'machine_code' => $record['machine_code'] ?? '未绑定',
                        'usage_count' => 0,
                        'last_used' => $record['created_at'],
                        'type' => 'active'
                    ];
                }
                $grouped[$key]['usage_count']++;
                if ($record['created_at'] > $grouped[$key]['last_used']) {
                    $grouped[$key]['last_used'] = $record['created_at'];
                }
            }


            uasort($grouped, function($a, $b) {
                return $b['usage_count'] - $a['usage_count'];
            });

            $finalResult = array_slice(array_values($grouped), 0, 10);
            error_log("getActiveLicensesStats: returning " . count($finalResult) . " active licenses");

            return $finalResult;
        } catch (\Exception $e) {
            return $this->getRecentLicensesAsActive($days);
        }
    }


    private function getRecentLicensesAsActive(int $days): array
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $licenses = $this->licenseModel->query()
                ->where('created_at', '>=', $cutoffDate)
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->get();

            $result = [];
            foreach ($licenses as $license) {
                $result[] = [
                    'license_key' => $license['license_key'],
                    'machine_code' => $license['machine_code'] ?? '未绑定',
                    'usage_count' => 0,
                    'last_used' => $license['created_at'],
                    'type' => 'recent',
                    'status' => $this->licenseModel->getStatusText($license['status'])
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
}
<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Session\SessionManager;
use AuthSystem\Core\Config\Config;
use AuthSystem\Models\UsageLog;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;


class LogController
{
    private UsageLog $usageLogModel;
    private AdminLog $adminLogModel;
    private Logger $logger;
    private Config $config;

    public function __construct(UsageLog $usageLogModel, AdminLog $adminLogModel, Logger $logger, Config $config)
    {
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
            $type = $request->get('type', 'usage');
            $page = (int)($request->get('page', 1));
            $search = $request->get('search');
            $perPage = 20;

            if ($type === 'admin') {
                $query = $this->adminLogModel->query();


                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('action', 'LIKE', $searchTerm)
                          ->orWhere('detail', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('user_agent', 'LIKE', $searchTerm);
                }

                $result = $query->orderBy('created_at', 'DESC')->paginate($page, $perPage);
            } else {
                $query = $this->usageLogModel->query();


                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('license_key', 'LIKE', $searchTerm)
                          ->orWhere('machine_code', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('status', 'LIKE', $searchTerm);
                }

                $result = $query->orderBy('created_at', 'DESC')->paginate($page, $perPage);
            }

            $systemName = $this->config->get('app.name');
            $brandHtml = $this->config->getBrandHtml();
            $html = $this->renderLogPage($result, $type, $search, $systemName, $brandHtml);
            return Response::html($html);

        } catch (\Exception $e) {
            $this->logger->error('Log page error', [
                'error' => $e->getMessage(),
            ]);

            return Response::html('<h1>错误</h1><p>加载日志页面时发生错误</p>');
        }
    }


    private function renderLogPage(array $result, string $type, ?string $search = null, string $systemName = '网络验证系统', string $brandHtml = ''): string
    {
        $logs = $result['data'];
        $pagination = $this->renderPagination($result, $type, $search);

        $usageActive = $type === 'usage' ? 'active' : '';
        $adminActive = $type === 'admin' ? 'active' : '';
        $searchValue = htmlspecialchars($search ?? '');


        if ($type === 'admin') {
            $searchPlaceholder = '搜索操作、详情、IP地址...';
        } else {
            $searchPlaceholder = '搜索许可证密钥、设备码、IP地址、状态...';
        }


        $searchQuery = $search ? '&search=' . urlencode($search) : '';

        if (empty($brandHtml)) {
            $brandHtml = '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($systemName);
        }

        $logsHtml = '';
        if ($type === 'admin') {
            foreach ($logs as $log) {
                $createdAt = date('Y-m-d H:i:s', strtotime($log['created_at']));
                $logsHtml .= <<<HTML
                    <tr>
                        <td>{$log['id']}</td>
                        <td><span class="badge bg-info">{$log['action']}</span></td>
                        <td>{$log['detail']}</td>
                        <td><code>{$log['ip_address']}</code></td>
                        <td>{$createdAt}</td>
                    </tr>
HTML;
            }
        } else {
            foreach ($logs as $log) {
                $createdAt = date('Y-m-d H:i:s', strtotime($log['created_at']));
                $statusBadge = $this->getStatusBadge($log['status']);
                $logsHtml .= <<<HTML
                    <tr>
                        <td>{$log['id']}</td>
                        <td><code>{$log['license_key']}</code></td>
                        <td><code>{$log['machine_code']}</code></td>
                        <td>{$statusBadge}</td>
                        <td><code>{$log['ip_address']}</code></td>
                        <td>{$createdAt}</td>
                    </tr>
HTML;
            }
        }

        $tableHeaders = $type === 'admin'
            ? '<th>ID</th><th>操作</th><th>详情</th><th>IP地址</th><th>时间</th>'
            : '<th>ID</th><th>许可证</th><th>机器码</th><th>状态</th><th>IP地址</th><th>时间</th>';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日志查看 - {$systemName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
{$brandHtml}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="/">
                        <i class="bi bi-house"></i> 首页
                    </a>
                    <a class="nav-link" href="/licenses">
                        <i class="bi bi-key"></i> 许可证管理
                    </a>
                    <a class="nav-link active" href="/logs">
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
                    <i class="bi bi-list-ul me-2"></i>日志查看
                </h1>
            </div>
        </div>

        <!-- 日志类型切换 -->
        <div class="row mb-3">
            <div class="col-12">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link {$usageActive}" href="/logs?type=usage{$searchQuery}">
                            <i class="bi bi-activity"></i> 使用日志
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {$adminActive}" href="/logs?type=admin{$searchQuery}">
                            <i class="bi bi-person-gear"></i> 管理日志
                        </a>
                    </li>
                    <li class="nav-item ms-auto">
                        <div class="d-flex gap-2">
                            <a class="nav-link" href="/logs/export?type={$type}{$searchQuery}" onclick="return confirm('确定要导出当前日志数据吗？')">
                                <i class="bi bi-download"></i> 导出数据
                            </a>
                            <button class="nav-link btn btn-link text-danger" onclick="cleanupLogs('{$type}')" title="清理/清空日志">
                                <i class="bi bi-trash"></i> 清理日志
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 搜索功能 -->
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="type" value="{$type}">
                    <input type="text" class="form-control me-2" name="search" placeholder="{$searchPlaceholder}" value="{$searchValue}">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> 搜索
                    </button>
                </form>
            </div>
        </div>

        <!-- 日志列表 -->
        <div class="card card-stack">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                {$tableHeaders}
                            </tr>
                        </thead>
                        <tbody>
                            {$logsHtml}
                        </tbody>
                    </table>
                </div>

                {$pagination}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme-manager.js"></script>
    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/notifications.js"></script>

    <script>
        async function cleanupLogs(type) {
            const typeName = type === 'admin' ? '管理日志' : '使用日志';
            const choice = await modernModal.select('请选择清理方式：', [
                { value: 'retain', label: '保留最近N天，清理更早记录' },
                { value: 'before', label: '删除N天前的所有记录' },
                { value: 'all', label: '清空所有记录（谨慎）' }
            ], 'retain', '清理日志');

            if (choice === null) return;

            let days = 30;
            if (choice !== 'all') {
                const defaultDays = type === 'admin' ? '7' : '30';
                const input = await modernModal.prompt('请输入天数（1-365）：', defaultDays, '设置天数');
                if (input === null) return;
                const d = parseInt(input);
                if (!d || d < 1 || d > 365) {
                    await modernModal.alert('天数必须在1-365之间', '参数错误', 'error');
                    return;
                }
                days = d;
            }

            const confirmMsg = choice === 'all'
                ? '确定要清空所有' + typeName + '吗？此操作不可撤销！'
                : (choice === 'retain'
                    ? '确定要删除' + days + '天之前的' + typeName + '并保留最近' + days + '天吗？'
                    : '确定要删除' + days + '天前的所有' + typeName + '记录吗？');

            const confirmed = await modernModal.confirm(confirmMsg, '确认操作');
            if (!confirmed) return;

            try {
                const response = await fetch('/logs/cleanup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type, mode: choice, days })
                });
                const result = await response.json();
                if (result.success) {
                    notify.success(result.message || '操作成功');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    notify.error(result.error || '操作失败');
                }
            } catch (error) {
                console.error(error);
                notify.error('网络错误');
            }
        }
    </script>
</body>
</html>
HTML;
    }


    private function getStatusBadge(string $status): string
    {
        if (strpos($status, '成功') !== false) {
            return '<span class="badge bg-success">' . htmlspecialchars($status) . '</span>';
        } else {
            return '<span class="badge bg-danger">' . htmlspecialchars($status) . '</span>';
        }
    }


    private function renderPagination(array $result, string $type, ?string $search = null): string
    {
        $currentPage = $result['current_page'];
        $lastPage = $result['last_page'];
        $total = $result['total'];
        $perPage = $result['per_page'];

        if ($lastPage <= 1) {
            return '';
        }


        $queryParams = "type={$type}";
        if ($search) {
            $queryParams .= "&search=" . urlencode($search);
        }

        $html = '<nav aria-label="日志分页"><ul class="pagination justify-content-center">';


        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?{$queryParams}&page={$prevPage}\">上一页</a></li>";
        }


        for ($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class=\"page-item {$active}\"><a class=\"page-link\" href=\"?{$queryParams}&page={$i}\">{$i}</a></li>";
        }


        if ($currentPage < $lastPage) {
            $nextPage = $currentPage + 1;
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?{$queryParams}&page={$nextPage}\">下一页</a></li>";
        }

        $html .= '</ul></nav>';

        $html .= "<div class=\"text-center text-muted mt-2\">共 {$total} 条记录，每页 {$perPage} 条</div>";

        return $html;
    }


    public function export(Request $request): Response
    {
        try {

            if (!SessionManager::isLoggedIn()) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }
            $type = $request->get('type', 'usage');
            $search = $request->get('search');
            $limit = 1000;

            if ($type === 'admin') {
                $query = $this->adminLogModel->query();


                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('action', 'LIKE', $searchTerm)
                          ->orWhere('detail', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('user_agent', 'LIKE', $searchTerm);
                }

                $logs = $query->orderBy('created_at', 'DESC')->limit($limit)->get();

                $filename = 'admin_logs_' . date('Y-m-d') . '.csv';
                $csv = "时间,操作,详情,IP地址\n";

                foreach ($logs as $log) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s\n",
                        $log['created_at'],
                        str_replace(',', ';', $log['action']),
                        str_replace(',', ';', $log['detail']),
                        $log['ip_address']
                    );
                }
            } else {
                $query = $this->usageLogModel->query();


                if ($search) {
                    $searchTerm = "%{$search}%";
                    $query->where('license_key', 'LIKE', $searchTerm)
                          ->orWhere('machine_code', 'LIKE', $searchTerm)
                          ->orWhere('ip_address', 'LIKE', $searchTerm)
                          ->orWhere('status', 'LIKE', $searchTerm);
                }

                $logs = $query->orderBy('created_at', 'DESC')->limit($limit)->get();

                $filename = 'usage_logs_' . date('Y-m-d') . '.csv';
                $csv = "时间,许可证密钥,机器码,状态,IP地址\n";

                foreach ($logs as $log) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s\n",
                        $log['created_at'],
                        $log['license_key'],
                        $log['machine_code'],
                        str_replace(',', ';', $log['status']),
                        $log['ip_address']
                    );
                }
            }


            $this->adminLogModel->logAction(
                '导出日志',
                "导出{$type}日志数据，共" . count($logs) . "条记录",
                $request->getClientIp(),
                $request->getUserAgent()
            );


            $response = new Response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($csv)
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Export logs error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '导出日志失败');
            return Response::redirect('/logs');
        }
    }


    public function cleanup(Request $request): Response
    {
        try {

            if (!SessionManager::isLoggedIn()) {
                return Response::json(['error' => '请先登录'], 401);
            }

            $data = $request->json() ?? $request->all();
            $type = $data['type'] ?? 'usage';
            $mode = $data['mode'] ?? 'retain';
            $days = (int)($data['days'] ?? ($type === 'admin' ? 7 : 30));

            if (!in_array($mode, ['retain', 'before', 'all'], true)) {
                return Response::json(['error' => '无效的清理模式'], 400);
            }
            if ($mode !== 'all' && ($days < 1 || $days > 365)) {
                return Response::json(['error' => '天数范围必须在1-365之间'], 400);
            }

            $count = 0;
            if ($type === 'admin') {
                if ($mode === 'all') {
                    $logs = $this->adminLogModel->query()->get();
                } elseif ($mode === 'retain') {

                    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                    $logs = $this->adminLogModel->query()
                        ->where('created_at', '<', $cutoffDate)
                        ->get();
                } else {

                    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                    $logs = $this->adminLogModel->query()
                        ->where('created_at', '<', $cutoffDate)
                        ->get();
                }
                foreach ($logs as $log) {
                    $this->adminLogModel->delete($log['id']);
                    $count++;
                }
            } else {
                if ($mode === 'all') {
                    $logs = $this->usageLogModel->query()->get();
                } elseif ($mode === 'retain') {

                    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                    $logs = $this->usageLogModel->query()
                        ->where('created_at', '<', $cutoffDate)
                        ->get();
                } else {

                    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                    $logs = $this->usageLogModel->query()
                        ->where('created_at', '<', $cutoffDate)
                        ->get();
                }
                foreach ($logs as $log) {
                    $this->usageLogModel->delete($log['id']);
                    $count++;
                }
            }

            $action = $mode === 'all' ? '清空日志' : ($mode === 'retain' ? '清理日志(保留N天)' : '删除历史日志');
            $logType = $type === 'admin' ? '管理' : '使用';
            $message = $mode === 'all'
                ? "已清空 {$logType} 日志，共 {$count} 条"
                : ($mode === 'retain'
                    ? "已删除 {$days} 天前的旧{$logType}日志，共 {$count} 条"
                    : "已删除 {$days} 天前的{$logType}日志，共 {$count} 条");


            $this->adminLogModel->logAction(
                $action,
                $message,
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Cleanup logs error', [
                'error' => $e->getMessage(),
            ]);
            return Response::json(['error' => '清理日志失败'], 500);
        }
    }
}
<?php

namespace AuthSystem\Web\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Session\SessionManager;
use AuthSystem\Core\Config\Config;
use AuthSystem\Models\License;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;


class LicenseController
{
    private License $licenseModel;
    private AdminLog $adminLogModel;
    private Logger $logger;
    private Config $config;

    public function __construct(License $licenseModel, AdminLog $adminLogModel, Logger $logger, Config $config)
    {
        $this->licenseModel = $licenseModel;
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
            $page = (int)($request->get('page', 1));
            $perPage = (int)($request->get('per_page', 20));
            $status = $request->get('status');
            $search = $request->get('search');

            $query = $this->licenseModel->query();


            if ($status !== null) {
                $query->where('status', (int)$status);
            }


            if ($search) {
                $searchTerm = "%{$search}%";

                if ($status === null) {


                    $allRecords = $this->licenseModel->query()->get();
                    $filteredRecords = [];

                    foreach ($allRecords as $record) {
                        $searchMatch = (
                            stripos($record['license_key'], $search) !== false ||
                            stripos($record['machine_code'] ?? '', $search) !== false ||
                            stripos($record['machine_note'] ?? '', $search) !== false
                        );

                        if ($searchMatch) {
                            $filteredRecords[] = $record;
                        }
                    }


                    $total = count($filteredRecords);
                    $lastPage = ceil($total / $perPage);
                    $currentPage = min($page, $lastPage);
                    $offset = ($currentPage - 1) * $perPage;
                    $pagedRecords = array_slice($filteredRecords, $offset, $perPage);

                    $result = [
                        'data' => $pagedRecords,
                        'current_page' => $currentPage,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $total
                    ];
                } else {

                    $allRecords = $this->licenseModel->query()->get();
                    $filteredRecords = [];

                    foreach ($allRecords as $record) {
                        $statusMatch = ($record['status'] == (int)$status);
                        $searchMatch = (
                            stripos($record['license_key'], $search) !== false ||
                            stripos($record['machine_code'] ?? '', $search) !== false ||
                            stripos($record['machine_note'] ?? '', $search) !== false
                        );

                        if ($statusMatch && $searchMatch) {
                            $filteredRecords[] = $record;
                        }
                    }


                    $total = count($filteredRecords);
                    $lastPage = ceil($total / $perPage);
                    $currentPage = min($page, $lastPage);
                    $offset = ($currentPage - 1) * $perPage;
                    $pagedRecords = array_slice($filteredRecords, $offset, $perPage);

                    $result = [
                        'data' => $pagedRecords,
                        'current_page' => $currentPage,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $total
                    ];
                }
            }


            if (!$search) {
                $result = $query->orderBy('created_at', 'DESC')->paginate($page, $perPage);
            }


            foreach ($result['data'] as &$license) {
                $license['status_text'] = $this->licenseModel->getStatusText($license['status']);
            }

            $systemName = $this->config->get('app.name');
            $brandHtml = $this->config->getBrandHtml();
            $html = $this->renderLicensePage($result, $status, $search, $systemName, $brandHtml);
            return Response::html($html);

        } catch (\Exception $e) {
            $this->logger->error('License page error', [
                'error' => $e->getMessage(),
            ]);

            return Response::html('<h1>错误</h1><p>加载许可证页面时发生错误</p>');
        }
    }


    private function renderLicensePage(array $result, ?string $status, ?string $search, string $systemName = '网络验证系统', string $brandHtml = ''): string
    {
        $licenses = $result['data'];
        $pagination = $this->renderPagination($result);

        $searchValue = htmlspecialchars($search ?? '');
        $statusOptions = $this->renderStatusOptions($status);

        if (empty($brandHtml)) {
            $brandHtml = '<i class="bi bi-shield-check"></i> ' . htmlspecialchars($systemName);
        }


        $notificationScript = '';

        $successMessage = SessionManager::getFlashMessage('success');
        $errorMessage = SessionManager::getFlashMessage('error');
        $infoMessage = SessionManager::getFlashMessage('info');

        if ($successMessage) {
            $message = htmlspecialchars($successMessage);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.success('{$message}'); });</script>";
        } elseif ($errorMessage) {
            $message = htmlspecialchars($errorMessage);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('{$message}'); });</script>";
        } elseif ($infoMessage) {
            $message = htmlspecialchars($infoMessage);
            $notificationScript = "<script>document.addEventListener('DOMContentLoaded', function() { notify.info('{$message}'); });</script>";
        }
        $alertHtml = '';

        $licensesHtml = '';
        foreach ($licenses as $license) {
            $createdAt = date('Y-m-d H:i', strtotime($license['created_at']));
            $expiresAt = $license['expires_at'] ? date('Y-m-d H:i', strtotime($license['expires_at'])) : '永不过期';
            $statusBadge = $this->getStatusBadge($license['status']);
            $machineCode = $license['machine_code'] ? htmlspecialchars($license['machine_code']) : '<span class="text-muted">未绑定</span>';
            $machineNote = $license['machine_note'] ? htmlspecialchars($license['machine_note']) : '<span class="text-muted">无备注</span>';

            $licensesHtml .= <<<HTML
                <tr>
                    <td>{$license['id']}</td>
                    <td><code>{$license['license_key']}</code></td>
                    <td>{$statusBadge}</td>
                    <td>{$machineCode}</td>
                    <td>{$machineNote}</td>
                    <td>{$createdAt}</td>
                    <td>{$expiresAt}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editLicense({$license['id']})" title="编辑备注">
                                <i class="bi bi-pencil"></i>
                            </button>
HTML;


            if ($license['status'] == 2) {
                $licensesHtml .= <<<HTML
                            <button class="btn btn-outline-success" onclick="enableLicense({$license['id']})" title="启用">
                                <i class="bi bi-check-circle"></i>
                            </button>
HTML;
            } else {
                $licensesHtml .= <<<HTML
                            <button class="btn btn-outline-warning" onclick="disableLicense({$license['id']})" title="禁用">
                                <i class="bi bi-x-circle"></i>
                            </button>
HTML;
            }

            if ($license['machine_code']) {
                $licensesHtml .= <<<HTML
                            <button class="btn btn-outline-info" onclick="unbindDevice({$license['id']})" title="解绑设备">
                                <i class="bi bi-link-45deg"></i>
                            </button>
HTML;
            }

            $licensesHtml .= <<<HTML
                            <button class="btn btn-outline-secondary" onclick="extendLicense({$license['id']})" title="调整有效期">
                                <i class="bi bi-clock-history"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteLicense({$license['id']})" title="删除">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>许可证管理 - {$systemName}</title>
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
                    <a class="nav-link active" href="/licenses">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-key me-2"></i>许可证管理
                    </h1>
                    <button class="btn btn-primary" onclick="createLicense()">
                        <i class="bi bi-plus-circle"></i> 创建许可证
                    </button>
                </div>
            </div>
        </div>

        <!-- 搜索和筛选 -->
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" class="d-flex">
                    <input type="text" class="form-control me-2" name="search" placeholder="密钥、设备码或备注..." value="{$searchValue}">
                    <select class="form-select me-2" name="status" style="width: auto;">
                        <option value="">所有状态</option>
                        {$statusOptions}
                    </select>
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> 搜索
                    </button>
                </form>
            </div>
        </div>

        <!-- 许可证列表 -->
        <div class="card card-stack">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>许可证密钥</th>
                                <th>状态</th>
                                <th>绑定设备</th>
                                <th>设备备注</th>
                                <th>创建时间</th>
                                <th>过期时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$licensesHtml}
                        </tbody>
                    </table>
                </div>

                {$pagination}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme-manager.js"></script>
    <script src="/assets/js/modal.js?v=<?php echo time(); ?>"></script>
    <script src="/assets/js/notifications.js?v=<?php echo time(); ?>"></script>
    <script>
        async function createLicense() {
            const result = await modernModal.createLicenseDialog();
            if (!result) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/licenses/create';

            let formHtml = `
                <input type="hidden" name="count" value="\${result.count}">
                <input type="hidden" name="duration_days" value="\${result.days}">
            `;


            if (result.formatOptions) {
                formHtml += `
                    <input type="hidden" name="prefix" value="\${result.formatOptions.prefix}">
                    <input type="hidden" name="length" value="\${result.formatOptions.length}">
                    <input type="hidden" name="charset" value="\${result.formatOptions.charset}">
                `;
            }

            form.innerHTML = formHtml;
            document.body.appendChild(form);
            form.submit();
        }

        async function editLicense(id) {

            const currentNote = getCurrentLicenseNote(id);
            const note = await modernModal.prompt('请输入设备备注:', currentNote, '编辑设备备注');
            if (note === null) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/licenses/\${id}/edit`;
            form.innerHTML = `<input type="hidden" name="machine_note" value="\${note}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function getCurrentLicenseNote(id) {

            const rows = document.querySelectorAll('tbody tr');
            for (const row of rows) {
                const firstCell = row.querySelector('td:first-child');
                if (firstCell && firstCell.textContent.trim() == id) {
                    const noteCell = row.querySelector('td:nth-child(5)');
                    if (noteCell) {
                        const noteText = noteCell.textContent.trim();
                        return noteText === '无备注' ? '' : noteText;
                    }
                }
            }
            return '';
        }

        async function deleteLicense(id) {
            const confirmed = await modernModal.confirm('确定要删除这个许可证吗？此操作不可撤销！', '删除确认');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/licenses/\${id}/delete`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function disableLicense(id) {
            const confirmed = await modernModal.confirm('确定要禁用这个许可证吗？', '禁用确认');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/licenses/\${id}/disable`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function enableLicense(id) {
            const confirmed = await modernModal.confirm('确定要启用这个许可证吗？', '启用确认');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/licenses/\${id}/enable`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function unbindDevice(id) {
            const confirmed = await modernModal.confirm('确定要解绑设备吗？许可证将恢复为未使用状态。', '解绑确认');
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/licenses/\${id}/unbind`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function extendLicense(id) {
            const days = await modernModal.prompt('请输入要调整的天数（-365到365，正数延长，负数减少）:', '30', '调整有效期');
            if (days === null) return;

            const daysNum = parseInt(days);
            if (!daysNum || daysNum < -365 || daysNum > 365) {
                await modernModal.alert('调整天数必须在-365到365之间（正数延长，负数减少）', '参数错误', 'error');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/licenses/\${id}/extend`;
            form.innerHTML = `<input type="hidden" name="days" value="\${daysNum}">`;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    {$notificationScript}
</body>
</html>
HTML;
    }


    private function renderStatusOptions(?string $currentStatus): string
    {
        $statuses = [
            '0' => '未使用',
            '1' => '已使用',
            '2' => '已禁用'
        ];

        $html = '';
        foreach ($statuses as $value => $text) {
            $selected = ($currentStatus === $value) ? 'selected' : '';
            $html .= "<option value=\"{$value}\" {$selected}>{$text}</option>";
        }

        return $html;
    }


    private function getStatusBadge(int $status): string
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-secondary">未使用</span>';
            case 1:
                return '<span class="badge bg-success">已使用</span>';
            case 2:
                return '<span class="badge bg-danger">已禁用</span>';
            default:
                return '<span class="badge bg-secondary">未知</span>';
        }
    }


    private function renderPagination(array $result): string
    {
        $currentPage = $result['current_page'];
        $lastPage = $result['last_page'];
        $total = $result['total'];
        $perPage = $result['per_page'];

        if ($lastPage <= 1) {
            return '';
        }

        $html = '<nav aria-label="许可证分页"><ul class="pagination justify-content-center">';


        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?page={$prevPage}\">上一页</a></li>";
        }


        for ($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class=\"page-item {$active}\"><a class=\"page-link\" href=\"?page={$i}\">{$i}</a></li>";
        }


        if ($currentPage < $lastPage) {
            $nextPage = $currentPage + 1;
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?page={$nextPage}\">下一页</a></li>";
        }

        $html .= '</ul></nav>';

        $html .= "<div class=\"text-center text-muted mt-2\">共 {$total} 条记录，每页 {$perPage} 条</div>";

        return $html;
    }


    public function create(Request $request): Response
    {
        try {

            if (!SessionManager::isLoggedIn()) {
                SessionManager::setFlashMessage('error', '请先登录');
                return Response::redirect('/login');
            }
            $data = $request->all();

            if (!isset($data['count']) || !isset($data['duration_days'])) {
                SessionManager::setFlashMessage('error', '参数缺失');
                return Response::redirect('/licenses');
            }

            $count = (int)$data['count'];
            $durationDays = (int)$data['duration_days'];

            if ($count < 1 || $count > 100) {
                SessionManager::setFlashMessage('error', '数量必须在1-100之间');
                return Response::redirect('/licenses');
            }

            if ($durationDays < 1 || $durationDays > 3650) {
                SessionManager::setFlashMessage('error', '有效期必须在1-3650天之间');
                return Response::redirect('/licenses');
            }


            $prefix = isset($data['prefix']) ? $data['prefix'] : null;
            $length = isset($data['length']) ? (int)$data['length'] : null;
            $charset = isset($data['charset']) ? $data['charset'] : null;


            if ($prefix !== null || $length !== null || $charset !== null) {
                if (empty($prefix) || $length < 8 || $length > 64 || empty($charset)) {
                    SessionManager::setFlashMessage('error', '自定义格式参数不正确');
                    return Response::redirect('/licenses');
                }
            }


            $licenseIds = $this->licenseModel->createMultipleLicenses($count, $durationDays, $prefix, $length, $charset);


            $this->adminLogModel->logAction(
                '创建许可证',
                "创建了 {$count} 个许可证，有效期 {$durationDays} 天",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', "成功创建 {$count} 个许可证");
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Create license error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '创建许可证失败');
            return Response::redirect('/licenses');
        }
    }


    public function edit(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches[1];
            $data = $request->all();

            $license = $this->licenseModel->find($id);
            if (!$license) {
                SessionManager::setFlashMessage('error', '许可证不存在');
                return Response::redirect('/licenses');
            }

            $updateData = [];
            if (isset($data['machine_note'])) {
                $updateData['machine_note'] = $data['machine_note'];
            }

            if (empty($updateData)) {
                SessionManager::setFlashMessage('error', '没有可更新的字段');
                return Response::redirect('/licenses');
            }

            $this->licenseModel->update($id, $updateData);


            $this->adminLogModel->logAction(
                '更新许可证',
                "更新许可证 ID: {$id}，密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', '许可证更新成功');
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Edit license error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '更新许可证失败');
            return Response::redirect('/licenses');
        }
    }


    public function delete(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches[1];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                SessionManager::setFlashMessage('error', '许可证不存在');
                return Response::redirect('/licenses');
            }

            $this->licenseModel->delete($id);


            $this->licenseModel->resetAutoIncrement();


            $this->adminLogModel->logAction(
                '删除许可证',
                "删除许可证 ID: {$id}，密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', '许可证删除成功');
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Delete license error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '删除许可证失败');
            return Response::redirect('/licenses');
        }
    }


    public function disable(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches[1];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                SessionManager::setFlashMessage('error', '许可证不存在');
                return Response::redirect('/licenses');
            }

            $this->licenseModel->disableLicense($id);


            $this->adminLogModel->logAction(
                '禁用许可证',
                "禁用许可证 ID: {$id}，密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', '许可证已禁用');
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Disable license error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '禁用许可证失败');
            return Response::redirect('/licenses');
        }
    }


    public function enable(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches[1];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                SessionManager::setFlashMessage('error', '许可证不存在');
                return Response::redirect('/licenses');
            }

            $this->licenseModel->enableLicense($id);


            $this->adminLogModel->logAction(
                '启用许可证',
                "启用许可证 ID: {$id}，密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', '许可证已启用');
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Enable license error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '启用许可证失败');
            return Response::redirect('/licenses');
        }
    }


    public function unbind(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches[1];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                SessionManager::setFlashMessage('error', '许可证不存在');
                return Response::redirect('/licenses');
            }

            $this->licenseModel->unbindDevice($id);


            $this->adminLogModel->logAction(
                '解绑设备',
                "解绑许可证 ID: {$id}，密钥: {$license['license_key']} 的设备",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            SessionManager::setFlashMessage('success', '设备解绑成功');
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Unbind device error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '解绑设备失败');
            return Response::redirect('/licenses');
        }
    }


    public function extend(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches[1];
            $data = $request->all();

            if (!isset($data['days']) || !is_numeric($data['days'])) {
                SessionManager::setFlashMessage('error', '延长时间参数错误');
                return Response::redirect('/licenses');
            }

            $days = (int)$data['days'];
            if ($days < -365 || $days > 365 || $days == 0) {
                SessionManager::setFlashMessage('error', '调整时间必须在-365到365天之间（不能为0）');
                return Response::redirect('/licenses');
            }

            $license = $this->licenseModel->find($id);
            if (!$license) {
                SessionManager::setFlashMessage('error', '许可证不存在');
                return Response::redirect('/licenses');
            }

            $this->licenseModel->extendExpiry($id, $days);


            $action = $days > 0 ? '延长有效期' : '缩短有效期';
            $description = $days > 0
                ? "延长许可证 ID: {$id}，密钥: {$license['license_key']} 有效期 {$days} 天"
                : "缩短许可证 ID: {$id}，密钥: {$license['license_key']} 有效期 " . abs($days) . " 天";

            $this->adminLogModel->logAction(
                $action,
                $description,
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $message = $days > 0
                ? "许可证有效期已延长 {$days} 天"
                : "许可证有效期已缩短 " . abs($days) . " 天";
            SessionManager::setFlashMessage('success', $message);
            return Response::redirect('/licenses');

        } catch (\Exception $e) {
            $this->logger->error('Extend license error', [
                'error' => $e->getMessage(),
            ]);

            SessionManager::setFlashMessage('error', '延长有效期失败');
            return Response::redirect('/licenses');
        }
    }
}
<?php

namespace AuthSystem\Api\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Models\License;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;


class LicenseController
{
    private License $licenseModel;
    private AdminLog $adminLogModel;
    private Logger $logger;

    public function __construct(License $licenseModel, AdminLog $adminLogModel, Logger $logger)
    {
        $this->licenseModel = $licenseModel;
        $this->adminLogModel = $adminLogModel;
        $this->logger = $logger;
    }


    public function index(Request $request): Response
    {
        try {
            $page = (int)($request->get('page', 1));
            $perPage = (int)($request->get('per_page', 20));
            $status = $request->get('status');
            $search = $request->get('search');

            $query = $this->licenseModel->query();


            if ($status !== null) {
                $query->where('status', (int)$status);
            }


            if ($search) {
                $query->where('license_key', 'LIKE', "%{$search}%");
            }

            $result = $query->paginate($page, $perPage);


            foreach ($result['data'] as &$license) {
                $license['status_text'] = $this->licenseModel->getStatusText($license['status']);
            }

            return Response::success($result);

        } catch (\Exception $e) {
            $this->logger->error('Get licenses error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('获取许可证列表失败', 500);
        }
    }


    public function store(Request $request): Response
    {
        try {
            $data = $request->json();

            if (!$data || !isset($data['count']) || !isset($data['duration_days'])) {
                return Response::validationError([
                    'count' => ['数量是必需的'],
                    'duration_days' => ['有效期天数是必需的'],
                ]);
            }

            $count = (int)$data['count'];
            $durationDays = (int)$data['duration_days'];

            if ($count < 1 || $count > 1000) {
                return Response::validationError([
                    'count' => ['数量必须在1-1000之间'],
                ]);
            }

            if ($durationDays < 1 || $durationDays > 3650) {
                return Response::validationError([
                    'duration_days' => ['有效期必须在1-3650天之间'],
                ]);
            }


            $licenseIds = $this->licenseModel->createMultipleLicenses($count, $durationDays);


            $this->adminLogModel->logAction(
                '创建许可证',
                "创建了 {$count} 个许可证，有效期 {$durationDays} 天",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            $this->logger->info('Licenses created', [
                'count' => $count,
                'duration_days' => $durationDays,
                'ip' => $request->getClientIp(),
            ]);

            return Response::success([
                'message' => "成功创建 {$count} 个许可证",
                'license_ids' => $licenseIds,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Create licenses error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('创建许可证失败', 500);
        }
    }


    public function update(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches['id'];
            $data = $request->json();

            $license = $this->licenseModel->find($id);
            if (!$license) {
                return Response::notFound('许可证不存在');
            }

            $allowedFields = ['status', 'machine_note'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                return Response::validationError(['message' => '没有可更新的字段']);
            }

            $this->licenseModel->update($id, $updateData);


            $this->adminLogModel->logAction(
                '更新许可证',
                "更新许可证 ID: {$id}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success(['message' => '许可证更新成功']);

        } catch (\Exception $e) {
            $this->logger->error('Update license error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('更新许可证失败', 500);
        }
    }


    public function destroy(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches['id'];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                return Response::notFound('许可证不存在');
            }

            $this->licenseModel->delete($id);


            $this->adminLogModel->logAction(
                '删除许可证',
                "删除许可证 ID: {$id}, 密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success(['message' => '许可证删除成功']);

        } catch (\Exception $e) {
            $this->logger->error('Delete license error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('删除许可证失败', 500);
        }
    }


    public function disable(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches['id'];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                return Response::notFound('许可证不存在');
            }

            $this->licenseModel->disableLicense($id);


            $this->adminLogModel->logAction(
                '禁用许可证',
                "禁用许可证 ID: {$id}, 密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success(['message' => '许可证已禁用']);

        } catch (\Exception $e) {
            $this->logger->error('Disable license error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('禁用许可证失败', 500);
        }
    }


    public function enable(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches['id'];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                return Response::notFound('许可证不存在');
            }

            $this->licenseModel->enableLicense($id);


            $this->adminLogModel->logAction(
                '启用许可证',
                "启用许可证 ID: {$id}, 密钥: {$license['license_key']}",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success(['message' => '许可证已启用']);

        } catch (\Exception $e) {
            $this->logger->error('Enable license error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('启用许可证失败', 500);
        }
    }


    public function unbind(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches['id'];

            $license = $this->licenseModel->find($id);
            if (!$license) {
                return Response::notFound('许可证不存在');
            }

            $this->licenseModel->unbindDevice($id);


            $this->adminLogModel->logAction(
                '解绑设备',
                "解绑许可证 ID: {$id}, 密钥: {$license['license_key']} 的设备",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success(['message' => '设备解绑成功']);

        } catch (\Exception $e) {
            $this->logger->error('Unbind device error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('解绑设备失败', 500);
        }
    }


    public function extend(Request $request, array $matches): Response
    {
        try {
            $id = (int)$matches['id'];
            $data = $request->json();

            if (!isset($data['days']) || !is_numeric($data['days'])) {
                return Response::validationError([
                    'days' => ['延长时间是必需的'],
                ]);
            }

            $days = (int)$data['days'];
            if ($days < 1 || $days > 3650) {
                return Response::validationError([
                    'days' => ['延长时间必须在1-3650天之间'],
                ]);
            }

            $license = $this->licenseModel->find($id);
            if (!$license) {
                return Response::notFound('许可证不存在');
            }

            $this->licenseModel->extendExpiry($id, $days);


            $this->adminLogModel->logAction(
                '延长有效期',
                "延长许可证 ID: {$id}, 密钥: {$license['license_key']} 有效期 {$days} 天",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success(['message' => "许可证有效期已延长 {$days} 天"]);

        } catch (\Exception $e) {
            $this->logger->error('Extend license error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('延长有效期失败', 500);
        }
    }


    public function stats(Request $request): Response
    {
        try {
            $stats = $this->licenseModel->getStats();

            return Response::success($stats);

        } catch (\Exception $e) {
            $this->logger->error('Get license stats error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('获取统计信息失败', 500);
        }
    }
}
<?php

namespace AuthSystem\Api\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Models\UsageLog;
use AuthSystem\Models\AdminLog;
use AuthSystem\Core\Logger\Logger;


class LogController
{
    private UsageLog $usageLogModel;
    private AdminLog $adminLogModel;
    private Logger $logger;

    public function __construct(UsageLog $usageLogModel, AdminLog $adminLogModel, Logger $logger)
    {
        $this->usageLogModel = $usageLogModel;
        $this->adminLogModel = $adminLogModel;
        $this->logger = $logger;
    }


    public function index(Request $request): Response
    {
        try {
            $page = (int)($request->get('page', 1));
            $perPage = (int)($request->get('per_page', 50));
            $type = $request->get('type', 'usage');
            $licenseKey = $request->get('license_key');
            $machineCode = $request->get('machine_code');
            $status = $request->get('status');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($type === 'admin') {
                $query = $this->adminLogModel->query();

                if ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('created_at', '<=', $endDate . ' 23:59:59');
                }

                $result = $query->paginate($page, $perPage);
            } else {
                $query = $this->usageLogModel->query();

                if ($licenseKey) {
                    $query->where('license_key', 'LIKE', "%{$licenseKey}%");
                }
                if ($machineCode) {
                    $query->where('machine_code', 'LIKE', "%{$machineCode}%");
                }
                if ($status) {
                    $query->where('status', 'LIKE', "%{$status}%");
                }
                if ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('created_at', '<=', $endDate . ' 23:59:59');
                }

                $result = $query->paginate($page, $perPage);
            }

            return Response::success($result);

        } catch (\Exception $e) {
            $this->logger->error('Get logs error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('获取日志失败', 500);
        }
    }


    public function stats(Request $request): Response
    {
        try {
            $days = (int)($request->get('days', 30));

            $usageStats = $this->usageLogModel->getStats($days);
            $adminStats = $this->adminLogModel->getActionStats($days);

            return Response::success([
                'usage' => $usageStats,
                'admin' => $adminStats,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Get log stats error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('获取统计信息失败', 500);
        }
    }


    public function export(Request $request): Response
    {
        try {
            $type = $request->get('type', 'usage');
            $format = $request->get('format', 'csv');
            $limit = (int)($request->get('limit', 1000));

            if ($type === 'usage' && $format === 'csv') {
                $csv = $this->usageLogModel->exportToCsv($limit);

                $response = new Response($csv, 200, [
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="usage_logs_' . date('Y-m-d') . '.csv"',
                ]);

                return $response;
            }

            return Response::error('不支持的导出格式', 400);

        } catch (\Exception $e) {
            $this->logger->error('Export logs error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('导出日志失败', 500);
        }
    }


    public function cleanup(Request $request): Response
    {
        try {
            $days = (int)($request->get('days', 90));

            $usageCleaned = $this->usageLogModel->cleanOldLogs($days);
            $adminCleaned = $this->adminLogModel->cleanOldLogs($days);


            $this->adminLogModel->logAction(
                '清理日志',
                "清理了 {$usageCleaned} 条使用日志和 {$adminCleaned} 条管理日志",
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return Response::success([
                'message' => '日志清理完成',
                'usage_cleaned' => $usageCleaned,
                'admin_cleaned' => $adminCleaned,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Cleanup logs error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error('清理日志失败', 500);
        }
    }
}
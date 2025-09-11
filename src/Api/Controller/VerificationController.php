<?php

namespace AuthSystem\Api\Controller;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Models\License;
use AuthSystem\Models\UsageLog;
use AuthSystem\Core\Logger\Logger;


class VerificationController
{
    private License $licenseModel;
    private UsageLog $usageLogModel;
    private Logger $logger;

    public function __construct(License $licenseModel, UsageLog $usageLogModel, Logger $logger)
    {
        $this->licenseModel = $licenseModel;
        $this->usageLogModel = $usageLogModel;
        $this->logger = $logger;
    }


    public function verify(Request $request): Response
    {
        try {
            $data = $request->json();

            if (!$data || !isset($data['license_key']) || !isset($data['machine_code'])) {
                return Response::validationError([
                    'license_key' => ['许可证密钥是必需的'],
                    'machine_code' => ['机器码是必需的'],
                ]);
            }

            $licenseKey = $data['license_key'];
            $machineCode = $data['machine_code'];


            $result = $this->licenseModel->verifyLicense($licenseKey, $machineCode);


            $this->usageLogModel->logUsage(
                $licenseKey,
                $machineCode,
                $result['message'],
                $request->getClientIp(),
                $request->getUserAgent()
            );


            $this->logger->info('License verification attempt', [
                'license_key' => $licenseKey,
                'machine_code' => $machineCode,
                'success' => $result['success'],
                'ip' => $request->getClientIp(),
            ]);

            if ($result['success']) {
                return Response::success($result['data'] ?? [], $result['message']);
            } else {
                return Response::error($result['message'], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error('License verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error('服务器内部错误', 500);
        }
    }
}
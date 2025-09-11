<?php

namespace AuthSystem\Models;

use PDO;


class UsageLog extends BaseModel
{
    protected string $table = 'usage_logs';
    protected array $fillable = [
        'license_key',
        'machine_code',
        'status',
        'ip_address',
        'user_agent',
    ];
    protected array $timestamps = ['created_at'];
    protected array $casts = [
        'created_at' => 'datetime',
    ];


    public function logUsage(string $licenseKey, string $machineCode, string $status, string $ipAddress = null, string $userAgent = null): int
    {
        $data = [
            'license_key' => $licenseKey,
            'machine_code' => $machineCode,
            'status' => $status,
            'ip_address' => $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        return $this->create($data);
    }


    public function getLicenseHistory(string $licenseKey, int $limit = 50): array
    {
        return $this->query()
            ->where('license_key', $licenseKey)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }


    public function getMachineHistory(string $machineCode, int $limit = 50): array
    {
        return $this->query()
            ->where('machine_code', $machineCode)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }


    public function getStats(int $days = 30): array
    {
        $stats = [];


        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total_requests'] = (int)$stmt->fetchColumn();


        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $stats['recent_requests'] = (int)$stmt->fetchColumn();


        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = '验证成功'");
        $stats['successful_requests'] = (int)$stmt->fetchColumn();


        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status != '验证成功'");
        $stats['failed_requests'] = (int)$stmt->fetchColumn();


        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status ORDER BY count DESC");
        $stats['by_status'] = $stmt->fetchAll();


        $stmt = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stats['by_date'] = $stmt->fetchAll();

        return $stats;
    }


    public function cleanOldLogs(int $days = 90): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);

        return $stmt->rowCount();
    }


    public function exportToCsv(int $limit = 1000): string
    {
        $logs = $this->query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        $csv = "时间,许可证密钥,机器码,状态,IP地址,用户代理\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $log['created_at'],
                $log['license_key'],
                $log['machine_code'],
                $log['status'],
                $log['ip_address'],
                str_replace(',', ';', $log['user_agent'])
            );
        }

        return $csv;
    }
}
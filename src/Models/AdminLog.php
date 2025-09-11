<?php

namespace AuthSystem\Models;

use PDO;


class AdminLog extends BaseModel
{
    protected string $table = 'admin_logs';
    protected array $fillable = [
        'action',
        'detail',
        'ip_address',
        'user_agent',
    ];
    protected array $timestamps = ['created_at'];
    protected array $casts = [
        'created_at' => 'datetime',
    ];


    public function logAction(string $action, string $detail = null, string $ipAddress = null, string $userAgent = null): int
    {
        $data = [
            'action' => $action,
            'detail' => $detail,
            'ip_address' => $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        return $this->create($data);
    }


    public function getActionHistory(int $limit = 100): array
    {
        return $this->query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }


    public function getActionStats(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT action, COUNT(*) as count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action
            ORDER BY count DESC
        ");
        $stmt->execute([$days]);

        return $stmt->fetchAll();
    }


    public function cleanOldLogs(int $days = 180): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);

        return $stmt->rowCount();
    }
}
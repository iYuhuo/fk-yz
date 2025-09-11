<?php

namespace AuthSystem\Models;

use PDO;


class License extends BaseModel
{
    protected string $table = 'licenses';
    protected array $fillable = [
        'license_key',
        'status',
        'machine_code',
        'duration_days',
        'expires_at',
        'last_used_at',
        'machine_note',
    ];
    protected array $casts = [
        'status' => 'int',
        'duration_days' => 'int',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];


    public const STATUS_UNUSED = 0;
    public const STATUS_USED = 1;
    public const STATUS_DISABLED = 2;


    public function findByLicenseKey(string $licenseKey): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $result = $stmt->fetch();

        return $result ?: null;
    }


    public function generateLicenseKey(string $prefix = null, int $length = null, string $charset = null): string
    {

        $prefix = $prefix ?? ($_ENV['LICENSE_PREFIX'] ?? 'zz');
        $length = $length ?? (int)($_ENV['LICENSE_LENGTH'] ?? 18);
        $charset = $charset ?? ($_ENV['LICENSE_CHARSET'] ?? 'abcdefghijklmnopqrstuvwxyz0123456789');


        $randomLength = $length - strlen($prefix);
        if ($randomLength <= 0) {
            $randomLength = 10;
        }


        $randomPart = '';
        $charsetLength = strlen($charset);

        for ($i = 0; $i < $randomLength; $i++) {
            $randomPart .= $charset[random_int(0, $charsetLength - 1)];
        }

        return $prefix . $randomPart;
    }


    public function validateLicenseKeyFormat(string $licenseKey): bool
    {
        $prefix = $_ENV['LICENSE_PREFIX'] ?? 'zz';
        $length = (int)($_ENV['LICENSE_LENGTH'] ?? 18);
        $charset = $_ENV['LICENSE_CHARSET'] ?? 'abcdefghijklmnopqrstuvwxyz0123456789';


        if (strlen($licenseKey) !== $length) {
            return false;
        }


        if (!str_starts_with($licenseKey, $prefix)) {
            return false;
        }


        $randomPart = substr($licenseKey, strlen($prefix));
        for ($i = 0; $i < strlen($randomPart); $i++) {
            if (strpos($charset, $randomPart[$i]) === false) {
                return false;
            }
        }

        return true;
    }


    public function getReusableId(): ?int
    {

        $stmt = $this->db->query("
            SELECT t1.id + 1 as gap_start
            FROM {$this->table} t1
            LEFT JOIN {$this->table} t2 ON t1.id + 1 = t2.id
            WHERE t2.id IS NULL AND t1.id < (SELECT MAX(id) FROM {$this->table})
            ORDER BY gap_start
            LIMIT 1
        ");

        $result = $stmt->fetch();
        return $result ? (int)$result['gap_start'] : null;
    }


    public function resetAutoIncrement(): bool
    {
        try {

            $stmt = $this->db->query("SELECT MAX(id) as max_id FROM {$this->table}");
            $result = $stmt->fetch();
            $maxId = $result ? (int)$result['max_id'] : 0;


            $nextId = $maxId + 1;
            $this->db->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = {$nextId}");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function reorderAllIds(): array
    {
        try {
            $this->db->beginTransaction();


            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id ASC");
            $licenses = $stmt->fetchAll();

            if (empty($licenses)) {
                $this->db->rollback();
                return ['success' => false, 'message' => '没有许可证需要整理'];
            }


            $this->db->exec("CREATE TEMPORARY TABLE temp_licenses LIKE {$this->table}");


            $newId = 1;
            $reorderedCount = 0;
            foreach ($licenses as $license) {
                $columns = array_keys($license);
                $placeholders = str_repeat('?,', count($columns) - 1) . '?';


                $license['id'] = $newId;
                $values = array_values($license);

                $columnsStr = implode(',', $columns);
                $stmt = $this->db->prepare("INSERT INTO temp_licenses ({$columnsStr}) VALUES ({$placeholders})");
                $stmt->execute($values);

                $reorderedCount++;
                $newId++;
            }


            $this->db->exec("DELETE FROM {$this->table}");


            $this->db->exec("INSERT INTO {$this->table} SELECT * FROM temp_licenses");


            $this->db->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = {$newId}");


            $this->db->exec("DROP TEMPORARY TABLE temp_licenses");

            $this->db->commit();

            return [
                'success' => true,
                'message' => "成功整理了 {$reorderedCount} 个许可证的ID",
                'reordered_count' => $reorderedCount,
                'new_max_id' => $newId - 1
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => '整理ID失败: ' . $e->getMessage()];
        }
    }


    public function createLicense(int $durationDays, string $licenseKey = null, string $prefix = null, int $length = null, string $charset = null): int
    {
        $data = [
            'license_key' => $licenseKey ?: $this->generateLicenseKey($prefix, $length, $charset),
            'status' => self::STATUS_UNUSED,
            'duration_days' => $durationDays,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$durationDays} days")),
        ];


        $reusableId = $this->getReusableId();
        if ($reusableId) {
            return $this->createWithSpecificId($reusableId, $data);
        }

        return $this->create($data);
    }


    private function createWithSpecificId(int $id, array $data): int
    {

        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $columnsStr = implode(',', $columns);


        $sql = "INSERT INTO {$this->table} (id, {$columnsStr}) VALUES (?, {$placeholders})";
        $values = array_merge([$id], array_values($data));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $id;
    }


    public function createMultipleLicenses(int $count, int $durationDays, string $prefix = null, int $length = null, string $charset = null): array
    {
        $licenseIds = [];

        for ($i = 0; $i < $count; $i++) {
            $licenseIds[] = $this->createLicense($durationDays, null, $prefix, $length, $charset);
        }

        return $licenseIds;
    }


    public function verifyLicense(string $licenseKey, string $machineCode): array
    {
        $license = $this->findByLicenseKey($licenseKey);

        if (!$license) {
            return ['success' => false, 'message' => '许可证不存在'];
        }


        if ($license['status'] === self::STATUS_DISABLED) {
            return ['success' => false, 'message' => '许可证已被禁用'];
        }


        if (strtotime($license['expires_at']) < time()) {
            return ['success' => false, 'message' => '许可证已过期'];
        }


        if ($license['machine_code'] === null) {

            $this->update($license['id'], [
                'machine_code' => $machineCode,
                'status' => self::STATUS_USED,
                'last_used_at' => date('Y-m-d H:i:s'),
            ]);


            $remainingDays = max(0, ceil((strtotime($license['expires_at']) - time()) / 86400));

            return [
                'success' => true,
                'message' => '验证成功，已绑定设备',
                'data' => [
                    'license_id' => $license['id'],
                    'expires_at' => $license['expires_at'],
                    'remaining_days' => $remainingDays,
                    'status' => 'bound'
                ]
            ];
        }

        if ($license['machine_code'] === $machineCode) {

            $this->update($license['id'], [
                'last_used_at' => date('Y-m-d H:i:s'),
            ]);


            $remainingDays = max(0, ceil((strtotime($license['expires_at']) - time()) / 86400));

            return [
                'success' => true,
                'message' => '验证成功',
                'data' => [
                    'license_id' => $license['id'],
                    'expires_at' => $license['expires_at'],
                    'remaining_days' => $remainingDays,
                    'status' => 'verified'
                ]
            ];
        }

        return ['success' => false, 'message' => '机器码不匹配，请使用绑定的设备'];
    }


    public function disableLicense(int $id): bool
    {
        return $this->update($id, ['status' => self::STATUS_DISABLED]);
    }


    public function enableLicense(int $id): bool
    {
        return $this->update($id, ['status' => self::STATUS_USED]);
    }


    public function unbindDevice(int $id): bool
    {
        return $this->update($id, [
            'machine_code' => null,
            'status' => self::STATUS_UNUSED,
            'last_used_at' => null,
        ]);
    }


    public function extendExpiry(int $id, int $days): bool
    {
        $license = $this->find($id);
        if (!$license) {
            return false;
        }


        $operator = $days >= 0 ? '+' : '';
        $newExpiry = date('Y-m-d H:i:s', strtotime($license['expires_at'] . " {$operator}{$days} days"));

        return $this->update($id, ['expires_at' => $newExpiry]);
    }


    public function getStats(): array
    {
        $stats = [];


        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = (int)$stmt->fetchColumn();


        $statuses = [self::STATUS_UNUSED, self::STATUS_USED, self::STATUS_DISABLED];
        foreach ($statuses as $status) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
            $stmt->execute([$status]);
            $stats['status_' . $status] = (int)$stmt->fetchColumn();
        }


        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
        $stats['expiring_soon'] = (int)$stmt->fetchColumn();

        return $stats;
    }


    public function getStatusText(int $status): string
    {
        $statusTexts = [
            self::STATUS_UNUSED => '未使用',
            self::STATUS_USED => '已使用',
            self::STATUS_DISABLED => '已禁用',
        ];

        return $statusTexts[$status] ?? '未知';
    }
}
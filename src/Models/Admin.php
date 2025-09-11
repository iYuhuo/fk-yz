<?php

namespace AuthSystem\Models;

use PDO;


class Admin extends BaseModel
{
    protected string $table = 'admin_settings';
    protected array $fillable = [
        'username',
        'password_hash',
        'email',
        'last_login_at',
    ];
    protected array $casts = [
        'last_login_at' => 'datetime',
    ];


    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();

        return $result ?: null;
    }


    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }


    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }


    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = $this->hashPassword($newPassword);
        return $this->update($id, ['password_hash' => $hash]);
    }


    public function updateLastLogin(int $id): bool
    {
        return $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }


    public function createDefaultAdmin(): int
    {
        $data = [
            'username' => 'admin',
            'password_hash' => $this->hashPassword('password'),
            'email' => 'admin@example.com',
        ];

        return $this->create($data);
    }


    public function adminExists(): bool
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int)$stmt->fetchColumn() > 0;
    }
}
<?php

namespace AuthSystem\Models;

use PDO;
use AuthSystem\Core\Config\Config;


abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = [];
    protected array $casts = [];
    protected array $timestamps = ['created_at', 'updated_at'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }


    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }


    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        $data = $this->addTimestamps($data, 'create');

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$this->db->lastInsertId();
    }


    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        $data = $this->addTimestamps($data, 'update');

        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";

        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }


    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }


    public function query(): QueryBuilder
    {
        return new QueryBuilder($this->db, $this->table);
    }


    protected function filterFillable(array $data): array
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }

        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }

        return $data;
    }


    protected function addTimestamps(array $data, string $action): array
    {
        $now = date('Y-m-d H:i:s');

        if ($action === 'create' && in_array('created_at', $this->timestamps)) {
            $data['created_at'] = $now;
        }

        if (in_array('updated_at', $this->timestamps)) {
            $data['updated_at'] = $now;
        }

        return $data;
    }


    protected function cast(array $data): array
    {
        foreach ($this->casts as $field => $type) {
            if (isset($data[$field])) {
                $data[$field] = $this->castValue($data[$field], $type);
            }
        }

        return $data;
    }


    protected function castValue($value, string $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'date':
                return $value ? date('Y-m-d', strtotime($value)) : null;
            case 'datetime':
                return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
            default:
                return $value;
        }
    }
}
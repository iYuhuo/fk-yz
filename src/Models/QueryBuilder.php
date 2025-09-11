<?php

namespace AuthSystem\Models;

use PDO;


class QueryBuilder
{
    private PDO $db;
    private string $table;
    private array $wheres = [];
    private array $orders = [];
    private array $groups = [];
    private array $havings = [];
    private int $limit = 0;
    private int $offset = 0;
    private array $joins = [];
    private array $selects = ['*'];

    public function __construct(PDO $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }


    public function select(array $columns): self
    {
        $this->selects = $columns;
        return $this;
    }


    public function where(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }


    public function orWhere(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }


    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
        ];

        return $this;
    }


    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => $values,
        ];

        return $this;
    }


    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null,
        ];

        return $this;
    }


    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null,
        ];

        return $this;
    }


    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }


    public function groupBy(string $column): self
    {
        $this->groups[] = $column;
        return $this;
    }


    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }


    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }


    public function paginate(int $page, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $this->limit($perPage)->offset($offset);

        $data = $this->get();
        $total = $this->count();

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }


    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }


    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }


    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->getBindings());

        return $stmt->fetchAll();
    }


    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }


    public function count(): int
    {
        $sql = $this->buildCountQuery();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->getBindings());

        return (int)$stmt->fetchColumn();
    }


    private function buildSelectQuery(): string
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";


        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }


        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }


        if (!empty($this->groups)) {
            $sql .= " GROUP BY " . implode(', ', $this->groups);
        }


        if (!empty($this->orders)) {
            $orderClauses = array_map(fn($order) => "{$order['column']} {$order['direction']}", $this->orders);
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }


        if ($this->limit > 0) {
            $sql .= " LIMIT {$this->limit}";
        }


        if ($this->offset > 0) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }


    private function buildCountQuery(): string
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";


        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }


        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }

        return $sql;
    }


    private function buildWhereClause(): string
    {
        $clauses = [];

        foreach ($this->wheres as $where) {
            $clause = $where['column'] . ' ' . $where['operator'];

            if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                $placeholders = str_repeat('?,', count($where['value']) - 1) . '?';
                $clause .= " ({$placeholders})";
            } elseif ($where['value'] !== null) {
                $clause .= ' ?';
            }

            $clauses[] = ($clauses ? $where['type'] . ' ' : '') . $clause;
        }

        return implode(' ', $clauses);
    }


    private function getBindings(): array
    {
        $bindings = [];

        foreach ($this->wheres as $where) {
            if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                $bindings = array_merge($bindings, $where['value']);
            } elseif ($where['value'] !== null) {
                $bindings[] = $where['value'];
            }
        }

        return $bindings;
    }
}
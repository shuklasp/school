<?php

namespace SPPMod\SPPDB;

/**
 * class QueryBuilder
 * Modern, fluent SQL query builder for SPPDB.
 * 
 * @author Satya Prakash Shukla
 */
class QueryBuilder
{
    /** @var SPPDB */
    protected $db;

    /** @var string */
    protected $table;

    /** @var array */
    protected $columns = ['*'];

    /** @var array */
    protected $wheres = [];

    /** @var array */
    protected $joins = [];

    /** @var array */
    protected $orders = [];

    /** @var int|null */
    protected $limit;

    /** @var int|null */
    protected $offset;

    /** @var array */
    protected $bindings = [];

    public function __construct(SPPDB $db, string $table)
    {
        $this->db = $db;
        $this->table = SPPDB::sppTable($table);
    }

    /**
     * Specify columns to select.
     */
    public function select($columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a raw select segment.
     */
    public function selectRaw(string $sql, array $bindings = []): self
    {
        $this->columns = [$sql];
        $this->addBindings($bindings);
        return $this;
    }

    /**
     * Add a WHERE clause.
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND'): self
    {
        // If the column is a Closure, we handle nested groupings
        if ($column instanceof \Closure) {
            return $this->whereNested($column, $boolean);
        }

        // Handle where('col', 'val') shortcut
        if (func_num_args() === 2 || ($value === null && !in_array(strtoupper((string)$operator), ['IS', 'IS NOT', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN']))) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        $this->addBindings([$value]);
        return $this;
    }

    /**
     * Add an OR WHERE clause.
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a raw WHERE clause.
     */
    public function whereRaw(string $sql, array $bindings = [], $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean
        ];

        $this->addBindings($bindings);
        return $this;
    }

    /**
     * Handle nested groupings.
     */
    protected function whereNested(\Closure $callback, $boolean = 'AND'): self
    {
        $query = new static($this->db, '');
        $query->table = ''; // Temporary for compilation
        
        $callback($query);

        $this->wheres[] = [
            'type' => 'nested',
            'query' => $query,
            'boolean' => $boolean
        ];

        $this->addBindings($query->getBindings());
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $table = SPPDB::sppTable($table);
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Set LIMIT.
     */
    public function limit(int $value): self
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * Set OFFSET.
     */
    public function offset(int $value): self
    {
        $this->offset = $value;
        return $this;
    }

    /**
     * Execute and return all results.
     */
    public function get(): array
    {
        return $this->db->execute_query($this->toSql(), $this->getBindings());
    }

    /**
     * Execute and return the first result.
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Return the count of matching rows.
     */
    public function count(): int
    {
        $originalColumns = $this->columns;
        $this->columns = ['COUNT(*) as aggregate'];
        $result = $this->first();
        $this->columns = $originalColumns;
        
        return (int)($result['aggregate'] ?? 0);
    }

    /**
     * Insert a new record.
     */
    public function insert(array $values): bool
    {
        if (empty($values)) return true;

        $columns = array_keys($values);
        $placeholders = array_fill(0, count($values), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(array_values($values));
        } catch (\PDOException $e) {
            throw new \SPP\SPPException("Database Insert Error: " . $e->getMessage());
        }
    }

    /**
     * Update existing records.
     */
    public function update(array $values): bool
    {
        if (empty($values)) return true;

        $set = [];
        foreach ($values as $column => $value) {
            $set[] = "$column = ?";
        }

        // We need to merge update bindings with where bindings
        $bindings = array_merge(array_values($values), $this->getBindings());
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . $this->compileWheres();
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($bindings);
        } catch (\PDOException $e) {
            throw new \SPP\SPPException("Database Update Error: " . $e->getMessage());
        }
    }

    /**
     * Delete records.
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}" . $this->compileWheres();
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($this->getBindings());
        } catch (\PDOException $e) {
            throw new \SPP\SPPException("Database Delete Error: " . $e->getMessage());
        }
    }

    /**
     * Compile the components into a SQL string.
     */
    public function toSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";

        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        $sql .= $this->compileWheres();

        if (!empty($this->orders)) {
            $sql .= " ORDER BY ";
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= implode(', ', $orderParts);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Compile where clauses.
     */
    protected function compileWheres(): string
    {
        if (empty($this->wheres)) return '';

        $sql = " WHERE ";
        $parts = [];

        foreach ($this->wheres as $i => $where) {
            $prefix = ($i === 0) ? '' : "{$where['boolean']} ";

            if ($where['type'] === 'basic') {
                $parts[] = "{$prefix}{$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'raw') {
                $parts[] = "{$prefix}{$where['sql']}";
            } elseif ($where['type'] === 'nested') {
                $parts[] = "{$prefix}(" . substr($where['query']->compileWheres(), 7) . ")";
            }
        }

        return $sql . implode(' ', $parts);
    }

    protected function addBindings(array $bindings): void
    {
        foreach ($bindings as $binding) {
            $this->bindings[] = $binding;
        }
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}

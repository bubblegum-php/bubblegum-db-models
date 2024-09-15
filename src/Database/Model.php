<?php

namespace Bubblegum\Database;

use Bubblegum\Database\Condition;
use Bubblegum\Database\DB;
use Bubblegum\Database\ConditionsUnion;

class Model implements \Iterator
{
    protected static string $tableName;

    protected ConditionsUnion $conditions;

    protected \PDOStatement $statement;

    protected array|false $data;

    protected int $currentIndex;

    public function __construct()
    {}

    public static function find(int $id): Model
    {
        return (new self())->where('id', '=', $id);
    }

    public function where(string $name, string $comparison, mixed $value): Model
    {
        $this->conditions->addCondition(new Condition($name, $comparison, $value));
        return $this;
    }

    protected function findByConditions(?array $columns = null): Model
    {
        $this->statement = DB::select(self::$tableName, $this->conditions, $columns);
        return $this;
    }

    public function get(): Model
    {
        $this->statement = DB::select(self::$tableName, $this->conditions);
        return $this;
    }

    public function fetchAll(): array|false
    {
        $this->get();
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function current(): Model
    {
        return $this;
    }

    public function next(): void
    {
        $this->currentIndex++;
        $this->data = $this->statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function key(): int
    {
        return $this->currentIndex;
    }

    public function valid(): bool
    {
        return $this->data !== false;
    }

    public function rewind(): void
    {
        $this->currentIndex = 0;
        $this->data = $this->statement->fetch(\PDO::FETCH_ASSOC);
    }
}
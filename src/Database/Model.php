<?php

namespace Bubblegum\Database;

use Bubblegum\Database\Condition;
use Bubblegum\Database\DB;
use Bubblegum\Database\ConditionsUnion;

class Model
{
    protected static string $tableName;

    protected ConditionsUnion $conditions;

    protected \PDOStatement $statement;

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
}
<?php

namespace Bubblegum\Database;

use Bubblegum\Database\Condition;
use Bubblegum\Database\DB;
use Bubblegum\Database\ConditionsUnion;
use Bubblegum\Exceptions\ModelException;

class Model implements \Iterator
{
    protected string $tableName;

    protected ConditionsUnion $conditions;

    protected \PDOStatement $statement;

    public array|false $data;

    protected int $currentIndex;

    public function __construct()
    {
        $this->conditions = new ConditionsUnion();
    }

    public function __get(string $columnName){
        return $this->data[$columnName] ?? null;
    }

    public static function find(int $id): Model
    {
        return (new static())->where('id', '=', $id)->get();
    }

    public function where(string $name, string $comparison, mixed $value): Model
    {
        $this->conditions->addCondition(new Condition($name, $comparison, $value));
        return $this;
    }
    
    public function get(?array $columns = null): Model
    {
        $this->statement = DB::select($this->tableName, $this->conditions, $columns);
        $this->statement->execute();
        return $this;
    }

    public function fetchAll(?array $columns = null): array|false
    {
        $this->get($columns);
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
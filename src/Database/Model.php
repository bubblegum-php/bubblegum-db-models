<?php

namespace Bubblegum\Database;

use Bubblegum\Database\Condition;
use Bubblegum\Database\DB;
use Bubblegum\Database\ConditionsUnion;
use Bubblegum\Exceptions\ModelException;

/**
 * Database model class
 */
class Model implements \Iterator
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var \Bubblegum\Database\ConditionsUnion
     */
    protected ConditionsUnion $conditions;

    /**
     * @var \PDOStatement|null
     */
    protected ?\PDOStatement $statement;

    /**
     * @var int
     */
    protected int $currentIndex;

    /**
     * @param array|false $data
     */
    public function __construct(
        protected array|false $data = []
    )
    {
        $this->conditions = new ConditionsUnion();
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $columnName
     * @return mixed|null
     */
    public function __get(string $columnName)
    {
        return $this->data[$columnName] ?? null;
    }

    /**
     * @param string $columnName
     * @param mixed $value
     * @return void
     */
    public function __set(string $columnName, mixed $value): void
    {
        $this->data[$columnName] = $value;
    }

    /**
     * Find one object in the database
     * @param int $id
     * @return Model
     */
    public static function find(int $id): Model
    {
        return (new static())->where('id', '=', $id)->first();
    }

    /**
     * Fresh model's data into the database (finds row by id)
     * @return $this
     */
    public function fresh(): Model
    {
        $this->where('id', '=', $this->data['id'])->first();
        return $this;
    }

    /**
     * Create row and returns current model for that row
     * @param array $data
     * @return Model
     */
    public static function create(array $data): Model
    {
        $model = new static();
        $model->id = DB::insert($model->getTableName(), $data);
        return $model->fresh();
    }

    /**
     * Saves updated data to the database (finds row by id)
     * @return void
     */
    public function save(): void
    {
        $this->data['updated_at'] = date('Y-m-d H:i:s');
        DB::update($this->tableName, $this->data, new ConditionsUnion([new Condition('id', '=', $this->data['id'])]));
    }

    /**
     * Add condition for selecting rows from the database
     * @param string $name
     * @param string $comparison
     * @param mixed $value
     * @return $this
     */
    public function where(string $name, string $comparison, mixed $value): Model
    {
        $this->conditions->addCondition(new Condition($name, $comparison, $value));
        return $this;
    }

    /**
     * Executes select statement and gets ready for fetches
     * @param array|null $columns columns to select (id column will be automatically added if it not there)
     * @return $this
     */
    public function get(?array $columns = null): Model
    {
        if ($columns !== null && !in_array('id', $columns)) {
            $columns[] = 'id';
        }
        $this->statement = DB::select($this->tableName, $this->conditions, $columns, $this->limit);
        $this->statement->execute();
        return $this;
    }

    /**
     * Fetches all to the array
     * @param array|null $columns
     * @return array|false
     */
    public function fetchAll(?array $columns = null): array|false
    {
        $this->get($columns);
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches first row and returns model with data
     * @param array|null $columns
     * @return $this
     */
    public function first(?array $columns = null): Model
    {
        $this->get($columns);
        $this->data = $this->statement->fetch(\PDO::FETCH_ASSOC);
        $this->statement = null;
        return $this;
    }

    /**
     * Implemented by Iterator interface
     * @return $this
     */
    public function current(): Model
    {
        return $this;
    }

    /**
     * Implemented by Iterator interface
     * @return void
     */
    public function next(): void
    {
        $this->currentIndex++;
        $this->data = $this->statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Implemented by Iterator interface
     * @return int
     */
    public function key(): int
    {
        return $this->currentIndex;
    }

    /**
     * Implemented by Iterator interface
     * @return bool
     */
    public function valid(): bool
    {
        return $this->data !== false;
    }

    /**
     * Implemented by Iterator interface
     * @return void
     */
    public function rewind(): void
    {
        $this->currentIndex = 0;
        $this->data = $this->statement->fetch(\PDO::FETCH_ASSOC);
    }
}
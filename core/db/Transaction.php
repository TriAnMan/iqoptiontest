<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 21:14
 */

namespace TriAn\IqoTest\core\db;


use TriAn\IqoTest\core\exception\DBException;
use TriAn\IqoTest\core\exception\TransactionException;

class Transaction
{
    /**
     * @var \PDO
     */
    protected $connection;

    protected $ended = false;

    /**
     * @var \PDOStatement[]
     * @todo: move to upper level where this cache can be actually helpful
     */
    protected $cache = [];

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        if (false === $this->connection->beginTransaction()) {
            throw new DBException(...$this->connection->errorInfo());
        }
    }

    /**
     * Executes SQL query in a transaction
     * @param string $query query
     * @param array $parameters
     * @return \PDOStatement
     * @throws TransactionException
     */
    public function execute($query, array $parameters)
    {
        if ($this->ended) {
            throw new TransactionException('Trying to execute statements on an ended transaction');
        }

        $statement = $this->cacheQuery($query);
        if (false === $statement->execute($parameters)) {
            throw new DBException(...$statement->errorInfo());
        }

        return $statement;
    }

    /**
     * Prepare and cache a query
     * @param string $query
     * @return \PDOStatement
     */
    protected function cacheQuery($query)
    {
        if (isset($query, $this->cache)) {
            return $this->cache[$query];
        }

        $statement = $this->connection->prepare($query);
        $this->cache[$query] = $statement;

        return $statement;
    }

    public function commit()
    {
        if ($this->ended) {
            return;
        }
        if (false === $this->connection->commit()) {
            throw new DBException(...$this->connection->errorInfo());
        }
        $this->ended = true;
    }

    public function rollBack()
    {
        if ($this->ended) {
            return;
        }
        if (false === $this->connection->rollBack()) {
            throw new DBException(...$this->connection->errorInfo());
        }
        $this->ended = true;
    }
}
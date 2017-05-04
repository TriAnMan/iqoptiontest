<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 21:14
 */

namespace TriAn\IqoTest\core\db;


use TriAn\IqoTest\core\exception\TransactionException;

class Transaction
{
    /**
     * @var \PDO
     */
    protected $connection;

    protected $committed = false;

    /**
     * @var \PDOStatement[]
     * @todo: move to upper level where this cache can be actually helpful
     */
    protected $cache = [];

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->beginTransaction();
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
        if ($this->committed) {
            throw new TransactionException('Trying to execute statements on an already committed transaction');
        }

        $statement = $this->cacheQuery($query);
        $statement->execute($parameters);

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
        $this->connection->commit();
        $this->committed = true;
    }
}